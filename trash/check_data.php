<?php
require __DIR__ . '/config/conn.php';

$stmt = $conn->query("SELECT * FROM divisi LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
var_dump($row);