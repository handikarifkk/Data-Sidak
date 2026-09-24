<?php
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    json_error('ID tidak valid');
}

$tanggal    = str_or_empty($_POST['tanggal'] ?? '');
$department = str_or_empty($_POST['department'] ?? '');
$lokasi     = str_or_empty($_POST['lokasi'] ?? '');
$mandor     = str_or_empty($_POST['mandor'] ?? '');

if ($tanggal === '' || $department === '' || $lokasi === '' || $mandor === '') {
    json_error('Mohon lengkapi minimal Tanggal, Department, Lokasi, dan Mandor.');
}

$no                = str_or_empty($_POST['no'] ?? '');
$kasie             = str_or_empty($_POST['kasie'] ?? '');
$temuan            = str_or_empty($_POST['temuan'] ?? '');
$verifikasi        = str_or_empty($_POST['verifikasi'] ?? '');
$tglVerifikasi     = str_or_empty($_POST['tglVerifikasi'] ?? '');
$status            = str_or_empty($_POST['status'] ?? '');
$correctiveAction  = str_or_empty($_POST['correctiveAction'] ?? '');
$pic               = str_or_empty($_POST['pic'] ?? '');

try {
    $check = $pdo->prepare("SELECT id FROM sidak WHERE id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        json_error('Data tidak ditemukan', 404);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE sidak SET no=?, tanggal=?, department=?, lokasi=?, mandor=?, kasie=?, temuan=?, verifikasi=?, tgl_verifikasi=?, status=?, corrective_action=?, pic=? WHERE id=?"
    );
    $stmt->execute([
        $no, $tanggal, $department, $lokasi, $mandor, $kasie, $temuan, $verifikasi,
        $tglVerifikasi !== '' ? $tglVerifikasi : null, $status, $correctiveAction, $pic, $id,
    ]);

    // ID foto lama yang tetap dipertahankan (urutan sesuai yang dikirim frontend)
    $keepIds = [];
    if (!empty($_POST['existing_photo_ids'])) {
        $raw = is_array($_POST['existing_photo_ids']) ? $_POST['existing_photo_ids'] : [$_POST['existing_photo_ids']];
        foreach ($raw as $v) {
            $iv = (int)$v;
            if ($iv > 0) $keepIds[] = $iv;
        }
    }

    // Hapus foto lama yang TIDAK ada di daftar keepIds (baik dari DB maupun file fisik)
    $ps = $pdo->prepare("SELECT * FROM sidak_photos WHERE sidak_id = ?");
    $ps->execute([$id]);
    foreach ($ps->fetchAll() as $cp) {
        if (!in_array((int)$cp['id'], $keepIds, true)) {
            $del = $pdo->prepare("DELETE FROM sidak_photos WHERE id = ?");
            $del->execute([$cp['id']]);
            delete_photo_file($cp['photo_path']);
        }
    }

    // Urutkan ulang sort_order foto yang dipertahankan sesuai urutan pengiriman
    $sortOrder = 0;
    foreach ($keepIds as $kid) {
        $upd = $pdo->prepare("UPDATE sidak_photos SET sort_order = ? WHERE id = ? AND sidak_id = ?");
        $upd->execute([$sortOrder, $kid, $id]);
        $sortOrder++;
    }

    // Simpan foto baru (ditambahkan setelah foto lama, sesuai urutan pada form)
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
            $ins->execute([$id, $saved['path'], $saved['width'], $saved['height'], $sortOrder]);
            $sortOrder++;
        }
    }

    $pdo->commit();
    json_response(['success' => true, 'id' => $id]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_error('Gagal menyimpan perubahan: ' . $e->getMessage(), 500);
}
