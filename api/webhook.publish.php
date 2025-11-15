<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

session_start();
header('Content-Type: application/json');

require __DIR__ . '/../config/conn.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if it's a status check request
if (isset($_GET['check']) && isset($_GET['code'])) {
    $checkStmt = $conn->prepare('
        SELECT created_at 
        FROM webhook_log 
        WHERE broadcast_code = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ');
    $checkStmt->execute([$_GET['code']]);
    $lastPublish = $checkStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'isPublished' => !empty($lastPublish),
        'lastPublishDate' => !empty($lastPublish) ? date('d M Y H:i:s', strtotime($lastPublish['created_at'])) : null
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$broadcastCode = $input['code'] ?? '';

if (empty($broadcastCode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing broadcast code']);
    exit;
}

try {
    $stmt = $conn->prepare('
        SELECT 
            b.*,
            d.divisi as division_name,
            m.mode as mode_name,
            i.description as item_description,
            u.id as user_id,
            u.first_name
        FROM broadcast b
        LEFT JOIN divisi d ON d.code = SUBSTRING_INDEX(SUBSTRING_INDEX(b.code, ".", 3), ".", -1)
        LEFT JOIN mode m ON m.mode = b.mode
        LEFT JOIN item i ON i.item = b.sku
        LEFT JOIN users u ON u.username = b.username
        WHERE b.code = ?
    ');
    $stmt->execute([$broadcastCode]);
    $broadcast = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Debug - Broadcast data: " . print_r($broadcast, true));

    if (!$broadcast) {
        throw new Exception('Broadcast not found');
    }

    // Get webhook endpoints for the broadcast's user
    $webhookStmt = $conn->prepare('
        SELECT w.*, u.username 
        FROM webhook w 
        JOIN users u ON w.id_user = u.id 
        WHERE u.username = ? AND (w.webhook_type = "broadcast" OR w.webhook_type = "Syno")
    ');
    $webhookStmt->execute([$broadcast['username']]);
    $webhooks = $webhookStmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Debug - Username: " . $broadcast['username']);
    error_log("Debug - Found webhooks: " . print_r($webhooks, true));

    if (empty($webhooks)) {
        throw new Exception('No webhook endpoints configured for user: ' . $broadcast['username'] . '. Please check webhook configuration.');
    }

    // Format mssg
    $mainText = trim(sprintf(
        "%s %s - %s %s",
        $broadcast['mode'],
        $broadcast['item_description'],
        $broadcast['sku'],
        $broadcast['param']
    ));

    $fullText = $mainText;
    if (!empty($broadcast['memo'])) {
        $fullText .= "\n\nMemo:\n" . $broadcast['memo'];
    }
    
    $fullText .= "\n\nPublisher: " . ($broadcast['first_name'] ?: $broadcast['username']);

    $successCount = 0;
    $errors = [];

    foreach ($webhooks as $webhook) {
        if ($webhook['webhook_type'] === 'Syno') {
            $payload = [
                'payload' => json_encode([
                    'text' => $fullText
                ])
            ];
        } else {
            $payload = [
                'text' => $fullText,
                'code' => $broadcast['code'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        $ch = curl_init($webhook['webhook_endpoint']);
        $postData = $webhook['webhook_type'] === 'Syno' ? 
            http_build_query($payload) : json_encode($payload);
        
        $headers = $webhook['webhook_type'] === 'Syno' ? 
            ['Content-Type: application/x-www-form-urlencoded'] :
            ['Content-Type: application/json'];

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verif
            CURLOPT_SSL_VERIFYHOST => 0,     // Disable hostname verif
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $successCount++;
        } else {
            $errorMsg = '';
            if (curl_errno($ch)) {
                $errorMsg = curl_error($ch);
            } else {
                $errorMsg = "HTTP {$httpCode}";
            }
            $errors[] = "Failed to send to {$webhook['destination']}: {$errorMsg}";
        }
        
        // Log webhook 
        $logStmt = $conn->prepare('
            INSERT INTO webhook_log 
            (id_webhook, broadcast_code, status_code, response, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        $logStmt->execute([
            $webhook['id'],
            $broadcastCode,
            $httpCode,
            $response
        ]);
        
        curl_close($ch);
    }

    if ($successCount > 0) {
        echo json_encode([
            'success' => true,
            'message' => "Successfully sent to {$successCount} webhook(s)",
            'details' => !empty($errors) ? $errors : null
        ]);
    } else {
        throw new Exception('Failed to send to any webhook endpoint. ' . implode(', ', $errors));
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
