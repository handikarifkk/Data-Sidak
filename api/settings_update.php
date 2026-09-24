<?php
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    json_error('Data pengaturan tidak valid');
}

$fields = [
    'petugas'    => str_or_empty($body['petugas'] ?? ''),
    'periode'    => str_or_empty($body['periode'] ?? ''),
    'lokasi_ttd' => str_or_empty($body['lokasiTtd'] ?? ''),
    'role1'      => str_or_empty($body['role1'] ?? ''),
    'role2'      => str_or_empty($body['role2'] ?? ''),
    'role3'      => str_or_empty($body['role3'] ?? ''),
];

try {
    $exists = $pdo->query("SELECT id FROM settings WHERE id = 1")->fetch();
    if ($exists) {
        $stmt = $pdo->prepare(
            "UPDATE settings SET petugas=?, periode=?, lokasi_ttd=?, role1=?, role2=?, role3=? WHERE id=1"
        );
        $stmt->execute(array_values($fields));
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO settings (id, petugas, periode, lokasi_ttd, role1, role2, role3) VALUES (1,?,?,?,?,?,?)"
        );
        $stmt->execute(array_values($fields));
    }
    json_response(['success' => true]);
} catch (Exception $e) {
    json_error('Gagal menyimpan pengaturan: ' . $e->getMessage(), 500);
}
