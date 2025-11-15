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

// user divs
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

// divs list for filters
$divisions = [];
if ($isAdmin) {
    $divQuery = $conn->query('SELECT id, divisi FROM divisi ORDER BY divisi');
    $divisions = $divQuery->fetchAll(PDO::FETCH_ASSOC);
}

// modes list for filters
$modes = [];
if ($isAdmin) {
    $modeQuery = $conn->query('SELECT id, mode FROM mode WHERE status = "active" ORDER BY mode');
    $modes = $modeQuery->fetchAll(PDO::FETCH_ASSOC);
} else {
    $modeQuery = $conn->prepare('SELECT id, mode FROM mode WHERE status = "active" AND id_divisi = :divisionId ORDER BY mode');
    $modeQuery->execute([':divisionId' => $idDivisi]);
    $modes = $modeQuery->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Query - Echo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
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

        /* Query Card */
        .query-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .query-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .query-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .query-title i {
            color: var(--accent-color);
        }

        /* Filters */
        .filters-section {
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        .filter-control {
            padding: 0.75rem;
            background: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .filter-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-filter {
            padding: 0.5rem 1rem;
            background: var(--accent-color);
            color: var(--text-primary);
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-filter:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        .btn-reset {
            padding: 0.5rem 1rem;
            background: var(--border-color);
            color: var(--text-primary);
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-reset:hover {
            background: var(--card-hover);
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            max-width: 1500px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            text-align: left;
            padding: 0.75rem;
            border-bottom: 2px solid var(--accent-color);
            color: var(--accent-color);
            font-weight: 700;
            font-size: 0.875rem;
            background-color: rgba(99, 102, 241, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            background-color: transparent;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background: rgba(99, 102, 241, 0.08);
            transition: background 0.2s ease;
        }

        .table tbody tr {
            transition: background 0.2s ease;
        }

        /* Striped rows for better readability */
        .table tbody tr:nth-child(odd) {
            background-color: rgba(0, 0, 0, 0.15);
        }

        .table tbody tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .table tbody tr:nth-child(odd):hover {
            background-color: rgba(99, 102, 241, 0.15);
        }

        .table tbody tr:nth-child(even):hover {
            background-color: rgba(99, 102, 241, 0.10);
        }

        /* DataTables Styling */
        .dataTables_wrapper {
            margin-top: 1rem;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background-color: var(--primary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
        }

        .dataTables_wrapper .dataTables_info {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--text-secondary) !important;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            border-radius: 6px;
            margin: 0 2px;
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--card-hover) !important;
            color: var(--text-primary) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--accent-color) !important;
            color: var(--text-primary) !important;
            border-color: var(--accent-color);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .table.dataTable {
            margin-top: 0 !important;
        }

        .table.dataTable thead th {
            padding: 0.75rem;
        }

        .table.dataTable tbody td {
            padding: 0.75rem;
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

        .status-badge.published {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success-color);
        }

        .status-badge.unpublished {
            background: rgba(99, 102, 241, 0.2);
            color: var(--accent-color);
        }

        /* Badge Styles */
        .badge {
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success-color);
        }

        /* Edit Mode Styles */
        .edit-mode-active {
            background: rgba(99, 102, 241, 0.2) !important;
            border: 2px dashed var(--accent-color) !important;
        }

        .table tbody tr.row-selected {
            background: rgba(99, 102, 241, 0.3) !important;
            box-shadow: inset 0 0 0 2px var(--accent-color);
        }

        .edit-mode-banner {
            position: fixed;
            top: 0;
            left: 15em;
            right: 0;
            height: 50px;
            background: linear-gradient(90deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            z-index: 999;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .edit-mode-banner.active {
            display: flex;
        }

        .edit-mode-banner-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .edit-mode-banner-title {
            font-size: 1rem;
            font-weight: 600;
        }

        .edit-mode-banner-info {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .edit-mode-banner-right {
            display: flex;
            gap: 0.75rem;
        }

        .edit-mode-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .edit-mode-btn-edit {
            background: white;
            color: #6366f1;
        }

        .edit-mode-btn-edit:hover {
            background: rgba(255, 255, 255, 0.9);
        }

        .edit-mode-btn-cancel {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .edit-mode-btn-cancel:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .main-content.edit-mode-active {
            margin-top: 50px;
        }

        /* Selection hint */
        .edit-mode-hint {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--accent-color);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            display: none;
            align-items: center;
            gap: 1rem;
            z-index: 998;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            animation: slideIn 0.3s ease;
        }

        .edit-mode-hint.active {
            display: flex;
        }

        @keyframes slideIn {
            from {
                transform: translateY(100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Inline Edit Styles */
        .table td.editing {
            padding: 0 !important;
            background: rgba(99, 102, 241, 0.2) !important;
        }

        .inline-edit-input {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary-bg);
            border: 2px solid var(--accent-color);
            border-radius: 4px;
            color: var(--text-primary);
            font-size: 0.875rem;
            font-family: inherit;
            box-shadow: 0 0 8px rgba(99, 102, 241, 0.3);
        }

        .inline-edit-input:focus {
            outline: none;
            border-color: var(--accent-hover);
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.5);
        }

        .table td.updated {
            background: rgba(16, 185, 129, 0.2) !important;
            animation: highlight-update 0.5s ease;
        }

        @keyframes highlight-update {
            0% {
                box-shadow: inset 0 0 0 3px var(--success-color);
            }
            100% {
                box-shadow: inset 0 0 0 0 transparent;
            }
        }

        /* Edit Modal Styles */
        .edit-modal-content {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }

        .edit-modal-header {
            background: linear-gradient(90deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            border-radius: 12px 12px 0 0;
            padding: 1.5rem;
        }

        .edit-modal-title {
            color: white;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .edit-modal-body {
            padding: 2rem;
        }

        .edit-form-input {
            background: var(--primary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 6px;
            padding: 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .edit-form-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background: var(--primary-bg);
            color: var(--text-primary);
        }

        .edit-form-input[readonly] {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .form-label {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .text-danger {
            color: var(--danger-color);
        }

        .edit-modal-footer {
            background: rgba(0, 0, 0, 0.1);
            border-top: 1px solid var(--border-color);
            padding: 1.5rem;
            border-radius: 0 0 12px 12px;
        }

        .edit-modal-save-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 500;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .edit-modal-save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            color: white;
        }

        .edit-modal-save-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
        }

        #editFormMessage {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
            border-radius: 6px;
            padding: 1rem;
        }

        #editFormMessage.error {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .query-header {
                flex-direction: column;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Edit Mode Banner -->
    <div class="edit-mode-banner" id="editModeBanner">
        <div class="edit-mode-banner-left">
            <div>
                <div class="edit-mode-banner-title">
                    <i class="fas fa-pencil-alt"></i> Edit Mode Active
                </div>
            </div>
            <div class="edit-mode-banner-info" id="selectedRowInfo">
                No row selected. Click a row to select it.
            </div>
        </div>
        <div class="edit-mode-banner-right">
            <button class="edit-mode-btn edit-mode-btn-edit" id="editModeEditBtn">
                <i class="fas fa-edit"></i> Edit Selected
            </button>
            <button class="edit-mode-btn edit-mode-btn-cancel" id="editModeExitBtn">
                <i class="fas fa-times"></i> Exit
            </button>
        </div>
    </div>

    <!-- Edit -->
    <div class="edit-mode-hint" id="editModeHint">
        <div>
            <i class="fas fa-info-circle"></i>
            Press <kbd>Insert</kbd> again to toggle Edit Mode off
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edit-modal-content">
                <div class="modal-header edit-modal-header">
                    <h5 class="modal-title edit-modal-title">
                        <i class="fas fa-edit"></i> Edit Broadcast Record
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body edit-modal-body">
                    <form id="editForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="editCode" class="form-label">Code</label>
                                    <input type="text" class="form-control edit-form-input" id="editCode" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="editSKU" class="form-label">SKU</label>
                                    <input type="text" class="form-control edit-form-input" id="editSKU" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="editMode" class="form-label">Mode <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control edit-form-input" id="editMode">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="editParam" class="form-label">Param</label>
                                    <input type="text" class="form-control edit-form-input" id="editParam">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editMemo" class="form-label">Memo</label>
                            <textarea class="form-control edit-form-input" id="editMemo" rows="3"></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <input type="text" class="form-control edit-form-input" id="editDescription" readonly>
                        </div>

                        <div class="form-group mb-3">
                            <label for="editFirstName" class="form-label">Name</label>
                            <input type="text" class="form-control edit-form-input" id="editFirstName" readonly>
                        </div>

                        <div class="alert alert-info" style="display: none;" id="editFormMessage">
                            <i class="fas fa-info-circle"></i> <span id="editFormMessageText"></span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer edit-modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary edit-modal-save-btn" id="editModalSaveBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

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
                <a href="query.php" class="nav-link active">
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
            <h1 class="page-title">Query</h1>
            <!-- <p class="page-subtitle">"Query page" cus someone request it</p> -->
        </div>

        <div class="query-card">
            <div class="query-header">
                <div class="query-title">
                    <i class="fas fa-database"></i>
                    All Broadcasts
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <div class="filter-row">
                    <div class="filter-group">
                        <label class="filter-label">Date Range From</label>
                        <input type="date" id="filterDateFrom" class="filter-control">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Date Range To</label>
                        <input type="date" id="filterDateTo" class="filter-control">
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="filter-group">
                        <label class="filter-label">Division</label>
                        <select id="filterDivision" class="filter-control">
                            <option value="">All Divisions</option>
                            <?php foreach ($divisions as $div): ?>
                            <option value="<?php echo htmlspecialchars($div['divisi']); ?>">
                                <?php echo htmlspecialchars($div['divisi']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="filter-group">
                        <label class="filter-label">Mode</label>
                        <select id="filterMode" class="filter-control">
                            <option value="">All Modes</option>
                            <?php foreach ($modes as $mode): ?>
                            <option value="<?php echo htmlspecialchars($mode['mode']); ?>">
                                <?php echo htmlspecialchars($mode['mode']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="filter-buttons">
                    <button class="btn-filter" id="btnApplyFilter">
                        <i class="fas fa-filter"></i>
                        Apply Filters
                    </button>
                    <button class="btn-reset" id="btnResetFilter">
                        <i class="fas fa-redo"></i>
                        Reset
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container">
                <table id="broadcastTable" class="table table-hover dt-responsive nowrap">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Code</th>
                            <th>SKU</th>
                            <th>Description</th>
                            <?php if ($isAdmin): ?>
                            <th>Division</th>
                            <?php endif; ?>
                            <th>Mode</th>
                            <th>Param</th>
                            <th>Name</th>
                            <th>Memo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            let editModeActive = false;
            let selectedRowData = null;
            let selectedRowElement = null;

            // Init DtTable
            let table = $('#broadcastTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: 'api/query.php',
                    type: 'POST',
                    data: function(d) {
                        d.dateFrom = $('#filterDateFrom').val();
                        d.dateTo = $('#filterDateTo').val();
                        d.division = $('#filterDivision').val();
                        d.mode = $('#filterMode').val();
                    }
                },
                columns: [
                    { data: 'date_time', width: '15%' },
                    { data: 'code', width: '10%' },
                    { data: 'sku', width: '8%' },
                    { data: 'item_description', width: '15%' }
                    <?php if ($isAdmin): ?>  
                     , { data: 'division_name', width: '10%' }
                    <?php endif; ?>
                    , 
                    { data: 'mode', width: '10%' },
                    { data: 'param', width: '8%' },
                    { data: 'first_name', width: '8%' },
                    { data: 'memo', width: '10%' },
                    { data: 'is_published', width: '8%' }
                ],
                columnDefs: [
                    {
                        targets: -1,
                        render: function(data) {
                            return data == 1 ? 
                                '<span class="status-badge published"><i class="fas fa-check"></i> Published</span>' :
                                '<span class="status-badge unpublished"><i class="fas fa-times"></i> Unpublished</span>';
                        }
                    }
                ],
                responsive: true,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    zeroRecords: "No records found",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
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
                pageLength: 25,
                order: [[0, 'desc']],
                dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
            });

            // insert = edit tggle
            $(document).on('keydown', function(e) {
                if (e.key === 'Insert') {
                    e.preventDefault();
                    toggleEditMode();
                }
            });

            function toggleEditMode() {
                editModeActive = !editModeActive;
                
                if (editModeActive) {
                    $('#editModeBanner').addClass('active');
                    $('#editModeHint').addClass('active');
                    $('.main-content').addClass('edit-mode-active');
                    $('body').css('pointer-events', 'none');
                    $('#broadcastTable').css('pointer-events', 'auto');
                    $('#editModeBanner').css('pointer-events', 'auto');
                    $('#editModeHint').css('pointer-events', 'auto');
                    
                    $('#broadcastTable tbody tr').css('cursor', 'pointer');
                } else {
                    exitEditMode();
                }
            }

            function exitEditMode() {
                editModeActive = false;
                $('#editModeBanner').removeClass('active');
                $('#editModeHint').removeClass('active');
                $('.main-content').removeClass('edit-mode-active');
                $('body').css('pointer-events', 'auto');
                $('#broadcastTable tbody tr').css('cursor', 'default').removeClass('row-selected');
                
                selectedRowData = null;
                selectedRowElement = null;
                $('#selectedRowInfo').text('No row selected. Click a row to select it.');
            }

            // Track double-click timing
            let lastClickTime = 0;
            let lastClickCell = null;

            // Click to select & double-click untuk edit
            $(document).on('click', '#broadcastTable tbody td', function(e) {
                if (!editModeActive) return;

                let $cell = $(this);
                let $row = $cell.closest('tr');
                let currentTime = new Date().getTime();
                let isDoubleClick = (currentTime - lastClickTime < 300) && lastClickCell === this;

                if (isDoubleClick) {
                    // Double-click = edit cell
                    e.preventDefault();
                    editCellInline($cell, $row);
                    lastClickTime = 0;
                    lastClickCell = null;
                } else {
                    // Single-click = select row
                    if (selectedRowElement) {
                        $(selectedRowElement).removeClass('row-selected');
                    }

                    $row.addClass('row-selected');
                    selectedRowElement = $row[0];
                    
                    // row data from DtTable
                    let rowData = table.row($row).data();
                    selectedRowData = rowData;

                    // Update info
                    let code = rowData.code || 'N/A';
                    let sku = rowData.sku || 'N/A';
                    $('#selectedRowInfo').html(`
                        <strong>Selected:</strong> Code: ${code} | SKU: ${sku}
                    `);

                    lastClickTime = currentTime;
                    lastClickCell = this;
                }
            });

            // Inline edit
            function editCellInline($cell, $row) {
                let cellIndex = $cell.index();
                let totalColumns = $row.find('td').length;
                
                // Non-editable 
                let readOnlyColumns = {
                    0: 'Date (Read-only)',
                    2: 'SKU (Read-only)',
                    3: 'Description (Read-only)',
                    4: 'Division (Read-only)',
                    7: 'Name (Read-only)',
                    9: 'Status (Read-only)'
                };

                if (readOnlyColumns.hasOwnProperty(cellIndex)) {
                    alert(readOnlyColumns[cellIndex] + ' - cannot be edited');
                    return;
                }

                let originalContent = $cell.text().trim();
                let originalHtml = $cell.html();
                
                // Create input
                let $input = $('<input type="text" class="inline-edit-input">');
                $input.val(originalContent);
    
                $cell.html('');
                $cell.addClass('editing');
                $cell.append($input);
                $input.focus();

                // Get column name untuk display
                let columnHeaders = ['Date', 'Code', 'SKU', 'Description', 'Division', 'Mode', 'Param', 'Name', 'Memo', 'Status'];
                let columnName = columnHeaders[cellIndex] || 'Field';

                // save
                function saveCellEdit() {
                    let newValue = $input.val().trim();
                    
                    if (newValue !== originalContent) {
                        // Send update to backend
                        let rowData = table.row($row).data();
                        let fieldName = getFieldNameByIndex(cellIndex);
                        
                        let updateData = {
                            broadcast_code: rowData.code,
                            field: fieldName,
                            old_value: originalContent,
                            new_value: newValue,
                            column_name: columnName
                        };

                        console.log('Sending update:', updateData);

                        $.ajax({
                            url: 'api/query-update.php',
                            type: 'POST',
                            dataType: 'json',
                            data: updateData,
                            success: function(response) {
                                if (response.success) {
                                    $cell.removeClass('editing');
                                    $cell.text(newValue);
                                    $cell.addClass('updated');
                                    setTimeout(() => $cell.removeClass('updated'), 2000);
                                    
                                    // Update row data in memory
                                    selectedRowData[fieldName] = newValue;
                                    
                                    console.log('Update successful:', response);
                                } else {
                                    alert('Error: ' + (response.message || 'Failed to update'));
                                    $cell.removeClass('editing');
                                    $cell.html(originalHtml);
                                }
                            },
                            error: function(xhr, status, error) {
                                let errorMsg = error;
                                try {
                                    let response = JSON.parse(xhr.responseText);
                                    errorMsg = response.message || error;
                                } catch(e) {}
                                
                                alert('Error updating data: ' + errorMsg);
                                $cell.removeClass('editing');
                                $cell.html(originalHtml);
                            }
                        });
                    } else {
                        $cell.removeClass('editing');
                        $cell.html(originalHtml);
                    }
                }

                // cancel (ESC key)
                function cancelCellEdit() {
                    $cell.removeClass('editing');
                    $cell.html(originalHtml);
                }

                // Key handlers
                $input.on('blur', saveCellEdit);
                $input.on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        saveCellEdit();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        cancelCellEdit();
                    }
                });
            }

            // Helper: get field name by column index
            function getFieldNameByIndex(index) {
                let totalColumns = $('#broadcastTable thead tr th').length;
                let isAdminView = totalColumns === 10;

                const adminFieldMap = {
                    0: 'date_time',           // Date
                    1: 'code',                 // Code
                    2: 'sku',                  // SKU
                    3: 'item_description',     // Description
                    4: 'division_name',        // Division (admin only)
                    5: 'mode',                 // Mode
                    6: 'param',                // Param
                    7: 'first_name',           // Name
                    8: 'memo',                 // Memo
                    9: 'is_published'          // Status
                };

                const nonAdminFieldMap = {
                    0: 'date_time',           // Date
                    1: 'code',                 // Code
                    2: 'sku',                  // SKU
                    3: 'item_description',     // Description
                    4: 'mode',                 // Mode
                    5: 'param',                // Param
                    6: 'first_name',           // Name
                    7: 'memo',                 // Memo
                    8: 'is_published'          // Status
                };
                
                let fieldMap = isAdminView ? adminFieldMap : nonAdminFieldMap;
                
                // Map display names ke actual db fields untuk editable
                const dbFieldMap = {
                    'code': 'code',
                    'mode': 'mode',
                    'param': 'param',
                    'memo': 'memo'
                };
                
                let displayField = fieldMap[index] || 'unknown';
                return dbFieldMap[displayField] || displayField;
            }

            // Edit btnn - Open Modal
            $('#editModeEditBtn').on('click', function() {
                if (!selectedRowData) {
                    alert('Please select a row first');
                    return;
                }
                $('#editCode').val(selectedRowData.code || '');
                $('#editSKU').val(selectedRowData.sku || '');
                $('#editMode').val(selectedRowData.mode || '');
                $('#editParam').val(selectedRowData.param || '');
                $('#editMemo').val(selectedRowData.memo || '');
                $('#editDescription').val(selectedRowData.item_description || '');
                $('#editFirstName').val(selectedRowData.first_name || '');

                window.currentEditData = {
                    code: selectedRowData.code || '',
                    sku: selectedRowData.sku || '',
                    mode: selectedRowData.mode || '',
                    param: selectedRowData.param || '',
                    memo: selectedRowData.memo || '',
                    description: selectedRowData.item_description || '',
                    first_name: selectedRowData.first_name || ''
                };

                // Show modal
                let editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
            });

            // Exit btn
            $('#editModeExitBtn').on('click', function() {
                exitEditMode();
            });

            // Edit Modal Save Button
            $('#editModalSaveBtn').on('click', function() {
                let broadcastCode = $('#editCode').val().trim();
                let newMode = $('#editMode').val().trim();
                let newParam = $('#editParam').val().trim();
                let newMemo = $('#editMemo').val().trim();

                if (!newMode) {
                    showEditFormMessage('Mode is required', true);
                    return;
                }

                let $saveBtn = $(this);
                $saveBtn.prop('disabled', true);
                showEditFormMessage('Saving...', false);

                // Prepare update req
                let updateRequests = [];

                // Check what changed
                if (newMode !== window.currentEditData.mode) {
                    updateRequests.push({
                        broadcast_code: broadcastCode,
                        field: 'mode',
                        old_value: window.currentEditData.mode,
                        new_value: newMode,
                        column_name: 'Mode'
                    });
                }

                if (newParam !== window.currentEditData.param) {
                    updateRequests.push({
                        broadcast_code: broadcastCode,
                        field: 'param',
                        old_value: window.currentEditData.param,
                        new_value: newParam,
                        column_name: 'Param'
                    });
                }

                if (newMemo !== window.currentEditData.memo) {
                    updateRequests.push({
                        broadcast_code: broadcastCode,
                        field: 'memo',
                        old_value: window.currentEditData.memo,
                        new_value: newMemo,
                        column_name: 'Memo'
                    });
                }

                if (updateRequests.length === 0) {
                    showEditFormMessage('No changes detected', true);
                    $saveBtn.prop('disabled', false);
                    return;
                }

                // Send all updates
                let completedRequests = 0;
                let failedRequests = 0;

                updateRequests.forEach(function(updateData, index) {
                    $.ajax({
                        url: 'api/query-update.php',
                        type: 'POST',
                        dataType: 'json',
                        data: updateData,
                        success: function(response) {
                            completedRequests++;
                            console.log('Updated ' + updateData.column_name + ':', response);

                            // Update in-memory data
                            if (updateData.field === 'mode') {
                                selectedRowData.mode = newMode;
                            } else if (updateData.field === 'param') {
                                selectedRowData.param = newParam;
                            } else if (updateData.field === 'memo') {
                                selectedRowData.memo = newMemo;
                            }

                            if (completedRequests + failedRequests === updateRequests.length) {
                                finalizeSave($saveBtn, failedRequests);
                            }
                        },
                        error: function(xhr, status, error) {
                            failedRequests++;
                            let errorMsg = error;
                            try {
                                let response = JSON.parse(xhr.responseText);
                                errorMsg = response.message || error;
                            } catch(e) {}
                            
                            console.error('Error updating ' + updateData.column_name + ':', errorMsg);

                            if (completedRequests + failedRequests === updateRequests.length) {
                                finalizeSave($saveBtn, failedRequests);
                            }
                        }
                    });
                });

                function finalizeSave($saveBtn, failedCount) {
                    $saveBtn.prop('disabled', false);

                    if (failedCount === 0) {
                        showEditFormMessage('✓ All changes saved successfully!', false);
                        
                        // Refresh 
                        setTimeout(function() {
                            table.draw();
                            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                            exitEditMode();
                        }, 1500);
                    } else {
                        showEditFormMessage(
                            failedCount + ' update(s) failed. Please try again.',
                            true
                        );
                    }
                }
            });

            // show messages modal
            function showEditFormMessage(message, isError) {
                let $msgDiv = $('#editFormMessage');
                let $msgText = $('#editFormMessageText');

                $msgText.text(message);
                $msgDiv.removeClass('error').addClass('error', isError);
                $msgDiv.toggle(true);

                if (!isError) {
                    setTimeout(function() {
                        if (message.includes('All changes saved')) {
                            $msgDiv.show();
                        }
                    }, 0);
                }
            }

            // Apply filter
            $('#btnApplyFilter').on('click', function() {
                table.draw();
            });

            // Reset filter
            $('#btnResetFilter').on('click', function() {
                $('#filterDateFrom').val('');
                $('#filterDateTo').val('');
                $('#filterDivision').val('');
                $('#filterMode').val('');
                table.draw();
            });

            // RT search
            $('#broadcastTable_filter input').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Hint 
            $(document).on('keydown', function(e) {
                if (e.key === 'Insert' && !editModeActive && !$('#editModeHint').hasClass('active')) {
                }
            });
        });
    </script>
</body>
</html>
