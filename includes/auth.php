<?php
// Berkas Manajemen Sesi & Autentikasi Admin

if (session_status() === PHP_SESSION_NONE) {
    // Set cookie lifetime dan keamanan
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set true jika menggunakan HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

/**
 * Cek apakah user telah login.
 * Jika belum login atau sesi kedaluwarsa, arahkan ke login.php atau kirim status 401 jika via AJAX.
 */
function check_auth() {
    $timeout_duration = 1800; // 30 menit dalam detik

    // Cek keberadaan session user ID
    if (!isset($_SESSION['user_id'])) {
        handle_unauthorized();
    }

    // Cek apakah sesi telah kedaluwarsa
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Sesi kedaluwarsa
        session_unset();
        session_destroy();
        handle_unauthorized();
    }

    // Perbarui waktu aktivitas terakhir
    $_SESSION['last_activity'] = time();
}

/**
 * Tangani kondisi tidak terautentikasi (Redirect atau status 401 JSON).
 */
function handle_unauthorized() {
    // Cek apakah request berupa AJAX
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($is_ajax) {
        header('Content-Type: application/json', true, 401);
        echo json_encode([
            'status' => 'unauthorized',
            'message' => 'Sesi Anda telah habis. Silakan login kembali.'
        ]);
        exit;
    } else {
        header("Location: login.php");
        exit;
    }
}

/**
 * Cek apakah user saat ini sedang login (tanpa redirect).
 * Berguna di halaman login untuk auto-redirect ke dashboard.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Menghasilkan CSRF token dan menyimpannya di sesi.
 * Jika token sudah ada, kembalikan token yang sama.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validasi CSRF token dari request POST.
 * Mengirim HTTP 403 jika token tidak valid.
 */
function validate_csrf_token() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($is_ajax) {
            header('Content-Type: application/json', true, 403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Token keamanan tidak valid. Silakan muat ulang halaman.'
            ]);
            exit;
        } else {
            die('<h3>Akses ditolak: Token keamanan tidak valid.</h3>');
        }
    }
}
