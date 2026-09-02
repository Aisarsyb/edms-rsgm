<?php
// API Menyimpan Data Pegawai (Tambah / Edit)
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

// Ambil input POST
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nip = trim($_POST['nip'] ?? '');
$name = trim($_POST['name'] ?? '');
$gelar = trim($_POST['gelar'] ?? '');
$employee_type = trim($_POST['employee_type'] ?? '');
$status_kepegawaian = trim($_POST['status_kepegawaian'] ?? '');
$is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

// Validasi Input Wajib
if (empty($nip) || empty($name) || empty($employee_type) || empty($status_kepegawaian)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Kolom NIP, Nama Lengkap, Jenis Pegawai, dan Status Kepegawaian wajib diisi.'
    ]);
    exit;
}

try {
    // Validasi Keunikan NIP
    if ($id > 0) {
        // Mode Edit: pastikan NIP tidak dipakai oleh pegawai lain
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE nip = ? AND id <> ?");
        $stmt->execute([$nip, $id]);
    } else {
        // Mode Tambah: pastikan NIP belum pernah terdaftar
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE nip = ?");
        $stmt->execute([$nip]);
    }
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'NIP sudah digunakan oleh pegawai lain.'
        ]);
        exit;
    }
    
    if ($id > 0) {
        // Jalankan Update
        $stmt = $pdo->prepare("UPDATE employees SET nip = ?, name = ?, gelar = ?, employee_type = ?, status_kepegawaian = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$nip, $name, $gelar !== '' ? $gelar : null, $employee_type, $status_kepegawaian, $is_active, $id]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Data pegawai berhasil diperbarui.'
        ]);
    } else {
        // Jalankan Insert
        $stmt = $pdo->prepare("INSERT INTO employees (nip, name, gelar, employee_type, status_kepegawaian, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nip, $name, $gelar !== '' ? $gelar : null, $employee_type, $status_kepegawaian, $is_active]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Pegawai baru berhasil ditambahkan.',
            'data' => [
                'id' => $pdo->lastInsertId()
            ]
        ]);
    }
    
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ]);
}
