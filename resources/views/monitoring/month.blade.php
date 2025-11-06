<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AINO | System Monitoring - Uptime Kuma</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #monitors-tbody tr:nth-child(odd) {
            background-color: #f6ffffff; /* Contoh warna abu-abu terang */
        }

        /* Memberikan warna background pada baris genap */
        #monitors-tbody tr:nth-child(even) {
            background-color: #ffffff; /* Contoh warna putih */
        }

        .dashboard-link {
            background:rgb(94, 95, 96);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
            font-size: 0.85rem;
        }

        .dashboard-link:hover {
            background:rgb(74, 75, 76);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg,rgb(0, 0, 0) 0%,rgb(0, 0, 0) 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            width: 100%;
        }

        .title h1 {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .title p {
            opacity: 0.8;
            font-size: 0.8rem;
        }

        .refresh-timer {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 0.7rem;
            height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.7rem;
            margin-bottom: 0.7rem;
            flex-shrink: 0;
        }

        .stat-card {
            background: white;
            padding: 0.7rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.8rem;
            min-height: 70px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
        }

        .stat-icon.total { background: #3498db; }
        .stat-icon.up { background: #2ecc71; }
        .stat-icon.down { background:rgb(157, 141, 139); }
        .stat-icon.paused { background: #95a5a6; }

        .stat-info h3 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-info p {
            color: #7f8c8d;
            font-size: 1rem;
            text-transform: uppercase;
            font-weight: 500;
        }

        .monitors-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .monitors-header {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid #ecf0f1;
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .monitors-header h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .connection-status {
            padding: 0.4rem 1rem;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .connection-status.connected {
            background: #d4edda;
            color: #155724;
        }

        .connection-status.error {
            background: #f8d7da;
            color: #721c24;
        }

        .table-container {
            flex: 1;
            overflow-y: auto;
            overflow-x: auto;
            min-height: 0;
        }

        .monitors-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
        }

        .monitors-table th,
        .monitors-table td {
            padding: 1.2rem 1rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .monitors-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            position: sticky;
            top: 0;
            z-index: 10;
            font-size: 1.1rem;
        }

        .status-indicator {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }

        .status-up { background: #2ecc71; }
        .status-down { background: #e74c3c; }
        .status-paused { background: #95a5a6; }

        .uptime-cell {
            font-weight: 600;
            text-align: center;
        }

        .uptime-percent {
            padding: 0.4rem 0.7rem;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .uptime-perfect {
            background: #d4edda;
            color: #155724;
        }

        .uptime-good {
            background: #d1ecf1;
            color: #0c5460;
        }

        .uptime-warning {
            background: #fff3cd;
            color: #856404;
        }

        .uptime-critical {
            background: #f8d7da;
            color: #721c24;
        }

        .uptime-down {
            background: #f5c6cb;
            color: #721c24;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .error-message {
            text-align: center;
            padding: 2rem;
            color: #e74c3c;
            background: #ffeaea;
            border-radius: 8px;
            margin: 1rem;
            font-size: 1.1rem;
        }

        /* Notification styles - Persistent for down servers */
        .notification-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column-reverse;
            gap: 10px;
            max-width: 350px;
        }

        .notification {
            background: #e74c3c;
            color: white;
            padding: 0.8rem 1.2rem;
            font-size: 0.95rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            word-wrap: break-word;
            animation: slideInFromRight 0.3s ease forwards;
            border-left: 4px solid #c0392b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification.down {
            background: #e74c3c;
            border-left-color: #c0392b;
        }

        .notification.up {
            background: #2ecc71;
            border-left-color: #27ae60;
            animation: slideInFromRight 0.3s ease forwards;
        }

        .notification-icon {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .notification-text {
            flex: 1;
            line-height: 1.4;
            font-weight: bold;
        }

        @keyframes slideInFromRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOutToRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100%);
            }
        }

        .notification.removing {
            animation: slideOutToRight 0.3s ease forwards;
        }

        @keyframes blink-red-green {
            0% { background-color: red; color: white; }
            50% { background-color: white; color: black; }
            100% { background-color: red; color: white; }
        }

        .blinking {
            animation: blink-red-green 1s infinite;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .right-tools {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
                height: calc(100vh - 80px);
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                margin-bottom: 1rem;
            }

            .stat-card {
                padding: 1rem;
                min-height: 100px;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.8rem;
            }

            .stat-info h3 {
                font-size: 2rem;
            }

            .monitors-header {
                padding: 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .monitors-table th,
            .monitors-table td {
                padding: 1rem 0.75rem;
                font-size: 1.5rem;
            }

            .notification-container {
                bottom: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }

            .notification {
                font-size: 1rem;
                padding: 1rem 1.2rem;
            }

            .right-tools {
                flex-direction: column;
                gap: 10px;
            }
        }

        /* Untuk layar besar (1080p ke atas) */
        @media (min-width: 1920px) {
            .title h1 {
                font-size: 2.2rem;
            }

            .title p {
                font-size: 1.2rem;
            }

            .refresh-timer {
                font-size: 1.2rem;
                padding: 0.8rem 1.3rem;
            }

            .dashboard-link {
                font-size: 1.2rem;
                padding: 12px 24px;
            }

            .container {
                padding: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .stat-card {
                padding: 1.5rem;
                min-height: 130px;
            }

            .stat-icon {
                width: 65px;
                height: 65px;
                font-size: 2.2rem;
            }

            .stat-info h3 {
                font-size: 3rem;
            }

            .stat-info p {
                font-size: 1.2rem;
            }

            .monitors-table {
                font-size: 1.2rem;
            }

            .monitors-table th,
            .monitors-table td {
                padding: 1.5rem 1.2rem;
                font-size: 2rem;
            }

            .monitors-table th {
                font-size: 1.6rem;
            }

            .monitors-header h2 {
                font-size: 1.8rem;
            }

            .connection-status {
                font-size: 1.5rem;
                padding: 0.5rem 1.2rem;
            }

            .uptime-percent {
                font-size: 1.5rem;
                padding: 0.4rem 0.8rem;
            }

            .status-indicator {
                width: 16px;
                height: 16px;
                margin-right: 12px;
            }

            .notification-container {
                max-width: 450px;
            }

            .notification {
                font-size: 1.1rem;
                padding: 1rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">
            <h1>AINO</h1>
            <p>System Monitoring - Uptime Kuma (30 Days)</p>
        </div>
        <div class="right-tools">
            <div class="refresh-timer">
                <span>Refreshing in</span>
                <span id="countdown">30</span>
                <span>secs</span>
            </div>
            <a href="/" class="dashboard-link">Weekly Dashboard</a>
            <a href="/admin-dashboard" class="dashboard-link">Admin Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">📊</div>
                <div class="stat-info">
                    <h3 id="total-monitors">-</h3>
                    <p>Total Monitor</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon up">✅</div>
                <div class="stat-info">
                    <h3 id="up-monitors">-</h3>
                    <p>Up</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon down">❌</div>
                <div class="stat-info">
                    <h3 id="down-monitors">-</h3>
                    <p>Down</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon paused">⏸️</div>
                <div class="stat-info">
                    <h3 id="paused-monitors">-</h3>
                    <p>Paused</p>
                </div>
            </div>
        </div>

        <div class="monitors-section">
            <div class="monitors-header">
                <h2>Monitors</h2>
                <div class="connection-status connected" id="connection-status">
                    Connected to Uptime Kuma
                </div>
            </div>
            <div class="table-container" id="tableContainer">
                <table class="monitors-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Avg 30 Days</th>
                            <th>Type</th>
                            <th id="date-header-0">-</th>
                            <th id="date-header-1">-</th>
                            <th id="date-header-2">-</th>
                            <th id="date-header-3">-</th>
                            <th id="date-header-4">-</th>
                            <th id="date-header-5">-</th>
                            <th id="date-header-6">-</th>
                        </tr>
                    </thead>
                    <tbody id="monitors-tbody">
                        <tr>
                            <td colspan="10" class="loading">Loading monitoring data from Uptime Kuma...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Persistent notification container -->
    <div id="notification-container" class="notification-container"></div>

    <script>
        let countdown = 30;
        let scrollDirection = 1; // 1 for down, -1 for up
        let isScrolling = false;
        let hasError = false;
        let downMonitorsState = new Set(); // Track currently down monitors

        // Countdown and auto refresh
        function updateCountdown() {
            document.getElementById('countdown').textContent = countdown;
            if (countdown <= 0) {
                countdown = 30;
                fetchMonitoringData();
            } else {
                countdown--;
            }
        }

        // Auto scroll function
        function autoScroll() {
            const container = document.getElementById('tableContainer');
            const scrollStep = 1;
            const scrollDelay = 22;
            let scrolling = true;

            const scroll = () => {
                if (hasError || !scrolling) return;

                const maxScroll = container.scrollHeight - container.clientHeight;

                if (scrollDirection === 1) {
                    container.scrollTop = Math.min(container.scrollTop + scrollStep, maxScroll);

                    // Cek jika sudah sampai bawah
                    if (Math.ceil(container.scrollTop) >= maxScroll) {
                        scrollDirection = -1;
                        scrolling = false;
                        setTimeout(() => {
                            scrolling = true;
                            scroll();
                        }, 1000); // jeda di bawah
                        return;
                    }
                } else {
                    container.scrollTop = Math.max(container.scrollTop - scrollStep, 0);

                    // Cek jika sudah sampai atas
                    if (container.scrollTop <= 0) {
                        scrollDirection = 1;
                        scrolling = false;
                        setTimeout(() => {
                            scrolling = true;
                            scroll();
                        }, 1000); // jeda di atas
                        return;
                    }
                }

                setTimeout(scroll, scrollDelay);
            };

            scroll();
        }

        // Get uptime class based on percentage
        function getUptimeClass(percentage) {
            if (percentage === 100) return 'uptime-perfect';
            if (percentage >= 99) return 'uptime-good';
            if (percentage >= 95) return 'uptime-warning';
            if (percentage >= 90) return 'uptime-critical';
            return 'uptime-down';
        }

        // Show persistent notification for down servers
        function showPersistentNotification(monitorName, type = 'down') {
            const container = document.getElementById('notification-container');
            const notificationId = `notif-${monitorName.replace(/[^a-zA-Z0-9]/g, '-')}`;

            // Remove existing notification if it exists
            const existing = document.getElementById(notificationId);
            if (existing) {
                existing.remove();
            }

            const notif = document.createElement('div');
            notif.id = notificationId;
            notif.className = `notification ${type}`;

            let icon, message, bgColor, borderColor;

            if (type === 'down') {
                icon = '🔴';
                message = `Monitor "${monitorName}" is DOWN!`;
                bgColor = '#e74c3c';
                borderColor = '#c0392b';
            } else if (type === 'up') {
                icon = '🟢';
                message = `Monitor "${monitorName}" is back UP!`;
                bgColor = '#2ecc71';
                borderColor = '#27ae60';
            }

            notif.style.background = bgColor;
            notif.style.borderLeftColor = borderColor;

            notif.innerHTML = `
                <div class="notification-icon">${icon}</div>
                <div class="notification-text">${message}</div>
            `;

            // Insert at the beginning (top of stack)
            container.insertBefore(notif, container.firstChild);

            // If it's an "up" notification, remove it after 5 seconds
            if (type === 'up') {
                setTimeout(() => {
                    if (notif.parentNode) {
                        notif.classList.add('removing');
                        setTimeout(() => {
                            if (notif.parentNode) {
                                notif.remove();
                            }
                        }, 300);
                    }
                }, 5000);
            }
        }

        // Remove notification for a specific monitor
        function removeNotification(monitorName) {
            const notificationId = `notif-${monitorName.replace(/[^a-zA-Z0-9]/g, '-')}`;
            const notification = document.getElementById(notificationId);
            if (notification) {
                notification.classList.add('removing');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }

        // Show temporary notification for other types
        function showTemporaryNotification(message, type = 'error', timeout = 5000) {
            const container = document.getElementById('notification-container');

            const notif = document.createElement('div');
            notif.className = 'notification';

            let bgColor, borderColor, icon;
            if (type === 'error') {
                bgColor = '#e74c3c';
                borderColor = '#c0392b';
                icon = '⚠️';
            } else if (type === 'warning') {
                bgColor = '#f39c12';
                borderColor = '#e67e22';
                icon = '⚠️';
            } else {
                bgColor = '#2ecc71';
                borderColor = '#27ae60';
                icon = '✅';
            }

            notif.style.background = bgColor;
            notif.style.borderLeftColor = borderColor;

            notif.innerHTML = `
                <div class="notification-icon">${icon}</div>
                <div class="notification-text">${message}</div>
            `;

            container.insertBefore(notif, container.firstChild);

            setTimeout(() => {
                if (notif.parentNode) {
                    notif.classList.add('removing');
                    setTimeout(() => {
                        if (notif.parentNode) {
                            notif.remove();
                        }
                    }, 300);
                }
            }, timeout);
        }

        // Update connection status
        function updateConnectionStatus(connected, message = '') {
            const statusElement = document.getElementById('connection-status');
            if (connected) {
                statusElement.className = 'connection-status connected';
                statusElement.textContent = 'Connected to Uptime Kuma';
                hasError = false;
            } else {
                statusElement.className = 'connection-status error';
                statusElement.textContent = message || 'Connection Error';
                hasError = true;
            }
        }

        // Fetch monitoring data
        async function fetchMonitoringData() {
            try {
                const response = await fetch('/api/monitoring-data-month', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();

                if (result.success) {
                    updateUI(result.data);
                    updateConnectionStatus(true);
                } else {
                    console.error('Failed to fetch monitoring data:', result.message);
                    updateConnectionStatus(false, 'API Error');
                    showTemporaryNotification('Failed to fetch data: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error fetching monitoring data:', error);
                updateConnectionStatus(false, 'Connection Failed');
                showTemporaryNotification('Connection error: ' + error.message, 'error');
            }
        }

        // Update UI with new data
        function updateUI(data) {
            // Update summary cards
            document.getElementById('total-monitors').textContent = data.summary.total;
            document.getElementById('up-monitors').textContent = data.summary.up;
            document.getElementById('down-monitors').textContent = data.summary.down;
            document.getElementById('paused-monitors').textContent = data.summary.paused;

            // Update date headers
            data.dates.forEach((date, index) => {
                document.getElementById(`date-header-${index}`).textContent = date;
            });

            // Update monitors table
            const tbody = document.getElementById('monitors-tbody');
            tbody.innerHTML = '';

            const currentDownMonitors = new Set();

            if (data.monitors.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="10" class="loading">No monitors found in Uptime Kuma</td>';
                tbody.appendChild(row);
                return;
            }

            data.monitors.forEach(monitor => {
                const row = document.createElement('tr');

                let statusClass = 'status-paused';
                if (monitor.status === 1) {
                    statusClass = 'status-up';
                } else if (monitor.status === 0) {
                    statusClass = 'status-down';
                    currentDownMonitors.add(monitor.friendly_name);
                }

                // Generate cells for last 7 days uptime
                let last7DaysCells = '';
                monitor.last_7_days.forEach(day => {
                    const uptime = day.uptime;
                    const uptimeClass = getUptimeClass(uptime);
                    last7DaysCells += `
                        <td class="uptime-cell">
                            <span class="uptime-percent ${uptimeClass}">${uptime}%</span>
                        </td>
                    `;
                });

                // Average 30 days cell
                const avgUptimeClass = getUptimeClass(monitor.average_30_days);
                const blinkClass = monitor.status === 0 ? 'blinking' : '';

                row.innerHTML = `
                    <td class="${blinkClass}">
                        <span class="status-indicator ${statusClass}"></span>
                        ${monitor.friendly_name}
                    </td>
                    <td class="uptime-cell">
                        <span class="uptime-percent ${avgUptimeClass}">${monitor.average_30_days}%</span>
                    </td>
                    <td>${monitor.type}</td>
                    ${last7DaysCells}
                `;

                tbody.appendChild(row);
            });

            // Show notifications for newly down monitors
            currentDownMonitors.forEach(monitorName => {
                if (!downMonitorsState.has(monitorName)) {
                    showPersistentNotification(monitorName, 'down');
                }
            });

            // Show "back up" notifications and remove persistent notifications for recovered monitors
            downMonitorsState.forEach(monitorName => {
                if (!currentDownMonitors.has(monitorName)) {
                    showPersistentNotification(monitorName, 'up');
                    removeNotification(monitorName);
                }
            });

            // Update the state
            downMonitorsState = new Set(currentDownMonitors);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            fetchMonitoringData();
            setInterval(updateCountdown, 1000);
            setTimeout(autoScroll, 3000); // Mulai scroll otomatis setelah 3 detik
        });
    </script>
</body>
</html>
