<?php
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
    $stmt = $conn->prepare('SELECT nama FROM divisi WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $idDivisi]);
    $row = $stmt->fetch();
    $divisiName = $row['nama'] ?? '';
}

// full access admin(it)
$isAdminAllAccess = false;
if (strtolower(trim($username)) === 'admin' && strtolower(trim($divisiName)) === 'it') {
    $isAdminAllAccess = true;
}

// Get view preference (grid/list)
$viewMode = isset($_GET['view']) && $_GET['view'] === 'list' ? 'list' : 'grid';

// Get active division (from URL parameter or user's division)
$activeDivision = isset($_GET['division']) ? (int)$_GET['division'] : $idDivisi;

// Fetch all (admin)
$allDivisions = [];
if ($isAdminAllAccess) {
    $divQuery = $conn->query('SELECT id, nama as name FROM divisi ORDER BY nama');
    $allDivisions = $divQuery->fetchAll(PDO::FETCH_ASSOC);
}

// Get modes
$modes = [];
if ($activeDivision) {
    if ($isAdminAllAccess || $activeDivision === $idDivisi) {
        $modeQuery = $conn->prepare('
            SELECT m.id, m.mode as name, m.description, m.status,
                   d.nama as division_name, d.id as division_id
            FROM mode m
            JOIN divisi d ON m.id_divisi = d.id
            WHERE m.id_divisi = :division_id
            ORDER BY m.mode
        ');
        $modeQuery->execute([':division_id' => $activeDivision]);
        $modes = $modeQuery->fetchAll(PDO::FETCH_ASSOC);
    }
}

//count modes 
function getModesCount($conn, $divisionId) {
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
    <title>Dashboard - Echo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/dashboard.css">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="logo-text">Echo</div>

            </div>
            <div class="user-menu">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($firstName ?: $username); ?></div>
                        <div class="user-role"><?php echo $isAdminAllAccess ? 'Administrator' : 'User'; ?></div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="welcome-section">
            <div class="welcome-header">
                <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($firstName ?: $username); ?></h1>
                <?php if ($isAdminAllAccess): ?>
                <div class="admin-badge">
                    <i class="fas fa-shield-alt"></i>
                    Administrator
                </div>
                <?php endif; ?>
            </div>
            <div class="user-info-grid">
                <div class="info-item">
                    <div class="info-icon username">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="info-content">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?php echo htmlspecialchars($username); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon division">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="info-content">
                        <span class="info-label">Division</span>
                        <span class="info-value"><?php echo htmlspecialchars($divisiName ?: 'No Division Assigned'); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="modes-section">
            <div class="modes-header">
                <h2 class="modes-title">
                    <i class="fas fa-th-large"></i>
                    Choose Modes
                </h2>
                <div class="view-toggle">
                    <a href="?view=grid<?php echo isset($_GET['division']) ? '&division='.$_GET['division'] : ''; ?>" 
                       class="view-btn <?php echo $viewMode === 'grid' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i> Grid
                    </a>
                    <a href="?view=list<?php echo isset($_GET['division']) ? '&division='.$_GET['division'] : ''; ?>" 
                       class="view-btn <?php echo $viewMode === 'list' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> List
                    </a>
                </div>
            </div>

            <?php if ($isAdminAllAccess): ?>
            <div class="division-tabs">
                <?php foreach ($allDivisions as $division): ?>
                <a href="?division=<?php echo $division['id']; ?>&view=<?php echo $viewMode; ?>" 
                   class="division-tab <?php echo $activeDivision == $division['id'] ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <?php echo htmlspecialchars($division['name']); ?>
                    <span class="badge"><?php echo getModesCount($conn, $division['id']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="modes-content <?php echo $viewMode === 'list' ? 'list-view' : 'grid-view'; ?>">
                <?php if (empty($modes)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Modes Found</h3>
                    <p><?php echo $activeDivision ? 'There are no modes configured for this division.' : 'Please select a division to view its modes.'; ?></p>
                </div>
                <?php else: ?>
                    <?php foreach ($modes as $mode): ?>
                    <div class="mode-card">
                        <div class="mode-header">
                            <h3 class="mode-name"><?php echo htmlspecialchars($mode['name']); ?></h3>
                            <div class="mode-icon">
                                <i class="fas <?php echo $mode['status'] === 'active' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            </div>
                        </div>
                        <p class="mode-description">
                            <?php echo htmlspecialchars($mode['description'] ?: $mode['name'] . ' mode for ' . $mode['division_name']); ?>
                        </p>
                        <div class="mode-footer">
                            <div class="mode-status <?php echo $mode['status'] === 'active' ? '' : 'inactive'; ?>">
                                <i class="fas fa-circle"></i>
                                <?php echo ucfirst($mode['status'] ?: 'active'); ?>
                            </div>
                            <?php if ($isAdminAllAccess): ?>
                            <button class="mode-action" onclick="toggleMode(<?php echo $mode['id']; ?>)">
                                <i class="fas <?php echo $mode['status'] === 'active' ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                                <?php echo $mode['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
    function toggleMode(modeId) {
        if (!confirm('Are you sure you want to change this mode\'s status?')) {
            return;
        }
        
        fetch('api/toggle_mode.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ modeId: modeId })
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Server error: ' + response.status);
            }
            return data;
        })
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to update mode status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating mode status: ' + error.message);
        });
    }

    // Add smooth scrolling to division tabs
    document.addEventListener('DOMContentLoaded', function() {
        const tabsContainer = document.querySelector('.division-tabs');
        if (tabsContainer) {
            const activeTab = tabsContainer.querySelector('.division-tab.active');
            if (activeTab) {
                activeTab.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }
        }
    });
    </script>
</body>
</html>