<?php
// API Menghapus Data Secara Permanen (Force Delete)
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

        // 2. Ambil semua file path dokumen milik pegawai ini untuk dihapus fisiknya
        $stmt_files = $pdo->prepare("
            SELECT dv.file_path 
            FROM document_versions dv
            JOIN documents d ON dv.document_id = d.id
            WHERE d.employee_id = ?
        ");
        $stmt_files->execute([$id]);
        $files = $stmt_files->fetchAll();

        // Hapus file fisik PDF satu per satu
        foreach ($files as $file) {
            $full_path = '../../' . $file['file_path'];
            if (file_exists($full_path)) {
                @unlink($full_path);
            }
        }

        // Hapus folder uploads milik pegawai tersebut (uploads/{employee_id}/)
        $employee_dir = '../../uploads/' . $id;
        if (is_dir($employee_dir)) {
            // Bersihkan file sisa (jika ada) lalu hapus direktori
            $remaining_files = glob($employee_dir . '/*');
            foreach ($remaining_files as $rem_file) {
                @unlink($rem_file);
            }
            @rmdir($employee_dir);
        }

        // 3. Hapus data pegawai dari database
        // (Cascading akan menghapus data di tabel `documents` & `document_versions` secara otomatis)
        $stmt_del = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt_del->execute([$id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data pegawai beserta berkas fisiknya berhasil dihapus secara permanen.']);
        exit;
    }

    if ($type === 'document') {
        // 1. Cek apakah dokumen ada di tempat sampah
        $stmt = $pdo->prepare("SELECT id FROM documents WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Dokumen tidak ditemukan di Tempat Sampah.']);
            exit;
        }

        // 2. Ambil file path versi dari dokumen ini untuk dihapus fisiknya
        $stmt_files = $pdo->prepare("SELECT file_path FROM document_versions WHERE document_id = ?");
        $stmt_files->execute([$id]);
        $files = $stmt_files->fetchAll();

        foreach ($files as $file) {
            $full_path = '../../' . $file['file_path'];
            if (file_exists($full_path)) {
                @unlink($full_path);
            }
        }

        // 3. Hapus record dokumen dari database (Cascading menghapus record di `document_versions`)
        $stmt_del = $pdo->prepare("DELETE FROM documents WHERE id = ?");
        $stmt_del->execute([$id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Kategori dokumen beserta berkas fisiknya berhasil dihapus secara permanen.']);
        exit;
    }

    // Tipe tidak valid
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tipe penghapusan tidak dikenal.']);

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
