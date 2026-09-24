<?php
// Endpoint migrasi satu-kali: menerima JSON hasil ekspor localStorage
// (lihat migrate.php + migrate_export_console.js) dan memasukkannya ke MySQL.
// Foto yang berupa base64 dataURL akan didekode menjadi file fisik di uploads/sidak/.

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    json_error('File migrasi tidak valid / bukan JSON');
}

$entries  = $payload['entries'] ?? [];
$settings = $payload['settings'] ?? null;

$imported = 0;
$errors = [];

try {
    $pdo->beginTransaction();

    if (is_array($settings)) {
        $fields = [
            'petugas'    => str_or_empty($settings['petugas'] ?? ''),
            'periode'    => str_or_empty($settings['periode'] ?? ''),
            'lokasi_ttd' => str_or_empty($settings['lokasiTtd'] ?? ''),
            'role1'      => str_or_empty($settings['role1'] ?? ''),
            'role2'      => str_or_empty($settings['role2'] ?? ''),
            'role3'      => str_or_empty($settings['role3'] ?? ''),
        ];
        $exists = $pdo->query("SELECT id FROM settings WHERE id = 1")->fetch();
        if ($exists) {
            $stmt = $pdo->prepare("UPDATE settings SET petugas=?, periode=?, lokasi_ttd=?, role1=?, role2=?, role3=? WHERE id=1");
            $stmt->execute(array_values($fields));
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (id, petugas, periode, lokasi_ttd, role1, role2, role3) VALUES (1,?,?,?,?,?,?)");
            $stmt->execute(array_values($fields));
        }
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    foreach ($entries as $idx => $e) {
        try {
            $tanggal    = str_or_empty($e['tanggal'] ?? '');
            $department = str_or_empty($e['department'] ?? '');
            $lokasi     = str_or_empty($e['lokasi'] ?? '');
            $mandor     = str_or_empty($e['mandor'] ?? '');

            if ($tanggal === '' || $department === '') {
                $errors[] = "Entri #$idx dilewati: tanggal/department kosong";
                continue;
            }

            $no = str_or_empty($e['no'] ?? '');
            if ($no === '') $no = (string)($idx + 1);

            $stmt = $pdo->prepare(
                "INSERT INTO sidak (no, tanggal, department, lokasi, mandor, kasie, temuan, verifikasi, tgl_verifikasi, status, corrective_action, pic)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
                $no, $tanggal, $department, $lokasi, $mandor,
                str_or_empty($e['kasie'] ?? ''),
                str_or_empty($e['temuan'] ?? ''),
                str_or_empty($e['verifikasi'] ?? ''),
                !empty($e['tglVerifikasi']) ? $e['tglVerifikasi'] : null,
                str_or_empty($e['status'] ?? ''),
                str_or_empty($e['correctiveAction'] ?? ''),
                str_or_empty($e['pic'] ?? ''),
            ]);
            $sidakId = (int)$pdo->lastInsertId();

            $photos = $e['photos'] ?? [];
            $sortOrder = 0;
            foreach ($photos as $p) {
                $dataUrl = is_array($p) ? ($p['dataUrl'] ?? '') : $p;
                if (!$dataUrl || strpos($dataUrl, 'base64,') === false) continue;

                [$meta, $b64] = explode('base64,', $dataUrl, 2);
                $bin = base64_decode($b64);
                if ($bin === false) continue;

                $ext = 'jpg';
                if (strpos($meta, 'image/png') !== false) $ext = 'png';
                elseif (strpos($meta, 'image/webp') !== false) $ext = 'webp';

                $filename = safe_random_filename($ext);
                $dest = UPLOAD_DIR . $filename;
                file_put_contents($dest, $bin);

                $sizeInfo = @getimagesize($dest);
                $w = $sizeInfo ? $sizeInfo[0] : (is_array($p) ? ($p['width'] ?? null) : null);
                $h = $sizeInfo ? $sizeInfo[1] : (is_array($p) ? ($p['height'] ?? null) : null);

                $ins = $pdo->prepare("INSERT INTO sidak_photos (sidak_id, photo_path, width, height, sort_order) VALUES (?,?,?,?,?)");
                $ins->execute([$sidakId, UPLOAD_URL . $filename, $w, $h, $sortOrder]);
                $sortOrder++;
            }

            $imported++;
        } catch (Exception $inner) {
            $errors[] = "Entri #$idx gagal: " . $inner->getMessage();
        }
    }

    $pdo->commit();
    json_response(['success' => true, 'imported' => $imported, 'total' => count($entries), 'errors' => $errors]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_error('Migrasi gagal: ' . $e->getMessage(), 500);
}
