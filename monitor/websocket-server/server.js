require('dotenv').config();
const WebSocket = require('ws');
const mysql = require('mysql2/promise');

// Deb config
const dbConfig = {
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME
};

// Create WS server
const wss = new WebSocket.Server({ 
    port: process.env.WS_PORT || 3000,
    host: '0.0.0.0' 
});

// Keep track of all connected clients
const clients = new Set();

// Db connection pool
const pool = mysql.createPool(dbConfig);

// Main func
async function fetchBroadcasts() {
    try {
        const connection = await pool.getConnection();
        const [rows] = await connection.execute(`
            SELECT 
                b.id,
                b.mode,
                      b.code,
                b.sku,
                i.description as item_description,
                b.param,
                b.memo,
                b.username,
                b.last_update,
                'Echo System' as division_name,
                EXISTS (
                    SELECT 1 
                    FROM webhook_log wl 
                    WHERE wl.broadcast_code = b.code 
                    AND wl.status_code >= 200 
                    AND wl.status_code < 300
                    AND DATE(wl.created_at) = CURDATE()
                ) as is_published
            FROM broadcast b
            LEFT JOIN item i ON i.item = b.sku
            WHERE DATE(b.date_created) = CURDATE()
            ORDER BY b.last_update DESC
        `);
        connection.release();
        return rows;
    } catch (error) {
        console.error('Database error:', error);
        return [];
    }
}

// cast data to all clients
function broadcast(data) {
    const message = JSON.stringify(data);
    clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(message);
        }
    });
}

// Check each 5000 ms
setInterval(async () => {
    const broadcasts = await fetchBroadcasts();
    if (broadcasts.length > 0) {
        broadcast({ type: 'broadcasts', data: broadcasts });
    }
}, 5000);

// WS handler
wss.on('connection', (ws) => {
    console.log('New client connected');
    clients.add(ws);

    // Send initial data
    fetchBroadcasts().then(broadcasts => {
        ws.send(JSON.stringify({ type: 'broadcasts', data: broadcasts }));
    });

    // Handle client disconnection
    ws.on('close', () => {
        console.log('Client disconnected');
        clients.delete(ws);
    });
});

console.log(`WebSocket server is running on port ${process.env.WS_PORT || 3000}`);