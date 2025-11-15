<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

if (ob_get_level()) ob_end_clean();

session_start();
header('Content-Type: application/json');

require __DIR__ . '/../config/conn.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$mode = $input['mode'] ?? '';
$object1 = $input['object1'] ?? '';
$object2 = $input['object2'] ?? '';
$memo = $input['memo'] ?? '';
$divisionId = $_SESSION['id_divisi'];
$username = $_SESSION['username'] ?? '';

try {
    // Get divs code
    $stmtDiv = $conn->prepare('SELECT code FROM divisi WHERE id = ?');
    $stmtDiv->execute([$divisionId]);
    $divisionCode = $stmtDiv->fetchColumn();

    if (!$divisionCode) {
        throw new Exception('Division code not found');
    }

    // Get current year's last seqnum
    $currentYear = date('y');
    $stmtSeq = $conn->prepare('
        SELECT MAX(CAST(SUBSTRING(code, -3) AS UNSIGNED)) as last_seq 
        FROM broadcast 
        WHERE code LIKE :codePattern
    ');
    $stmtSeq->execute([
        ':codePattern' => "EC.{$currentYear}.{$divisionCode}.%"
    ]);
    $lastSeq = $stmtSeq->fetchColumn() ?: 0;
    
    $newSeq = str_pad($lastSeq + 1, 3, '0', STR_PAD_LEFT);
    
    // formating
    $code = "EC.{$currentYear}.{$divisionCode}.{$newSeq}";

    $stmt = $conn->prepare('
        INSERT INTO broadcast (mode, code, sku, param, memo, username) 
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    
    $result = $stmt->execute([
        $mode,
        $code,
        $object1,
        $object2,
        $memo,
        $username
    ]);

    if ($result) {
    
        $stmtBroadcast = $conn->prepare('
            SELECT 
                b.*,
                d.divisi as division_name,
                d.id as division_id,
                m.mode as mode_name,
                i.description as item_description,
                u.first_name
            FROM broadcast b
            LEFT JOIN divisi d ON d.code = SUBSTRING_INDEX(SUBSTRING_INDEX(b.code, ".", 3), ".", -1)
            LEFT JOIN mode m ON m.mode = b.mode
            LEFT JOIN item i ON i.item = b.sku
            LEFT JOIN users u ON u.username = b.username
            WHERE b.code = ?
        ');
        $stmtBroadcast->execute([$code]);
        $broadcastData = $stmtBroadcast->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Data saved successfully',
            'code' => $code,
            'broadcast' => $broadcastData
        ]);
    } else {
        throw new Exception('Failed to save data');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}