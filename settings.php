<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$firstName = $_SESSION['first_name'] ?? '';
$idDivisi = $_SESSION['id_divisi'] ?? null;

require __DIR__ . '/config/conn.php';

// get user divs
$divisiName = '';
if ($idDivisi) {
    $stmt = $conn->prepare('SELECT divisi FROM divisi WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $idDivisi]);
    $row = $stmt->fetch();
    $divisiName = $row['divisi'] ?? '';
}

// is admin?
$isAdmin = false;
if (strcasecmp(trim($username), 'it') === 0 && strcasecmp(trim($divisiName), 'it') === 0) {
    $isAdmin = true;
}

// Get divs list for admin
$divisions = [];
if ($isAdmin) {
    $divQuery = $conn->query('SELECT id, divisi FROM divisi ORDER BY divisi');
    $divisions = $divQuery->fetchAll(PDO::FETCH_ASSOC);
}

// Get modes list based on user access
try {
    if ($isAdmin) {
        $modeQuery = $conn->query('
            SELECT m.*, d.divisi as division_name 
            FROM mode m
            JOIN divisi d ON m.id_divisi = d.id
            ORDER BY d.divisi, m.mode
        ');
        if ($modeQuery === false) {
            error_log("Admin mode query error: " . print_r($conn->errorInfo(), true));
        }
    } else {
        $modeQuery = $conn->prepare('
            SELECT m.*, d.divisi as division_name 
            FROM mode m
            JOIN divisi d ON m.id_divisi = d.id
            WHERE m.id_divisi = :divisionId
            ORDER BY m.mode
        ');
        $modeQuery->execute([':divisionId' => $idDivisi]);
        if ($modeQuery->errorCode() !== '00000') {
            error_log("User mode query error: " . print_r($modeQuery->errorInfo(), true));
        }
    }

    $modes = $modeQuery->fetchAll(PDO::FETCH_ASSOC);
    error_log("Modes found: " . count($modes));
    error_log("First mode: " . print_r($modes[0] ?? 'no modes', true));
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $modes = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Echo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        :root {
            --primary-bg: #0f172a;
            --sidebar-bg: #1e293b;
            --card-bg: #1e293b;
            --card-hover: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-color: #6366f1;
            --accent-hover: #4f46e5;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --border-color: #334155;
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-primary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
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
            overflow-y: auto;
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
            background: var(--card-hover);
            color: var(--text-primary);
        }

        .nav-link.active {
            background: var(--accent-color);
            color: var(--text-primary);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem;
            background: var(--card-hover);
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
            color: var(--danger-color);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
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

        /* Settings Container */
        .settings-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            max-width: 1200px;
        }

        /* Settings Navigation */
        .settings-nav {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1rem;
            height: fit-content;
            border: 1px solid var(--border-color);
        }

        .settings-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--text-secondary);
        }

        .settings-nav-item:hover {
            background: var(--card-hover);
            color: var(--text-primary);
        }

        .settings-nav-item.active {
            background: var(--accent-color);
            color: var(--text-primary);
        }

        .settings-nav-item i {
            width: 20px;
            text-align: center;
        }

        /* Settings Content */
        .settings-content {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .settings-section {
            display: none;
        }

        .settings-section.active {
            display: block;
        }

        .section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--accent-color);
        }

        .section-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        /* Form Controls */
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--primary-bg);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.9375rem;
            transition: all 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(0, 255, 157, 0.1);
        }

        /* Buttons */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--accent-color);
            color: var(--text-primary);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-secondary {
            background: var(--card-hover);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--border-color);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            margin-top: 1rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            text-align: left;
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.875rem;
        }

        .table td {
            padding: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        /* DataTables Custom Styling */
        .dataTables_wrapper {
            margin-top: 1rem;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: var(--text-secondary);
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background-color: var(--primary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 6px;
            padding: 4px 8px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--text-secondary) !important;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            border-radius: 6px;
            margin: 0 4px;
            padding: 0.3rem 0.6rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--card-hover) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--accent-color) !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--accent-color);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
            color: var(--text-muted) !important;
            border: 1px solid var(--border-color);
            background: var(--card-bg) !important;
            opacity: 0.5;
        }

        /* Table Styling */
        .table.dataTable {
            color: var(--text-primary);
            margin-top: 1rem !important;
            margin-bottom: 1rem !important;
        }

        .table.dataTable thead th,
        .table.dataTable thead td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-weight: 600;
        }

        .table.dataTable tbody td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background-color: transparent;
        }

        .table.dataTable.stripe tbody tr.odd {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .table.dataTable.hover tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control::before,
        table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control::before {
            background-color: var(--accent-color);
            border: 1.5px solid var(--accent-color);
        }

        .table.dataTable>tbody>tr.child ul.dtr-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .table.dataTable>tbody>tr.child span.dtr-title {
            color: var(--text-secondary);
        }

        /* Compact spacing for action buttons */
        .table.dataTable .d-flex.gap-2 {
            gap: 0.5rem !important;
        }

        /* Status Badge */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-badge.active {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success-color);
        }

        .status-badge.inactive {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger-color);
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--border-color);
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: var(--accent-color);
        }

        input:checked+.toggle-slider:before {
            transform: translateX(26px);
        }

        /* Modal */
        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
        }

        .modal-title {
            color: var(--text-primary);
        }

        .btn-close {
            filter: invert(1);
        }

        /* Form Group */
        .form-group {
            margin-bottom: 1.5rem;
        }

        /* Mobile Responsive */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--accent-color);
            color: var(--primary-bg);
            border: none;
            padding: 0.75rem;
            border-radius: 10px;
            cursor: pointer;
        }

        @media (max-width: 992px) {
            .settings-container {
                grid-template-columns: 1fr;
            }

            .settings-nav {
                display: flex;
                overflow-x: auto;
                padding-bottom: 0.5rem;
            }

            .settings-nav-item {
                white-space: nowrap;
                margin-right: 0.5rem;
                margin-bottom: 0;
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
        }
    </style>
</head>

<body>
    <!-- Mobile Menu Toggle -->
    <!-- <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button> -->

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="cast.php" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="logo-text">Echo</div>
            </a>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="cast.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
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
                <a href="settings.php" class="nav-link active">
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
                    <div class="user-role"><?php echo $isAdmin ? 'Administrator' : 'User'; ?></div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">Manage your application settings and preferences</p>
        </div>

        <div class="settings-container">
            <!-- Settings Nav -->
            <div class="settings-nav">
                <div class="settings-nav-item active" data-section="profile">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </div>
                <div class="settings-nav-item" data-section="modes">
                    <i class="fas fa-list"></i>
                    <span>Modes</span>
                </div>
                <!-- <div class="settings-nav-item" data-section="appearance">
                    <i class="fas fa-palette"></i>
                    <span>Appearance</span>
                </div> -->
                <?php if ($isAdmin): ?>
                    <div class="settings-nav-item" data-section="admin">
                        <i class="fas fa-shield-alt"></i>
                        <span>Admin</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Settings Content -->
            <div class="settings-content">
                <!-- Profile Section -->
                <div class="settings-section active" id="profile-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-user"></i>
                            Profile Settings
                        </h2>
                        <p class="section-description">Manage your personal information and account details</p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="firstName">First Name</label>
                                <input type="text" class="form-control" id="firstName" value="<?= htmlspecialchars($firstName) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="username">Username</label>
                                <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($username) ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="division">Division</label>
                                <input type="text" class="form-control" id="division" value="<?= htmlspecialchars($divisiName) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control" id="email" placeholder="your.email@example.com">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-primary" id="saveProfileBtn">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </div>

                <!-- Modes Section -->
                <div class="settings-section" id="modes-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-list"></i>
                            Mode Settings
                        </h2>
                        <p class="section-description">Manage broadcast modes for your division</p>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5">Available Modes</h3>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModeModal">
                            <i class="fas fa-plus"></i>
                            Add Mode
                        </button>
                    </div>

                    <div class="table-container">
                        <table id="modesTable" class="table table-hover dt-responsive nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Mode</th>
                                    <?php if ($isAdmin): ?>
                                        <th>Division</th>
                                    <?php endif; ?>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th style="width: 100px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!empty($modes)):
                                    foreach ($modes as $mode):
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($mode['mode']) ?></td>
                                            <?php if ($isAdmin): ?>
                                                <td><?= htmlspecialchars($mode['division_name']) ?></td>
                                            <?php endif; ?>
                                            <td><?= htmlspecialchars($mode['description']) ?></td>
                                            <td>
                                                <span class="status-badge <?= $mode['status'] === 'active' ? 'active' : 'inactive' ?>">
                                                    <?= ucfirst($mode['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <button class="btn btn-sm btn-secondary edit-mode"
                                                        data-id="<?= $mode['id'] ?>"
                                                        data-mode="<?= htmlspecialchars($mode['mode']) ?>"
                                                        data-description="<?= htmlspecialchars($mode['description']) ?>"
                                                        data-status="<?= $mode['status'] ?>"
                                                        data-division="<?= htmlspecialchars($mode['id_divisi']) ?>"
                                                        title="Edit Mode">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <!-- <button class="btn btn-sm btn-danger delete-mode"
                                                        data-id="<?= $mode['id'] ?>"
                                                        data-mode="<?= htmlspecialchars($mode['mode']) ?>"
                                                        title="Delete Mode">
                                                        <i class="fas fa-trash"></i>
                                                    </button> -->
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                    endforeach;
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Appearance Section -->
                <!-- <div class="settings-section" id="appearance-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-palette"></i>
                            Appearance Settings
                        </h2>
                        <p class="section-description">Customize the look and feel of the application</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="theme">Theme</label>
                        <select class="form-select" id="theme">
                            <option value="dark" selected>Dark</option>
                            <option value="light">Light</option>
                            <option value="auto">Auto (System)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="accentColor">Accent Color</label>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm" style="background-color: #00ff9d; width: 40px; height: 40px; border-radius: 8px;"></button>
                            <button class="btn btn-sm" style="background-color: #3b82f6; width: 40px; height: 40px; border-radius: 8px;"></button>
                            <button class="btn btn-sm" style="background-color: #8b5cf6; width: 40px; height: 40px; border-radius: 8px;"></button>
                            <button class="btn btn-sm" style="background-color: #ef4444; width: 40px; height: 40px; border-radius: 8px;"></button>
                            <button class="btn btn-sm" style="background-color: #f59e0b; width: 40px; height: 40px; border-radius: 8px;"></button>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <div>
                                <h5 class="mb-1">Compact View</h5>
                                <p class="text-secondary mb-0">Use more compact layout for better space utilization</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div> -->

                <?php if ($isAdmin): ?>
                    <div class="settings-section" id="admin-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-shield-alt"></i>
                                Administrator Settings
                            </h2>
                            <p class="section-description">Manage system-wide settings and configurations</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="systemMaintenance">System Maintenance</label>
                            <div class="d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <h5 class="mb-1">Maintenance Mode</h5>
                                    <p class="text-secondary mb-0">Enable maintenance mode to temporarily disable user access</p>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="sessionTimeout">Session Timeout (minutes)</label>
                            <input type="number" class="form-control" id="sessionTimeout" value="30" min="5" max="120">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="maxBroadcasts">Max Broadcasts per User (per day)</label>
                            <input type="number" class="form-control" id="maxBroadcasts" value="100" min="1" max="1000">
                        </div>

                        <div class="form-group">
                            <button class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Admin Settings
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Add Mode Modal -->
    <div class="modal fade" id="addModeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Mode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addModeForm">
                        <?php if ($isAdmin): ?>
                            <div class="form-group mb-3">
                                <label for="modeDivision" class="form-label">Division</label>
                                <select class="form-select" id="modeDivision" required>
                                    <option value="">Select Division</option>
                                    <?php foreach ($divisions as $div): ?>
                                        <option value="<?= $div['id'] ?>"><?= htmlspecialchars($div['divisi']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="form-group mb-3">
                            <label for="modeName" class="form-label">Mode Name</label>
                            <input type="text" class="form-control" id="modeName" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="modeDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="modeDescription" rows="3" required></textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label d-block">Status</label>
                            <div class="toggle-switch">
                                <input type="checkbox" id="modeStatus" checked>
                                <span class="toggle-slider"></span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveMode">Save Mode</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Mode Modal -->
    <div class="modal fade" id="editModeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Mode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editModeForm">
                        <input type="hidden" id="editModeId">
                        <?php if ($isAdmin): ?>
                            <div class="form-group mb-3">
                                <label for="editModeDivision" class="form-label">Division</label>
                                <select class="form-select" id="editModeDivision" required>
                                    <option value="">Select Division</option>
                                    <?php foreach ($divisions as $div): ?>
                                        <option value="<?= $div['id'] ?>"><?= htmlspecialchars($div['divisi']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="form-group mb-3">
                            <label for="editModeName" class="form-label">Mode Name</label>
                            <input type="text" class="form-control" id="editModeName" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editModeDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editModeDescription" rows="3" required></textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editModeStatus" class="form-label">Status</label>
                            <select class="form-select" id="editModeStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="updateMode">Update Mode</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="client/js/profile-settings.js"></script>
    <script>
        $(document).ready(function() {
            const modesTable = $('#modesTable').DataTable({
                responsive: true,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    zeroRecords: "No records available",
                    info: "Showing page _PAGE_ of _PAGES_",
                    infoEmpty: "No records available",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                autoWidth: false,
                pageLength: 10,
                order: [
                    [0, 'asc']
                ],
                dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                columnDefs: [{
                        targets: -1,
                        orderable: false,
                        searchable: false,
                        width: '100px',
                        className: 'text-end'
                    },
                    {
                        targets: 2,
                        width: '100px',
                        className: 'text-center'
                    }
                ],
                stripeClasses: ['stripe-1', 'stripe-2']
            });

            $('.settings-nav-item[data-section="modes"]').on('click', function() {
                modesTable.columns.adjust().responsive.recalc();
            });


            // document.getElementById('mobileMenuToggle').addEventListener('click', function() {
            //     document.getElementById('sidebar').classList.toggle('active');
            // });

            // Settings Nav
            const settingsNavItems = document.querySelectorAll('.settings-nav-item');
            const settingsSections = document.querySelectorAll('.settings-section');

            settingsNavItems.forEach(item => {
                item.addEventListener('click', function() {
                    const targetSection = this.dataset.section;

                    settingsNavItems.forEach(navItem => navItem.classList.remove('active'));
                    this.classList.add('active');

                    settingsSections.forEach(section => section.classList.remove('active'));
                    document.getElementById(`${targetSection}-section`).classList.add('active');
                });
            });

            // Handle add mode
            document.getElementById('saveMode').addEventListener('click', async function() {
                const modeName = document.getElementById('modeName').value;
                const modeDescription = document.getElementById('modeDescription').value;
                const modeDivision = document.getElementById('modeDivision')?.value;
                const modeStatus = document.getElementById('modeStatus').checked ? 'active' : 'inactive';

                try {
                    const response = await fetch('api/mode.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'create',
                            name: modeName,
                            description: modeDescription,
                            status: modeStatus,
                            division_id: modeDivision
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to add mode');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while adding mode');
                }
            });

            // Handle edit mode 
            document.querySelectorAll('.edit-mode').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const mode = this.dataset.mode;
                    const description = this.dataset.description;
                    const status = this.dataset.status;
                    const division = this.dataset.division;

                    document.getElementById('editModeId').value = id;
                    document.getElementById('editModeName').value = mode;
                    document.getElementById('editModeDescription').value = description;
                    document.getElementById('editModeStatus').value = status;

                    const divisionSelect = document.getElementById('editModeDivision');
                    if (divisionSelect) {
                        divisionSelect.value = division;
                    }

                    new bootstrap.Modal(document.getElementById('editModeModal')).show();
                });
            });

            // Handle update mode
            document.getElementById('updateMode').addEventListener('click', async function() {
                const id = document.getElementById('editModeId').value;
                const modeName = document.getElementById('editModeName').value;
                const modeDescription = document.getElementById('editModeDescription').value;
                const modeStatus = document.getElementById('editModeStatus').value;
                const modeDivision = document.getElementById('editModeDivision')?.value;

                try {
                    const response = await fetch('api/mode.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update',
                            id: id,
                            name: modeName,
                            description: modeDescription,
                            status: modeStatus,
                            division_id: modeDivision
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to update mode');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating mode');
                }
            });
        });
    </script>
</body>

</html>