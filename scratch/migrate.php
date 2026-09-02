<?php
// Migration script: Add status_kepegawaian column to employees table
require_once __DIR__ . '/../config/database.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'status_kepegawaian'");
    $column = $stmt->fetch();
    
    if (!$column) {
        // Add status_kepegawaian column
        $pdo->exec("ALTER TABLE employees ADD COLUMN status_kepegawaian VARCHAR(100) NOT NULL DEFAULT 'PNS'");
        echo "Column 'status_kepegawaian' successfully added to 'employees' table!\n";
    } else {
        echo "Column 'status_kepegawaian' already exists.\n";
    }
} catch (\PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
