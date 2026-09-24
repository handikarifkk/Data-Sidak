<?php
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

try {
    $stmt = $pdo->query("SELECT * FROM sidak ORDER BY tanggal DESC, id DESC");
    $rows = $stmt->fetchAll();

    $photosBySidak = [];
    if ($rows) {
        $ids = array_column($rows, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $ps = $pdo->prepare("SELECT * FROM sidak_photos WHERE sidak_id IN ($placeholders) ORDER BY sort_order ASC, id ASC");
        $ps->execute($ids);
        foreach ($ps->fetchAll() as $p) {
            $photosBySidak[$p['sidak_id']][] = [
                'id'     => (int)$p['id'],
                'url'    => $p['photo_path'],
                'width'  => $p['width'] !== null ? (int)$p['width'] : null,
                'height' => $p['height'] !== null ? (int)$p['height'] : null,
            ];
        }
    }

    $entries = array_map(function ($r) use ($photosBySidak) {
        return [
            'id'                => (int)$r['id'],
            'no'                => $r['no'],
            'tanggal'           => $r['tanggal'],
            'department'        => $r['department'],
            'lokasi'            => $r['lokasi'],
            'mandor'            => $r['mandor'],
            'kasie'             => $r['kasie'],
            'temuan'            => $r['temuan'],
            'verifikasi'        => $r['verifikasi'],
            'tglVerifikasi'     => $r['tgl_verifikasi'],
            'status'            => $r['status'],
            'correctiveAction'  => $r['corrective_action'],
            'pic'               => $r['pic'],
            'photos'            => $photosBySidak[$r['id']] ?? [],
        ];
    }, $rows);

    json_response(['success' => true, 'data' => $entries]);
} catch (Exception $e) {
    json_error('Gagal mengambil data: ' . $e->getMessage(), 500);
}
