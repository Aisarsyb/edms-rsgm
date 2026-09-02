<?php
// API Soft Delete Dokumen
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

$id = isset($_POST['id']) ? intval($_POST['id']) : 0; // document_id

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Dokumen tidak valid.']);
    exit;
}

try {
    // 1. Cek apakah dokumen terdaftar dan aktif
    $stmt = $pdo->prepare("SELECT id FROM documents WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Dokumen tidak ditemukan atau sudah dihapus.']);
        exit;
    }

    // 2. Tandai deleted_at
    $stmt = $pdo->prepare("UPDATE documents SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Kategori dokumen berhasil dipindahkan ke Tempat Sampah.'
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ]);
}
