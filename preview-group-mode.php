<?php
require __DIR__ . '/config/conn.php';

$sampleData = $conn->query('
    SELECT 
        b.*,
        d.divisi as division_name,
        d.id as division_id,
        m.mode as mode_name,
        m.description as mode_description,
        i.description as item_description,
        u.first_name,
        COUNT(*) OVER (PARTITION BY b.mode) as entry_count
    FROM broadcast b
    LEFT JOIN divisi d ON d.code = SUBSTRING_INDEX(SUBSTRING_INDEX(b.code, ".", 3), ".", -1)
    LEFT JOIN mode m ON m.mode = b.mode
    LEFT JOIN item i ON i.item = b.sku
    LEFT JOIN users u ON u.username = b.username
    ORDER BY b.mode, b.date_created DESC
    LIMIT 100
')->fetchAll(PDO::FETCH_ASSOC);

// Group data by Mode
$groupedData = [];
foreach ($sampleData as $item) {
    $mode = $item['mode'];
    if (!isset($groupedData[$mode])) {
        $groupedData[$mode] = [
            'mode' => $mode,
            'mode_name' => $item['mode_name'],
            'mode_description' => $item['mode_description'],
            'division_name' => $item['division_name'],
            'count' => $item['entry_count'],
            'entries' => []
        ];
    }
    $groupedData[$mode]['entries'][] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Group by Mode - Echo</title>
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

            --gradient-production: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-quality: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-packaging: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
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

        /* Mode Group Styling */
        .mode-group {
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            border-radius: 12px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .mode-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px;
            background: var(--dark-hover);
            border-bottom: 1px solid var(--dark-border);
            cursor: pointer;
        }

        .mode-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mode-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .mode-icon.production { background: var(--gradient-production); }
        .mode-icon.quality { background: var(--gradient-quality); }
        .mode-icon.packaging { background: var(--gradient-packaging); }

        .mode-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .mode-info h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .mode-info .division {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .mode-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mode-meta .badge {
            background: var(--dark-bg);
            color: var(--text-muted);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
        }

        .btn-expand {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            width: 32px;
            height: 32px;
            border-radius: 8px;
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

        .mode-entries {
            padding: 5px;
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
        }

        .history-item:last-child {
            margin-bottom: 0;
        }

        .history-item:hover {
            transform: translateX(2px);
            border-left: 3px solid var(--primary);
        }

        .history-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .history-title {
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .history-meta {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
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
        .mode-group.collapsed .mode-entries {
            display: none;
        }

        .mode-group.collapsed .btn-expand i {
            transform: rotate(-90deg);
        }

        .btn-expand i {
            transition: transform 0.2s ease;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="history-container">
        <div class="history-header">
            <h3>
                <i class="fas fa-clock"></i>
                Recent Entries (Grouped by Mode)
            </h3>
        </div>

        <div class="history-list">
            <?php if (empty($groupedData)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No recent entries found</p>
            </div>
            <?php endif; ?>

            <?php foreach ($groupedData as $group): ?>
            <div class="mode-group collapsed">
                <div class="mode-header">
                    <div class="mode-title">
                        <div class="mode-icon <?php echo strtolower($group['division_name']); ?>">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="mode-info">
                            <h4><?php echo htmlspecialchars($group['mode_name'] ?? $group['mode']); ?></h4>
                            <span class="division"><?php echo htmlspecialchars($group['division_name']); ?></span>
                        </div>
                    </div>
                    <div class="mode-meta">
                        <span class="badge">
                            <i class="fas fa-chart-bar"></i>
                            <?php echo $group['count']; ?> entries
                        </span>
                        <button class="btn-expand">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="mode-entries">
                    <?php foreach ($group['entries'] as $entry): ?>
                    <div class="history-item">
                        <div class="history-item-header">
                            <div>
                                <div class="history-title">
                                    <?php echo htmlspecialchars($entry['item_description'] ? $entry['item_description'] . ' - ' : ''); ?>
                                    <?php echo htmlspecialchars($entry['sku']); ?>
                                </div>
                                <div class="history-meta">
                                    <span><i class="far fa-clock"></i> 
                                        <?php 
                                        $date = new DateTime($entry['date_created']);
                                        echo $date->format('d M Y H:i');
                                        ?>
                                    </span>
                                    <span><i class="fas fa-user"></i> 
                                        <?php echo htmlspecialchars($entry['first_name'] ?? $entry['username']); ?>
                                    </span>
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
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        document.querySelectorAll('.mode-header').forEach(header => {
            header.addEventListener('click', () => {
                const group = header.closest('.mode-group');
                group.classList.toggle('collapsed');
            });
        });

        document.querySelectorAll('.history-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (!e.target.closest('.btn-expand')) {
                    item.classList.toggle('expanded');
                }
            });
        });
    </script>
</body>
</html>