<?php
// Script memperbarui password admin
require_once __DIR__ . '/../config/database.php';

$hash = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->execute([$hash]);

echo "Password admin berhasil diperbarui di database!\n";
