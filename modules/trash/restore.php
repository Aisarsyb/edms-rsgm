<?php
// API Memulihkan Data Terhapus Sementara (Restore)
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

$type = trim($_POST['type'] ?? ''); // 'employee' atau 'document'
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if (empty($type) || $id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parameter tipe dan ID tidak valid.']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($type === 'employee') {
        // 1. Cek apakah pegawai ada di tempat sampah
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Pegawai tidak ditemukan di Tempat Sampah.']);
            exit;
        }

        // 2. Pulihkan pegawai (set deleted_at = NULL)
        $stmt = $pdo->prepare("UPDATE employees SET deleted_at = NULL WHERE id = ?");
        $stmt->execute([$id]);

        // 3. Pulihkan juga seluruh dokumen milik pegawai tersebut
        $stmt = $pdo->prepare("UPDATE documents SET deleted_at = NULL WHERE employee_id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data pegawai beserta dokumennya berhasil dipulihkan.']);
        exit;
    } 
    
    if ($type === 'document') {
        // 1. Cek apakah dokumen ada di tempat sampah
        $stmt = $pdo->prepare("
            SELECT d.id, d.employee_id, e.deleted_at as employee_deleted_at
            FROM documents d
            JOIN employees e ON d.employee_id = e.id
            WHERE d.id = ? AND d.deleted_at IS NOT NULL
        ");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if (!$doc) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Dokumen tidak ditemukan di Tempat Sampah.']);
            exit;
        }

        // Jika pegawai pemilik dokumen tersebut sedang dihapus, tolak pemulihan dokumen
        if (!empty($doc['employee_deleted_at'])) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Tidak dapat memulihkan dokumen karena pegawai pemilik dokumen masih berada di tempat sampah.'
            ]);
            exit;
        }

        // 2. Pulihkan dokumen
        $stmt = $pdo->prepare("UPDATE documents SET deleted_at = NULL WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Kategori dokumen berhasil dipulihkan.']);
        exit;
    }

    // Tipe tidak valid
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tipe pemulihan tidak dikenal.']);

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
