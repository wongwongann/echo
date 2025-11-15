<?php
require __DIR__ . '/config/conn.php';

$sampleData = $conn->query('
    SELECT 
        b.*,
        d.divisi as division_name,
        d.id as division_id,
        m.mode as mode_name,
        i.description as item_description,
        u.first_name,
        COUNT(*) OVER (PARTITION BY b.sku) as entry_count
    FROM broadcast b
    LEFT JOIN divisi d ON d.code = SUBSTRING_INDEX(SUBSTRING_INDEX(b.code, ".", 3), ".", -1)
    LEFT JOIN mode m ON m.mode = b.mode
    LEFT JOIN item i ON i.item = b.sku
    LEFT JOIN users u ON u.username = b.username
    ORDER BY b.sku, b.date_created DESC
    LIMIT 100
')->fetchAll(PDO::FETCH_ASSOC);

// Group data by SKU
$groupedData = [];
foreach ($sampleData as $item) {
    $sku = $item['sku'];
    if (!isset($groupedData[$sku])) {
        $groupedData[$sku] = [
            'sku' => $sku,
            'description' => $item['item_description'],
            'count' => $item['entry_count'],
            'entries' => []
        ];
    }
    $groupedData[$sku]['entries'][] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Group by SKU - Echo</title>
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
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            padding: 2rem;
        }

        .history-container {
            background: var(--dark-bg);
            border: 1px solid var(--dark-border);
            border-radius: 12px;
            padding: 1rem;
            max-width: 800px;
            margin: 0 auto;
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

        /* SKU Group Styling */
        .sku-group {
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            border-radius: 12px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .sku-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: var(--dark-hover);
            border-bottom: 1px solid var(--dark-border);
            cursor: pointer;
        }

        .sku-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sku-title h4 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .sku-title .badge {
            background: var(--dark-bg);
            color: var(--text-muted);
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
        }

        .sku-title i {
            color: var(--primary);
        }

        .btn-expand {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            width: 24px;
            height: 24px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-expand:hover {
            background: var(--dark-bg);
            color: var(--text-primary);
        }

        .sku-entries {
            padding: 1rem;
            background: var(--dark-card);
        }

        /* History Item Styling */
        .history-item {
            background: var(--dark-bg);
            border: 1px solid var(--dark-border);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border-left: 3px solid transparent;
        }

        .history-item:last-child {
            margin-bottom: 0;
        }

        .history-item:hover {
            transform: translateX(2px);
            border-left-color: var(--primary);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .history-info {
            flex: 1;
            min-width: 0;
        }

        .history-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .history-status {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
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
            min-width: 70px;
        }

        .history-detail-value {
            color: var(--text-primary);
        }

        /* Collapsed state */
        .sku-group.collapsed .sku-entries {
            display: none;
        }

        .sku-group.collapsed .btn-expand i {
            transform: rotate(-90deg);
        }

        .btn-expand i {
            transition: transform 0.2s ease;
        }
    </style>
</head>
<body>
    <div class="history-container">
        <div class="history-header">
            <h3>
                <i class="fas fa-clock"></i>
                Recent Entries (Grouped by SKU)
            </h3>
        </div>

        <div class="history-list">
            <?php foreach ($groupedData as $group): ?>
            <div class="sku-group collapsed">
                <div class="sku-header">
                    <div class="sku-title">
                        <i class="fas fa-box"></i>
                        <h4><?php echo htmlspecialchars($group['description'] ? $group['description'] . ' - ' : ''); ?><?php echo htmlspecialchars($group['sku']); ?></h4>
                        <span class="badge"><?php echo $group['count']; ?> entries</span>
                    </div>
                    <button class="btn-expand">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="sku-entries">
                    <?php foreach ($group['entries'] as $entry): ?>
                    <div class="history-item">
                        <div class="history-item-header">
                            <div class="history-icon <?php echo strtolower($entry['division_name']); ?>">
                                <i class="fas fa-broadcast-tower"></i>
                            </div>
                            <div class="history-info">
                                <div class="history-meta">
                                    <span>
                                        <i class="far fa-clock"></i>
                                        <?php 
                                        $date = new DateTime($entry['date_created']);
                                        echo $date->format('d M Y H:i');
                                        ?>
                                    </span>
                                    <span class="history-status"><?php echo htmlspecialchars($entry['mode_name'] ?? $entry['mode']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="history-details">
                            <div class="history-detail">
                                <span class="history-detail-label">Parameter:</span>
                                <span class="history-detail-value"><?php echo htmlspecialchars($entry['param']); ?></span>
                            </div>
                            <div class="history-detail">
                                <span class="history-detail-label">Memo:</span>
                                <span class="history-detail-value"><?php echo htmlspecialchars($entry['memo']); ?></span>
                            </div>
                            <div class="history-detail">
                                <span class="history-detail-label">User:</span>
                                <span class="history-detail-value"><?php echo htmlspecialchars($entry['first_name'] ?? $entry['username']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        document.querySelectorAll('.sku-header').forEach(header => {
            header.addEventListener('click', () => {
                const group = header.closest('.sku-group');
                group.classList.toggle('collapsed');
            });
        });

        document.querySelectorAll('.history-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (!e.target.closest('.btn-publish')) {
                    item.classList.toggle('expanded');
                }
            });
        });
    </script>
</body>
</html>