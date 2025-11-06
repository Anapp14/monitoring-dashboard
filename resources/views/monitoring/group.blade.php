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
            background: linear-gradient(135deg, rgb(0, 0, 0) 0%, rgb(0, 0, 0) 100%);
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

        .right-tools {
            display: flex;
            align-items: center;
            gap: 15px;
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

        .dashboard-link {
            background: rgb(94, 95, 96);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
            font-size: 0.85rem;
        }

        .dashboard-link:hover {
            background: rgb(74, 75, 76);
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
        .stat-icon.down { background: rgb(157, 141, 139); }
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

        .add-group-section {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.7rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .add-group-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .add-group-btn:hover {
            transform: translateY(-2px);
        }

        .add-group-form {
            display: none;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #f0f0f0;
        }

        .add-group-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 0.8rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.6rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-buttons {
            display: flex;
            gap: 0.8rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .groups-scroll-container {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: 0;
        }

        .group-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .group-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }

        .group-header:hover {
            opacity: 0.95;
        }

        .group-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .group-title h2 {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .group-stats {
            display: flex;
            gap: 0.8rem;
            font-size: 0.85rem;
        }

        .group-stat {
            padding: 0.3rem 0.6rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            font-weight: 600;
        }

        .group-actions {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .delete-group-btn {
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: background 0.2s ease;
        }

        .delete-group-btn:hover {
            background: rgba(192, 57, 43, 1);
        }

        .collapse-icon {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .collapse-icon.collapsed {
            transform: rotate(-90deg);
        }

        .group-content {
            padding: 1rem;
            max-height: 600px;
            overflow-y: auto;
            transition: max-height 0.3s ease;
        }

        .group-content.collapsed {
            max-height: 0;
            padding: 0;
            overflow: hidden;
        }

        .monitors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .monitor-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            border-left: 4px solid #95a5a6;
            transition: all 0.3s ease;
            position: relative;
        }

        .monitor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .monitor-card.up {
            border-left-color: #2ecc71;
        }

        .monitor-card.down {
            border-left-color: #e74c3c;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .monitor-card.paused {
            border-left-color: #95a5a6;
        }

        .monitor-card.assigned {
            opacity: 0.6;
        }

        .monitor-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-up { background: #2ecc71; }
        .status-down { background: #e74c3c; }
        .status-paused { background: #95a5a6; }

        .monitor-info {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-bottom: 0.3rem;
        }

        .monitor-uptime {
            margin-top: 0.7rem;
            padding-top: 0.7rem;
            border-top: 1px solid #dee2e6;
        }

        .uptime-label {
            font-size: 0.7rem;
            color: #7f8c8d;
            margin-bottom: 0.3rem;
        }

        .uptime-value {
            font-size: 1.1rem;
            font-weight: 700;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            display: inline-block;
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

        .assign-btn, .remove-monitor-btn {
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            width: 100%;
            transition: all 0.2s ease;
        }

        .assign-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .assign-btn:hover {
            opacity: 0.9;
        }

        .remove-monitor-btn {
            background: #e74c3c;
            color: white;
        }

        .remove-monitor-btn:hover {
            background: #c0392b;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: white;
            font-size: 1.1rem;
        }

        .connection-status {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .connection-status.connected {
            background: #d4edda;
            color: #155724;
        }

        .connection-status.error {
            background: #f8d7da;
            color: #721c24;
        }

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

        .notification.up {
            background: #2ecc71;
            border-left-color: #27ae60;
        }

        .notification.success {
            background: #3498db;
            border-left-color: #2980b9;
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

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .group-list {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .group-option {
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .group-option:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }

        .group-option.selected {
            border-color: #667eea;
            background: #e8eaf6;
        }

        @media (max-width: 768px) {
            .monitors-grid {
                grid-template-columns: 1fr;
            }

            .group-header {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }

            .right-tools {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">
            <h1>AINO</h1>
            <p>System Monitoring - Uptime Kuma (7 Days)</p>
        </div>
        <div class="right-tools">
            <div class="refresh-timer">
                <span>Refreshing in</span>
                <span id="countdown">30</span>
                <span>secs</span>
            </div>
            <a href="/" class="dashboard-link">Weekly Dashboard</a>
            <a href="/month-dashboard" class="dashboard-link">Month Dashboard</a>
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

        <div class="add-group-section">
            <button class="add-group-btn" onclick="toggleAddGroupForm()">➕ Add New Group</button>
            
            <div class="add-group-form" id="addGroupForm">
                <div class="form-group">
                    <label>Group Name</label>
                    <input type="text" id="groupNameInput" placeholder="Enter group name...">
                </div>
                <div class="form-buttons">
                    <button class="btn btn-primary" onclick="createGroup()" id="createGroupBtn">Create Group</button>
                    <button class="btn btn-secondary" onclick="toggleAddGroupForm()">Cancel</button>
                </div>
            </div>
        </div>

        <div class="groups-scroll-container" id="groupsScrollContainer">
            <div class="loading">Loading monitoring data from Uptime Kuma...</div>
        </div>
    </div>

    <div class="connection-status connected" id="connection-status">
        Connected to Uptime Kuma
    </div>

    <div id="notification-container" class="notification-container"></div>

    <!-- Modal for assigning monitor to group -->
    <div class="modal-overlay" id="assignModal" onclick="closeAssignModal(event)">
        <div class="modal" onclick="event.stopPropagation()">
            <h3>Assign to Group</h3>
            <div class="group-list" id="groupListModal"></div>
            <div class="form-buttons">
                <button class="btn btn-primary" onclick="confirmAssign()" id="confirmAssignBtn">Assign</button>
                <button class="btn btn-secondary" onclick="closeAssignModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let groups = [];
        let allMonitors = [];
        let countdown = 30;
        let hasError = false;
        let downMonitorsState = new Set();
        let selectedGroupForAssign = null;
        let monitorToAssign = null;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function updateCountdown() {
            document.getElementById('countdown').textContent = countdown;
            if (countdown <= 0) {
                countdown = 30;
                fetchMonitoringData();
            } else {
                countdown--;
            }
        }

        function getUptimeClass(percentage) {
            if (percentage === 100) return 'uptime-perfect';
            if (percentage >= 99) return 'uptime-good';
            if (percentage >= 95) return 'uptime-warning';
            if (percentage >= 90) return 'uptime-critical';
            return 'uptime-down';
        }

        function showNotification(message, type = 'down') {
            const container = document.getElementById('notification-container');
            const notif = document.createElement('div');
            notif.className = `notification ${type}`;

            let icon = type === 'down' ? '🔴' : type === 'up' ? '🟢' : 'ℹ️';

            notif.innerHTML = `
                <div style="font-size: 1.2rem;">${icon}</div>
                <div style="flex: 1; font-weight: bold;">${message}</div>
            `;

            container.insertBefore(notif, container.firstChild);

            setTimeout(() => {
                if (notif.parentNode) {
                    notif.remove();
                }
            }, 5000);
        }

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

        async function loadGroups() {
            try {
                const response = await fetch('/api/groups', {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const result = await response.json();

                if (result.success) {
                    groups = result.data.map(g => ({
                        id: g.id,
                        name: g.name,
                        monitorIds: g.monitor_ids || []
                    }));
                } else {
                    console.error('Failed to load groups:', result.message);
                }
            } catch (error) {
                console.error('Error loading groups:', error);
            }
        }

        function toggleAddGroupForm() {
            const form = document.getElementById('addGroupForm');
            form.classList.toggle('active');
            if (form.classList.contains('active')) {
                document.getElementById('groupNameInput').focus();
            }
        }

        async function createGroup() {
            const nameInput = document.getElementById('groupNameInput');
            const name = nameInput.value.trim();
            const btn = document.getElementById('createGroupBtn');

            if (!name) {
                showNotification('Please enter a group name!', 'down');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Creating...';

            try {
                const response = await fetch('/api/groups', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ name: name })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Group created successfully!', 'success');
                    await loadGroups();
                    renderGroups();
                    nameInput.value = '';
                    toggleAddGroupForm();
                } else {
                    showNotification(result.message || 'Failed to create group', 'down');
                }
            } catch (error) {
                console.error('Error creating group:', error);
                showNotification('Error creating group', 'down');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Create Group';
            }
        }

        async function deleteGroup(groupId) {
            if (!confirm('Are you sure you want to delete this group?')) {
                return;
            }

            try {
                const response = await fetch(`/api/groups/${groupId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Group deleted successfully!', 'success');
                    await loadGroups();
                    renderGroups();
                } else {
                    showNotification(result.message || 'Failed to delete group', 'down');
                }
            } catch (error) {
                console.error('Error deleting group:', error);
                showNotification('Error deleting group', 'down');
            }
        }

        function toggleGroup(groupIndex) {
            const content = document.getElementById(`group-content-${groupIndex}`);
            const icon = document.getElementById(`collapse-icon-${groupIndex}`);
            
            content.classList.toggle('collapsed');
            icon.classList.toggle('collapsed');
        }

        function showAssignModal(monitorId) {
            if (groups.length === 0) {
                showNotification('Please create a group first!', 'down');
                return;
            }

            monitorToAssign = monitorId;
            selectedGroupForAssign = null;

            const modal = document.getElementById('assignModal');
            const groupList = document.getElementById('groupListModal');
            
            groupList.innerHTML = '';
            
            groups.forEach(group => {
                const option = document.createElement('div');
                option.className = 'group-option';
                option.textContent = group.name;
                option.onclick = () => selectGroupForAssign(group.id, option);
                groupList.appendChild(option);
            });

            modal.classList.add('active');
        }

        function selectGroupForAssign(groupId, element) {
            selectedGroupForAssign = groupId;
            document.querySelectorAll('.group-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            element.classList.add('selected');
        }

        function closeAssignModal(event) {
            if (event && event.target.id !== 'assignModal') return;
            document.getElementById('assignModal').classList.remove('active');
            selectedGroupForAssign = null;
            monitorToAssign = null;
        }

        async function confirmAssign() {
            if (!selectedGroupForAssign || !monitorToAssign) {
                showNotification('Please select a group', 'down');
                return;
            }

            const btn = document.getElementById('confirmAssignBtn');
            btn.disabled = true;
            btn.textContent = 'Assigning...';

            try {
                const response = await fetch(`/api/groups/${selectedGroupForAssign}/assign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ monitor_id: monitorToAssign })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Monitor assigned successfully!', 'success');
                    await loadGroups();
                    renderGroups();
                    closeAssignModal();
                } else {
                    showNotification(result.message || 'Failed to assign monitor', 'down');
                }
            } catch (error) {
                console.error('Error assigning monitor:', error);
                showNotification('Error assigning monitor', 'down');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Assign';
            }
        }

        async function removeMonitorFromGroup(groupId, monitorId) {
            if (!confirm('Remove this monitor from the group?')) {
                return;
            }

            try {
                const response = await fetch(`/api/groups/${groupId}/remove`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ monitor_id: monitorId })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification('Monitor removed from group!', 'success');
                    await loadGroups();
                    renderGroups();
                } else {
                    showNotification(result.message || 'Failed to remove monitor', 'down');
                }
            } catch (error) {
                console.error('Error removing monitor:', error);
                showNotification('Error removing monitor', 'down');
            }
        }

        function isMonitorAssigned(monitorId) {
            return groups.some(g => g.monitorIds.includes(monitorId));
        }

        function calculateGroupAverage(monitorIds) {
            if (monitorIds.length === 0) return 0;
            
            const groupMonitors = allMonitors.filter(m => monitorIds.includes(String(m.id)));
            if (groupMonitors.length === 0) return 0;
            
            const sum = groupMonitors.reduce((acc, m) => acc + m.average_7_days, 0);
            return (sum / groupMonitors.length).toFixed(2);
        }

        function getGroupStats(monitorIds) {
            const groupMonitors = allMonitors.filter(m => monitorIds.includes(String(m.id)));
            return {
                total: groupMonitors.length,
                up: groupMonitors.filter(m => m.status === 1).length,
                down: groupMonitors.filter(m => m.status === 0).length
            };
        }

        function renderMonitorCard(monitor, groupId = null) {
            let statusClass = 'paused';
            if (monitor.status === 1) {
                statusClass = 'up';
            } else if (monitor.status === 0) {
                statusClass = 'down';
            }

            const uptimeClass = getUptimeClass(monitor.average_7_days);
            const isAssigned = isMonitorAssigned(String(monitor.id));

            return `
                <div class="monitor-card ${statusClass} ${isAssigned && !groupId ? 'assigned' : ''}">
                    <div class="monitor-name">
                        <span class="status-indicator status-${statusClass}"></span>
                        ${monitor.friendly_name}
                    </div>
                    <div class="monitor-info">Type: ${monitor.type}</div>
                    ${monitor.ping ? `<div class="monitor-info">Ping: ${monitor.ping}ms</div>` : ''}
                    <div class="monitor-uptime">
                        <div class="uptime-label">7-Day Average</div>
                        <span class="uptime-value ${uptimeClass}">${monitor.average_7_days}%</span>
                    </div>
                    ${groupId 
                        ? `<button class="remove-monitor-btn" onclick="removeMonitorFromGroup(${groupId}, '${monitor.id}')">Remove from Group</button>`
                        : !isAssigned 
                            ? `<button class="assign-btn" onclick="showAssignModal('${monitor.id}')">Assign to Group</button>`
                            : '<div style="text-align: center; margin-top: 0.5rem; font-size: 0.8rem; color: #95a5a6;">Already assigned</div>'
                    }
                </div>
            `;
        }

        function renderGroups() {
            const container = document.getElementById('groupsScrollContainer');
            container.innerHTML = '';

            // Render user-created groups
            groups.forEach((group, groupIndex) => {
                const groupDiv = document.createElement('div');
                groupDiv.className = 'group-section';

                const groupMonitors = allMonitors.filter(m => group.monitorIds.includes(String(m.id)));
                const average = calculateGroupAverage(group.monitorIds);
                const stats = getGroupStats(group.monitorIds);
                const uptimeClass = getUptimeClass(parseFloat(average));

                let monitorsHTML = '';
                if (groupMonitors.length === 0) {
                    monitorsHTML = '<div style="text-align: center; padding: 2rem; color: #95a5a6; font-style: italic;">No monitors in this group yet. Assign monitors from the "Ungrouped Monitors" section below.</div>';
                } else {
                    monitorsHTML = '<div class="monitors-grid">';
                    groupMonitors.forEach(monitor => {
                        monitorsHTML += renderMonitorCard(monitor, group.id);
                    });
                    monitorsHTML += '</div>';
                }

                groupDiv.innerHTML = `
                    <div class="group-header" onclick="toggleGroup(${groupIndex})">
                        <div class="group-title">
                            <h2>${group.name}</h2>
                            <div class="group-stats">
                                <span class="group-stat">Total: ${stats.total}</span>
                                <span class="group-stat">Up: ${stats.up}</span>
                                <span class="group-stat">Down: ${stats.down}</span>
                            </div>
                        </div>
                        <div class="group-actions">
                            <span class="uptime-value ${uptimeClass}">${average}% Avg</span>
                            <button class="delete-group-btn" onclick="event.stopPropagation(); deleteGroup(${group.id})">🗑️</button>
                            <span class="collapse-icon" id="collapse-icon-${groupIndex}">▼</span>
                        </div>
                    </div>
                    <div class="group-content" id="group-content-${groupIndex}">
                        ${monitorsHTML}
                    </div>
                `;

                container.appendChild(groupDiv);
            });

            // Render ungrouped monitors
            const ungroupedMonitors = allMonitors.filter(m => !isMonitorAssigned(String(m.id)));
            if (ungroupedMonitors.length > 0) {
                const ungroupedDiv = document.createElement('div');
                ungroupedDiv.className = 'group-section';

                const ungroupedAverage = calculateUngroupedAverage(ungroupedMonitors);
                const ungroupedStats = {
                    total: ungroupedMonitors.length,
                    up: ungroupedMonitors.filter(m => m.status === 1).length,
                    down: ungroupedMonitors.filter(m => m.status === 0).length
                };
                const uptimeClass = getUptimeClass(parseFloat(ungroupedAverage));

                let ungroupedHTML = '<div class="monitors-grid">';
                ungroupedMonitors.forEach(monitor => {
                    ungroupedHTML += renderMonitorCard(monitor, null);
                });
                ungroupedHTML += '</div>';

                ungroupedDiv.innerHTML = `
                    <div class="group-header" onclick="toggleGroup('ungrouped')">
                        <div class="group-title">
                            <h2>📋 Ungrouped Monitors</h2>
                            <div class="group-stats">
                                <span class="group-stat">Total: ${ungroupedStats.total}</span>
                                <span class="group-stat">Up: ${ungroupedStats.up}</span>
                                <span class="group-stat">Down: ${ungroupedStats.down}</span>
                            </div>
                        </div>
                        <div class="group-actions">
                            <span class="uptime-value ${uptimeClass}">${ungroupedAverage}% Avg</span>
                            <span class="collapse-icon" id="collapse-icon-ungrouped">▼</span>
                        </div>
                    </div>
                    <div class="group-content" id="group-content-ungrouped">
                        ${ungroupedHTML}
                    </div>
                `;

                container.appendChild(ungroupedDiv);
            }

            // Show empty state if no monitors at all
            if (allMonitors.length === 0) {
                container.innerHTML = '<div class="loading">No monitors found in Uptime Kuma</div>';
            }
        }

        function calculateUngroupedAverage(monitors) {
            if (monitors.length === 0) return 0;
            const sum = monitors.reduce((acc, m) => acc + m.average_7_days, 0);
            return (sum / monitors.length).toFixed(2);
        }

        async function fetchMonitoringData() {
            try {
                const response = await fetch('/api/monitoring-data', {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const result = await response.json();

                if (result.success) {
                    updateUI(result.data);
                    updateConnectionStatus(true);
                } else {
                    console.error('Failed to fetch monitoring data:', result.message);
                    updateConnectionStatus(false, 'API Error');
                }
            } catch (error) {
                console.error('Error fetching monitoring data:', error);
                updateConnectionStatus(false, 'Connection Failed');
            }
        }

        function updateUI(data) {
            // Update summary cards
            document.getElementById('total-monitors').textContent = data.summary.total;
            document.getElementById('up-monitors').textContent = data.summary.up;
            document.getElementById('down-monitors').textContent = data.summary.down;
            document.getElementById('paused-monitors').textContent = data.summary.paused;

            // Store monitors data
            allMonitors = data.monitors.map(monitor => ({
                id: monitor.id,
                friendly_name: monitor.friendly_name,
                type: monitor.type,
                status: monitor.status,
                ping: monitor.ping,
                average_7_days: monitor.average_7_days
            }));

            const currentDownMonitors = new Set();
            data.monitors.forEach(monitor => {
                if (monitor.status === 0) {
                    currentDownMonitors.add(monitor.friendly_name);
                }
            });

            // Show notifications for newly down monitors
            currentDownMonitors.forEach(monitorName => {
                if (!downMonitorsState.has(monitorName)) {
                    showNotification(`Monitor "${monitorName}" is DOWN!`, 'down');
                }
            });

            // Show "back up" notifications for recovered monitors
            downMonitorsState.forEach(monitorName => {
                if (!currentDownMonitors.has(monitorName)) {
                    showNotification(`Monitor "${monitorName}" is back UP!`, 'up');
                }
            });

            downMonitorsState = new Set(currentDownMonitors);

            // Render groups with monitors
            renderGroups();
        }

        document.addEventListener('DOMContentLoaded', async function() {
            await loadGroups();
            await fetchMonitoringData();
            setInterval(updateCountdown, 1000);
        });
    </script>
</body>
</html>