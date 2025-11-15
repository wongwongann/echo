<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Echo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --accent-color: #8b5cf6;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
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
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* Modern Header */
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--primary-color) !important;
            font-size: 24px;
            font-weight: 700;
            text-decoration: none;
        }

        .logo-icon {
            width: 42px;
            height: 42px;
            background: var(--gradient-1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(99, 102, 241, 0.25);
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            background: var(--gradient-2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(240, 147, 251, 0.25);
        }

        .user-name {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-primary);
        }

        .user-role {
            font-size: 12px;
            color: var(--text-secondary);
            background: var(--background);
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 2px;
        }

        .logout-btn {
            background: var(--background);
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 10px;
        }

        .logout-btn:hover {
            background: var(--danger-color);
            color: white;
            border-color: var(--danger-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25);
        }

        /* Modern Card Styles */
        .card-custom {
            border: none;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--background) 0%, white 100%);
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }

        .card-header-custom::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 1.5rem;
            right: 1.5rem;
            height: 2px;
            background: var(--gradient-1);
            border-radius: 2px;
        }

        .card-header-custom i {
            color: var(--primary-color);
            font-size: 18px;
        }

        /* Form Styles */
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: var(--background);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            background: white;
        }

        /* Preview Styles */
        .preview-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 1rem;
            background: var(--background);
            padding: 4px;
            border-radius: 10px;
        }

        .preview-tab {
            background: transparent;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 8px;
            flex: 1;
        }

        .preview-tab.active {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .preview-content {
            background: var(--background);
            padding: 1.25rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .preview-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .preview-item:last-child {
            margin-bottom: 0;
        }

        .preview-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-color);
            min-width: 70px;
        }

        .preview-value {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
        }

        /* Modern History Styles */
        .history-list {
            padding: 1rem;
            max-height: 600px;
            overflow-y: auto;
        }

        .history-list::-webkit-scrollbar {
            width: 6px;
        }

        .history-list::-webkit-scrollbar-track {
            background: var(--background);
            border-radius: 10px;
        }

        .history-list::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 10px;
        }

        .history-list::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        .history-item {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 12px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .history-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--gradient-1);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .history-item:hover {
            transform: translateX(4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
        }

        .history-item:hover::before {
            opacity: 1;
        }

        .history-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .history-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .history-mode-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
        }

        .history-mode-icon.production {
            background: var(--gradient-1);
        }

        .history-mode-icon.quality {
            background: var(--gradient-2);
        }

        .history-mode-icon.packaging {
            background: var(--gradient-3);
        }

        .history-mode-icon.shipping {
            background: var(--gradient-4);
        }

        .history-mode-info {
            flex: 1;
        }

        .history-mode {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .history-date {
            font-size: 12px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .history-item-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }

        .history-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .history-status.draft {
            background: rgba(100, 116, 139, 0.1);
            color: var(--text-secondary);
        }

        .history-status.published {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .history-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .history-detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .history-detail-icon {
            width: 28px;
            height: 28px;
            background: var(--background);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: var(--text-secondary);
        }

        .history-detail-content {
            flex: 1;
            min-width: 0;
        }

        .history-detail-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .history-detail-value {
            font-size: 13px;
            color: var(--text-primary);
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .history-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }

        .history-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .history-user-avatar {
            width: 24px;
            height: 24px;
            background: var(--gradient-1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
            font-weight: 600;
        }

        /* Button Styles */
        .btn-primary-custom {
            background: var(--gradient-1);
            border: none;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.5s ease;
        }

        .btn-primary-custom:hover::before {
            left: 100%;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.25);
        }

        .btn-publish {
            background: var(--success-color);
            border: none;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-publish:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.25);
        }

        .btn-publish:disabled {
            background: var(--text-secondary);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-refresh {
            background: white;
            border: 1px solid var(--border-color);
            padding: 8px;
            border-radius: 10px;
            color: var(--text-secondary);
            transition: all 0.3s ease;
        }

        .btn-refresh:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: rotate(180deg);
        }

        /* Autocomplete styles */
        .autocomplete-wrapper {
            position: relative;
        }

        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 300px;
            overflow-y: auto;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
            margin-top: 4px;
        }

        .autocomplete-results.show {
            display: block;
        }

        .autocomplete-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: background 0.2s ease;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item:hover {
            background: var(--background);
        }

        .autocomplete-item-title {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 14px;
        }

        .autocomplete-item-subtitle {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* Loading spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .history-section {
                margin-top: 1.5rem;
            }
        }

        @media (max-width: 767.98px) {
            .user-info {
                display: none;
            }
            
            .history-details {
                grid-template-columns: 1fr;
            }
            
            .history-item-header {
                flex-direction: column;
                gap: 12px;
            }
            
            .history-item-right {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

    <!-- Main Content -->
    <main class="container-fluid my-4">
        <div class="row g-4">
            <!-- Left Column: Input and Preview -->
            <div class="col-lg-6">
                <!-- Input Section -->
                <div class="card card-custom mb-4">
                    <div class="card-header card-header-custom">
                        <i class="fas fa-edit"></i>
                        Input
                    </div>
                    <div class="card-body">
                        <form id="inputForm">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="modeInput" class="form-label">Mode</label>
                                    <select id="modeInput" class="form-select">
                                        <option value="">Select Mode</option>
                                        <option value="Production">Production</option>
                                        <option value="Quality Control">Quality Control</option>
                                        <option value="Packaging">Packaging</option>
                                        <option value="Shipping">Shipping</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="object1Input" class="form-label">SKU</label>
                                    <div class="autocomplete-wrapper">
                                        <input type="text" id="object1Input" class="form-control" placeholder="Enter SKU" value="">
                                        <div id="autocompleteResults" class="autocomplete-results"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="object2Input" class="form-label">Parameter</label>
                                    <input type="text" id="object2Input" class="form-control" placeholder="Enter Param" value="---">
                                </div>
                                <div class="col-12">
                                    <label for="memoInput" class="form-label">Memo</label>
                                    <input type="text" id="memoInput" class="form-control" placeholder="Enter memo" value="txt">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="card card-custom">
                    <div class="card-header card-header-custom">
                        <i class="fas fa-eye"></i>
                        Current Preview
                    </div>
                    <div class="card-body">
                        <div class="preview-tabs">
                            <button type="button" class="preview-tab" data-tab="detailed">Detailed</button>
                            <button type="button" class="preview-tab active" data-tab="text">Text</button>
                        </div>
                        
                        <div class="preview-pane" id="detailedPreview" style="display: none;">
                            <div class="preview-content">
                                <div class="preview-item">
                                    <span class="preview-label">Mode</span>
                                    <span>:</span>
                                    <span id="modePreview" class="preview-value">---</span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">SKU</span>
                                    <span>:</span>
                                    <span id="object1Preview" class="preview-value">---</span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Parameter</span>
                                    <span>:</span>
                                    <span id="object2Preview" class="preview-value">---</span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Memo</span>
                                    <span>:</span>
                                    <span id="memoPreview" class="preview-value">---</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="preview-pane" id="textPreview">
                            <div class="preview-content">
                                <p class="mb-2" id="textPreviewMain">---</p>
                                <small class="text-muted">Memo:</small>
                                <p class="mb-0 text-muted" id="textPreviewMemo">---</p>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" id="submitForm" class="btn btn-primary-custom w-100">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                <span class="button-text">Submit</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Modern History -->
            <div class="col-lg-6">
                <div class="card card-custom h-100">
                    <div class="card-header card-header-custom">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <i class="fas fa-history"></i>
                                History
                            </div>
                            <button type="button" class="btn btn-refresh" id="refreshHistory">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="history-list">
                        <!-- Sample History Items -->
                        <div class="history-item" data-id="1">
                            <div class="history-item-header">
                                <div class="history-item-left">
                                    <div class="history-mode-icon production">
                                        <i class="fas fa-industry"></i>
                                    </div>
                                    <div class="history-mode-info">
                                        <div class="history-mode">Production</div>
                                        <div class="history-date">
                                            <i class="far fa-clock"></i>
                                            Dec 15, 14:30
                                        </div>
                                    </div>
                                </div>
                                <div class="history-item-right">
                                    <span class="history-status draft">Draft</span>
                                </div>
                            </div>
                            <div class="history-details">
                                <div class="history-detail-item">
                                    <div class="history-detail-icon">
                                        <i class="fas fa-barcode"></i>
                                    </div>
                                    <div class="history-detail-content">
                                        <div class="history-detail-label">SKU</div>
                                        <div class="history-detail-value">PRD-2023-001</div>
                                    </div>
                                </div>
                                <div class="history-detail-item">
                                    <div class="history-detail-icon">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div class="history-detail-content">
                                        <div class="history-detail-label">Parameter</div>
                                        <div class="history-detail-value">Standard Quality</div>
                                    </div>
                                </div>
                            </div>
                            <div class="history-footer">
                                <div class="history-user">
                                    <div class="history-user-avatar">JD</div>
                                    <span>john.doe</span>
                                </div>
                                <button type="button" class="btn btn-publish">
                                    <i class="fas fa-paper-plane"></i>
                                    Publish
                                </button>
                            </div>
                        </div>

                        <div class="history-item" data-id="2">
                            <div class="history-item-header">
                                <div class="history-item-left">
                                    <div class="history-mode-icon quality">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="history-mode-info">
                                        <div class="history-mode">Quality Control</div>
                                        <div class="history-date">
                                            <i class="far fa-clock"></i>
                                            Dec 15, 13:45
                                        </div>
                                    </div>
                                </div>
                                <div class="history-item-right">
                                    <span class="history-status published">Published</span>
                                </div>
                            </div>
                            <div class="history-details">
                                <div class="history-detail-item">
                                    <div class="history-detail-icon">
                                        <i class="fas fa-barcode"></i>
                                    </div>
                                    <div class="history-detail-content">
                                        <div class="history-detail-label">SKU</div>
                                        <div class="history-detail-value">QC-2023-045</div>
                                    </div>
                                </div>
                                <div class="history-detail-item">
                                    <div class="history-detail-icon">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div class="history-detail-content">
                                        <div class="history-detail-label">Parameter</div>
                                        <div class="history-detail-value">High Priority</div>
                                    </div>
                                </div>
                            </div>
                            <div class="history-footer">
                                <div class="history-user">
                                    <div class="history-user-avatar">JS</div>
                                    <span>jane.smith</span>
                                </div>
                                <button type="button" class="btn btn-publish" disabled>
                                    <i class="fas fa-check"></i>
                                    Published
                                </button>
                            </div>
                        </div>

                        <div class="history-item" data-id="3">
                            <div class="history-item-header">
                                <div class="history-item-left">
                                    <div class="history-mode-icon packaging">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div class="history-mode-info">
                                        <div class="history-mode">Packaging</div>
                                        <div class="history-date">
                                            <i class="far fa-clock"></i>
                                            Dec 15, 12:20
                                        </div>
                                    </div>
                                </div>
                                <div class="history-item-right">
                                    <span class="history-status draft">Draft</span>
                                </div>
                            </div>
                            <div class="history-details">
                                <div class="history-detail-item">
                                    <div class="history-detail-icon">
                                        <i class="fas fa-barcode"></i>
                                    </div>
                                    <div class="history-detail-content">
                                        <div class="history-detail-label">SKU</div>
                                        <div class="history-detail-value">PKG-2023-012</div>
                                    </div>
                                </div>
                                <div class="history-detail-item">
                                    <div class="history-detail-icon">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div class="history-detail-content">
                                        <div class="history-detail-label">Parameter</div>
                                        <div class="history-detail-value">Express Shipping</div>
                                    </div>
                                </div>
                            </div>
                            <div class="history-footer">
                                <div class="history-user">
                                    <div class="history-user-avatar">MW</div>
                                    <span>mike.wilson</span>
                                </div>
                                <button type="button" class="btn btn-publish">
                                    <i class="fas fa-paper-plane"></i>
                                    Publish
                                </button>
                            </div>
                        </div>

                        <div class="history-item" data-id="4">
                            <div class="history-item-header">
                                <div class="history-item-left">
                                    <div class="history-mode-icon shipping">
                                        <i class="fas fa-truck"></i>
                                    </div>
                                    <div class="history-mode-info">
                                        <div class="history-mode">Shipping</div>
                                        <div class="history-date">
                                            <i class="far fa-clock"></i>
                                            Dec 15, 11:15
                                        </div>
                                    </div>
                                </div>
                                <div class="history-item-right">
                                    <span class="history-status published">Published</span>
                                </div>
                            </div>
                            <div class="history-details">
                                <div class="history-detail-item">
                                    <div class="history-detail-icon">
                                        <i class="fas fa-barcode"></i>
                                    </div>
                                    <div class="history-detail-content">
                                        <div class="history-detail-label">SKU</div>
                                        <div class="history-detail-value">SHP-2023-089</div>
                                    </div>
                                </div>
                                <div class="history-detail-item">
                                    <div class="history-detail-icon">
                                        <i class="fas fa-cog"></i>
                                    </div>
                                    <div class="history-detail-content">
                                        <div class="history-detail-label">Parameter</div>
                                        <div class="history-detail-value">International</div>
                                    </div>
                                </div>
                            </div>
                            <div class="history-footer">
                                <div class="history-user">
                                    <div class="history-user-avatar">SJ</div>
                                    <span>sarah.jones</span>
                                </div>
                                <button type="button" class="btn btn-publish" disabled>
                                    <i class="fas fa-check"></i>
                                    Published
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Preview tab switching
            const previewTabs = document.querySelectorAll('.preview-tab');
            const previewPanes = document.querySelectorAll('.preview-pane');
            
            previewTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and panes
                    previewTabs.forEach(t => t.classList.remove('active'));
                    previewPanes.forEach(p => p.style.display = 'none');
                    
                    // Add active class to clicked tab and show corresponding pane
                    this.classList.add('active');
                    document.getElementById(targetTab + 'Preview').style.display = 'block';
                });
            });
            
            // Update preview on input change
            const modeInput = document.getElementById('modeInput');
            const object1Input = document.getElementById('object1Input');
            const object2Input = document.getElementById('object2Input');
            const memoInput = document.getElementById('memoInput');
            
            function updatePreview() {
                const mode = modeInput.value || '---';
                const object1 = object1Input.value || '---';
                const object2 = object2Input.value || '---';
                const memo = memoInput.value || '---';
                
                // Update detailed preview
                document.getElementById('modePreview').textContent = mode;
                document.getElementById('object1Preview').textContent = object1;
                document.getElementById('object2Preview').textContent = object2;
                document.getElementById('memoPreview').textContent = memo;
                
                // Update text preview
                const textPreviewMain = `${mode} ${object1} ${object2}`;
                document.getElementById('textPreviewMain').textContent = textPreviewMain;
                document.getElementById('textPreviewMemo').textContent = memo;
            }
            
            modeInput.addEventListener('change', updatePreview);
            object1Input.addEventListener('input', updatePreview);
            object2Input.addEventListener('input', updatePreview);
            memoInput.addEventListener('input', updatePreview);
            
            // Initialize preview
            updatePreview();
            
            // Submit form
            const submitBtn = document.getElementById('submitForm');
            submitBtn.addEventListener('click', function() {
                const spinner = this.querySelector('.spinner-border');
                const buttonText = this.querySelector('.button-text');
                
                // Show loading state
                spinner.classList.remove('d-none');
                buttonText.textContent = 'Submitting...';
                this.disabled = true;
                
                // Get form data
                const formData = {
                    mode: modeInput.value,
                    object1: object1Input.value,
                    object2: object2Input.value,
                    memo: memoInput.value
                };
                
                // Simulate API call
                setTimeout(() => {
                    // Reset button state
                    spinner.classList.add('d-none');
                    buttonText.textContent = 'Submit';
                    this.disabled = false;
                    
                    // Show success toast
                    showToast('Success', 'Data submitted successfully', 'success');
                    
                    // Add new item to history (simulation)
                    addHistoryItem(formData);
                    
                    // Reset form
                    modeInput.value = '';
                    object1Input.value = '';
                    object2Input.value = '---';
                    memoInput.value = 'txt';
                    updatePreview();
                }, 1500);
            });
            
            // Publish button functionality
            document.querySelectorAll('.btn-publish').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const button = this;
                    
                    // Show loading state
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Publishing...';
                    button.disabled = true;
                    
                    // Simulate API call
                    setTimeout(() => {
                        // Update button state
                        button.innerHTML = '<i class="fas fa-check"></i> Published';
                        
                        // Update status badge
                        const historyItem = button.closest('.history-item');
                        const statusBadge = historyItem.querySelector('.history-status');
                        statusBadge.textContent = 'Published';
                        statusBadge.classList.remove('draft');
                        statusBadge.classList.add('published');
                        
                        // Show success toast
                        showToast('Success', 'Item published successfully', 'success');
                    }, 1000);
                });
            });
            
            // Refresh history
            document.getElementById('refreshHistory').addEventListener('click', refreshHistory);
            
            function refreshHistory() {
                const refreshBtn = document.getElementById('refreshHistory');
                refreshBtn.style.transform = 'rotate(360deg)';
                
                // Simulate API call
                setTimeout(() => {
                    refreshBtn.style.transform = '';
                    showToast('Success', 'History refreshed', 'success');
                }, 1000);
            }
            
            // Add new history item (simulation)
            function addHistoryItem(data) {
                const historyList = document.querySelector('.history-list');
                const newItem = document.createElement('div');
                newItem.className = 'history-item';
                newItem.dataset.id = Date.now();
                
                const now = new Date();
                const dateStr = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                
                // Determine mode icon class
                let modeIconClass = 'production';
                let modeIcon = 'fa-industry';
                if (data.mode === 'Quality Control') {
                    modeIconClass = 'quality';
                    modeIcon = 'fa-check-circle';
                } else if (data.mode === 'Packaging') {
                    modeIconClass = 'packaging';
                    modeIcon = 'fa-box';
                } else if (data.mode === 'Shipping') {
                    modeIconClass = 'shipping';
                    modeIcon = 'fa-truck';
                }
                
                newItem.innerHTML = `
                    <div class="history-item-header">
                        <div class="history-item-left">
                            <div class="history-mode-icon ${modeIconClass}">
                                <i class="fas ${modeIcon}"></i>
                            </div>
                            <div class="history-mode-info">
                                <div class="history-mode">${data.mode || '---'}</div>
                                <div class="history-date">
                                    <i class="far fa-clock"></i>
                                    ${dateStr}, ${timeStr}
                                </div>
                            </div>
                        </div>
                        <div class="history-item-right">
                            <span class="history-status draft">Draft</span>
                        </div>
                    </div>
                    <div class="history-details">
                        <div class="history-detail-item">
                            <div class="history-detail-icon">
                                <i class="fas fa-barcode"></i>
                            </div>
                            <div class="history-detail-content">
                                <div class="history-detail-label">SKU</div>
                                <div class="history-detail-value">${data.object1 || '---'}</div>
                            </div>
                        </div>
                        <div class="history-detail-item">
                            <div class="history-detail-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="history-detail-content">
                                <div class="history-detail-label">Parameter</div>
                                <div class="history-detail-value">${data.object2 || '---'}</div>
                            </div>
                        </div>
                    </div>
                    <div class="history-footer">
                        <div class="history-user">
                            <div class="history-user-avatar">JD</div>
                            <span>john.doe</span>
                        </div>
                        <button type="button" class="btn btn-publish">
                            <i class="fas fa-paper-plane"></i>
                            Publish
                        </button>
                    </div>
                `;
                
                // Add to top of history list
                historyList.insertBefore(newItem, historyList.firstChild);
                
                // Add event listener to new publish button
                const newPublishBtn = newItem.querySelector('.btn-publish');
                newPublishBtn.addEventListener('click', function() {
                    const button = this;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Publishing...';
                    button.disabled = true;
                    
                    setTimeout(() => {
                        button.innerHTML = '<i class="fas fa-check"></i> Published';
                        const statusBadge = newItem.querySelector('.history-status');
                        statusBadge.textContent = 'Published';
                        statusBadge.classList.remove('draft');
                        statusBadge.classList.add('published');
                        showToast('Success', 'Item published successfully', 'success');
                    }, 1000);
                });
            }
            
            // Toast notification function using Bootstrap Toast
            function showToast(title, message, type) {
                const toastContainer = document.querySelector('.toast-container');
                const toastId = 'toast-' + Date.now();
                const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
                
                const toastHtml = `
                    <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <strong>${title}</strong><br>
                                ${message}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                
                toastContainer.insertAdjacentHTML('beforeend', toastHtml);
                
                const toastElement = document.getElementById(toastId);
                const toast = new bootstrap.Toast(toastElement, {
                    autohide: true,
                    delay: 3000
                });
                
                toast.show();
                
                // Remove toast element after hidden
                toastElement.addEventListener('hidden.bs.toast', function() {
                    this.remove();
                });
            }
        });
    </script>
</body>

</html>