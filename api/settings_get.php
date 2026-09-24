<?php
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

try {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->exec("INSERT INTO settings (id) VALUES (1)");
        $row = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
    }

    if (empty($row['periode'])) {
        $periode = date('m') . date('Y');
        $upd = $pdo->prepare("UPDATE settings SET periode = ? WHERE id = 1");
        $upd->execute([$periode]);
        $row['periode'] = $periode;
    }

    json_response(['success' => true, 'data' => [
        'petugas'   => $row['petugas'],
        'periode'   => $row['periode'],
        'lokasiTtd' => $row['lokasi_ttd'],
        'role1'     => $row['role1'],
        'role2'     => $row['role2'],
        'role3'     => $row['role3'],
    ]]);
} catch (Exception $e) {
    json_error('Gagal mengambil pengaturan: ' . $e->getMessage(), 500);
}
