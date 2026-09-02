<?php
// Berkas Pengujian Otomatis (Database, Seeding, dan Logika Bisnis)
// Letakkan di scratch/test.php

require_once __DIR__ . '/../config/database.php';

echo "<h2>--- EDMS RSGM UNAIR - RUNNING DIAGNOSTIC TEST ---</h2>";

// Test 1: Verifikasi Koneksi Database
echo "<strong>Test 1: Koneksi Database...</strong> ";
if (isset($pdo) && $pdo instanceof PDO) {
    echo "<span style='color: green;'>BERHASIL (Koneksi Terjalin)</span><br>";
} else {
    echo "<span style='color: red;'>GAGAL (PDO tidak terdefinisi)</span><br>";
    exit;
}

// Test 2: Verifikasi Akun Admin Default
echo "<strong>Test 2: Verifikasi Data Admin Seeding...</strong> ";
try {
    $stmt = $pdo->prepare("SELECT name, username, password FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        echo "<span style='color: green;'>BERHASIL (Admin ditemukan: " . htmlspecialchars($admin['name']) . ")</span><br>";
        
        // Verifikasi password_verify untuk "admin123"
        echo "<strong>Test 2b: Verifikasi Hash Password Admin ('admin123')...</strong> ";
        if (password_verify('admin123', $admin['password'])) {
            echo "<span style='color: green;'>BERHASIL (Verifikasi Password Cocok)</span><br>";
        } else {
            echo "<span style='color: red;'>GAGAL (Password tidak cocok dengan hash)</span><br>";
        }
    } else {
        echo "<span style='color: red;'>GAGAL (Akun admin tidak ditemukan di database)</span><br>";
    }
} catch (\PDOException $e) {
    echo "<span style='color: red;'>GAGAL (Error database: " . $e->getMessage() . ")</span><br>";
}

// Test 3: Uji Logika Status Kedaluwarsa Dokumen
echo "<strong>Test 3: Uji Logika Perhitungan Status Dokumen...</strong><br>";

function hitungStatusUji($expiredDateStr) {
    if (empty($expiredDateStr)) return 'Aktif';
    
    $today = new DateTime();
    $expiry = new DateTime($expiredDateStr);
    $interval = $today->diff($expiry);
    
    if ($interval->invert) {
        return 'Kedaluwarsa';
    } else {
        $days_left = $interval->days;
        if ($days_left <= 30) {
            return 'Akan Berakhir';
        } else {
            return 'Aktif';
        }
    }
}

$todayStr = (new DateTime())->format('Y-m-d');
$expiringSoonDate = (new DateTime('+15 days'))->format('Y-m-d');
$activeDate = (new DateTime('+40 days'))->format('Y-m-d');
$expiredDate = (new DateTime('-5 days'))->format('Y-m-d');

$cases = [
    ['date' => null, 'expected' => 'Aktif', 'desc' => 'Tanpa masa berlaku (Seumur hidup)'],
    ['date' => $activeDate, 'expected' => 'Aktif', 'desc' => 'Sisa 40 hari (>30 hari)'],
    ['date' => $expiringSoonDate, 'expected' => 'Akan Berakhir', 'desc' => 'Sisa 15 hari (<=30 hari)'],
    ['date' => $expiredDate, 'expected' => 'Kedaluwarsa', 'desc' => 'Lewat 5 hari (Masa lalu)']
];

$successCount = 0;
foreach ($cases as $c) {
    $res = hitungStatusUji($c['date']);
    echo "- Kasus: {$c['desc']} (Tanggal: " . ($c['date'] ?? 'NULL') . "). Hasil: <strong>$res</strong>. ";
    if ($res === $c['expected']) {
        echo "<span style='color: green;'>PASS</span><br>";
        $successCount++;
    } else {
        echo "<span style='color: red;'>FAIL (Ekspektasi: {$c['expected']})</span><br>";
    }
}

if ($successCount === count($cases)) {
    echo "<strong>Test 3 Keseluruhan:</strong> <span style='color: green;'>BERHASIL (Logika Kalkulasi Akurat)</span><br>";
} else {
    echo "<strong>Test 3 Keseluruhan:</strong> <span style='color: red;'>GAGAL</span><br>";
}

echo "<h3>Diagnostik Selesai.</h3>";
