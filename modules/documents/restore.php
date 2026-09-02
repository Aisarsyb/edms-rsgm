<?php
// API Memulihkan Versi Dokumen Lama menjadi Versi Terbaru Aktif
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

// Ambil version_id yang ingin dipulihkan
$version_id = isset($_POST['version_id']) ? intval($_POST['version_id']) : 0;

if ($version_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Versi tidak valid.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Dapatkan detail versi target yang akan dipulihkan
    $stmt = $pdo->prepare("
        SELECT dv.*, d.employee_id 
        FROM document_versions dv
        JOIN documents d ON dv.document_id = d.id
        WHERE dv.id = ? AND d.deleted_at IS NULL
    ");
    $stmt->execute([$version_id]);
    $target_version = $stmt->fetch();

    if (!$target_version) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Versi dokumen tidak ditemukan atau kategori dokumen terhapus.']);
        exit;
    }

    $document_id = $target_version['document_id'];

    // 2. Dapatkan nomor versi tertinggi saat ini untuk kategori dokumen tersebut
    $stmt_max = $pdo->prepare("SELECT MAX(version_number) as max_v FROM document_versions WHERE document_id = ?");
    $stmt_max->execute([$document_id]);
    $max_res = $stmt_max->fetch();
    $next_version_number = intval($max_res['max_v']) + 1;

    // 3. Masukkan record versi baru sebagai hasil salinan (copy-restore)
    $stmt_ins = $pdo->prepare("
        INSERT INTO document_versions 
        (document_id, version_number, document_number, start_date, expired_date, file_path, uploaded_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $uploader_id = $_SESSION['user_id'];
    
    $stmt_ins->execute([
        $document_id,
        $next_version_number,
        $target_version['document_number'],
        $target_version['start_date'],
        $target_version['expired_date'],
        $target_version['file_path'],
        $uploader_id
    ]);

    $new_version_id = $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Versi lama berhasil dipulihkan sebagai versi ' . $next_version_number . ' (Aktif).',
        'data' => [
            'document_id' => $document_id,
            'new_version_id' => $new_version_id,
            'version_number' => $next_version_number
        ]
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
