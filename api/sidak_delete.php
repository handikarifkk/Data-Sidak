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

try {
    $pdo->beginTransaction();

    // Ambil path foto dulu untuk dibersihkan fisik setelah DB dihapus
    $ps = $pdo->prepare("SELECT photo_path FROM sidak_photos WHERE sidak_id = ?");
    $ps->execute([$id]);
    $paths = array_column($ps->fetchAll(), 'photo_path');

    // ON DELETE CASCADE pada sidak_photos akan otomatis menghapus baris foto terkait
    $del = $pdo->prepare("DELETE FROM sidak WHERE id = ?");
    $del->execute([$id]);
    if ($del->rowCount() === 0) {
        $pdo->rollBack();
        json_error('Data tidak ditemukan', 404);
    }

    $pdo->commit();

    foreach ($paths as $p) {
        delete_photo_file($p);
    }

    json_response(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_error('Gagal menghapus data: ' . $e->getMessage(), 500);
}
