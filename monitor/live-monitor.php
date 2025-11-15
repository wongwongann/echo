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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Broadcast Monitor - Echo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #0f172a;
            --card-bg: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --accent-color: #00ff9d;
            --danger-color: #ff4d4d;
            --warning-color: #ffd700;
            --mode-production: #3b82f6;
            --mode-quality: #10b981;
            --mode-packaging: #f59e0b;
        }

        body {
            background-color: var(--primary-bg);
            color: var(--text-primary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .header {
            background-color: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 0.75rem 0;
        }

        .broadcast-card {
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 0.75rem;
            transform-origin: center;
            padding: 0.75rem;
            transition: all 2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .broadcast-card.new-entry {
            animation: cardAppear 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid var(--accent-color);
        }

        .broadcast-card.fading {
            animation: fadeToNormal 10s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes fadeToNormal {
            0% {
                border-color: var(--accent-color);
                border-width: 2px;
            }
            100% {
                border-color: rgba(255, 255, 255, 0.1);
                border-width: 1px;
            }
        }

        @keyframes newEntryStage1 {
            0% {
                background: rgba(0, 255, 157, 0.3);
                border-color: rgba(0, 255, 157, 0.4);
            }
            100% {
                background: var(--card-bg);
                border-color: rgba(255, 255, 255, 0.1);
            }
        }

        @keyframes cardAppear {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .broadcast-time {
            color: var(--accent-color);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .broadcast-date {
            color: var(--text-secondary);
            font-size: 0.75rem;
        }

        .broadcast-division {
            color: var(--text-secondary);
            font-size: 0.75rem;
            margin-bottom: 0.25rem;
        }

        .broadcast-code {
            background: rgba(0, 255, 157, 0.1);
            color: var(--accent-color);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-family: 'Consolas', monospace;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .broadcast-sku {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .broadcast-param {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .broadcast-memo {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-style: italic;
        }

        .mode-badge {

            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-bottom: 0.5rem;
            /* min-width: 100px; */
            text-align: center;
        }

        .mode-badge.production {
            background: rgba(59, 130, 246, 0.2);
            color: var(--mode-production);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .mode-badge.quality {
            background: rgba(16, 185, 129, 0.2);
            color: var(--mode-quality);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .mode-badge.packaging {
            background: rgba(245, 158, 11, 0.2);
            color: var(--mode-packaging);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .connection-status {
            font-size: 0.85rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }

        .connection-status.connected {
            background: rgba(0, 255, 157, 0.1);
            color: var(--accent-color);
        }

        .connection-status.disconnected {
            background: rgba(255, 77, 77, 0.1);
            color: var(--danger-color);
        }

        .broadcast-user {
            color: var(--text-secondary);
            font-size: 0.75rem;
            display: flex;
            align-items: center;
        }

        .nav-btn {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        .nav-btn:hover {
            background: rgba(0, 255, 157, 0.1);
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        .nav-btn.danger:hover {
            background: rgba(255, 77, 77, 0.1);
            border-color: var(--danger-color);
            color: var(--danger-color);
        }

        .main-container {
            padding: 1rem;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .status-indicator.connected {
            background-color: var(--accent-color);
        }

        .status-indicator.disconnected {
            background-color: var(--danger-color);
        }

        .mode-section {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .publish-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 0.8rem;
        }

        .publish-status.published {
            color: var(--mode-quality);
        }

        .publish-status.unpublished {
            color: var(--warning-color);
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-broadcast-tower fs-4 me-2" style="color: var(--accent-color)"></i>
                        <h1 class="h4 mb-0">Live Broadcast Monitor</h1>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex align-items-center gap-2">
                        <div id="connectionStatus" class="connection-status disconnected">
                            <i class="fas fa-circle-notch fa-spin me-2"></i>
                            Connecting...
                        </div>
                        <a href="../cast.php" class="btn nav-btn">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </a>
                        <a href="logout.php" class="btn nav-btn danger">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-container">
        <div id="broadcastList">
            <!-- Broadcast items will be inserted here -->
        </div>
    </main>

    <script>
        let ws;
        let reconnectAttempts = 0;
        const maxReconnectAttempts = 5;
        const reconnectDelay = 3000;

        function connectWebSocket() {
            const wsHost = window.location.hostname;
            ws = new WebSocket(`ws://${wsHost}:3000`);

            ws.onopen = () => {
                console.log('Connected to WebSocket server');
                updateConnectionStatus(true);
                reconnectAttempts = 0;
            };

            ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'broadcasts') {
                    updateBroadcasts(data.data);
                }
            };

            ws.onclose = () => {
                console.log('Disconnected from WebSocket server');
                updateConnectionStatus(false);

                if (reconnectAttempts < maxReconnectAttempts) {
                    reconnectAttempts++;
                    setTimeout(connectWebSocket, reconnectDelay);
                }
            };

            ws.onerror = (error) => {
                console.error('WebSocket error:', error);
                updateConnectionStatus(false);
            };
        }

        function updateConnectionStatus(connected) {
            const statusElement = document.getElementById('connectionStatus');
            if (connected) {
                statusElement.className = 'connection-status connected';
                statusElement.innerHTML = '<span class="status-indicator connected"></span>Connected';
            } else {
                statusElement.className = 'connection-status disconnected';
                statusElement.innerHTML = '<span class="status-indicator disconnected"></span>Reconnecting...';
            }
        }

        function formatDateTime(dateStr) {
            const date = new Date(dateStr);
            return {
                time: date.toLocaleTimeString('en-US', {
                    hour12: false,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                }),
                date: date.toLocaleDateString('en-US', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                })
            };
        }

        function createBroadcastCard(broadcast) {
            const {
                time,
                date
            } = formatDateTime(broadcast.last_update);
            return `
                <div class="broadcast-card" data-broadcast-id="${broadcast.id}">
                    <div class="row align-items-center">
                        <div class="col-md-1">
                            <div class="broadcast-time">${time}</div>
                            <div class="broadcast-date">${date}</div>
                        </div>
                     
                        <div class="col-md-2">
                            <div class="broadcast-division">${broadcast.division_name || 'N/A'}</div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="broadcast-code">${broadcast.code}</div>
                                <div class="publish-status ${broadcast.is_published ? 'published' : 'unpublished'}" 
                                     title="${broadcast.is_published ? 'Published' : 'Not Published'}">
                                    <i class="fas ${broadcast.is_published ? 'fa-check-circle' : 'fa-clock'}"></i>
                                </div>
                            </div>
                        </div>
                          <div class="col-md-1">
                               <div class="mode-badge ${broadcast.mode.toLowerCase()}">${broadcast.mode}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="broadcast-sku">${broadcast.item_description ? broadcast.item_description + ' - ' : ''}${broadcast.sku || 'N/A'}</div>
                            <div class="broadcast-param">${broadcast.param || 'N/A'}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="broadcast-memo">${broadcast.memo || 'No memo'}</div>
                        </div>
                           <div class="col-md-1">
                            <div class="mode-section">
                              
                                <div class="broadcast-user">
                                    <i class="fas fa-user-circle me-1"></i>${broadcast.username}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function updateBroadcasts(broadcasts) {
            const broadcastList = document.getElementById('broadcastList');
            const currentItems = new Set(Array.from(broadcastList.children).map(el => el.dataset.broadcastId));
            const now = new Date();

            broadcasts.forEach((broadcast, index) => {
                const existingItem = document.querySelector(`[data-broadcast-id="${broadcast.id}"]`);
                const newCard = createBroadcastCard(broadcast);

                if (!existingItem) {
                    const temp = document.createElement('div');
                    temp.innerHTML = newCard;
                    const newElement = temp.firstElementChild;
                    
                    newElement.classList.add('new-entry');
                    
                    setTimeout(() => {
                        newElement.style.animation = 'fadeToNormal 60s cubic-bezier(0.4, 0, 0.2, 1) forwards';
                    }, 500); 

                    if (index === 0) {
                        broadcastList.insertAdjacentElement('afterbegin', newElement);
                    } else {
                        broadcastList.appendChild(newElement);
                    }
                } else {
                    // Update publish status if change
                    const publishStatus = existingItem.querySelector('.publish-status');
                    const publishIcon = publishStatus.querySelector('i');
                    
                    if (broadcast.is_published && !publishStatus.classList.contains('published')) {
                        publishStatus.classList.remove('unpublished');
                        publishStatus.classList.add('published');
                        publishIcon.classList.remove('fa-clock');
                        publishIcon.classList.add('fa-check-circle');
                        publishStatus.title = 'Published';
                    } else if (!broadcast.is_published && !publishStatus.classList.contains('unpublished')) {
                        publishStatus.classList.remove('published');
                        publishStatus.classList.add('unpublished');
                        publishIcon.classList.remove('fa-check-circle');
                        publishIcon.classList.add('fa-clock');
                        publishStatus.title = 'Not Published';
                    }
                }
                currentItems.delete(broadcast.id.toString());
            });

            currentItems.forEach(id => {
                const oldItem = document.querySelector(`[data-broadcast-id="${id}"]`);
                if (oldItem) {
                    oldItem.remove();
                }
            });
        }

        // Init connection
        connectWebSocket();
    </script>
</body>

</html>