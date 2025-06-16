<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\vault_manager.php
session_start();
require_once 'classes/Database.php';
require_once 'classes/Vault.php';
require_once 'classes/EncryptionHelper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureIt Vault Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.15);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
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
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(45deg, #718096, #4a5568);
        }

        .btn-danger {
            background: linear-gradient(45deg, #e53e3e, #c53030);
        }

        .results-section {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .vault-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .vault-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .item-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #ffffff;
        }

        .item-type {
            background: rgba(102, 126, 234, 0.3);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .item-details {
            color: #e2e8f0;
            margin-bottom: 15px;
        }

        .item-actions {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            padding: 6px 15px;
            font-size: 0.9rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
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

        .hidden {
            display: none;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #cbd5e0;
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

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-vault"></i> Vault Manager</h1>
            <p>Manage your secure vault items, passwords, and sensitive data</p>
            <div class="nav-links">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="class_explorer.php" class="nav-link">
                    <i class="fas fa-code"></i> Class Explorer
                </a>
                <a href="user_manager.php" class="nav-link">
                    <i class="fas fa-users"></i> User Manager
                </a>
            </div>
        </div>

        <div class="grid">
            <!-- Add New Vault Item -->
            <div class="action-section">
                <h2 class="section-title">
                    <i class="fas fa-plus-circle"></i> Add Vault Item
                </h2>
                <form id="addItemForm">
                    <div class="form-group">
                        <label for="itemType">Item Type</label>
                        <select id="itemType" class="select-control" required>
                            <option value="">Select item type</option>
                            <option value="password">Password</option>
                            <option value="note">Secure Note</option>
                            <option value="card">Credit Card</option>
                            <option value="identity">Identity</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="itemName">Item Name</label>
                        <input type="text" id="itemName" class="form-control" placeholder="Enter item name" required>
                    </div>
                    <div class="form-group">
                        <label for="itemUsername">Username/Email</label>
                        <input type="text" id="itemUsername" class="form-control" placeholder="Enter username or email">
                    </div>
                    <div class="form-group">
                        <label for="itemPassword">Password</label>
                        <input type="password" id="itemPassword" class="form-control" placeholder="Enter password">
                    </div>
                    <div class="form-group">
                        <label for="itemUrl">Website URL</label>
                        <input type="url" id="itemUrl" class="form-control" placeholder="https://example.com">
                    </div>
                    <div class="form-group">
                        <label for="itemNotes">Notes</label>
                        <textarea id="itemNotes" class="form-control" rows="3" placeholder="Additional notes"></textarea>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Add Item
                    </button>
                </form>
            </div>

            <!-- Search and Filter -->
            <div class="action-section">
                <h2 class="section-title">
                    <i class="fas fa-search"></i> Search Vault
                </h2>
                <div class="form-group">
                    <label for="searchQuery">Search Terms</label>
                    <input type="text" id="searchQuery" class="form-control" placeholder="Search by name, username, or URL">
                </div>
                <div class="form-group">
                    <label for="filterType">Filter by Type</label>
                    <select id="filterType" class="select-control">
                        <option value="">All Types</option>
                        <option value="password">Passwords</option>
                        <option value="note">Secure Notes</option>
                        <option value="card">Credit Cards</option>
                        <option value="identity">Identities</option>
                    </select>
                </div>
                <button type="button" class="btn" onclick="searchVault()">
                    <i class="fas fa-search"></i> Search
                </button>
                <button type="button" class="btn btn-secondary" onclick="loadAllItems()">
                    <i class="fas fa-list"></i> Show All
                </button>
            </div>
        </div>

        <!-- Results Section -->
        <div class="action-section">
            <h2 class="section-title">
                <i class="fas fa-list"></i> Vault Items
            </h2>
            <div id="statusMessage"></div>
            <div id="vaultItems" class="loading">
                <i class="fas fa-spinner fa-spin"></i> Loading vault items...
            </div>
        </div>

        <!-- Import/Export Section -->
        <div class="action-section">
            <h2 class="section-title">
                <i class="fas fa-exchange-alt"></i> Import/Export
            </h2>
            <div class="grid">
                <div>
                    <h3>Export Vault</h3>
                    <p>Export your vault data to a secure backup file</p>
                    <button type="button" class="btn" onclick="exportVault()">
                        <i class="fas fa-download"></i> Export Vault
                    </button>
                </div>
                <div>
                    <h3>Import Data</h3>
                    <p>Import data from other password managers</p>
                    <input type="file" id="importFile" class="form-control" accept=".json,.csv" style="margin-bottom: 10px;">
                    <button type="button" class="btn btn-secondary" onclick="importVault()">
                        <i class="fas fa-upload"></i> Import Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            loadAllItems();
        });

        // Add new vault item
        document.getElementById('addItemForm').addEventListener('submit', function(e) {
            e.preventDefault();
            addVaultItem();
        });

        function addVaultItem() {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('type', document.getElementById('itemType').value);
            formData.append('name', document.getElementById('itemName').value);
            formData.append('username', document.getElementById('itemUsername').value);
            formData.append('password', document.getElementById('itemPassword').value);
            formData.append('url', document.getElementById('itemUrl').value);
            formData.append('notes', document.getElementById('itemNotes').value);

            fetch('vault_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Item added successfully!', 'success');
                    document.getElementById('addItemForm').reset();
                    loadAllItems();
                } else {
                    showMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error: ' + error.message, 'error');
            });
        }

        function loadAllItems() {
            document.getElementById('vaultItems').innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading vault items...</div>';
            
            fetch('vault_api.php?action=getAll')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayVaultItems(data.items);
                } else {
                    showMessage('Error loading items: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error: ' + error.message, 'error');
            });
        }

        function searchVault() {
            const query = document.getElementById('searchQuery').value;
            const type = document.getElementById('filterType').value;
            
            const params = new URLSearchParams();
            params.append('action', 'search');
            if (query) params.append('query', query);
            if (type) params.append('type', type);

            document.getElementById('vaultItems').innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';

            fetch('vault_api.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayVaultItems(data.items);
                } else {
                    showMessage('Search error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error: ' + error.message, 'error');
            });
        }

        function displayVaultItems(items) {
            const container = document.getElementById('vaultItems');
            
            if (!items || items.length === 0) {
                container.innerHTML = '<div class="loading">No vault items found.</div>';
                return;
            }

            let html = '';
            items.forEach(item => {
                html += `
                    <div class="vault-item">
                        <div class="item-header">
                            <div class="item-title">
                                <i class="fas fa-${getItemIcon(item.type)}"></i> ${item.name}
                            </div>
                            <div class="item-type">${item.type}</div>
                        </div>
                        <div class="item-details">
                            ${item.username ? `<strong>Username:</strong> ${item.username}<br>` : ''}
                            ${item.url ? `<strong>URL:</strong> <a href="${item.url}" target="_blank" style="color: #90cdf4;">${item.url}</a><br>` : ''}
                            ${item.notes ? `<strong>Notes:</strong> ${item.notes}<br>` : ''}
                            <strong>Created:</strong> ${new Date(item.created_at).toLocaleString()}
                        </div>
                        <div class="item-actions">
                            <button class="btn btn-small" onclick="editItem(${item.id})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-small btn-secondary" onclick="copyPassword(${item.id})">
                                <i class="fas fa-copy"></i> Copy Password
                            </button>
                            <button class="btn btn-small btn-danger" onclick="deleteItem(${item.id})">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function getItemIcon(type) {
            const icons = {
                'password': 'key',
                'note': 'sticky-note',
                'card': 'credit-card',
                'identity': 'id-card'
            };
            return icons[type] || 'lock';
        }

        function editItem(id) {
            // Implementation for editing items
            showMessage('Edit functionality will be implemented', 'success');
        }

        function copyPassword(id) {
            fetch(`vault_api.php?action=getPassword&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.password) {
                    navigator.clipboard.writeText(data.password).then(() => {
                        showMessage('Password copied to clipboard!', 'success');
                    });
                } else {
                    showMessage('Error retrieving password', 'error');
                }
            })
            .catch(error => {
                showMessage('Network error: ' + error.message, 'error');
            });
        }

        function deleteItem(id) {
            if (confirm('Are you sure you want to delete this item?')) {
                fetch('vault_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Item deleted successfully!', 'success');
                        loadAllItems();
                    } else {
                        showMessage('Error deleting item: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showMessage('Network error: ' + error.message, 'error');
                });
            }
        }

        function exportVault() {
            fetch('vault_api.php?action=export')
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'secureit_vault_export_' + Date.now() + '.json';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                showMessage('Vault exported successfully!', 'success');
            })
            .catch(error => {
                showMessage('Export error: ' + error.message, 'error');
            });
        }

        function importVault() {
            const file = document.getElementById('importFile').files[0];
            if (!file) {
                showMessage('Please select a file to import', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'import');
            formData.append('file', file);

            fetch('vault_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(`Import successful! ${data.imported_count} items imported.`, 'success');
                    loadAllItems();
                } else {
                    showMessage('Import error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Network error: ' + error.message, 'error');
            });
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
