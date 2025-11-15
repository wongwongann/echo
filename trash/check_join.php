<?php
require __DIR__ . '/config/conn.php';

try {
    $stmt = $conn->query("SELECT b.*, d.* FROM broadcast b LEFT JOIN divisi d ON d.id = 1 LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($row);
} catch (Exception $e) {
    echo $e->getMessage();
}