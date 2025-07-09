<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Monitor Status</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            margin: 0 auto;
            padding-top: 100px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
            width: 100%;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
        }

        .refresh-info {
            display: flex;
            align-items: center;
            gap: 16px; /* Tambahkan spasi antar elemen */
            background: rgb(33, 34, 34);
            border-radius: 6px;
            padding: 8px 16px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px; /* Jarak antar elemen */
        }

        .refresh-text {
            background: rgb(33, 34, 34);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
        }

        .back-btn {
            background: rgb(33, 34, 34);
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .refresh-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }

        .refresh-btn:hover {
            background: #2980b9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 500;
        }

        .stat-card.total .stat-number { color:rgb(0, 0, 0); }
        .stat-card.up .stat-number { color:rgb(0, 0, 0); }
        .stat-card.down .stat-number { color: #e74c3c; }
        .stat-card.paused .stat-number { color: #95a5a6; }

        .monitors-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .monitors-header {
            padding: 20px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .monitors-header h2 {
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
        }

        .connection-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .connection-status.connected {
            background: #d4edda;
            color: #155724;
        }

        .connection-status.error {
            background: #f8d7da;
            color: #721c24;
        }

        .monitors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1px;
            background: #ecf0f1;
            padding: 1px;
        }

        .monitor-card {
            background: white;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background-color 0.3s ease;
            border: 2px solid transparent; /* default border biar stabil saat animasi */
        }

        .blink-red-white {
            animation: blinkRedWhite 1s infinite;
        }

        @keyframes blinkRedWhite {
            0% {
                background-color: #f8d7da;
                border-color: #e74c3c;
            }
            50% {
                background-color: white;
                border-color: white;
            }
            100% {
                background-color: #f8d7da;
                border-color: #e74c3c;
            }
        }

        /* Hapus animation: pulse dari sini */
        .monitor-status.down {
            background: #f8d7da;
            color: #721c24;
        }


        .monitor-card:hover {
            background: #f8f9fa;
        }

        .monitor-info {
            flex: 1;
        }

        .monitor-name {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }

        .monitor-type {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
        }

        .monitor-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .monitor-status.up {
            background: #d4edda;
            color: #155724;
        }

        .monitor-status.down {
            background: #f8d7da;
            color: #721c24;
            animation: pulse 2s infinite;
        }

        .monitor-status.paused {
            background: #e2e3e5;
            color: #6c757d;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.up { background: #27ae60; }
        .status-dot.down { background: #e74c3c; }
        .status-dot.paused { background: #95a5a6; }

        .loading, .error {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 16px;
        }

        .error {
            color: #e74c3c;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .monitors-grid {
                grid-template-columns: 1fr;
            }

            .monitor-card {
                padding: 15px;
            }
        }

        .notification {
            position: relative;
            margin-bottom: 10px;
            background: #e74c3c;
            color: white;
            padding: 0.6rem 1rem;
            font-size: 0.85rem;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
            max-width: 280px;
            white-space: pre-line;
            word-wrap: break-word;
            animation: slideIn 0.3s ease forwards;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .back-btn {
            background:rgb(33, 34, 34);
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s ease;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Dashboard</h1>
            <div class="header-actions">
                <div class="refresh-text">Refreshing in: <span id="countdown">30</span>s</div>
                <a href="/" class="back-btn">Back to Dashboard</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number" id="total-monitors">-</div>
                <div class="stat-label">Total Monitors</div>
            </div>
            <div class="stat-card up">
                <div class="stat-number" id="up-monitors">-</div>
                <div class="stat-label">Up</div>
            </div>
            <div class="stat-card down">
                <div class="stat-number" id="down-monitors">-</div>
                <div class="stat-label">Down</div>
            </div>
            <div class="stat-card paused">
                <div class="stat-number" id="paused-monitors">-</div>
                <div class="stat-label">Paused</div>
            </div>
        </div>

        <div class="monitors-section">
            <div class="monitors-header">
                <h2>Monitor Status</h2>
                <div class="connection-status connected" id="connection-status">
                    Connected
                </div>
            </div>
            <div class="monitors-grid" id="monitors-grid">
                <div class="loading">Loading monitors...</div>
            </div>
        </div>
    </div>

    <script>
        let countdown = 30;
        let autoRefreshInterval;
        let countdownInterval;

        function updateCountdown() {
            document.getElementById('countdown').textContent = countdown;
            if (countdown <= 0) {
                countdown = 30;
                fetchMonitorData();
            } else {
                countdown--;
            }
        }

        function updateConnectionStatus(connected, message = '') {
            const statusElement = document.getElementById('connection-status');
            if (connected) {
                statusElement.className = 'connection-status connected';
                statusElement.textContent = 'Connected';
            } else {
                statusElement.className = 'connection-status error';
                statusElement.textContent = message || 'Error';
            }
        }

        function getStatusClass(status) {
            switch(status) {
                case 1: return 'up';
                case 0: return 'down';
                case 2: return 'paused';
                default: return 'paused';
            }
        }

        function getStatusText(status) {
            switch(status) {
                case 1: return 'Up';
                case 0: return 'Down';
                case 2: return 'Paused';
                default: return 'Unknown';
            }
        }

        async function fetchMonitorData() {
            try {
                const response = await fetch('/api/admin-monitoring-data', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();

                if (result.success) {
                    updateUI(result.data);
                    updateConnectionStatus(true);
                } else {
                    throw new Error(result.message || 'Failed to fetch monitoring data');
                }
            } catch (error) {
                console.error('Error fetching monitoring data:', error);
                updateConnectionStatus(false, 'Connection Failed');
                
                document.getElementById('monitors-grid').innerHTML = 
                    `<div class="error">Failed to load monitors: ${error.message}</div>`;
            }
        }

        function updateUI(data) {
            // Update summary statistics
            document.getElementById('total-monitors').textContent = data.summary.total;
            document.getElementById('up-monitors').textContent = data.summary.up;
            document.getElementById('down-monitors').textContent = data.summary.down;
            document.getElementById('paused-monitors').textContent = data.summary.paused;

            // Update monitors grid
            const monitorsGrid = document.getElementById('monitors-grid');

            if (data.monitors.length === 0) {
                monitorsGrid.innerHTML = '<div class="loading">No monitors found</div>';
                return;
            }

            monitorsGrid.innerHTML = '';

            const downMonitors = [];

            data.monitors.forEach(monitor => {
                // ⛔ Jangan tampilkan jika avg 7 hari = 0
                if (monitor.average_7_days === 0) {
                    return;
                }

                const statusClass = getStatusClass(monitor.status);
                const statusText = getStatusText(monitor.status);

                // Simpan untuk alert jika status down
                if (monitor.status === 0) {
                    downMonitors.push(monitor.name);
                }

                const monitorCard = document.createElement('div');
                monitorCard.className = 'monitor-card';

                // Tambahkan animasi berkedip jika down
                if (monitor.status === 0) {
                    monitorCard.classList.add('blink-red-white');
                }

                monitorCard.innerHTML = `
                    <div class="monitor-info">
                        <div class="monitor-name">${monitor.name}</div>
                        <div class="monitor-type">${monitor.type}</div>
                    </div>
                    <div class="monitor-status ${statusClass}">
                        <div class="status-dot ${statusClass}"></div>
                        ${statusText}
                    </div>
                `;

                monitorsGrid.appendChild(monitorCard);
            });

            // Alert jika ada monitor down
            if (downMonitors.length > 0) {
                downMonitors.forEach(name => {
                    showNotification(`🔴 Monitor "${name}" is DOWN!`, 'warning');
                });
            }

        }



        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            fetchMonitorData();
            
            // Start countdown
            countdownInterval = setInterval(updateCountdown, 1000);
        });

        // Cleanup intervals when page is unloaded
        window.addEventListener('beforeunload', function() {
            if (countdownInterval) clearInterval(countdownInterval);
            if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        });

        function showNotification(message, type = 'error', timeout = 5000) {
            const container = document.getElementById('notification-container');

            const notif = document.createElement('div');
            notif.className = 'notification show';

            if (type === 'error') {
                notif.style.background = '#e74c3c';
            } else if (type === 'warning') {
                notif.style.background = '#f39c12';
            } else {
                notif.style.background = '#2ecc71';
            }

            notif.innerText = message;

            container.appendChild(notif);

            setTimeout(() => {
                notif.classList.remove('show');
                notif.style.opacity = '0';
                setTimeout(() => container.removeChild(notif), 500);
            }, timeout);
        }

    </script>
    <!-- Notification container -->
    <div id="notification-container" style="position: fixed; top: 100px; right: 20px; z-index: 1000;"></div>
</body>
</html>