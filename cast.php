<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/php_errors.log');

if (ob_get_level()) ob_end_clean();

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/config/conn.php';

$userId = (int) $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$firstName = $_SESSION['first_name'] ?? '';
$idDivisi = $_SESSION['id_divisi'] ?? null;

$divisiName = '';
if ($idDivisi) {
    $stmt = $conn->prepare('SELECT divisi FROM divisi WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $idDivisi]);
    $row = $stmt->fetch();
    $divisiName = $row['divisi'] ?? '';
}

// full access admin(it)
$isAdminAllAccess = false;

// Debug 
error_log("Auth Debug - Username: " . $username . ", Divisi: " . $divisiName);
error_log("Auth Debug - Lowercase comparison - Username: " . strtolower(trim($username)) . ", Divisi: " . strtolower(trim($divisiName)));

if (strcasecmp(trim($username), 'it') === 0 && strcasecmp(trim($divisiName), 'it') === 0) {
    $isAdminAllAccess = true;
    error_log("Auth Debug - Admin access granted");
} else {
    error_log("Auth Debug - Admin access denied");
}

$viewMode = isset($_GET['view']) && $_GET['view'] === 'list' ? 'list' : 'grid';

$activeDivision = isset($_GET['division']) ? (int)$_GET['division'] : $idDivisi;

// Fetch all (admin)
$allDivisions = [];
if ($isAdminAllAccess) {
    $divQuery = $conn->query('SELECT id, divisi as name FROM divisi ORDER BY divisi');
    $allDivisions = $divQuery->fetchAll(PDO::FETCH_ASSOC);
}

$modes = [];
if ($isAdminAllAccess) {
    $modeQuery = $conn->query('
        SELECT m.id, m.mode as name, m.description, m.status,
               d.divisi as division_name, d.id as division_id
        FROM mode m
        JOIN divisi d ON m.id_divisi = d.id
        WHERE m.status = "active"
        ORDER BY d.divisi, m.mode
    ');
    $modes = $modeQuery->fetchAll(PDO::FETCH_ASSOC);
} else {
    $modeQuery = $conn->prepare('
        SELECT m.id, m.mode as name, m.description, m.status,
               d.divisi as division_name, d.id as division_id
        FROM mode m
        JOIN divisi d ON m.id_divisi = d.id
        WHERE m.status = "active" 
        AND m.id_divisi = :divisionId
        ORDER BY m.mode
    ');
    $modeQuery->execute([':divisionId' => $idDivisi]);
    $modes = $modeQuery->fetchAll(PDO::FETCH_ASSOC);
}

// Get recent broadcasts
$recentBroadcasts = [];
if ($isAdminAllAccess) {
    $broadcastQuery = $conn->query('
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
        ORDER BY b.id DESC
        LIMIT 5
    ');
    $recentBroadcasts = $broadcastQuery->fetchAll(PDO::FETCH_ASSOC);
} else {
    $broadcastQuery = $conn->prepare('
        SELECT 
            b.*,
            d.divisi as division_name,
            d.id as division_id,
            m.mode as mode_name,
            i.description as item_description
        FROM broadcast b
        LEFT JOIN divisi d ON d.code = SUBSTRING_INDEX(SUBSTRING_INDEX(b.code, ".", 3), ".", -1)
        LEFT JOIN mode m ON m.mode = b.mode
        LEFT JOIN item i ON i.item = b.sku
        WHERE d.id = :divisionId
        ORDER BY b.id DESC
        LIMIT 5
    ');
    $broadcastQuery->execute([':divisionId' => $idDivisi]);
    $recentBroadcasts = $broadcastQuery->fetchAll(PDO::FETCH_ASSOC);
}

//count modes 
function getModesCount($conn, $divisionId)
{
    $stmt = $conn->prepare('SELECT COUNT(*) FROM mode WHERE id_divisi = ?');
    $stmt->execute([$divisionId]);
    return $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entry - Echo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;

            --dark-bg: #0f172a;
            --dark-sidebar: #1e293b;
            --dark-card: #1e293b;
            --dark-hover: #334155;
            --dark-border: #334155;

            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;

            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-4: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Sidebar Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 230px;
            background: var(--dark-sidebar);
            border-right: 1px solid var(--dark-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--dark-border);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-primary);
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--gradient-1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background: var(--dark-hover);
            color: var(--text-primary);
        }

        .nav-link.active {
            background: var(--primary);
            color: white;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid var(--dark-border);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem;
            background: var(--dark-hover);
            border-radius: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: var(--gradient-2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .logout-btn {
            padding: 0.5rem;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .logout-btn:hover {
            color: var(--danger);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 229px;
            padding: 1rem 1rem;
        }

        .content-header {
            margin-bottom: 1rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #f1f5f9 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        /* Quick Input Card */
        .quick-input-card {
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .card-header-custom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary);
        }

        .input-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group-modern {
            position: relative;
        }

        .form-label-modern {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .form-control-modern {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--dark-bg);
            border: 2px solid var(--dark-border);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 0.9375rem;
            transition: all 0.2s ease;
        }

        .form-control-modern:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-control-modern::placeholder {
            color: var(--text-muted);
        }

        /* Two Column Layout for Preview and History */
        .preview-history-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            max-width: 100%;
            width: 100%;
        }

        .history-container {
            min-width: 0;
            /* Prevents flex item from overflowing */
            width: 100%;
            overflow: hidden;
        }

        /* Preview Section */
        .preview-container {
            background: var(--dark-bg);
            border: 1px solid var(--dark-border);
            border-radius: 12px;
            padding: 1rem;
            height: fit-content;
        }

        .preview-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--dark-border);
        }

        .preview-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .preview-header i {
            color: var(--primary);
        }

        .preview-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--dark-border);
        }

        .preview-tab {
            padding: 0.5rem 1rem;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .preview-tab:hover {
            color: var(--text-primary);
        }

        .preview-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .preview-content {
            padding: 1rem;
            background: var(--dark-card);
            border-radius: 8px;
            min-height: 120px;
        }

        .preview-pane {
            display: none;
        }

        .preview-pane.active {
            display: block;
        }

        .preview-item {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .preview-label {
            color: var(--primary);
            font-weight: 600;
            min-width: 80px;
        }

        .preview-value {
            color: var(--text-primary);
        }

        /* History Section */
        .history-container {
            background: var(--dark-bg);
            border: 1px solid var(--dark-border);
            border-radius: 12px;
            padding: 1rem;
            height: fit-content;
        }

        .history-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--dark-border);
        }

        .history-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .history-header i {
            color: var(--primary);
        }

        .view-all-btn {
            background: transparent;
            border: 1px solid var(--dark-border);
            color: var(--text-secondary);
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .view-all-btn:hover {
            background: var(--dark-hover);
            color: var(--text-primary);
        }

        .date-filter {
            width: auto;
        }

        .date-filter input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.8);
            cursor: pointer;
        }

        .history-filter-btn:hover {
            background: var(--dark-hover) !important;
            color: var(--text-primary) !important;
        }

        .history-list {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .history-list::-webkit-scrollbar {
            width: 6px;
        }

        .history-list::-webkit-scrollbar-track {
            background: var(--dark-bg);
            border-radius: 3px;
        }

        .history-list::-webkit-scrollbar-thumb {
            background: var(--dark-border);
            border-radius: 3px;
        }

        .history-item {
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .history-item:last-child {
            margin-bottom: 0;
        }

        .history-item:hover {
            transform: translateX(4px);
            border-color: var(--primary);
        }

        .history-item-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .history-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .history-icon.production {
            background: var(--gradient-1);
        }

        .history-icon.quality {
            background: var(--gradient-3);
        }

        .history-icon.packaging {
            background: var(--gradient-4);
        }

        .history-info {
            flex: 1;
            min-width: 0;
        }

        .history-title {
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            word-wrap: break-word;
            max-width: 100%;
        }

        .history-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .history-status {
            padding: 0.125rem 0.5rem;
            border-radius: 4px;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .history-status.draft {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .history-status.published {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .btn-publish {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
        }

        .btn-publish:hover {
            color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        .btn-publish i {
            font-size: 0.875rem;
        }

        .history-details {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid var(--dark-border);
            display: none;
        }

        .history-item.expanded .history-details {
            display: block;
        }

        .history-detail {
            display: flex;
            margin-bottom: 0.375rem;
            font-size: 0.75rem;
        }

        .history-detail:last-child {
            margin-bottom: 0;
        }

        .history-detail-label {
            color: var(--text-secondary);
            min-width: 60px;
        }

        .history-detail-value {
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            word-wrap: break-word;
            white-space: normal;
            max-width: 100%;
        }

        .history-info {
            flex: 1;
            min-width: 0;
            max-width: 100%;
        }

        .history-item {
            max-width: 100%;
            width: 100%;
            overflow-wrap: break-word;
        }

        .history-details {
            max-width: 100%;
            overflow: hidden;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-primary-modern {
            background: var(--gradient-1);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.3);
        }

        .btn-secondary-modern {
            background: transparent;
            color: var(--text-secondary);
            border: 2px solid var(--dark-border);
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-secondary-modern:hover {
            background: var(--dark-hover);
            color: var(--text-primary);
        }

        /* Keyboard Shortcuts */
        .shortcuts-hint {
            display: flex;
            gap: 1rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        .shortcut {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .key {
            padding: 0.125rem 0.375rem;
            background: var(--dark-hover);
            border: 1px solid var(--dark-border);
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.75rem;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 2000;
        }

        .toast {
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            min-width: 300px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
        }

        .toast.success {
            border-left: 4px solid var(--success);
        }

        .toast.error {
            border-left: 4px solid var(--danger);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .toast-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .btn-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 0.875rem;
            cursor: pointer;
        }

        .btn-close:hover {
            color: var(--text-primary);
        }

        .toast-body {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        /* Mobile Responsive */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 10px;
            cursor: pointer;
        }

        @media (max-width: 992px) {
            .preview-history-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 4rem 1rem 1rem;
            }

            .input-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Spinner */
        .spinner-border {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Autocomplete */
        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            border-radius: 8px;
            margin-top: 0.25rem;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .autocomplete-results.show {
            display: block;
        }

        .autocomplete-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .autocomplete-item:hover {
            background: var(--dark-hover);
        }

        .autocomplete-item-title {
            font-weight: 500;
            color: var(--text-primary);
        }

        .autocomplete-item-subtitle {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
    </style>
</head>

<body>

    <div class="app-container">
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="#" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="logo-text">Echo</div>
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="fas fa-home"></i>
                        <span>Entry</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="monitor/live-monitor.php" class="nav-link">
                        <i class="fa-solid fa-display"></i>
                        <span>Monitor</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="query.php" class="nav-link">
                        <i class="fas fa-search"></i>
                        <span>Query</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($firstName ?: $username); ?></div>
                        <div class="user-role"><?php echo $isAdminAllAccess ? 'Administrator' : 'User'; ?></div>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- <div class="content-header">
                <h1 class="page-title">Quick Input</h1>
                <p class="page-subtitle"></p>
            </div> -->

            <!-- Input Card -->
            <div class="quick-input-card">
                <div class="card-header-custom">
                    <h2 class="card-title">
                        <i class="fas fa-bolt"></i>
                        New Entry
                    </h2>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <div class="form-group-modern ">
                            <label class="form-label-modern" for="modeInput">Mode</label>
                            <select id="modeInput" class="form-control-modern form-select">
                                <option value="">Select Mode</option>
                                <?php if ($isAdminAllAccess): ?>
                                    <?php
                                    $currentDiv = null;
                                    foreach ($modes as $mode):
                                        $divLabel = $mode['division_name'] ?? 'Unknown';
                                        if ($currentDiv !== $divLabel):
                                            if ($currentDiv !== null) echo "</optgroup>\n";
                                            echo '<optgroup label="' . htmlspecialchars($divLabel) . '">';
                                            $currentDiv = $divLabel;
                                        endif;
                                    ?>
                                        <option value="<?php echo htmlspecialchars($mode['name']); ?>" data-division-id="<?php echo htmlspecialchars($mode['division_id'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($mode['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if ($currentDiv !== null) echo "</optgroup>\n"; ?>
                                <?php else: ?>
                                    <?php foreach ($modes as $mode): ?>
                                        <option value="<?php echo htmlspecialchars($mode['name']); ?>">
                                            <?php echo htmlspecialchars($mode['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group-modern ">
                            <label class="form-label-modern" for="object1Input">SKU</label>
                            <div class="autocomplete-wrapper">
                                <input type="text" id="object1Input" class="form-control-modern" placeholder="Enter SKU">
                                <div id="autocompleteResults" class="autocomplete-results"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group-modern ">
                            <label class="form-label-modern" for="object2Input">Parameter</label>
                            <input type="text" id="object2Input" class="form-control-modern" placeholder="Enter Parameter" value="">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group-modern ">
                            <label class="form-label-modern" for="memoInput">Memo</label>
                            <input type="text" id="memoInput" class="form-control-modern" placeholder="Enter Memo" value="">
                        </div>
                    </div>
                </div>

                <!-- Preview and History Side by Side -->
                <div class="preview-history-container">
                    <!-- Preview Section -->
                    <div class="preview-container">
                        <div class="preview-header">
                            <i class="fas fa-eye"></i>
                            <h3>Preview</h3>
                        </div>
                        <div class="preview-tabs">
                            <button class="preview-tab" data-tab="detailed">Detailed</button>
                            <button class="preview-tab active" data-tab="text">Text</button>
                        </div>
                        <div class="preview-content">
                            <div class="preview-pane" id="detailedPreview">
                                <div class="preview-item">
                                    <span class="preview-label">Mode:</span>
                                    <span class="preview-value" id="modePreview">---</span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">SKU:</span>
                                    <span class="preview-value" id="object1Preview">---</span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Parameter:</span>
                                    <span class="preview-value" id="object2Preview">---</span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Memo:</span>
                                    <span class="preview-value" id="memoPreview">---</span>
                                </div>
                            </div>
                            <div class="preview-pane active" id="textPreview">
                                <p class="text-preview-main" id="textPreviewMain">---</p>
                                <span style="font-size: 15px; font-style: italic; color:var(--text-secondary);">Memo:</span>
                                <p class="text-preview-memo" id="textPreviewMemo">---</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Entries Section -->
                    <div class="history-container">
                        <div class="history-header">
                            <h3>
                                <i class="fas fa-clock"></i>
                                Recent Entries
                            </h3>
                            <div class="d-flex gap-2 align-items-center">
                                <div class="input-group input-group-sm date-filter">
                                    <input type="date" class="form-control form-control-sm" id="historyDateFilter"
                                        value="<?php echo date('Y-m-d'); ?>"
                                        style="background: var(--dark-bg); color: var(--text-primary); border-color: var(--dark-border);">
                                    <button class="btn btn-sm history-filter-btn" type="button" id="clearDateFilter"
                                        style="background: var(--dark-hover); color: var(--text-secondary); border-color: var(--dark-border);">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <button class="view-all-btn">View All</button>
                            </div>
                        </div>

                        <div class="history-list">
                            <?php if (empty($recentBroadcasts)): ?>
                                <div class="text-center text-muted">
                                    <p>No recent entries found</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentBroadcasts as $broadcast): ?>
                                    <div class="history-item">
                                        <div class="history-item-header">
                                            <div class="history-icon <?php echo strtolower($broadcast['division_name']); ?>">
                                                <i class="fas fa-broadcast-tower"></i>
                                            </div>
                                            <div class="history-info">
                                                <div class="history-title"><?php echo ($broadcast['item_description'] ? htmlspecialchars($broadcast['item_description']) . ' - ' : '') .
                                                                                htmlspecialchars($broadcast['sku']); ?></div>
                                                <div class="history-meta">
                                                    <span>
                                                        <i class="far fa-clock"></i> <?php
                                                                                        $date = new DateTime($broadcast['date_created']);
                                                                                        echo $date->format('d M Y H:i');
                                                                                        ?>
                                                    </span>
                                                    <span class="history-status published"><?php echo htmlspecialchars($broadcast['mode_name'] ?? $broadcast['mode']); ?></span>
                                                    <button class="btn-publish" title="Publish" data-code="<?php echo htmlspecialchars($broadcast['code']); ?>" onclick="event.stopPropagation();">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="history-details">
                                            <div class="history-detail">
                                                <span class="history-detail-label">Parameter:</span>
                                                <span class="history-detail-value"><?php echo htmlspecialchars($broadcast['param']); ?></span>
                                            </div>
                                            <div class="history-detail">
                                                <span class="history-detail-label">Memo:</span>
                                                <span class="history-detail-value"><?php echo htmlspecialchars($broadcast['memo']); ?></span>
                                            </div>
                                            <div class="history-detail">
                                                <span class="history-detail-label">User:</span>
                                                <span class="history-detail-value"><?php echo htmlspecialchars($broadcast['first_name'] ?? $broadcast['username']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="button" id="submitForm" class="btn-primary-modern">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span class="button-text">Submit</span>
                    </button>
                    <button type="button" class="btn-secondary-modern" id="clearForm">
                        <i class="fas fa-times"></i>
                        <span>Clear</span>
                    </button>
                </div>

                <div class="shortcuts-hint">
                    <div class="shortcut">
                        <span class="key">Ctrl</span>
                        <span>+</span>
                        <span class="key">Enter</span>
                        <span>Submit</span>
                    </div>
                    <div class="shortcut">
                        <span class="key">Tab</span>
                        <span>Next Field</span>
                    </div>
                    <div class="shortcut">
                        <span class="key">Esc</span>
                        <span>Clear</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="toast-container"></div>

    <!-- Republish Confirmation Modal -->
    <div class="modal fade" id="republishModal" tabindex="-1" aria-labelledby="republishModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: var(--dark-card); color: var(--text-primary); border: 1px solid var(--dark-border);">
                <div class="modal-header" style="border-bottom-color: var(--dark-border);">
                    <h5 class="modal-title" id="republishModalLabel">Confirm Republish</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This broadcast has been published before. Are you sure you want to publish it again?</p>
                    <div class="broadcast-info mt-3">
                        <strong>Last published:</strong> <span id="lastPublishDate"></span>
                    </div>
                </div>
                <div class="modal-footer" style="border-top-color: var(--dark-border);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmRepublish">Yes, Publish Again</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="client/js/cast.js"></script>
    <script>
        // document.getElementById('mobileMenuToggle').addEventListener('click', function() {
        //     document.getElementById('sidebar').classList.toggle('active');
        // });

        function formatDateTime(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatBroadcastDate(code) {
            const year = '20' + code.split('.')[1];
            const now = new Date();
            return now.toLocaleString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        function updateHistoryList(newBroadcast) {
            const historyList = document.querySelector('.history-list');

            const historyItem = document.createElement('div');
            historyItem.className = 'history-item';

            historyItem.innerHTML = `
                <div class="history-item-header">
                    <div class="history-icon ${(newBroadcast.division_name || '').toLowerCase()}">
                        <i class="fas fa-broadcast-tower"></i>
                    </div>
                    <div class="history-info">
                        <div class="history-title">${newBroadcast.item_description ? newBroadcast.item_description + ' - ' : ''}${newBroadcast.sku}</div>
                        <div class="history-meta">
                            <span>
                                <i class="far fa-clock"></i> ${formatDateTime(newBroadcast.date_created)}
                            </span>
                            <span class="history-status published">${newBroadcast.mode_name || newBroadcast.mode}</span>
                            <button class="btn-publish" title="Publish" data-code="${newBroadcast.code}" onclick="event.stopPropagation();">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="history-details">
                    <div class="history-detail">
                        <span class="history-detail-label">Parameter:</span>
                        <span class="history-detail-value">${newBroadcast.param}</span>
                    </div>
                    <div class="history-detail">
                        <span class="history-detail-label">Memo:</span>
                        <span class="history-detail-value">${newBroadcast.memo}</span>
                    </div>
                    <div class="history-detail">
                        <span class="history-detail-label">User:</span>
                        <span class="history-detail-value">${newBroadcast.first_name || newBroadcast.username}</span>
                    </div>
                </div>
            `;

            // exander
            historyItem.addEventListener('click', function() {
                this.classList.toggle('expanded');
            });

            // new item on top
            if (historyList.firstChild) {
                historyList.insertBefore(historyItem, historyList.firstChild);
            } else {
                historyList.appendChild(historyItem);
            }

            // trial
            const items = historyList.querySelectorAll('.history-item');
            if (items.length > 5) {
                historyList.removeChild(items[items.length - 1]);
            }
        }

        // Expand/Collapse
        document.querySelectorAll('.history-item').forEach(item => {
            item.addEventListener('click', function() {
                this.classList.toggle('expanded');
            });
        });

        // Clear Form
        document.getElementById('clearForm').addEventListener('click', function() {
            resetForm();
        });

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter 
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                submitBroadcast();
            }

            // Esc
            if (e.key === 'Escape') {
                resetForm();
            }
        });

        document.addEventListener('broadcastSubmitted', function(e) {
            if (e.detail && e.detail.broadcast) {
                updateHistoryList(e.detail.broadcast);
            }
        });

        // Date Filter Handler
        document.getElementById('historyDateFilter').addEventListener('change', function(e) {
            const selectedDate = e.target.value;
            const historyItems = document.querySelectorAll('.history-item');

            historyItems.forEach(item => {
                const dateStr = item.querySelector('.history-meta span i').nextSibling.textContent.trim();
                const itemDate = new Date(dateStr);
                const filterDate = new Date(selectedDate);

                // Compare only the date part
                const isSameDate = itemDate.getFullYear() === filterDate.getFullYear() &&
                    itemDate.getMonth() === filterDate.getMonth() &&
                    itemDate.getDate() === filterDate.getDate();

                item.style.display = isSameDate ? 'block' : 'none';
            });
        });

        // Clear Date Filter
        document.getElementById('clearDateFilter').addEventListener('click', function() {
            document.getElementById('historyDateFilter').value = new Date().toISOString().split('T')[0];
            const historyItems = document.querySelectorAll('.history-item');
            historyItems.forEach(item => {
                item.style.display = 'block';
            });
        });
    </script>
</body>

</html>