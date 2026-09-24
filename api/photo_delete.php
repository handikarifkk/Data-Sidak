<?php
// Endpoint mandiri untuk menghapus satu foto berdasarkan ID (DB + file fisik).
// Tidak dipanggil oleh UI utama (penghapusan foto pada form Edit baru diterapkan
// saat "Simpan" ditekan, sesuai perilaku aplikasi asli), tersedia untuk kebutuhan lain.

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    json_error('ID foto tidak valid');
}

try {
    $ps = $pdo->prepare("SELECT photo_path FROM sidak_photos WHERE id = ?");
    $ps->execute([$id]);
    $row = $ps->fetch();
    if (!$row) {
        json_error('Foto tidak ditemukan', 404);
    }

    $del = $pdo->prepare("DELETE FROM sidak_photos WHERE id = ?");
    $del->execute([$id]);
    delete_photo_file($row['photo_path']);

    json_response(['success' => true]);
} catch (Exception $e) {
    json_error('Gagal menghapus foto: ' . $e->getMessage(), 500);
}
