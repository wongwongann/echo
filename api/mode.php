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

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// user info
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$idDivisi = $_SESSION['id_divisi'];

// admin?
$stmt = $conn->prepare('SELECT divisi FROM divisi WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $idDivisi]);
$row = $stmt->fetch();
$divisiName = $row['divisi'] ?? '';

$isAdmin = (strcasecmp(trim($username), 'it') === 0 && strcasecmp(trim($divisiName), 'it') === 0);

try {
    switch ($action) {
        case 'create':
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $status = $input['status'] ?? 'active';
            $divisionId = $input['division_id'] ?? null;

            if (empty($name) || empty($description)) {
                throw new Exception('Name and description are required');
            }

            // If not admin, can only create for own division
            if (!$isAdmin) {
                $divisionId = $idDivisi;
            } else if (empty($divisionId)) {
                throw new Exception('Division is required');
            }

            // Verify division exists
            $divCheck = $conn->prepare('SELECT id FROM divisi WHERE id = ?');
            $divCheck->execute([$divisionId]);
            if (!$divCheck->fetch()) {
                throw new Exception('Invalid division');
            }

            $stmt = $conn->prepare('
                INSERT INTO mode (mode, description, status, id_divisi) 
                VALUES (:name, :description, :status, :divisionId)
            ');
            
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':status' => $status,
                ':divisionId' => $divisionId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Mode created successfully'
            ]);
            break;

        case 'update':
            $id = $input['id'] ?? '';
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $status = $input['status'] ?? 'active';
            $divisionId = $input['division_id'] ?? null;

            if (empty($id) || empty($name) || empty($description)) {
                throw new Exception('ID, name and description are required');
            }

            // If not admin, can only update own division's modes
            if (!$isAdmin) {
                // Verify mode belongs to user's division
                $checkStmt = $conn->prepare('SELECT id FROM mode WHERE id = :id AND id_divisi = :divisionId');
                $checkStmt->execute([':id' => $id, ':divisionId' => $idDivisi]);
                if (!$checkStmt->fetch()) {
                    throw new Exception('Mode not found or access denied');
                }
                $divisionId = $idDivisi;
            } else {
                // Admin can update any mode but needs valid division
                if (empty($divisionId)) {
                    throw new Exception('Division is required');
                }
                
                // Verify division exists
                $divCheck = $conn->prepare('SELECT id FROM divisi WHERE id = ?');
                $divCheck->execute([$divisionId]);
                if (!$divCheck->fetch()) {
                    throw new Exception('Invalid division');
                }
            }

            $stmt = $conn->prepare('
                UPDATE mode 
                SET mode = :name, 
                    description = :description, 
                    status = :status,
                    id_divisi = :divisionId
                WHERE id = :id
            ');
            
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':description' => $description,
                ':status' => $status,
                ':divisionId' => $divisionId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Mode updated successfully'
            ]);
            break;

        case 'delete':
            $id = $input['id'] ?? '';

            if (empty($id)) {
                throw new Exception('ID is required');
            }

            // If not admin, can only delete own division's modes
            if (!$isAdmin) {
                $checkStmt = $conn->prepare('SELECT id FROM mode WHERE id = :id AND id_divisi = :divisionId');
                $checkStmt->execute([':id' => $id, ':divisionId' => $idDivisi]);
                if (!$checkStmt->fetch()) {
                    throw new Exception('Mode not found or access denied');
                }
            }

            // Check if mode is being used in broadcasts
            $usageStmt = $conn->prepare('SELECT COUNT(*) FROM broadcast WHERE mode = (SELECT mode FROM mode WHERE id = :id)');
            $usageStmt->execute([':id' => $id]);
            if ($usageStmt->fetchColumn() > 0) {
                throw new Exception('Cannot delete mode that is being used in broadcasts');
            }

            $stmt = $conn->prepare('DELETE FROM mode WHERE id = :id');
            $stmt->execute([':id' => $id]);

            echo json_encode([
                'success' => true,
                'message' => 'Mode deleted successfully'
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