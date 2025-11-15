<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../config/conn.php';

$userId = (int) $_SESSION['user_id'];
$username = $_SESSION['username'];
$idDivisi = $_SESSION['id_divisi'];

// if admin?
$stmt = $conn->prepare('SELECT divisi FROM divisi WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $idDivisi]);
$row = $stmt->fetch();
$divisiName = $row['divisi'] ?? '';
$isAdmin = (strcasecmp(trim($username), 'it') === 0 && strcasecmp(trim($divisiName), 'it') === 0);

try {
    // Get POST dt
    $broadcastCode = trim($_POST['broadcast_code'] ?? '');
    $field = trim($_POST['field'] ?? '');
    $newValue = trim($_POST['new_value'] ?? '');
    $oldValue = trim($_POST['old_value'] ?? '');
    $columnName = trim($_POST['column_name'] ?? '');

    // Debug logging
    error_log("Query-Update Request: code=$broadcastCode, field=$field, columnName=$columnName, newValue=$newValue");

    if (empty($broadcastCode) || empty($field)) {
        throw new Exception('Invalid parameters: code=' . $broadcastCode . ', field=' . $field);
    }

    // editable 
    $editableFields = ['mode', 'param', 'memo', 'code'];
    if (!in_array($field, $editableFields)) {
        throw new Exception('Field cannot be edited: ' . $field . '. Editable fields: ' . implode(', ', $editableFields));
    }

    // Get broadcast record first
    $getBroadcast = $conn->prepare('
        SELECT b.id, b.sku, b.mode, b.param, b.memo, b.code, m.id_divisi
        FROM broadcast b
        LEFT JOIN mode m ON m.mode = b.mode
        WHERE b.code = :code LIMIT 1
    ');
    $getBroadcast->execute([':code' => $broadcastCode]);
    $broadcastData = $getBroadcast->fetch(PDO::FETCH_ASSOC);

    if (!$broadcastData) {
        throw new Exception('Broadcast record not found');
    }

    // Check access - user dapat hanya edit record mereka sendiri atau jika admin
    $recordDivision = $broadcastData['id_divisi'] ?? null;
    if (!$isAdmin && $recordDivision != $idDivisi) {
        throw new Exception('You do not have permission to edit this record');
    }

    // Validate new value based on field type
    if ($field === 'code') {
        // note: Code harus unique
        $checkCode = $conn->prepare('SELECT id FROM broadcast WHERE code = :code AND id != :id LIMIT 1');
        $checkCode->execute([':code' => $newValue, ':id' => $broadcastData['id']]);
        if ($checkCode->fetch()) {
            throw new Exception('Code already exists');
        }
        if (empty($newValue)) {
            throw new Exception('Code cannot be empty');
        }
    }

    // Prepare update query
    $updateFields = [
        'mode' => 'mode',
        'param' => 'param',
        'memo' => 'memo',
        'code' => 'code'
    ];

    $dbField = $updateFields[$field] ?? null;
    if (!$dbField) {
        throw new Exception('Invalid field mapping');
    }

    // Update table
    $updateStmt = $conn->prepare("
        UPDATE broadcast 
        SET $dbField = :value, last_update = NOW()
        WHERE code = :code
    ");
    
    $updateStmt->execute([
        ':value' => $newValue,
        ':code' => $broadcastCode
    ]);

    // Log the change
    error_log("User: $username | Updated $field | Code: $broadcastCode | Old: '$oldValue' | New: '$newValue'");

    echo json_encode([
        'success' => true,
        'message' => "$columnName updated successfully",
        'field' => $field,
        'old_value' => $oldValue,
        'new_value' => $newValue,
        'code' => $broadcastCode
    ]);

} catch (Exception $e) {
    error_log("Query Update Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
