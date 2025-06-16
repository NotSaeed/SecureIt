<?php
// Redirect to the main vault interface
header('Location: main_vault.php');
exit();
?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vault - SecureIt Password Manager</title>
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

        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .navbar-brand i {
            color: #48bb78;
        }

        .navbar-nav {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .nav-link {
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        .user-menu {
            position: relative;
        }

        .user-dropdown {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 8px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .welcome-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        .welcome-section h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #ffffff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #48bb78, #38a169);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .quick-actions {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .action-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
        }

        .action-icon {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .action-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .action-description {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .recent-items {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .vault-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .vault-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .item-title {
            font-size: 1.1rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
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
            font-size: 0.9rem;
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-success {
            background: linear-gradient(45deg, #48bb78, #38a169);
        }

        .btn-info {
            background: linear-gradient(45deg, #4299e1, #3182ce);
        }

        .btn-warning {
            background: linear-gradient(45deg, #ed8936, #dd6b20);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            opacity: 0.8;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.6;
        }

        @media (max-width: 768px) {
            .navbar-nav {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">
                <i class="fas fa-shield-alt"></i>
                SecureIt
            </div>
            <div class="navbar-nav">
                <a href="vault.php" class="nav-link active">
                    <i class="fas fa-vault"></i> My Vault
                </a>
                <a href="vault_manager.php" class="nav-link">
                    <i class="fas fa-cogs"></i> Manage
                </a>
                <a href="class_explorer.php" class="nav-link">
                    <i class="fas fa-code"></i> Tools
                </a>
                <a href="user_manager.php" class="nav-link">
                    <i class="fas fa-users"></i> Admin
                </a>
                <div class="user-menu">
                    <div class="user-dropdown" onclick="toggleUserMenu()">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($user_name); ?>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <p>Your secure vault is ready to protect your digital life</p>
        </div>

        <!-- Vault Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-key"></i>
                </div>
                <div class="stat-number"><?php echo $vaultStats['login_items'] ?? 0; ?></div>
                <div class="stat-label">Login Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="stat-number"><?php echo $vaultStats['card_items'] ?? 0; ?></div>
                <div class="stat-label">Payment Cards</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-sticky-note"></i>
                </div>
                <div class="stat-number"><?php echo $vaultStats['note_items'] ?? 0; ?></div>
                <div class="stat-label">Secure Notes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="stat-number"><?php echo $vaultStats['identity_items'] ?? 0; ?></div>
                <div class="stat-label">Identities</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number"><?php echo $vaultStats['favorite_items'] ?? 0; ?></div>
                <div class="stat-label">Favorites</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-number"><?php echo $vaultStats['total_items'] ?? 0; ?></div>
                <div class="stat-label">Total Items</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i> Quick Actions
            </h2>
            <div class="actions-grid">
                <div class="action-card" onclick="location.href='vault_manager.php'">
                    <div class="action-icon">
                        <i class="fas fa-plus-circle" style="color: #48bb78;"></i>
                    </div>
                    <div class="action-title">Add New Item</div>
                    <div class="action-description">Store a new password, card, or secure note</div>
                </div>
                <div class="action-card" onclick="location.href='vault_manager.php#search'">
                    <div class="action-icon">
                        <i class="fas fa-search" style="color: #4299e1;"></i>
                    </div>
                    <div class="action-title">Search Vault</div>
                    <div class="action-description">Find specific items in your vault</div>
                </div>
                <div class="action-card" onclick="location.href='class_explorer.php'">
                    <div class="action-icon">
                        <i class="fas fa-key" style="color: #ed8936;"></i>
                    </div>
                    <div class="action-title">Generate Password</div>
                    <div class="action-description">Create strong, secure passwords</div>
                </div>
                <div class="action-card" onclick="location.href='vault_manager.php#export'">
                    <div class="action-icon">
                        <i class="fas fa-download" style="color: #9f7aea;"></i>
                    </div>
                    <div class="action-title">Export Data</div>
                    <div class="action-description">Backup your vault securely</div>
                </div>
            </div>
        </div>

        <!-- Recent Items -->
        <div class="recent-items">
            <h2 class="section-title">
                <i class="fas fa-clock"></i> Recent Items
            </h2>
            
            <?php if (!empty($recentItems)): ?>
                <?php foreach ($recentItems as $item): ?>
                    <div class="vault-item">
                        <div class="item-header">
                            <div class="item-title">
                                <i class="fas fa-<?php echo getItemIcon($item['item_type']); ?>"></i>
                                <?php echo htmlspecialchars($item['item_name']); ?>
                            </div>
                            <div class="item-type"><?php echo ucfirst($item['item_type']); ?></div>
                        </div>
                        <div class="item-details">
                            <?php if ($item['website_url']): ?>
                                <strong>URL:</strong> <?php echo htmlspecialchars($item['website_url']); ?><br>
                            <?php endif; ?>
                            <strong>Last Updated:</strong> <?php echo date('M d, Y g:i A', strtotime($item['updated_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="vault_manager.php" class="btn">
                        <i class="fas fa-list"></i> View All Items
                    </a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-vault"></i>
                    <h3>Your vault is empty</h3>
                    <p>Start by adding your first password or secure item</p>
                    <a href="vault_manager.php" class="btn" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i> Add Your First Item
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            // Simple user menu toggle (to be enhanced)
            const options = [
                'Profile Settings',
                'Security Settings',
                'Logout'
            ];
            
            const choice = confirm('User Menu:\n1. Profile Settings\n2. Security Settings\n3. Logout\n\nClick OK to logout, Cancel to stay');
            
            if (choice) {
                if (confirm('Are you sure you want to logout?')) {
                    window.location.href = 'logout.php';
                }
            }
        }

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .action-card, .vault-item');
            
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>
</html>

<?php
function getItemIcon($type) {
    $icons = [
        'login' => 'key',
        'card' => 'credit-card',
        'identity' => 'id-card',
        'note' => 'sticky-note',
        'ssh_key' => 'terminal'
    ];
    return $icons[$type] ?? 'lock';
}
?>
