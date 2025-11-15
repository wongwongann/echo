<?php
require __DIR__ . '/config/conn.php';

$stmt = $conn->query("SHOW COLUMNS FROM divisi");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . "\n";
}