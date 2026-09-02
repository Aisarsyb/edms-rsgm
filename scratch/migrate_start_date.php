<?php
// Migration script: Add start_date column to document_versions table
require_once __DIR__ . '/../config/database.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM document_versions LIKE 'start_date'");
    $column = $stmt->fetch();
    
    if (!$column) {
        // Add start_date column
        $pdo->exec("ALTER TABLE document_versions ADD COLUMN start_date DATE DEFAULT NULL AFTER document_number");
        echo "Column 'start_date' successfully added to 'document_versions' table!\n";
    } else {
        echo "Column 'start_date' already exists.\n";
    }
} catch (\PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
