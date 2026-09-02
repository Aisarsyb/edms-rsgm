<?php
// API Mendapatkan Daftar Notifikasi Berkas Kedaluwarsa & Akan Berakhir
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Proteksi Sesi Login
check_auth();

header('Content-Type: application/json');

try {
    // Query untuk mengambil versi berkas teraktif (terbaru) yang kadaluwarsa atau berakhir dalam 30 hari
    $query = "
        SELECT 
            e.id as employee_id,
            e.name as employee_name,
            e.nip as employee_nip,
            d.id as document_id,
            d.document_type,
            dv.version_number,
            dv.expired_date,
            dv.file_path,
            DATEDIFF(dv.expired_date, CURDATE()) as days_left
        FROM documents d
        JOIN employees e ON d.employee_id = e.id
        JOIN document_versions dv ON dv.document_id = d.id
        JOIN (
            SELECT document_id, MAX(version_number) as max_version 
            FROM document_versions 
            GROUP BY document_id
        ) latest_v ON dv.document_id = latest_v.document_id AND dv.version_number = latest_v.max_version
        WHERE d.deleted_at IS NULL 
          AND e.deleted_at IS NULL 
          AND dv.expired_date IS NOT NULL 
          AND dv.expired_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY dv.expired_date ASC
    ";

    $stmt = $pdo->query($query);
    $alerts = $stmt->fetchAll();

    $notifications = [];
    foreach ($alerts as $row) {
        $days = intval($row['days_left']);
        
        if ($days < 0) {
            $status = 'Expired';
            $message = 'Berkas ' . $row['document_type'] . ' (v' . $row['version_number'] . ') telah kedaluwarsa sejak ' . abs($days) . ' hari yang lalu.';
        } else if ($days === 0) {
            $status = 'Expired';
            $message = 'Berkas ' . $row['document_type'] . ' (v' . $row['version_number'] . ') kedaluwarsa hari ini!';
        } else {
            $status = 'Warning';
            $message = 'Berkas ' . $row['document_type'] . ' (v' . $row['version_number'] . ') akan kedaluwarsa dalam ' . $days . ' hari.';
        }

        $notifications[] = [
            'employee_id' => intval($row['employee_id']),
            'employee_name' => $row['employee_name'],
            'employee_nip' => $row['employee_nip'],
            'document_id' => intval($row['document_id']),
            'document_type' => $row['document_type'],
            'expired_date' => $row['expired_date'],
            'file_path' => $row['file_path'],
            'status' => $status,
            'message' => $message,
            'days_left' => $days
        ];
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($notifications),
        'data' => $notifications
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ]);
}
