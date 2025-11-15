<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/php_errors.log');

if (ob_get_level()) ob_end_clean();

session_start();
header('Content-Type: application/json');

require __DIR__ . '/config/conn.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? $_POST['username'] ?? '');
$password = $input['password'] ?? $_POST['password'] ?? '';
$remember = !empty($input['remember'] ?? $_POST['remember'] ?? false);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username and password required.']);
    exit;
}

try {
    $stmt = $conn->prepare('SELECT * FROM `users` WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
        exit;
    }

    $hash = $user['password'];

    $verified = false;
    if (password_verify($password, $hash)) {
        $verified = true;
    } elseif ($password === $hash) {
        $verified = true;
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare('UPDATE `users` SET password = :ph WHERE id = :id');
        $update->execute([':ph' => $newHash, ':id' => $user['id']]);
    }

    if ($verified) {
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['id_divisi'] = $user['id_divisi'];

        if ($remember) {
            // frontend uses localStorage; server can set long-lived cookie if needed
            setcookie('remember_user', $user['username'], time() + (30*24*60*60), "/");
        } else {
            if (isset($_COOKIE['remember_user'])) {
                setcookie('remember_user', '', time() - 3600, "/");
            }
        }

        echo json_encode(['success' => true, 'redirect' => 'cast.php']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit;
}
?>