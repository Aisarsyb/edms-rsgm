<?php
// API Mendapatkan Dokumen Pegawai & Riwayat Versi
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Proteksi Sesi Login
check_auth();

header('Content-Type: application/json');

try {
    // Skenario A: Mendapatkan riwayat versi untuk satu dokumen tertentu
    if (isset($_GET['document_id'])) {
        $document_id = intval($_GET['document_id']);
        
        // Cek apakah kategori dokumen ada
        $stmt = $pdo->prepare("
            SELECT d.*, e.name as employee_name 
            FROM documents d
            JOIN employees e ON d.employee_id = e.id
            WHERE d.id = ? AND d.deleted_at IS NULL
        ");
        $stmt->execute([$document_id]);
        $document = $stmt->fetch();
        
        if (!$document) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Kategori dokumen tidak ditemukan.']);
            exit;
        }
        
        // Tarik semua versi diurutkan dari versi terbaru ke terlama
        $stmt = $pdo->prepare("
            SELECT dv.*, u.name as uploader_name 
            FROM document_versions dv
            LEFT JOIN users u ON dv.uploaded_by = u.id
            WHERE dv.document_id = ?
            ORDER BY dv.version_number DESC
        ");
        $stmt->execute([$document_id]);
        $versions = $stmt->fetchAll();
        
        echo json_encode([
            'status' => 'success',
            'document' => $document,
            'data' => $versions
        ]);
        exit;
    }
    
    // Skenario B: Mendapatkan daftar dokumen aktif beserta data versi terbarunya untuk satu pegawai
    if (isset($_GET['employee_id'])) {
        $employee_id = intval($_GET['employee_id']);
        
        // Ambil dokumen beserta versi terbaru dalam satu query (menghindari N+1)
        $stmt = $pdo->prepare("
            SELECT d.id as document_id, d.document_type,
                   lv.id as version_id, lv.version_number, lv.document_number, 
                   lv.start_date, lv.expired_date, lv.file_path, lv.created_at as uploaded_at
            FROM documents d
            LEFT JOIN document_versions lv ON lv.document_id = d.id
                AND lv.version_number = (
                    SELECT MAX(dv2.version_number) FROM document_versions dv2 WHERE dv2.document_id = d.id
                )
            WHERE d.employee_id = ? AND d.deleted_at IS NULL
            ORDER BY d.document_type ASC
        ");
        $stmt->execute([$employee_id]);
        $documents = $stmt->fetchAll();
        
        $result = [];
        $today = new DateTime();
        
        foreach ($documents as $doc) {
            if (empty($doc['version_id'])) {
                // Dokumen tanpa versi apapun, lewati
                continue;
            }
            
            // Hitung status kelayakan berdasarkan tanggal berlaku
            $status = 'Aktif';
            $badge_color = 'green';
            
            if (!empty($doc['expired_date'])) {
                $expiry = new DateTime($doc['expired_date']);
                $interval = $today->diff($expiry);
                
                if ($interval->invert) {
                    $status = 'Kedaluwarsa';
                    $badge_color = 'red';
                } else {
                    $days_left = $interval->days;
                    if ($days_left <= 30) {
                        $status = 'Akan Berakhir';
                        $badge_color = 'yellow';
                    }
                }
            }
            
            $result[] = [
                'document_id' => $doc['document_id'],
                'document_type' => $doc['document_type'],
                'version_id' => $doc['version_id'],
                'version_number' => $doc['version_number'],
                'document_number' => $doc['document_number'],
                'start_date' => $doc['start_date'],
                'expired_date' => $doc['expired_date'],
                'file_path' => $doc['file_path'],
                'uploaded_at' => $doc['uploaded_at'],
                'status' => $status,
                'badge_color' => $badge_color
            ];
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $result
        ]);
        exit;
    }
    
    // Parameter tidak lengkap
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Parameter employee_id atau document_id wajib dikirimkan.']);
    
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ]);
}
