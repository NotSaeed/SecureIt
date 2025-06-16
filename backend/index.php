<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\index.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureIt Backend Dashboard</title>
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
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .header h1 {
            color: #4a5568;
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header p {
            color: #718096;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 5px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card.database { border-left-color: #48bb78; }
        .stat-card.users { border-left-color: #4299e1; }
        .stat-card.vault { border-left-color: #ed8936; }
        .stat-card.security { border-left-color: #e53e3e; }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .stat-card.database .stat-icon { color: #48bb78; }
        .stat-card.users .stat-icon { color: #4299e1; }
        .stat-card.vault .stat-icon { color: #ed8936; }
        .stat-card.security .stat-icon { color: #e53e3e; }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .feature-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .feature-icon {
            font-size: 1.8rem;
            margin-right: 15px;
            color: #667eea;
        }

        .feature-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
        }

        .feature-list {
            list-style: none;
        }

        .feature-list li {
            padding: 8px 0;
            color: #4a5568;
            display: flex;
            align-items: center;
        }

        .feature-list li:before {
            content: "✓";
            color: #48bb78;
            font-weight: bold;
            margin-right: 10px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-online { background-color: #48bb78; }
        .status-offline { background-color: #e53e3e; }

        @media (max-width: 768px) {
            .header h1 { font-size: 2rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .features-grid { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> SecureIt Backend Dashboard</h1>
            <p>Professional Password Manager Backend System</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card database">
                <div class="stat-icon"><i class="fas fa-database"></i></div>
                <div class="stat-number">8</div>
                <div class="stat-label">Database Tables</div>
            </div>
            
            <div class="stat-card users">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number" id="userCount">Loading...</div>
                <div class="stat-label">Registered Users</div>
            </div>
            
            <div class="stat-card vault">
                <div class="stat-icon"><i class="fas fa-key"></i></div>
                <div class="stat-number" id="vaultCount">Loading...</div>
                <div class="stat-label">Vault Items</div>
            </div>
            
            <div class="stat-card security">
                <div class="stat-icon"><i class="fas fa-lock"></i></div>
                <div class="stat-number">AES-256</div>
                <div class="stat-label">Encryption Level</div>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon"><i class="fas fa-cogs"></i></div>
                    <div class="feature-title">Core Classes</div>
                </div>
                <ul class="feature-list">
                    <li><span class="status-indicator status-online"></span>Database Connection</li>
                    <li><span class="status-indicator status-online"></span>User Management</li>
                    <li><span class="status-indicator status-online"></span>Vault System</li>
                    <li><span class="status-indicator status-online"></span>Encryption Helper</li>
                    <li><span class="status-indicator status-online"></span>Password Generator</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="feature-title">Security Features</div>
                </div>
                <ul class="feature-list">
                    <li><span class="status-indicator status-online"></span>AES-256-GCM Encryption</li>
                    <li><span class="status-indicator status-online"></span>Argon2ID Password Hashing</li>
                    <li><span class="status-indicator status-online"></span>TOTP 2FA Authentication</li>
                    <li><span class="status-indicator status-online"></span>Session Management</li>
                    <li><span class="status-indicator status-online"></span>CORS Protection</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon"><i class="fas fa-api"></i></div>
                    <div class="feature-title">API Endpoints</div>
                </div>
                <ul class="feature-list">
                    <li><span class="status-indicator status-online"></span>Authentication API</li>
                    <li><span class="status-indicator status-online"></span>Vault Management API</li>
                    <li><span class="status-indicator status-online"></span>Password Generator API</li>
                    <li><span class="status-indicator status-online"></span>Secure Send API</li>
                    <li><span class="status-indicator status-online"></span>Reports API</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-header">
                    <div class="feature-icon"><i class="fas fa-tools"></i></div>
                    <div class="feature-title">Advanced Tools</div>
                </div>
                <ul class="feature-list">
                    <li><span class="status-indicator status-online"></span>Security Manager</li>
                    <li><span class="status-indicator status-online"></span>Report Generator</li>
                    <li><span class="status-indicator status-online"></span>Send Manager</li>
                    <li><span class="status-indicator status-online"></span>Authenticator</li>
                    <li><span class="status-indicator status-online"></span>Migration System</li>
                </ul>
            </div>
        </div>

        <div class="action-buttons">
            <a href="class_explorer.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Explore Classes
            </a>
            <a href="vault_manager.php" class="btn btn-success">
                <i class="fas fa-vault"></i> Vault Manager
            </a>
            <a href="user_manager.php" class="btn btn-info">
                <i class="fas fa-users-cog"></i> User Manager
            </a>
            <a href="test_backend.php" class="btn btn-warning">
                <i class="fas fa-vial"></i> Run Tests
            </a>
        </div>
    </div>

    <script>
        // Load dynamic statistics
        async function loadStats() {
            try {
                const response = await fetch('api/stats.php');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('userCount').textContent = data.stats.total_users || 0;
                    document.getElementById('vaultCount').textContent = data.stats.total_vault_items || 0;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
                document.getElementById('userCount').textContent = '0';
                document.getElementById('vaultCount').textContent = '0';
            }
        }

        // Load stats on page load
        loadStats();
    </script>
</body>
</html>
