<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
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

try {
    // DtTables parameters
    $draw = (int)($_POST['draw'] ?? 1);
    $start = (int)($_POST['start'] ?? 0);
    $length = (int)($_POST['length'] ?? 25);
    $search = trim($_POST['search']['value'] ?? '');
    
    // Filter parameters
    $dateFrom = trim($_POST['dateFrom'] ?? '');
    $dateTo = trim($_POST['dateTo'] ?? '');
    $filterDivision = trim($_POST['division'] ?? '');
    $filterMode = trim($_POST['mode'] ?? '');

    // Sorting
    $orderColumn = 0;
    $orderDir = 'DESC';
    if (isset($_POST['order'][0])) {
        $orderColumn = (int)$_POST['order'][0]['column'];
        $orderDir = strtoupper($_POST['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
    }

    // base query compons
    $selectPart = "
        SELECT 
            b.id,
            DATE_FORMAT(b.date_created, '%Y-%m-%d %H:%i:%s') as date_time,
            b.code,
            b.sku,
            i.description as item_description,
            d.divisi as division_name,
            b.mode,
            b.param,
            COALESCE(u.first_name, b.username) as first_name,
            b.memo,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM webhook_log wl 
                    WHERE wl.broadcast_code = b.code 
                    AND wl.status_code >= 200 
                    AND wl.status_code < 300
                ) THEN 1
                ELSE 0
            END as is_published
    ";

    $fromPart = "
        FROM broadcast b
        LEFT JOIN item i ON i.item = b.sku
        LEFT JOIN mode m ON m.mode = b.mode
        LEFT JOIN divisi d ON d.id = m.id_divisi
        LEFT JOIN users u ON u.username = b.username
    ";

    $baseQuery = $selectPart . $fromPart;

    // Build WHERE clause
    $whereConditions = [];
    $params = [];

    // Search filter
    if (!empty($search)) {
        $searchTerm = "%{$search}%";
        $whereConditions[] = "(b.code LIKE ? OR b.sku LIKE ? OR i.description LIKE ? OR b.mode LIKE ? OR b.username LIKE ?)";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    // DR filter
    if (!empty($dateFrom)) {
        $whereConditions[] = "DATE(b.date_created) >= ?";
        $params[] = $dateFrom;
    }
    if (!empty($dateTo)) {
        $whereConditions[] = "DATE(b.date_created) <= ?";
        $params[] = $dateTo;
    }

    // Division filter
    if (!empty($filterDivision)) {
        $whereConditions[] = "d.divisi = ?";
        $params[] = $filterDivision;
    }

    // Mode filter
    if (!empty($filterMode)) {
        $whereConditions[] = "b.mode = ?";
        $params[] = $filterMode;
    }

    // Combine conditions (main WHERE)
    if (!empty($whereConditions)) {
        $baseQuery .= " WHERE " . implode(" AND ", $whereConditions);
    }

    // filtered records count
    $countSelectPart = "SELECT COUNT(*) as total";
    $countQuery = $countSelectPart . $fromPart;
    
    if (!empty($whereConditions)) {
        $countQuery .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($params);
    $filteredCount = $countStmt->fetch()['total'] ?? 0;

    // total count without filters 
    $totalQuery = "SELECT COUNT(*) as total FROM broadcast b LEFT JOIN mode m ON m.mode = b.mode";
    $totalParams = [];
    
    $totalStmt = $conn->prepare($totalQuery);
    $totalStmt->execute($totalParams);
    $totalCount = $totalStmt->fetch()['total'] ?? 0;

    $orderColumns = ['b.date_created', 'b.code', 'b.sku', 'i.description', 'd.divisi', 'b.mode', 'b.param', 'u.first_name', 'b.memo', 'is_published'];

    $orderColumnName = $orderColumns[$orderColumn] ?? 'b.date_created';
    $baseQuery .= " ORDER BY {$orderColumnName} {$orderDir}";
    $baseQuery .= " LIMIT ? OFFSET ?";
    $params[] = $length;
    $params[] = $start;

    $stmt = $conn->prepare($baseQuery);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // data format (dt tables)
    $formattedData = [];
    foreach ($data as $row) {
        $formattedData[] = [
            'date_time' => $row['date_time'] ?? 'N/A',
            'code' => htmlspecialchars($row['code'] ?? ''),
            'sku' => htmlspecialchars($row['sku'] ?? ''),
            'item_description' => htmlspecialchars($row['item_description'] ?? 'N/A'),
            'division_name' => htmlspecialchars($row['division_name'] ?? ''),
            'mode' => htmlspecialchars($row['mode'] ?? ''),
            'param' => htmlspecialchars($row['param'] ?? ''),
            'first_name' => htmlspecialchars($row['first_name'] ?? ''),
            'memo' => htmlspecialchars($row['memo'] ?? ''),
            'is_published' => $row['is_published']
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalCount,
        'recordsFiltered' => $filteredCount,
        'data' => $formattedData
    ]);

} catch (Exception $e) {
    error_log("Query Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'error' => 'An error occurred',
        'message' => $e->getMessage()
    ]);
}
