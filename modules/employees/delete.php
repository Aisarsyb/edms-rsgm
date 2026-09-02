<?php
// API Soft Delete Pegawai
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Proteksi Sesi Login
check_auth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode HTTP tidak diperbolehkan.']);
    exit;
}

// Validasi CSRF Token
validate_csrf_token();

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Pegawai tidak valid.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Cek apakah pegawai ada dan aktif
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Pegawai tidak ditemukan atau sudah dihapus.']);
        exit;
    }

    // 2. Lakukan Soft Delete pada pegawai
    $stmt = $pdo->prepare("UPDATE employees SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    // 3. Lakukan Soft Delete pada dokumen-dokumen terkait pegawai tersebut
    // Catatan: Hanya dokumen yang belum terhapus sebelumnya yang ditandai terhapus pada waktu yang sama
    $stmt = $pdo->prepare("UPDATE documents SET deleted_at = NOW() WHERE employee_id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Pegawai berhasil dihapus sementara ke Tempat Sampah.'
    ]);

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ]);
}
