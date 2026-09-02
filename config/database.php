<?php
// Berkas Konfigurasi Koneksi Database PDO

$host = '127.0.0.1';
$db   = 'edms_rsgm';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Kembalikan respons JSON error jika dipanggil via API/AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json', true, 500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Koneksi ke database gagal: ' . $e->getMessage()
        ]);
        exit;
    } else {
        // Tampilan error ramah pengguna untuk akses halaman biasa
        die("<h3>Kesalahan Sistem: Tidak dapat terhubung ke database.</h3><p>Pastikan MySQL XAMPP Anda sudah dijalankan.</p>");
    }
}
