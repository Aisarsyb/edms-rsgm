<?php
// API Statistik & Pengingat Dashboard
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Proteksi Sesi Login
check_auth();

header('Content-Type: application/json');

try {
    // 1. Total Pegawai Aktif
    $stmt_emp = $pdo->prepare("SELECT COUNT(*) as total FROM employees WHERE deleted_at IS NULL");
    $stmt_emp->execute();
    $total_employees = intval($stmt_emp->fetch()['total']);

    // 2. Total Kategori Dokumen Aktif
    $stmt_doc_cnt = $pdo->prepare("
        SELECT COUNT(d.id) as total 
        FROM documents d
        JOIN employees e ON d.employee_id = e.id
        WHERE d.deleted_at IS NULL AND e.deleted_at IS NULL
    ");
    $stmt_doc_cnt->execute();
    $total_documents = intval($stmt_doc_cnt->fetch()['total']);

    // 3. Klasifikasi Status Dokumen & Daftar Pengingat (Reminder)
    // Ambil seluruh dokumen aktif beserta versi terbarunya dalam satu query (menghindari N+1)
    $stmt_docs = $pdo->prepare("
        SELECT d.id as document_id, d.employee_id, d.document_type, 
               e.name as employee_name, e.nip as employee_nip,
               lv.version_number, lv.document_number, lv.expired_date, lv.file_path
        FROM documents d
        JOIN employees e ON d.employee_id = e.id
        LEFT JOIN document_versions lv ON lv.document_id = d.id
            AND lv.version_number = (
                SELECT MAX(dv2.version_number) FROM document_versions dv2 WHERE dv2.document_id = d.id
            )
        WHERE d.deleted_at IS NULL AND e.deleted_at IS NULL
    ");
    $stmt_docs->execute();
    $all_documents = $stmt_docs->fetchAll();

    $active_count = 0;
    $warning_count = 0;
    $expired_count = 0;
    $reminders = [];

    $today = new DateTime();

    foreach ($all_documents as $doc) {
        if (empty($doc['version_number'])) {
            // Dokumen tanpa versi apapun, lewati
            continue;
        }

        if (empty($doc['expired_date'])) {
            // Dokumen tanpa masa berlaku dianggap selalu Aktif
            $active_count++;
        } else {
            $expiry = new DateTime($doc['expired_date']);
            $interval = $today->diff($expiry);

            if ($interval->invert) {
                // Sudah kedaluwarsa
                $expired_count++;
                
                // Masukkan ke daftar pengingat
                $reminders[] = [
                    'employee_id' => $doc['employee_id'],
                    'document_id' => $doc['document_id'],
                    'employee_name' => $doc['employee_name'],
                    'employee_nip' => $doc['employee_nip'],
                    'document_type' => $doc['document_type'],
                    'document_number' => $doc['document_number'],
                    'expired_date' => $doc['expired_date'],
                    'status' => 'Kedaluwarsa',
                    'days_label' => 'Lewat ' . $interval->days . ' hari'
                ];
            } else {
                $days_left = $interval->days;
                if ($days_left <= 30) {
                    // Akan berakhir dalam 30 hari
                    $warning_count++;
                    
                    $reminders[] = [
                        'employee_id' => $doc['employee_id'],
                        'document_id' => $doc['document_id'],
                        'employee_name' => $doc['employee_name'],
                        'employee_nip' => $doc['employee_nip'],
                        'document_type' => $doc['document_type'],
                        'document_number' => $doc['document_number'],
                        'expired_date' => $doc['expired_date'],
                        'status' => 'Akan Berakhir',
                        'days_label' => 'Sisa ' . $days_left . ' hari'
                    ];
                } else {
                    // Aktif (> 30 hari)
                    $active_count++;
                }
            }
        }
    }

    // Urutkan pengingat: Kedaluwarsa terlebih dahulu, baru Akan Berakhir
    usort($reminders, function($a, $b) {
        if ($a['status'] === $b['status']) {
            return strcmp($a['expired_date'], $b['expired_date']);
        }
        return ($a['status'] === 'Kedaluwarsa') ? -1 : 1;
    });

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_employees' => $total_employees,
            'total_documents' => $total_documents,
            'counts' => [
                'active' => $active_count,
                'warning' => $warning_count,
                'expired' => $expired_count
            ],
            'reminders' => $reminders
        ]
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ]);
}
