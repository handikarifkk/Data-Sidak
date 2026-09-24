<?php
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    json_error('ID tidak valid');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM sidak WHERE id = ?");
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    if (!$r) {
        json_error('Data tidak ditemukan', 404);
    }

    $ps = $pdo->prepare("SELECT * FROM sidak_photos WHERE sidak_id = ? ORDER BY sort_order ASC, id ASC");
    $ps->execute([$id]);
    $photos = array_map(function ($p) {
        return [
            'id'     => (int)$p['id'],
            'url'    => $p['photo_path'],
            'width'  => $p['width'] !== null ? (int)$p['width'] : null,
            'height' => $p['height'] !== null ? (int)$p['height'] : null,
        ];
    }, $ps->fetchAll());

    json_response(['success' => true, 'data' => [
        'id'               => (int)$r['id'],
        'no'               => $r['no'],
        'tanggal'          => $r['tanggal'],
        'department'       => $r['department'],
        'lokasi'           => $r['lokasi'],
        'mandor'           => $r['mandor'],
        'kasie'            => $r['kasie'],
        'temuan'           => $r['temuan'],
        'verifikasi'       => $r['verifikasi'],
        'tglVerifikasi'    => $r['tgl_verifikasi'],
        'status'           => $r['status'],
        'correctiveAction' => $r['corrective_action'],
        'pic'              => $r['pic'],
        'photos'           => $photos,
    ]]);
} catch (Exception $e) {
    json_error('Gagal mengambil data: ' . $e->getMessage(), 500);
}
