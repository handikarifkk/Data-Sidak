<?php
// ==========================================================
// Helper bersama untuk seluruh endpoint API.
// ==========================================================

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error($message, $code = 400) {
    json_response(['success' => false, 'message' => $message], $code);
}

function str_or_empty($v) {
    return isset($v) ? trim((string)$v) : '';
}

define('UPLOAD_DIR', __DIR__ . '/../uploads/sidak/');
define('UPLOAD_URL', 'uploads/sidak/'); // relative to app root (index.php)
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_MIME', ['image/jpeg', 'image/png', 'image/webp']);
define('MAX_UPLOAD_SIZE', 8 * 1024 * 1024); // 8 MB, cukup longgar untuk foto hasil kompresi client

/**
 * Generate nama file acak & aman (mencegah tebak nama file / directory traversal).
 */
function safe_random_filename($ext) {
    $ext = preg_replace('/[^a-z0-9]/i', '', $ext);
    return bin2hex(random_bytes(16)) . '.' . strtolower($ext);
}

/**
 * Validasi & simpan satu file foto yang di-upload (dari $_FILES).
 * Mengembalikan ['path' => relative_path, 'width' => int, 'height' => int]
 * atau melempar Exception jika tidak valid.
 */
function save_uploaded_photo($fileArrItem) {
    if (!isset($fileArrItem['error']) || $fileArrItem['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload foto gagal (kode error: ' . ($fileArrItem['error'] ?? '?') . ')');
    }
    if ($fileArrItem['size'] <= 0 || $fileArrItem['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('Ukuran file foto tidak valid atau terlalu besar (maks 8MB)');
    }

    $ext = strtolower(pathinfo($fileArrItem['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpeg') $ext = 'jpg';
    if (!in_array($ext, ALLOWED_EXT, true)) {
        throw new Exception('Ekstensi file tidak diizinkan (hanya jpg/jpeg/png/webp)');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $fileArrItem['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ALLOWED_MIME, true)) {
        throw new Exception('Tipe file tidak valid (bukan gambar jpg/png/webp)');
    }

    $imgInfo = @getimagesize($fileArrItem['tmp_name']);
    if ($imgInfo === false) {
        throw new Exception('File yang diupload bukan gambar yang valid');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = safe_random_filename($ext);
    $dest = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($fileArrItem['tmp_name'], $dest)) {
        throw new Exception('Gagal menyimpan file foto ke server');
    }

    return [
        'path'   => UPLOAD_URL . $filename,
        'width'  => $imgInfo[0],
        'height' => $imgInfo[1],
    ];
}

/**
 * Hapus file fisik foto (jika ada) berdasarkan path relatif yang tersimpan di DB.
 * Mencegah directory traversal dengan hanya mengizinkan file di dalam UPLOAD_DIR.
 */
function delete_photo_file($relativePath) {
    $filename = basename($relativePath); // strip any path components for safety
    $full = UPLOAD_DIR . $filename;
    if (is_file($full)) {
        @unlink($full);
    }
}
