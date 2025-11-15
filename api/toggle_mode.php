<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

if (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

session_start();
require_once '../config/conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user is admin
$userId = (int) $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$divisiName = '';

if ($username) {

    $stmt = $conn->prepare('SELECT nama FROM divisi WHERE id = (SELECT id_divisi FROM user WHERE username = ?) LIMIT 1');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    $divisiName = $row['nama'] ?? '';
}

$isAdmin = strtolower(trim($username)) === 'admin' && strtolower(trim($divisiName)) === 'it';

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$modeId = (int) ($input['modeId'] ?? 0);

if (!$modeId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid mode ID']);
    exit;
}

try {
    error_log("Attempting to toggle mode ID: " . $modeId);
    
    // Get current mode status
    $stmt = $conn->prepare('SELECT status FROM mode WHERE id = ?');
    $stmt->execute([$modeId]);
    $mode = $stmt->fetch();
    
    if (!$mode) {
        error_log("Mode not found for ID: " . $modeId);
        throw new Exception('Mode not found');
    }
    
    error_log("Current mode status: " . ($mode['status'] ?? 'null'));
    
    $currentStatus = $mode['status'] ?? 'active';
    $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
    
    error_log("New status will be: " . $newStatus);
    
    // Update mode status
    $stmt = $conn->prepare('UPDATE mode SET status = ? WHERE id = ?');
    $result = $stmt->execute([$newStatus, $modeId]);
    
    if (!$result) {
        error_log("Failed to update mode status. PDO Error: " . json_encode($stmt->errorInfo()));
        throw new Exception('Database update failed');
    }
    
    error_log("Mode status successfully updated");
    
    echo json_encode([
        'success' => true,
        'message' => 'Mode status updated successfully',
        'data' => ['status' => $newStatus]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update mode status: ' . $e->getMessage()
    ]);
}
?>