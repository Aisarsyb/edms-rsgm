<?php
// API Unggah Dokumen Pegawai (Dengan Versioning)
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

// Ambil data input
$employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$document_type = trim($_POST['document_type'] ?? '');
$document_number = trim($_POST['document_number'] ?? '');
$start_date = trim($_POST['start_date'] ?? '');
$expired_date = trim($_POST['expired_date'] ?? '');

// Jika memilih jenis kustom
if ($document_type === 'KUSTOM_LAIN') {
    $document_type = trim($_POST['custom_document_type'] ?? '');
}

// 1. Validasi input wajib
if ($employee_id <= 0 || empty($document_type)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Pegawai dan Jenis Dokumen wajib diisi/dipilih.']);
    exit;
}

// Cek apakah file diunggah
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $err_code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengunggah file. Kode Error: ' . $err_code
    ]);
    exit;
}

$file = $_FILES['file'];

// 2. Validasi Ukuran File (Maksimal 10 MB)
$max_size = 10 * 1024 * 1024; // 10 MB
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Ukuran file terlalu besar. Maksimal adalah 10 MB.']);
    exit;
}

// 3. Validasi Tipe Berkas (Hanya PDF)
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'pdf') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format file harus PDF. Ekstensi file .' . $ext . ' ditolak.']);
    exit;
}

// Validasi Mime Type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if ($mime_type !== 'application/pdf') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Berkas terdeteksi tidak valid. Tipe MIME harus application/pdf.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Pastikan pegawai target terdaftar dan aktif
    $stmt = $pdo->prepare("SELECT id, name, status_kepegawaian FROM employees WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Pegawai tidak ditemukan atau sudah dinonaktifkan.']);
        exit;
    }

    // --- Logika Tambahan Perhitungan & Kelayakan Tanggal Kedaluwarsa ---
    if (in_array($document_type, ['SIP', 'KGB', 'Kenaikan Pangkat'])) {
        // Harus menginput setidaknya salah satu
        if (empty($start_date) && empty($expired_date)) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Tanggal Terbit atau Tanggal Kedaluwarsa wajib diisi untuk jenis dokumen ' . $document_type . '.'
            ]);
            exit;
        }

        // Cek Kelayakan KGB
        if ($document_type === 'KGB') {
            $allowed_status = ['PNS', 'P3K', 'Pegawai Tetap (PT)'];
            if (!in_array($employee['status_kepegawaian'], $allowed_status)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Dokumen KGB hanya berlaku untuk pegawai dengan status PNS, P3K, atau Pegawai Tetap (PT). Status saat ini: ' . $employee['status_kepegawaian']
                ]);
                exit;
            }
        }

        // Cek Kelayakan Kenaikan Pangkat
        if ($document_type === 'Kenaikan Pangkat') {
            $allowed_status = ['PNS', 'Pegawai Tetap (PT)'];
            if (!in_array($employee['status_kepegawaian'], $allowed_status)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Dokumen Kenaikan Pangkat hanya berlaku untuk pegawai dengan status PNS atau Pegawai Tetap (PT). Status saat ini: ' . $employee['status_kepegawaian']
                ]);
                exit;
            }
        }

        // Kalkulasi otomatis tanggal kedaluwarsa jika kosong namun tanggal terbit diisi
        if (empty($expired_date) && !empty($start_date)) {
            try {
                $date_obj = new DateTime($start_date);
                if ($document_type === 'SIP') {
                    $date_obj->modify('+5 years');
                } else if ($document_type === 'KGB') {
                    $date_obj->modify('+2 years');
                } else if ($document_type === 'Kenaikan Pangkat') {
                    $date_obj->modify('+4 years');
                }
                $expired_date = $date_obj->format('Y-m-d');
            } catch (\Exception $e) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Format tanggal terbit tidak valid.']);
                exit;
            }
        }
    }

    // 4. Periksa apakah kategori dokumen ini sudah ada untuk pegawai tersebut (aktif)
    $stmt = $pdo->prepare("
        SELECT id FROM documents 
        WHERE employee_id = ? AND document_type = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$employee_id, $document_type]);
    $doc = $stmt->fetch();
    
    $document_id = 0;
    $version_number = 1;
    
    if ($doc) {
        // Kategori dokumen sudah ada, tentukan nomor versi berikutnya
        $document_id = $doc['id'];
        
        $stmt_v = $pdo->prepare("SELECT MAX(version_number) as max_v FROM document_versions WHERE document_id = ?");
        $stmt_v->execute([$document_id]);
        $max_res = $stmt_v->fetch();
        $version_number = intval($max_res['max_v']) + 1;
    } else {
        // Kategori dokumen belum ada, buat record baru di tabel `documents`
        $stmt_ins = $pdo->prepare("INSERT INTO documents (employee_id, document_type) VALUES (?, ?)");
        $stmt_ins->execute([$employee_id, $document_type]);
        $document_id = $pdo->lastInsertId();
    }

    // 5. Simpan File Fisik PDF ke Subfolder Pegawai `uploads/{employee_id}/`
    $upload_dir = '../../uploads/' . $employee_id . '/';
    if (!is_dir($upload_dir)) {
        // Buat direktori jika belum ada
        mkdir($upload_dir, 0755, true);
    }
    
    // Nama file terformat agar aman dan rapi: {jenis_dokumen}_v{versi}_{timestamp}.pdf
    $sanitized_type = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($document_type));
    $file_name = $sanitized_type . '_v' . $version_number . '_' . time() . '.pdf';
    $dest_path = $upload_dir . $file_name;
    
    // Path penyimpanan yang dicatat di DB relatif terhadap root web (misal: uploads/15/file.pdf)
    $db_path = 'uploads/' . $employee_id . '/' . $file_name;

    if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
        throw new \Exception('Gagal memindahkan file ke direktori tujuan penyimpanan.');
    }

    // 6. Simpan riwayat versi ke tabel `document_versions`
    $stmt_ver = $pdo->prepare("
        INSERT INTO document_versions 
        (document_id, version_number, document_number, start_date, expired_date, file_path, uploaded_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $uploader_id = $_SESSION['user_id'];
    $start_val = ($start_date !== '') ? $start_date : null;
    $exp_val = ($expired_date !== '') ? $expired_date : null;
    $num_val = ($document_number !== '') ? $document_number : null;
    
    $stmt_ver->execute([
        $document_id,
        $version_number,
        $num_val,
        $start_val,
        $exp_val,
        $db_path,
        $uploader_id
    ]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Berkas berhasil diunggah sebagai versi ' . $version_number . '.',
        'data' => [
            'document_id' => $document_id,
            'version_number' => $version_number,
            'file_path' => $db_path
        ]
    ]);

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Bersihkan file yang sudah dipindahkan jika terjadi kegagalan setelah move_uploaded_file
    if (isset($dest_path) && file_exists($dest_path)) {
        @unlink($dest_path);
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengunggah berkas: ' . $e->getMessage()
    ]);
}
