<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers for the proxy
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Target
$api_url = 'http://192.168.2.100/efiq/api/api.item.php?function=get';

$ch = curl_init();

// cURL opt
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // set TO

$response = curl_exec($ch);

if(curl_errno($ch)) {
    echo json_encode([
        'status' => 0,
        'message' => 'Curl error: ' . curl_error($ch),
        'error' => curl_errno($ch)
    ]);
} else {
    // Decode & sanitize response
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['data']) && is_array($decoded['data'])) {
        foreach ($decoded['data'] as &$item) {
            $item['item'] = isset($item['item']) ? (string)$item['item'] : '';
            $item['name'] = isset($item['name']) ? (string)$item['name'] : '';
            $item['description'] = isset($item['description']) ? (string)$item['description'] : '';
        }
        unset($item); // Break reference
    }
 
    echo json_encode($decoded);
}

curl_close($ch);