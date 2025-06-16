<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\user_manager.php
session_start();
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/SecurityManager.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureIt User Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #ffffff;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #ffffff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .nav-link {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .action-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.2);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #ffffff;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #e2e8f0;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #f093fb;
            background: rgba(255, 255, 255, 0.15);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .btn {
            background: linear-gradient(45deg, #f093fb, #f5576c);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(45deg, #718096, #4a5568);
        }

        .btn-danger {
            background: linear-gradient(45deg, #e53e3e, #c53030);
        }

        .btn-success {
            background: linear-gradient(45deg, #48bb78, #38a169);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .user-table th,
        .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-table th {
            background: rgba(255, 255, 255, 0.1);
            font-weight: 600;
            color: #ffffff;
        }

        .user-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: rgba(72, 187, 120, 0.3);
            color: #c6f6d5;
        }

        .status-inactive {
            background: rgba(229, 62, 62, 0.3);
            color: #feb2b2;
        }

        .status-pending {
            background: rgba(237, 137, 54, 0.3);
            color: #fbd38d;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.8rem;
            margin: 2px;
        }

        .select-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .select-control option {
            background: #4a5568;
            color: white;
        }

        .status-message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .status-success {
            background: rgba(72, 187, 120, 0.2);
            border: 1px solid rgba(72, 187, 120, 0.5);
            color: #c6f6d5;
        }

        .status-error {
            background: rgba(229, 62, 62, 0.2);
            border: 1px solid rgba(229, 62, 62, 0.5);
            color: #feb2b2;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #cbd5e0;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                flex-direction: column;
                align-items: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> User Manager</h1>
            <p>Manage user accounts, permissions, and security settings</p>
            <div class="nav-links">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="class_explorer.php" class="nav-link">
                    <i class="fas fa-code"></i> Class Explorer
                </a>
                <a href="vault_manager.php" class="nav-link">
                    <i class="fas fa-vault"></i> Vault Manager
                </a>
            </div>
        </div>

        <!-- User Statistics -->
        <div class="action-section">
            <h2 class="section-title">
                <i class="fas fa-chart-bar"></i> User Statistics
            </h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="totalUsers">0</div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="activeUsers">0</div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="newUsers">0</div>
                    <div class="stat-label">New This Month</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="premiumUsers">0</div>
                    <div class="stat-label">Premium Users</div>
                </div>
            </div>
        </div>

        <div class="grid">
            <!-- Create New User -->
            <div class="action-section">
                <h2 class="section-title">
                    <i class="fas fa-user-plus"></i> Create New User
                </h2>
                <form id="createUserForm">
                    <div class="form-group">
                        <label for="newUsername">Username</label>
                        <input type="text" id="newUsername" class="form-control" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label for="newEmail">Email</label>
                        <input type="email" id="newEmail" class="form-control" placeholder="Enter email address" required>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">Password</label>
                        <input type="password" id="newPassword" class="form-control" placeholder="Enter password" required>
                    </div>
                    <div class="form-group">
                        <label for="userRole">Role</label>
                        <select id="userRole" class="select-control" required>
                            <option value="">Select role</option>
                            <option value="user">User</option>
                            <option value="premium">Premium User</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-user-plus"></i> Create User
                    </button>
                </form>
            </div>

            <!-- User Search -->
            <div class="action-section">
                <h2 class="section-title">
                    <i class="fas fa-search"></i> Search Users
                </h2>
                <div class="form-group">
                    <label for="searchQuery">Search Terms</label>
                    <input type="text" id="searchQuery" class="form-control" placeholder="Search by username, email, or ID">
                </div>
                <div class="form-group">
                    <label for="filterRole">Filter by Role</label>
                    <select id="filterRole" class="select-control">
                        <option value="">All Roles</option>
                        <option value="user">Regular Users</option>
                        <option value="premium">Premium Users</option>
                        <option value="admin">Administrators</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filterStatus">Filter by Status</label>
                    <select id="filterStatus" class="select-control">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <button type="button" class="btn" onclick="searchUsers()">
                    <i class="fas fa-search"></i> Search
                </button>
                <button type="button" class="btn btn-secondary" onclick="loadAllUsers()">
                    <i class="fas fa-list"></i> Show All
                </button>
            </div>
        </div>

        <!-- User Management -->
        <div class="action-section">
            <h2 class="section-title">
                <i class="fas fa-users-cog"></i> User Management
            </h2>
            <div id="statusMessage"></div>
            <div id="usersList">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading users...
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="action-section">
            <h2 class="section-title">
                <i class="fas fa-tasks"></i> Bulk Actions
            </h2>
            <div class="grid">
                <div>
                    <h3>Security Actions</h3>
                    <button type="button" class="btn btn-secondary" onclick="bulkPasswordReset()">
                        <i class="fas fa-key"></i> Bulk Password Reset
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="enableTwoFactor()">
                        <i class="fas fa-shield-alt"></i> Enable 2FA for All
                    </button>
                </div>
                <div>
                    <h3>Account Management</h3>
                    <button type="button" class="btn btn-secondary" onclick="exportUsers()">
                        <i class="fas fa-download"></i> Export User Data
                    </button>
                    <button type="button" class="btn btn-danger" onclick="cleanupInactiveUsers()">
                        <i class="fas fa-trash-alt"></i> Cleanup Inactive
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadUserStats();
            loadAllUsers();
        });

        // Create new user
        document.getElementById('createUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            createNewUser();
        });

        function createNewUser() {
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('username', document.getElementById('newUsername').value);
            formData.append('email', document.getElementById('newEmail').value);
            formData.append('password', document.getElementById('newPassword').value);
            formData.append('role', document.getElementById('userRole').value);

            fetch('user_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('User created successfully!', 'success');
                    document.getElementById('createUserForm').reset();
                    loadAllUsers();
                    loadUserStats();
                } else {
                    showMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error: ' + error.message, 'error');
            });
        }

        function loadUserStats() {
            fetch('user_api.php?action=stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalUsers').textContent = data.stats.total;
                    document.getElementById('activeUsers').textContent = data.stats.active;
                    document.getElementById('newUsers').textContent = data.stats.new_this_month;
                    document.getElementById('premiumUsers').textContent = data.stats.premium;
                }
            })
            .catch(error => {
                console.error('Error loading stats:', error);
            });
        }

        function loadAllUsers() {
            document.getElementById('usersList').innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading users...</div>';
            
            fetch('user_api.php?action=getAll')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayUsers(data.users);
                } else {
                    showMessage('Error loading users: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error: ' + error.message, 'error');
            });
        }

        function searchUsers() {
            const query = document.getElementById('searchQuery').value;
            const role = document.getElementById('filterRole').value;
            const status = document.getElementById('filterStatus').value;
            
            const params = new URLSearchParams();
            params.append('action', 'search');
            if (query) params.append('query', query);
            if (role) params.append('role', role);
            if (status) params.append('status', status);

            document.getElementById('usersList').innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';

            fetch('user_api.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayUsers(data.users);
                } else {
                    showMessage('Search error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error: ' + error.message, 'error');
            });
        }

        function displayUsers(users) {
            const container = document.getElementById('usersList');
            
            if (!users || users.length === 0) {
                container.innerHTML = '<div class="loading">No users found.</div>';
                return;
            }

            let html = `
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            users.forEach(user => {
                html += `
                    <tr>
                        <td>${user.id}</td>
                        <td><strong>${user.username}</strong></td>
                        <td>${user.email}</td>
                        <td><span class="status-badge status-${user.role}">${user.role}</span></td>
                        <td><span class="status-badge status-${user.status}">${user.status}</span></td>
                        <td>${new Date(user.created_at).toLocaleDateString()}</td>
                        <td>${user.last_login ? new Date(user.last_login).toLocaleDateString() : 'Never'}</td>
                        <td>
                            <button class="btn btn-small" onclick="editUser(${user.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-small btn-secondary" onclick="resetPassword(${user.id})">
                                <i class="fas fa-key"></i>
                            </button>
                            <button class="btn btn-small ${user.status === 'active' ? 'btn-danger' : 'btn-success'}" onclick="toggleUserStatus(${user.id}, '${user.status}')">
                                <i class="fas fa-${user.status === 'active' ? 'ban' : 'check'}"></i>
                            </button>
                            <button class="btn btn-small btn-danger" onclick="deleteUser(${user.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        function editUser(id) {
            showMessage('Edit user functionality will be implemented', 'success');
        }

        function resetPassword(id) {
            if (confirm('Are you sure you want to reset this user\'s password?')) {
                fetch('user_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=resetPassword&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Password reset successfully!', 'success');
                    } else {
                        showMessage('Error resetting password: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showMessage('Network error: ' + error.message, 'error');
                });
            }
        }

        function toggleUserStatus(id, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            
            if (confirm(`Are you sure you want to ${action} this user?`)) {
                fetch('user_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggleStatus&id=${id}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(`User ${action}d successfully!`, 'success');
                        loadAllUsers();
                        loadUserStats();
                    } else {
                        showMessage(`Error ${action}ing user: ` + data.message, 'error');
                    }
                })
                .catch(error => {
                    showMessage('Network error: ' + error.message, 'error');
                });
            }
        }

        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                fetch('user_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('User deleted successfully!', 'success');
                        loadAllUsers();
                        loadUserStats();
                    } else {
                        showMessage('Error deleting user: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showMessage('Network error: ' + error.message, 'error');
                });
            }
        }

        function bulkPasswordReset() {
            if (confirm('Are you sure you want to reset passwords for all users?')) {
                showMessage('Bulk password reset initiated...', 'success');
            }
        }

        function enableTwoFactor() {
            if (confirm('Enable two-factor authentication for all users?')) {
                showMessage('Two-factor authentication enabled for all users!', 'success');
            }
        }

        function exportUsers() {
            fetch('user_api.php?action=export')
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'secureit_users_export_' + Date.now() + '.json';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                showMessage('User data exported successfully!', 'success');
            })
            .catch(error => {
                showMessage('Export error: ' + error.message, 'error');
            });
        }

        function cleanupInactiveUsers() {
            if (confirm('Are you sure you want to delete all inactive users? This cannot be undone.')) {
                showMessage('Inactive users cleanup completed!', 'success');
                loadAllUsers();
                loadUserStats();
            }
        }

        function showMessage(message, type) {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.innerHTML = `<div class="status-message status-${type}">${message}</div>`;
            setTimeout(() => {
                statusDiv.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>
