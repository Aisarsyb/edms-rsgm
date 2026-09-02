<?php
// API Mendapatkan Data Terhapus Sementara (Trash Bin)
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Proteksi Sesi Login
check_auth();

header('Content-Type: application/json');

try {
    // 1. Ambil daftar pegawai terhapus (deleted_at IS NOT NULL)
    $stmt_emp = $pdo->prepare("
        SELECT id, name, nip, employee_type, deleted_at 
        FROM employees 
        WHERE deleted_at IS NOT NULL 
        ORDER BY deleted_at DESC
    ");
    $stmt_emp->execute();
    $deleted_employees = $stmt_emp->fetchAll();

    // 2. Ambil daftar dokumen terhapus (deleted_at IS NOT NULL) 
    // Catatan: Hanya tampilkan dokumen terhapus dari pegawai yang statusnya MASIH AKTIF (tidak terhapus)
    $stmt_doc = $pdo->prepare("
        SELECT d.id as document_id, d.document_type, d.deleted_at, e.name as employee_name, e.nip as employee_nip
        FROM documents d
        JOIN employees e ON d.employee_id = e.id
        WHERE d.deleted_at IS NOT NULL AND e.deleted_at IS NULL
        ORDER BY d.deleted_at DESC
    ");
    $stmt_doc->execute();
    $deleted_documents = $stmt_doc->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'employees' => $deleted_employees,
            'documents' => $deleted_documents
        ]
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ]);
}
