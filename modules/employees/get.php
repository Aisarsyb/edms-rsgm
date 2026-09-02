<?php
// API Mendapatkan Data Pegawai (Aktif)
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Proteksi Sesi Login (Mengembalikan JSON 401 jika kedaluwarsa)
check_auth();

header('Content-Type: application/json');

try {
    // Jika meminta detail pegawai tunggal berdasarkan ID
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $employee = $stmt->fetch();
        
        if ($employee) {
            echo json_encode([
                'status' => 'success',
                'data' => $employee
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Pegawai tidak ditemukan.'
            ]);
        }
        exit;
    }
    
    // Default: Ambil daftar seluruh pegawai aktif
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : 'ALL';
    
    $query = "SELECT * FROM employees WHERE deleted_at IS NULL";
    $params = [];
    
    // Terapkan Filter Pencarian (NIP atau Nama)
    if ($search !== '') {
        $query .= " AND (nip LIKE ? OR name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Terapkan Filter Tipe Pegawai
    if ($type !== 'ALL') {
        $query .= " AND employee_type = ?";
        $params[] = $type;
    }
    
    // Urutkan berdasarkan nama pegawai
    $query .= " ORDER BY name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $employees = $stmt->fetchAll();
    
    echo json_encode([
        'status' => 'success',
        'count' => count($employees),
        'data' => $employees
    ]);
    
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ]);
}
