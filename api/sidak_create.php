<?php
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$tanggal    = str_or_empty($_POST['tanggal'] ?? '');
$department = str_or_empty($_POST['department'] ?? '');
$lokasi     = str_or_empty($_POST['lokasi'] ?? '');
$mandor     = str_or_empty($_POST['mandor'] ?? '');

if ($tanggal === '' || $department === '' || $lokasi === '' || $mandor === '') {
    json_error('Mohon lengkapi minimal Tanggal, Department, Lokasi, dan Mandor.');
}

$no = str_or_empty($_POST['no'] ?? '');

$kasie             = str_or_empty($_POST['kasie'] ?? '');
$temuan            = str_or_empty($_POST['temuan'] ?? '');
$verifikasi        = str_or_empty($_POST['verifikasi'] ?? '');
$tglVerifikasi     = str_or_empty($_POST['tglVerifikasi'] ?? '');
$status            = str_or_empty($_POST['status'] ?? '');
$correctiveAction  = str_or_empty($_POST['correctiveAction'] ?? '');
$pic               = str_or_empty($_POST['pic'] ?? '');

try {
    $pdo->beginTransaction();

    // Jika nomor tidak diisi, hitung otomatis seperti versi lama (max no + 1)
    if ($no === '') {
        $maxNo = 0;
        foreach ($pdo->query("SELECT no FROM sidak")->fetchAll() as $row) {
            $n = intval($row['no']);
            if ($n > $maxNo) $maxNo = $n;
        }
        $no = (string)($maxNo + 1);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO sidak (no, tanggal, department, lokasi, mandor, kasie, temuan, verifikasi, tgl_verifikasi, status, corrective_action, pic)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
        $no, $tanggal, $department, $lokasi, $mandor, $kasie, $temuan, $verifikasi,
        $tglVerifikasi !== '' ? $tglVerifikasi : null, $status, $correctiveAction, $pic,
    ]);
    $sidakId = (int)$pdo->lastInsertId();

    $sortOrder = 0;
    if (!empty($_FILES['new_photos']) && is_array($_FILES['new_photos']['tmp_name'])) {
        $count = count($_FILES['new_photos']['tmp_name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['new_photos']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $fileItem = [
                'name'     => $_FILES['new_photos']['name'][$i],
                'type'     => $_FILES['new_photos']['type'][$i],
                'tmp_name' => $_FILES['new_photos']['tmp_name'][$i],
                'error'    => $_FILES['new_photos']['error'][$i],
                'size'     => $_FILES['new_photos']['size'][$i],
            ];
            $saved = save_uploaded_photo($fileItem);
            $ins = $pdo->prepare("INSERT INTO sidak_photos (sidak_id, photo_path, width, height, sort_order) VALUES (?,?,?,?,?)");
            $ins->execute([$sidakId, $saved['path'], $saved['width'], $saved['height'], $sortOrder]);
            $sortOrder++;
        }
    }

    $pdo->commit();
    json_response(['success' => true, 'id' => $sidakId]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_error('Gagal menyimpan data: ' . $e->getMessage(), 500);
}
