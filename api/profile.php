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
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            $stmt = $conn->prepare('
                SELECT u.id, u.username, u.first_name, u.email, d.divisi, d.id as id_divisi
                FROM users u
                LEFT JOIN divisi d ON u.id_divisi = d.id
                WHERE u.id = :userId
                LIMIT 1
            ');
            $stmt->execute([':userId' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('User not found');
            }

            echo json_encode([
                'success' => true,
                'data' => $user
            ]);
            break;

        case 'update':
            $firstName = trim($input['first_name'] ?? '');
            $email = trim($input['email'] ?? '');

            if (empty($firstName)) {
                throw new Exception('First name is required');
            }

            // Vall email format
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }

            // if email is already used 
            if (!empty($email)) {
                $checkEmail = $conn->prepare('SELECT id FROM users WHERE email = :email AND id != :userId LIMIT 1');
                $checkEmail->execute([':email' => $email, ':userId' => $userId]);
                if ($checkEmail->fetch()) {
                    throw new Exception('Email is already in use');
                }
            }

            // Update user 
            $updateStmt = $conn->prepare('
                UPDATE users 
                SET first_name = :firstName, email = :email
                WHERE id = :userId
            ');
            
            $result = $updateStmt->execute([
                ':firstName' => $firstName,
                ':email' => $email,
                ':userId' => $userId
            ]);

            if (!$result) {
                throw new Exception('Failed to update profile');
            }

            // Update sess
            $_SESSION['first_name'] = $firstName;

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
