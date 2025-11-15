<?php
require __DIR__ . '/config/conn.php';

try {
    // Get all tables
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
        
        // Get columns for each table
        $cols = $conn->query("SHOW COLUMNS FROM `$table`");
        while ($col = $cols->fetch(PDO::FETCH_ASSOC)) {
            echo "  * {$col['Field']} ({$col['Type']})\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}