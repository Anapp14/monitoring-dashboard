<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AINO | System Monitoring - Uptime Kuma</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .title h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .title p {
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .refresh-timer {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem;
            height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            flex-shrink: 0;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.total { background: #3498db; }
        .stat-icon.up { background: #2ecc71; }
        .stat-icon.down { background: #e74c3c; }
        .stat-icon.paused { background: #95a5a6; }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-info p {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
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
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #ecf0f1;
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .monitors-header h2 {
            color: #2c3e50;
            font-size: 1.3rem;
        }

        .connection-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
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
        }

        .monitors-table th,
        .monitors-table td {
            padding: 1rem;
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
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .status-up { background: #2ecc71; }
        .status-down { background: #e74c3c; }
        .status-paused { background: #95a5a6; }

        .uptime-cell {
            font-weight: 600;
            text-align: center;
        }

        .uptime-percent {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
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
        }

        .error-message {
            text-align: center;
            padding: 2rem;
            color: #e74c3c;
            background: #ffeaea;
            border-radius: 8px;
            margin: 1rem;
        }

        /* Notification styles */
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


        .notification.show {
            transform: translateX(0);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
                height: calc(100vh - 60px);
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                margin-bottom: 1rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .monitors-header {
                padding: 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .monitors-table th,
            .monitors-table td {
                padding: 0.75rem 0.5rem;
                font-size: 0.9rem;
            }

            .notification {
                right: 10px;
                left: 10px;
                transform: translateY(-100px);
            }

            .notification.show {
                transform: translateY(0);
            }
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

    </style>
</head>
<body>
    <div class="header">
        <div class="title">
            <h1>AINO</h1>
            <p>System Monitoring - Uptime Kuma</p>
        </div>
        <div class="refresh-timer">
            <span>Refreshing in</span>
            <span id="countdown">30</span>
            <span>secs</span>
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
                            <th>Avg 7 Days</th>
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

    <!-- Notification element -->
    {{-- <div id="notification" class="notification">
        <span id="notification-text"></span>
    </div> --}}

    <!-- Notification container -->
    <div id="notification-container" style="position: fixed; top: 100px; right: 20px; z-index: 1000;"></div>


    <script>
        let countdown = 30;
        let scrollDirection = 1; // 1 for down, -1 for up
        let isScrolling = false;
        let hasError = false;

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
            const scrollDelay = 50;
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

        // Show notification
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

            notif.innerText = message; // agar \n jadi baris baru


            container.appendChild(notif);

            setTimeout(() => {
                notif.classList.remove('show');
                notif.style.opacity = '0';
                setTimeout(() => container.removeChild(notif), 500);
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
                const response = await fetch('/api/monitoring-data', {
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
                    showNotification('Failed to fetch data: ' + result.message);
                }
            } catch (error) {
                console.error('Error fetching monitoring data:', error);
                updateConnectionStatus(false, 'Connection Failed');
                showNotification('Connection error: ' + error.message);
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

            let downMonitors = [];

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
                    downMonitors.push(monitor.friendly_name);
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

                // Average 7 days cell
                const avgUptimeClass = getUptimeClass(monitor.average_7_days);
                const blinkClass = monitor.status === 0 ? 'blinking' : '';
                
                row.innerHTML = `
                    <td class="${blinkClass}">
                        <span class="status-indicator ${statusClass}"></span>
                        ${monitor.friendly_name}
                    </td>
                    <td class="uptime-cell">
                        <span class="uptime-percent ${avgUptimeClass}">${monitor.average_7_days}%</span>
                    </td>
                    <td>${monitor.type}</td>
                    ${last7DaysCells}
                `;

                
                tbody.appendChild(row);
            });

            // Show notification if there are down monitors
            if (downMonitors.length > 0) {
                downMonitors.forEach(name => {
                    showNotification(`🔴 Monitor "${name}" is DOWN!`, 'warning');
                });
            }

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