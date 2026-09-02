<?php
// API Proxy untuk Melayani File PDF dengan Autentikasi
// Menggantikan akses langsung ke folder uploads/ yang sudah diblokir .htaccess
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Proteksi Sesi Login
check_auth();

$file_path = isset($_GET['path']) ? trim($_GET['path']) : '';

// Validasi parameter
if (empty($file_path)) {
    http_response_code(400);
    echo 'Parameter path tidak valid.';
    exit;
}

// Sanitasi path: cegah directory traversal
$file_path = str_replace(['..', '\\'], ['', '/'], $file_path);

// Pastikan path diawali dengan 'uploads/'
if (strpos($file_path, 'uploads/') !== 0) {
    http_response_code(403);
    echo 'Akses ditolak.';
    exit;
}

// Resolve path absolut
$full_path = realpath('../../' . $file_path);
$uploads_dir = realpath('../../uploads');

// Pastikan file berada di dalam folder uploads
if ($full_path === false || $uploads_dir === false || strpos($full_path, $uploads_dir) !== 0) {
    http_response_code(404);
    echo 'Berkas tidak ditemukan.';
    exit;
}

// Pastikan file ada dan berekstensi PDF
if (!file_exists($full_path) || strtolower(pathinfo($full_path, PATHINFO_EXTENSION)) !== 'pdf') {
    http_response_code(404);
    echo 'Berkas PDF tidak ditemukan.';
    exit;
}

// Kirim file dengan header yang sesuai
$file_size = filesize($full_path);

header('Content-Type: application/pdf');
header('Content-Length: ' . $file_size);

// Jika parameter download=1 dikirim, force download
if (isset($_GET['download']) && $_GET['download'] === '1') {
    header('Content-Disposition: attachment; filename="' . basename($full_path) . '"');
} else {
    header('Content-Disposition: inline; filename="' . basename($full_path) . '"');
}

// Kirim isi file
readfile($full_path);
exit;
