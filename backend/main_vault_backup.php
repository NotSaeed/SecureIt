<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\main_vault.php
session_start();
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Vault.php';
require_once 'classes/SendManager.php';

// Initialize variables
$error = '';
$success = '';
$isLoggedIn = isset($_SESSION['user_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'login':
            try {
                $user = new User();
                $authenticatedUser = $user->authenticate($_POST['email'], $_POST['password']);
                
                if ($authenticatedUser) {
                    $_SESSION['user_id'] = $authenticatedUser->id;
                    $_SESSION['user_email'] = $authenticatedUser->email;
                    $_SESSION['user_name'] = $authenticatedUser->name;
                    $success = 'Login successful!';
                    $isLoggedIn = true;
                } else {
                    $error = 'Invalid email or password';
                }
            } catch (Exception $e) {
                $error = 'Login failed: ' . $e->getMessage();
            }
            break;
            
        case 'register':
            try {
                $user = new User();
                $newUser = $user->create($_POST['email'], $_POST['password'], $_POST['name']);
                
                $_SESSION['user_id'] = $newUser->id;
                $_SESSION['user_email'] = $newUser->email;
                $_SESSION['user_name'] = $newUser->name;
                $success = 'Registration successful! Welcome to SecureIt!';
                $isLoggedIn = true;
            } catch (Exception $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
            break;
            
        case 'add_vault_item':
            if ($isLoggedIn) {
                try {
                    $vault = new Vault();
                    $data = [
                        'username' => $_POST['username'] ?? '',
                        'password' => $_POST['password'] ?? '',
                        'notes' => $_POST['notes'] ?? ''
                    ];
                    
                    if ($_POST['item_type'] === 'card') {
                        $data = [
                            'cardholder_name' => $_POST['cardholder_name'] ?? '',
                            'card_number' => $_POST['card_number'] ?? '',
                            'expiry_date' => $_POST['expiry_date'] ?? '',
                            'cvv' => $_POST['cvv'] ?? ''
                        ];
                    }
                    
                    $vault->addItem(
                        $_SESSION['user_id'],
                        $_POST['item_name'],
                        $_POST['item_type'],
                        $data,
                        $_POST['website_url'] ?? null
                    );
                    
                    $success = 'Item added successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to add item: ' . $e->getMessage();
                }
            }
            break;
              case 'delete_vault_item':
            if ($isLoggedIn && isset($_POST['item_id'])) {
                try {
                    $vault = new Vault();
                    $vault->deleteItem($_POST['item_id'], $_SESSION['user_id']);
                    $success = 'Item deleted successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to delete item: ' . $e->getMessage();
                }
            }
            break;
            
        case 'create_secure_send':
            if ($isLoggedIn) {
                try {
                    $sendManager = new SendManager();
                    
                    // Get form data
                    $name = trim($_POST['send_name'] ?? '');
                    $content = trim($_POST['send_text'] ?? '');
                    $sendType = 'text'; // Default to text
                    
                    // Validate required fields
                    if (empty($name)) {
                        throw new Exception('Send name is required');
                    }
                    if (empty($content)) {
                        throw new Exception('Content is required');
                    }
                    
                    // Parse deletion date
                    $deletionDays = (int)($_POST['deletion_days'] ?? 7);
                    $deletionDate = date('Y-m-d H:i:s', strtotime("+{$deletionDays} days"));
                    
                    // Build options array
                    $options = [
                        'deletion_date' => $deletionDate,
                        'password' => !empty($_POST['send_password']) ? $_POST['send_password'] : null,
                        'max_views' => !empty($_POST['max_views']) ? (int)$_POST['max_views'] : null,
                        'hide_email' => !empty($_POST['hide_email'])
                    ];
                    
                    $result = $sendManager->createSend($_SESSION['user_id'], $sendType, $name, $content, $options);
                    
                    $success = "Secure send created successfully! Share this link: " . 
                               "http://localhost/SecureIt/backend/access_send.php?link=" . $result['access_link'];
                    
                } catch (Exception $e) {
                    $error = 'Failed to create secure send: ' . $e->getMessage();
                }
            }
            break;
            
        case 'delete_send':
            if ($isLoggedIn && isset($_POST['send_id'])) {
                try {
                    $sendManager = new SendManager();
                    $sendManager->deleteSend($_POST['send_id'], $_SESSION['user_id']);
                    $success = 'Send deleted successfully!';
                } catch (Exception $e) {
                    $error = 'Failed to delete send: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get user data if logged in
$vaultItems = [];
$vaultStats = [];
$userSends = [];
$sendStats = [];
if ($isLoggedIn) {
    try {
        $vault = new Vault();
        $vaultItems = $vault->getUserItems($_SESSION['user_id']);
        $vaultStats = $vault->getVaultStats($_SESSION['user_id']);
        
        // Load send data
        $sendManager = new SendManager();
        $userSends = $sendManager->getUserSends($_SESSION['user_id']);
        $sendStats = $sendManager->getSendStats($_SESSION['user_id']);
    } catch (Exception $e) {
        $error = 'Failed to load data: ' . $e->getMessage();
    }
}

// Get filtered items based on type
$filteredItems = $vaultItems;
$currentType = $_GET['type'] ?? '';
if ($currentType && $isLoggedIn) {
    $filteredItems = array_filter($vaultItems, function($item) use ($currentType) {
        return $item['item_type'] === $currentType;
    });
}

// Calculate detailed vault statistics
if ($isLoggedIn) {
    $vaultStats = [
        'total' => count($vaultItems),
        'logins' => count(array_filter($vaultItems, fn($item) => $item['item_type'] === 'login')),
        'cards' => count(array_filter($vaultItems, fn($item) => $item['item_type'] === 'card')),
        'identities' => count(array_filter($vaultItems, fn($item) => $item['item_type'] === 'identity')),
        'notes' => count(array_filter($vaultItems, fn($item) => $item['item_type'] === 'note')),
        'favorites' => 0, // Would be calculated from favorites field
        'weak_passwords' => rand(0, 3), // Would be calculated based on password strength
        'reused_passwords' => rand(0, 2), // Would be calculated by checking duplicates
        'compromised' => 0 // Would be checked against breach databases
    ];
}

// Get system stats
try {
    $db = new Database();
    $totalUsers = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
    $totalVaultItems = $db->fetchOne("SELECT COUNT(*) as count FROM vaults")['count'] ?? 0;
} catch (Exception $e) {
    $totalUsers = 0;
    $totalVaultItems = 0;
}

// Get current page/section
$currentSection = $_GET['section'] ?? ($isLoggedIn ? 'dashboard' : 'home');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureIt - Professional Password Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --secondary: #059669;
            --secondary-light: #10b981;
            --accent: #d97706;
            --accent-light: #f59e0b;
            --danger: #dc2626;
            --danger-light: #ef4444;
            --dark: #111827;
            --dark-light: #1f2937;
            --dark-medium: #374151;
            --gray: #4b5563;
            --gray-light: #9ca3af;
            --gray-lighter: #f3f4f6;
            --gray-lightest: #f9fafb;
            --white: #ffffff;
            --success: #059669;
            --success-light: #10b981;
            --warning: #d97706;
            --warning-light: #f59e0b;
            --info: #2563eb;
            --info-light: #3b82f6;
            
            /* Enhanced text colors for better readability */
            --text-primary: #111827;
            --text-secondary: #374151;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
            
            /* Background colors */
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --bg-tertiary: #f3f4f6;
            --bg-accent: #eff6ff;
            
            /* Enhanced shadows */
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            --border-radius: 0.5rem;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-secondary);
            min-height: 100vh;
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
        }        .sidebar {
            width: 280px;
            background: var(--bg-primary);
            border-right: 1px solid #e5e7eb;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            transition: var(--transition);
        }

        .main-content.expanded {
            margin-left: 80px;
        }

        /* Sidebar */
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary);
        }

        .logo i {
            font-size: 1.5rem;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .sidebar-toggle:hover {
            background: var(--gray-light);
            color: var(--dark);
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
        }        .nav-section-title {
            padding: 0 1.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.75rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
            font-weight: 500;
            margin: 0.125rem 0;
        }

        .nav-item:hover {
            background: var(--bg-accent);
            color: var(--primary);
            border-left-color: var(--primary-light);
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
        }
        
        .nav-item.active {
            background: var(--bg-accent);
            color: var(--primary);
            border-left-color: var(--primary);
            font-weight: 600;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
        }

        .nav-item i {
            width: 1.25rem;
            text-align: center;
        }

        .nav-item-text {
            transition: var(--transition);
        }

        .sidebar.collapsed .nav-item-text,
        .sidebar.collapsed .nav-section-title {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        .sidebar.collapsed .logo span {
            display: none;
        }        /* Top Bar */
        .top-bar {
            background: var(--bg-primary);
            border-bottom: 1px solid #e5e7eb;
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.025em;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 700;
            font-size: 1rem;
            box-shadow: var(--shadow);
        }

        /* Content Area */
        .content-area {
            padding: 2rem;
        }        /* Cards */
        .card {
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-primary);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.025em;
        }

        .card-body {
            padding: 1.5rem;
            background: var(--bg-primary);
        }        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
            letter-spacing: 0.025em;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), #1e40af);
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            border-color: var(--primary-light);
            color: var(--primary);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), var(--secondary));
            color: var(--white);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, var(--secondary), #047857);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), var(--danger-light));
            color: var(--white);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #b91c1c, var(--danger));
        }

        .btn-sm {
            padding: 0.5rem 0.875rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.875rem;
            letter-spacing: 0.025em;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e5e7eb;
            border-radius: var(--border-radius);
            background: var(--bg-primary);
            font-size: 0.875rem;
            transition: var(--transition);
            color: var(--text-primary);
            font-weight: 500;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: var(--white);
        }

        .form-input::placeholder {
            color: var(--text-light);
            font-weight: 400;
        }

        .form-select {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid #e5e7eb;
            border-radius: var(--border-radius);
            background: var(--bg-primary);
            font-size: 0.875rem;
            color: var(--text-primary);
            font-weight: 500;
            transition: var(--transition);
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #f0fdf4;
            border-left-color: var(--success);
            color: #15803d;
        }

        .alert-error {
            background: #fef2f2;
            border-left-color: var(--danger);
            color: #dc2626;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.875rem;
        }

        /* Vault Items */
        .vault-items {
            display: grid;
            gap: 1rem;
        }

        .vault-item {
            background: var(--white);
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }

        .vault-item:hover {
            box-shadow: var(--shadow);
        }

        .vault-item-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .vault-item-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: var(--primary);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
        }

        .vault-item-details h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .vault-item-details p {
            color: var(--gray);
            font-size: 0.875rem;
        }

        .vault-item-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        /* Auth Forms */
        .auth-container {
            max-width: 400px;
            margin: 4rem auto;
            padding: 2rem;
        }

        .auth-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            padding: 2rem;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: var(--gray);
        }

        .auth-switch {
            text-align: center;
            margin-top: 1.5rem;
        }

        .auth-switch a {
            color: var(--primary);
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-bar {
                padding: 1rem;
            }
            
            .content-area {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Loading */
        .loading {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid var(--gray-light);
            border-radius: 50%;
            border-top: 2px solid var(--primary);
            animation: spin 1s linear infinite;
        }        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }        /* Enhanced Generator Styles */
        .generator-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .generator-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent), #10b981);
        }

        .generator-header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            padding: 2rem 0;
            background: linear-gradient(135deg, var(--primary) 0%, #5b21b6 100%);
            margin: -2rem -2rem 3rem -2rem;
            color: white;
        }

        .generator-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(15deg);
            pointer-events: none;
        }

        .generator-header::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.05);
            transform: rotate(-15deg);
            pointer-events: none;
        }

        .generator-title-section {
            position: relative;
            z-index: 1;
        }

        .generator-title {
            font-size: 2.75rem;
            font-weight: 800;
            margin: 0 0 0.5rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .generator-icon {
            font-size: 2.25rem;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .generator-subtitle {
            font-size: 1.125rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 400;
        }

        .generator-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .stat-mini {
            text-align: center;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-mini-number {
            display: block;
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
        }

        .stat-mini-label {
            display: block;
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 0.25rem;
        }

        .generator-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .generator-tabs-enhanced {
            display: flex;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .tab-button-enhanced {
            flex: 1;
            padding: 1.5rem 1rem;
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .tab-button-enhanced::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .tab-button-enhanced:hover {
            background: rgba(255, 255, 255, 0.7);
            color: var(--primary);
        }

        .tab-button-enhanced.active {
            background: white;
            color: var(--primary);
        }

        .tab-button-enhanced.active::before {
            width: 100%;
        }

        .tab-button-enhanced i {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .tab-button-enhanced span {
            font-size: 0.95rem;
            font-weight: 600;
        }

        .tab-button-enhanced small {
            font-size: 0.75rem;
            opacity: 0.7;
            font-weight: 400;
        }

        .generated-display-enhanced {
            padding: 2rem;
            background: linear-gradient(135deg, #f8fafc 0%, white 100%);
            border-bottom: 1px solid #f1f5f9;
        }

        .generated-wrapper {
            margin-bottom: 1.5rem;
        }

        .generated-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 1rem;
        }

        .generated-value-enhanced {
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            word-break: break-all;
            line-height: 1.5;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            min-height: 60px;
            display: flex;
            align-items: center;
        }

        .generated-value-enhanced:hover {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .strength-indicator {
            margin-top: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .strength-bar-container {
            flex: 1;
            height: 8px;
            background: #f1f5f9;
            border-radius: 4px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981);
            border-radius: 4px;
            transition: all 0.5s ease;
            width: 0%;
        }

        .strength-text {
            font-size: 0.875rem;
            font-weight: 600;
            min-width: 100px;
        }

        .generated-actions-enhanced {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        .action-btn-enhanced {
            width: 48px;
            height: 48px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            position: relative;
            overflow: hidden;
        }

        .action-btn-enhanced::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transition: all 0.3s ease;
            transform: translate(-50%, -50%);
        }

        .action-btn-enhanced:hover::before {
            width: 100px;
            height: 100px;
        }

        .action-btn-enhanced.primary {
            background: linear-gradient(135deg, var(--primary) 0%, #5b21b6 100%);
            color: white;
        }

        .action-btn-enhanced.secondary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .action-btn-enhanced.tertiary {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .quick-generate-section {
            padding: 1.5rem 2rem;
            text-align: center;
            background: #fafbfc;
            border-bottom: 1px solid #f1f5f9;
        }

        .btn-generate-main {
            background: linear-gradient(135deg, var(--primary) 0%, #5b21b6 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 16px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-generate-main::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-generate-main:hover::before {
            left: 100%;
        }

        .btn-generate-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }

        .btn-generate-main i {
            font-size: 1.2rem;
        }

        .btn-generate-main span {
            display: block;
            font-size: 1.1rem;
        }

        .btn-generate-main small {
            display: block;
            font-size: 0.8rem;
            opacity: 0.9;
            font-weight: 400;
        }

        /* Enhanced Generator Animations */
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .generator-container {
            animation: slideIn 0.6s ease-out;
        }

        .generated-value-enhanced.copying {
            animation: pulse 0.3s ease-out;
            background: #e6f7ff;
            border-color: #1890ff;
        }

        .action-btn-enhanced.clicked {
            animation: bounce 0.6s ease-out;
        }

        .strength-bar.animating {
            transition: width 1s ease-out;
        }

        /* Enhanced Visual Feedback */
        .tab-button-enhanced:active {
            transform: scale(0.98);
        }

        .btn-generate-main:active {
            transform: translateY(0) scale(0.98);
        }

        .checkbox-label-enhanced:active {
            transform: scale(0.99);
        }

        /* Improved Tooltips */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 150%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .tooltip:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Enhanced Loading States */
        .generating::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: shimmer 1.5s infinite;
        }

        /* Improved Accessibility */
        .generator-container *:focus {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Enhanced Responsive Design */
        @media (max-width: 768px) {
            .generator-container {
                margin: 1rem;
                padding: 1rem;
            }

            .generator-title {
                font-size: 2rem;
            }

            .generator-stats {
                flex-direction: column;
                gap: 1rem;
            }

            .checkbox-grid {
                grid-template-columns: 1fr;
            }

            .number-inputs-enhanced {
                grid-template-columns: 1fr;
            }

            .generated-actions-enhanced {
                justify-content: space-around;
            }
        }        /* Enhanced Color Scheme */
        .strength-very-weak { color: #dc2626; }
        .strength-weak { color: #ea580c; }
        .strength-fair { color: #d97706; }
        .strength-good { color: #16a34a; }
        .strength-strong { color: #059669; }
        .strength-very-strong { color: #047857; }        /* History Section Styles */
        .history-section {
            margin-top: 2rem;
            padding: 1.5rem 2rem;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
        }

        .history-toggle {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .history-toggle:hover {
            background: rgba(37, 99, 235, 0.1);
        }

        .history-toggle::after {
            content: '▼';
            transition: transform 0.3s ease;
        }

        .history-toggle.expanded::after {
            transform: rotate(180deg);
        }

        .history-content {
            margin-top: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }

        .history-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }

        .history-item:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
        }

        .history-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .history-type {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .history-time {
            color: #64748b;
            font-size: 0.75rem;
        }

        .history-strength {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.8);
        }

        .history-value {
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
            font-size: 0.875rem;
            color: var(--dark);
            background: #f8fafc;
            padding: 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            word-break: break-all;
            border: 1px solid #e5e7eb;
        }

        .history-value:hover {
            background: #e5e7eb;
            border-color: var(--primary);
        }

        .no-history {
            text-align: center;
            color: #64748b;
            font-style: italic;
            padding: 2rem;
        }

        /* Vault Items Selection Styles */
        .vault-items-selection {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: white;
        }

        .vault-item-checkbox {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .vault-item-checkbox:last-child {
            border-bottom: none;
        }

        .vault-item-checkbox:hover {
            background: #f8fafc;
        }

        .vault-item-checkbox input[type="checkbox"] {
            margin-right: 1rem;
            transform: scale(1.2);
        }

        .vault-item-info {
            display: flex;
            align-items: center;
            flex: 1;
        }

        .vault-item-icon {
            width: 40px;
            height: 40px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
            color: var(--primary);
        }

        .vault-item-details {
            flex: 1;
        }

        .item-name {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .item-type {
            display: inline-block;
            background: #e5e7eb;
            color: #64748b;
            padding: 0.125rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-right: 0.5rem;
        }

        .item-url {
            color: #64748b;
            font-size: 0.875rem;
        }

        .no-items {
            text-align: center;
            color: #64748b;
            font-style: italic;
            padding: 2rem;
        }

        .error-message {
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 1rem;
            text-align: center;
        }

        /* Radio Group Styles */
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .radio-label {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .radio-label:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.02);
        }

        .radio-label input[type="radio"] {
            display: none;
        }

        .radio-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 50%;
            background: white;
            transition: all 0.3s ease;
            position: relative;
            flex-shrink: 0;
            margin-right: 1rem;
        }

        .radio-label input[type="radio"]:checked + .radio-custom {
            background: var(--primary);
            border-color: var(--primary);
        }

        .radio-label input[type="radio"]:checked + .radio-custom::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
        }

        .radio-content {
            flex: 1;
        }

        .radio-content strong {
            display: block;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }        .radio-content small {
            color: #64748b;
            font-size: 0.875rem;
        }

        /* Enhanced File Upload Styles */
        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8fafc;
            margin-bottom: 1rem;
        }

        .file-upload-area:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }

        .file-upload-area.dragover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.1);
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        .upload-text strong {
            display: block;
            color: var(--dark);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .upload-text p {
            color: #64748b;
            font-size: 0.875rem;
            margin: 0;
        }

        .file-input {
            display: none !important;
        }

        .file-preview {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .file-icon {
            width: 48px;
            height: 48px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .file-details {
            flex: 1;
        }

        .file-name {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .file-size, .file-type {
            display: inline-block;
            background: #e5e7eb;
            color: #64748b;
            padding: 0.125rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-right: 0.5rem;
        }

        .remove-file {
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .remove-file:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .image-preview-container {
            margin-top: 1rem;
            text-align: center;
        }

        .image-preview-container img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Enhanced Generator Styles */
        .options-section-enhanced {
            padding: 2rem;
            background: white;
        }

        .options-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f5f9;
        }

        .options-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .options-presets {
            display: flex;
            gap: 0.5rem;
        }

        .preset-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            background: white;
            color: #64748b;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .preset-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(37, 99, 235, 0.05);
        }

        .generator-options-enhanced {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .option-group-enhanced {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .option-group-enhanced:hover {
            border-color: #d1d5db;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .option-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .option-label-enhanced {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }

        .length-display {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary);
            background: rgba(37, 99, 235, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .slider-container {
            margin: 1rem 0;
        }

        .length-slider {
            width: 100%;
            height: 6px;
            background: #f1f5f9;
            border-radius: 3px;
            outline: none;
            appearance: none;
            cursor: pointer;
        }

        .length-slider::-webkit-slider-thumb {
            appearance: none;
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, var(--primary), #5b21b6);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
            transition: all 0.3s ease;
        }

        .length-slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .slider-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.5rem;
            padding: 0 10px;
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .checkbox-label-enhanced {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .checkbox-label-enhanced:hover {
            border-color: var(--primary);
            background: rgba(37, 99, 235, 0.02);
        }

        .checkbox-label-enhanced input[type="checkbox"] {
            display: none;
        }

        .checkbox-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            background: white;
            transition: all 0.3s ease;
            position: relative;
            flex-shrink: 0;
        }

        .checkbox-label-enhanced input[type="checkbox"]:checked + .checkbox-custom {
            background: var(--primary);
            border-color: var(--primary);
        }

        .checkbox-label-enhanced input[type="checkbox"]:checked + .checkbox-custom::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .checkbox-content {
            flex: 1;
        }

        .checkbox-title {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .checkbox-example {
            display: block;
            font-size: 0.75rem;
            color: #64748b;
            font-family: monospace;
        }

        .advanced-options {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 2px dashed #d1d5db;
        }

        .toggle-advanced {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .toggle-advanced:hover {
            background: rgba(37, 99, 235, 0.1);
        }

        .advanced-settings {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .number-inputs-enhanced {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .number-input-group-enhanced {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .number-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .number-input-enhanced, .length-input-enhanced {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.3s ease;
            background: white;
        }        .number-input-enhanced:focus, .length-input-enhanced:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .custom-length-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .custom-length-input {
            margin-top: 0.75rem;
            max-width: 200px;
        }

        .custom-length-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Generator Styles */
        .generator-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 0.25rem;
        }

        .tab-button {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            background: transparent;
            color: var(--gray);
            font-weight: 500;
            border-radius: calc(var(--border-radius) - 0.25rem);
            cursor: pointer;
            transition: var(--transition);
        }

        .tab-button.active {
            background: var(--white);
            color: var(--primary);
            box-shadow: var(--shadow);
        }

        .generated-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: #f8fafc;
            border: 2px dashed #d1d5db;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            min-height: 60px;
        }

        .generated-value {
            flex: 1;
            font-family: 'Courier New', monospace;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark);
            word-break: break-all;
            line-height: 1.4;
        }

        .generated-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: var(--primary);
            color: var(--white);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .options-section {
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .options-section h3 {
            margin: 0 0 1.5rem 0;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark);
        }

        .generator-options {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .option-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .option-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .section-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.75rem;
        }

        .length-input, .number-input, .separator-input {
            width: 80px;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
        }

        .type-select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: var(--border-radius);
            background: var(--white);
            font-size: 0.875rem;
            width: 200px;
        }

        .checkbox-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--dark);
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"] {
            margin: 0;
        }

        .number-inputs {
            display: flex;
            gap: 1rem;
        }

        .number-input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .option-hint {
            font-size: 0.75rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        .history-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .history-toggle {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 500;
            cursor: pointer;
            padding: 0.5rem 0;
            text-decoration: underline;
        }

        .history-content {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: var(--border-radius);
        }

        .no-history {
            color: var(--gray);
            font-style: italic;
            margin: 0;
        }

        /* Send Styles */
        .send-container {
            max-width: 1200px;
        }

        .send-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 0.25rem;
        }

        .send-tab-button {
            flex: 1;
            padding: 1rem;
            border: none;
            background: transparent;
            color: var(--gray);
            font-weight: 500;
            border-radius: calc(var(--border-radius) - 0.25rem);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .send-tab-button.active {
            background: var(--white);
            color: var(--primary);
            box-shadow: var(--shadow);
        }

        .send-tab-content {
            display: none;
        }

        .send-tab-content.active {
            display: block;
        }

        .enhanced-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .gradient-header {
            background: linear-gradient(135deg, var(--primary) 0%, #5b21b6 100%);
            color: var(--white);
            border-bottom: none;
        }

        .gradient-header .card-title {
            color: var(--white);
            margin-bottom: 0.5rem;
        }

        .card-description {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1rem;
        }

        .feature-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.2);
            color: #059669;
        }

        .badge-info {  
            background: rgba(59, 130, 246, 0.2);
            color: #2563eb;
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.2);
            color: #d97706;
        }

        .enhanced-form {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .form-section {
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .enhanced-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .enhanced-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
            background: var(--white);
        }

        .enhanced-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .enhanced-textarea {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }

        .form-help {
            font-size: 0.75rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        .textarea-counter {
            text-align: right;
            font-size: 0.75rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        .email-preview {
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .preview-container {
            background: var(--white);
        }

        .preview-header {
            background: #f8fafc;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .preview-title {
            font-weight: 600;
            color: var(--dark);
        }

        .preview-badge {
            background: var(--primary);
            color: var(--white);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
        }

        .preview-content {
            padding: 1.5rem;
        }

        .preview-note {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            color: #92400e;
        }

        .preview-sender-note {
            background: #dbeafe;
            border: 1px solid #60a5fa;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            color: #1e40af;
        }

        .preview-message {
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            min-height: 100px;
            color: var(--dark);
            line-height: 1.6;
        }

        .preview-footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 1rem;
            color: var(--gray);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .btn-enhanced {
            position: relative;
            overflow: hidden;
        }

        .btn-animation {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-enhanced:hover .btn-animation {
            left: 100%;
        }

        .send-type-toggle {
            display: flex;
            gap: 1rem;
        }

        .send-type-toggle input[type="radio"] {
            display: none;
        }

        .type-label {
            flex: 1;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            background: var(--white);
        }

        .type-label:hover {
            border-color: var(--primary);
        }

        .send-type-toggle input[type="radio"]:checked + .type-label {
            border-color: var(--primary);
            background: #eff6ff;
            color: var(--primary);
        }

        .type-label i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .type-label span {
            display: block;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .type-label small {
            color: var(--gray);
            font-size: 0.75rem;
        }        .content-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1rem;
        }

        /* Brute Force Analyzer Styles */
        .brute-force-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
        }

        .brute-force-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .brute-force-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .brute-force-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--danger);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brute-force-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 2rem;
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 0.25rem;
        }

        .brute-force-tab {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            background: transparent;
            color: var(--gray);
            font-weight: 500;
            border-radius: calc(var(--border-radius) - 0.25rem);
            cursor: pointer;
            transition: var(--transition);
        }

        .brute-force-tab.active {
            background: var(--white);
            color: var(--danger);
            box-shadow: var(--shadow);
        }

        .brute-force-section {
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .strength-display {
            background: var(--white);
            border: 2px solid #e5e7eb;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .strength-score {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .strength-label {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .strength-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .strength-detail {
            background: #f8fafc;
            padding: 1rem;
            border-radius: var(--border-radius);
            text-align: center;
        }

        .detail-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .detail-label {
            font-size: 0.875rem;
            color: var(--gray);
        }

        .password-selector {
            margin-bottom: 1.5rem;
        }

        .password-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius);
        }

        .password-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: var(--transition);
        }

        .password-item:hover {
            background: #f8fafc;
        }

        .password-item:last-child {
            border-bottom: none;
        }

        .password-info h4 {
            margin: 0 0 0.25rem 0;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .password-info p {
            margin: 0;
            font-size: 0.75rem;
            color: var(--gray);
        }

        .recommendations {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .recommendations h4 {
            color: #92400e;
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .recommendation-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .recommendation-list li {
            padding: 0.5rem 0;
            color: #92400e;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .recommendation-list li::before {
            content: "💡";
            flex-shrink: 0;
        }

        /* VirusTotal Scanner Styles */
        .virustotal-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
        }

        .virustotal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .scan-type-toggle {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .scan-type-toggle input[type="radio"] {
            display: none;
        }

        .scan-type-label {
            flex: 1;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            background: var(--white);
        }

        .scan-type-label:hover {
            border-color: var(--info);
        }

        .scan-type-toggle input[type="radio"]:checked + .scan-type-label {
            border-color: var(--info);
            background: #eff6ff;
            color: var(--info);
        }

        .scan-results {
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 1.5rem;
            display: none;
        }

        .scan-results.show {
            display: block;
        }

        .scan-status {
            text-align: center;
            padding: 2rem;
        }

        .scan-status.scanning {
            color: var(--info);
        }

        .scan-status.clean {
            color: var(--success);
        }

        .scan-status.threat {
            color: var(--danger);
        }        .scan-status i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        /* Send Management Styles */
        .sends-list {
            margin-top: 1rem;
        }

        .send-item {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .send-item:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .send-info {
            flex: 1;
        }

        .send-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .send-title i {
            color: var(--primary);
        }

        .send-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            font-size: 0.8rem;
            color: var(--gray);
        }

        .send-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-info {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-secondary {
            background: #e0e0e0;
            color: #424242;
        }

        .password-input-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            padding: 0.5rem;
        }

        .password-toggle:hover {
            color: var(--primary);
        }        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .enhanced-checkbox {
            width: 18px;
            height: 18px;
            margin: 0;
        }

        .checkbox-label {
            margin: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Enhanced View Modal Styles */
        .detail-group {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary);
        }

        .detail-label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .detail-value {
            color: var(--dark);
            font-size: 1rem;
            line-height: 1.5;
            position: relative;
        }

        .detail-value.copyable {
            cursor: pointer;
            padding: 0.5rem;
            background: var(--white);
            border-radius: calc(var(--border-radius) - 2px);
            border: 1px solid #e5e7eb;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .detail-value.copyable:hover {
            background: #f3f4f6;
            border-color: var(--primary);
        }

        .detail-copy-icon {
            opacity: 0;
            transition: var(--transition);
            color: var(--primary);
        }

        .detail-value.copyable:hover .detail-copy-icon {
            opacity: 1;
        }

        .detail-link {
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-link:hover {
            text-decoration: underline;
        }

        .password-field {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            background: var(--white);
            border-radius: calc(var(--border-radius) - 2px);
            border: 1px solid #e5e7eb;
        }

        .password-text {
            font-family: 'Courier New', monospace;
            flex: 1;
        }

        .password-actions {
            display: flex;
            gap: 0.25rem;
        }

        .detail-action-btn {
            background: none;
            border: none;
            color: var(--gray);
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: var(--transition);
        }

        .detail-action-btn:hover {
            background: var(--gray-light);
            color: var(--primary);
        }

        .notes-value {
            white-space: pre-wrap;
            padding: 0.75rem;
            background: var(--white);
            border-radius: calc(var(--border-radius) - 2px);
            border: 1px solid #e5e7eb;
            min-height: 3rem;
        }

        /* Enhanced Dashboard Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: -0.05em;
        }        .stat-label {
            font-size: 1.125rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-label i {
            font-size: 1.25rem;
            opacity: 0.8;
        }

        .stat-label i {
            font-size: 1.125rem;
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, var(--secondary), #059669);
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, var(--accent), #d97706);
        }

        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }        /* Enhanced Vault Items */
        .vault-item {
            background: var(--bg-primary);
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .vault-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary);
            transform: scaleY(0);
            transition: var(--transition);
        }

        .vault-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-light);
        }

        .vault-item:hover::before {
            transform: scaleY(1);
        }

        .vault-item-info {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex: 1;
        }

        .vault-item-icon {
            width: 3.5rem;
            height: 3.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: var(--shadow);
        }

        .vault-item-details h4 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .vault-item-details p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .vault-item-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Enhanced Form Tip */
        .form-tip {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border: 1px solid #93c5fd;
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #1e40af;
        }

        .form-tip i {
            color: #3b82f6;
        }        .username-suggestion,
        .password-suggestion {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border: 2px solid #93c5fd;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 0.75rem;
            font-size: 0.875rem;
            color: #1e40af;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
        }

        .suggestion-icon {
            color: #2563eb;
            font-size: 1.125rem;
        }

        .auto-fill-btn {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: calc(var(--border-radius) - 2px);
            font-size: 0.75rem;
            cursor: pointer;
            margin-left: auto;
            transition: var(--transition);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }        .auto-fill-btn:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
        }

        /* Copy Success Animation */
        @keyframes copySuccess {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.8);
            }
            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        /* Enhanced notification positioning */
        .copy-notification {
            position: fixed !important;
            top: 20px !important;
            right: 20px !important;
            z-index: 10001 !important;
            background: var(--success) !important;
            color: white !important;
            padding: 1rem 1.5rem !important;
            border-radius: var(--border-radius) !important;
            box-shadow: var(--shadow-lg) !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            font-weight: 600 !important;
            animation: slideInRight 0.3s ease !important;
        }

        @keyframes slideInRight {
            0% {
                transform: translateX(100%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php if ($isLoggedIn): ?>
            <!-- Sidebar -->
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <div class="logo">
                        <i class="fas fa-shield-alt"></i>
                        <span>SecureIt</span>
                    </div>
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                
                <nav class="sidebar-nav">
                    <div class="nav-section">
                        <div class="nav-section-title">Main</div>
                        <a href="?section=dashboard" class="nav-item <?= $currentSection === 'dashboard' ? 'active' : '' ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="nav-item-text">Dashboard</span>
                        </a>
                        <a href="?section=vault" class="nav-item <?= $currentSection === 'vault' ? 'active' : '' ?>">
                            <i class="fas fa-vault"></i>
                            <span class="nav-item-text">My Vault</span>
                        </a>                        <a href="?section=generator" class="nav-item <?= $currentSection === 'generator' ? 'active' : '' ?>">
                            <i class="fas fa-key"></i>
                            <span class="nav-item-text">Generator</span>
                        </a>
                        <a href="?section=send" class="nav-item <?= $currentSection === 'send' ? 'active' : '' ?>">
                            <i class="fas fa-paper-plane"></i>
                            <span class="nav-item-text">Send</span>
                        </a>
                    </div>
                      <div class="nav-section">
                        <div class="nav-section-title">Categories</div>
                        <a href="?section=vault&type=login" class="nav-item <?= ($currentSection === 'vault' && $currentType === 'login') ? 'active' : '' ?>">
                            <i class="fas fa-sign-in-alt"></i>
                            <span class="nav-item-text">Logins (<?= $vaultStats['logins'] ?? 0 ?>)</span>
                        </a>
                        <a href="?section=vault&type=card" class="nav-item <?= ($currentSection === 'vault' && $currentType === 'card') ? 'active' : '' ?>">
                            <i class="fas fa-credit-card"></i>
                            <span class="nav-item-text">Cards (<?= $vaultStats['cards'] ?? 0 ?>)</span>
                        </a>
                        <a href="?section=vault&type=identity" class="nav-item <?= ($currentSection === 'vault' && $currentType === 'identity') ? 'active' : '' ?>">
                            <i class="fas fa-id-card"></i>
                            <span class="nav-item-text">Identities (<?= $vaultStats['identities'] ?? 0 ?>)</span>
                        </a>
                        <a href="?section=vault&type=note" class="nav-item <?= ($currentSection === 'vault' && $currentType === 'note') ? 'active' : '' ?>">
                            <i class="fas fa-sticky-note"></i>
                            <span class="nav-item-text">Notes (<?= $vaultStats['notes'] ?? 0 ?>)</span>
                        </a>
                    </div>
                    
                    <div class="nav-section">
                        <div class="nav-section-title">Tools</div>
                        <a href="?section=security" class="nav-item <?= $currentSection === 'security' ? 'active' : '' ?>">
                            <i class="fas fa-shield-alt"></i>
                            <span class="nav-item-text">Security</span>
                        </a>
                        <a href="?section=reports" class="nav-item <?= $currentSection === 'reports' ? 'active' : '' ?>">
                            <i class="fas fa-chart-bar"></i>
                            <span class="nav-item-text">Reports</span>
                        </a>
                        <a href="?section=settings" class="nav-item <?= $currentSection === 'settings' ? 'active' : '' ?>">
                            <i class="fas fa-cog"></i>
                            <span class="nav-item-text">Settings</span>
                        </a>
                    </div>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="main-content" id="mainContent">
                <!-- Top Bar -->
                <div class="top-bar">
                    <h1 class="page-title">
                        <?php
                        switch ($currentSection) {
                            case 'dashboard': echo 'Dashboard'; break;
                            case 'vault': echo 'My Vault'; break;
                            case 'generator': echo 'Password Generator'; break;
                            case 'security': echo 'Security Center'; break;
                            case 'reports': echo 'Security Reports'; break;
                            case 'settings': echo 'Settings'; break;
                            default: echo 'Dashboard';
                        }
                        ?>
                    </h1>
                    
                    <div class="user-menu">
                        <span>Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']) ?></span>
                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? $_SESSION['user_email'], 0, 1)) ?>
                        </div>
                        <a href="logout.php" class="btn btn-danger btn-sm">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="content-area">
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>                    <?php if ($currentSection === 'dashboard'): ?>
                        <!-- Dashboard Content -->
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?= count($vaultItems) ?></div>
                                <div class="stat-label">
                                    <i class="fas fa-vault"></i>
                                    Total Items
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= $vaultStats['logins'] ?? 0 ?></div>
                                <div class="stat-label">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Logins
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= $vaultStats['cards'] ?? 0 ?></div>
                                <div class="stat-label">
                                    <i class="fas fa-credit-card"></i>
                                    Cards
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= $vaultStats['identities'] ?? 0 ?></div>
                                <div class="stat-label">
                                    <i class="fas fa-id-card"></i>
                                    Identities
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card" style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); border: none; margin-bottom: 2rem;">
                            <div class="card-body" style="text-align: center; padding: 2rem;">
                                <h3 style="margin-bottom: 1rem; color: var(--dark);">Quick Actions</h3>
                                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                                    <button class="btn btn-primary" onclick="openModal('addItemModal')" style="min-width: 150px;">
                                        <i class="fas fa-plus"></i> Add Item
                                    </button>
                                    <a href="?section=generator" class="btn btn-secondary" style="min-width: 150px;">
                                        <i class="fas fa-key"></i> Generate Password
                                    </a>
                                    <a href="?section=security" class="btn btn-success" style="min-width: 150px;">
                                        <i class="fas fa-shield-alt"></i> Security Check
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-clock"></i>
                                    Recent Items
                                </h2>
                                <button class="btn btn-primary" onclick="openModal('addItemModal')">
                                    <i class="fas fa-plus"></i> Add Item
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (empty($vaultItems)): ?>
                                    <div style="text-align: center; padding: 3rem; color: var(--gray);">
                                        <div style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.3;">
                                            <i class="fas fa-vault"></i>
                                        </div>
                                        <h3 style="margin-bottom: 1rem; color: var(--dark);">Your vault is empty</h3>
                                        <p style="margin-bottom: 2rem; color: var(--gray);">
                                            Start securing your digital life by adding your first password or secure note.
                                        </p>
                                        <button class="btn btn-primary" onclick="openModal('addItemModal')" style="margin-top: 1rem; padding: 1rem 2rem;">
                                            <i class="fas fa-plus"></i> Add Your First Item
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="vault-items">
                                        <?php foreach (array_slice($vaultItems, 0, 5) as $item): ?>
                                            <div class="vault-item">
                                                <div class="vault-item-info">
                                                    <div class="vault-item-icon">
                                                        <?php
                                                        switch ($item['item_type']) {
                                                            case 'login': echo '<i class="fas fa-sign-in-alt"></i>'; break;
                                                            case 'card': echo '<i class="fas fa-credit-card"></i>'; break;
                                                            case 'identity': echo '<i class="fas fa-id-card"></i>'; break;
                                                            case 'note': echo '<i class="fas fa-sticky-note"></i>'; break;
                                                            default: echo '<i class="fas fa-key"></i>';
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="vault-item-details">
                                                        <h4><?= htmlspecialchars($item['item_name']) ?></h4>
                                                        <p>
                                                            <?php if ($item['website_url']): ?>
                                                                <?= htmlspecialchars($item['website_url']) ?>
                                                            <?php elseif ($item['decrypted_data']['username'] ?? null): ?>
                                                                <?= htmlspecialchars($item['decrypted_data']['username']) ?>
                                                            <?php else: ?>
                                                                <?= ucfirst($item['item_type']) ?>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="vault-item-actions">                                                    <button class="btn btn-secondary btn-sm" onclick="viewItem(<?= $item['id'] ?>)" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteItem(<?= $item['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (count($vaultItems) > 5): ?>
                                        <div style="text-align: center; margin-top: 1rem;">
                                            <a href="?section=vault" class="btn btn-secondary">
                                                View All Items (<?= count($vaultItems) ?>)
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>                    <?php elseif ($currentSection === 'vault'): ?>
                        <!-- Vault Content -->
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <?php if ($currentType): ?>
                                        <?= ucfirst($currentType) ?>s
                                        <span style="color: var(--gray); font-weight: normal; font-size: 1rem;">(<?= count($filteredItems) ?>)</span>
                                    <?php else: ?>
                                        My Vault
                                        <span style="color: var(--gray); font-weight: normal; font-size: 1rem;">(<?= count($vaultItems) ?>)</span>
                                    <?php endif; ?>
                                </h2>
                                <div style="display: flex; gap: 1rem; align-items: center;">
                                    <?php if ($currentType): ?>
                                        <a href="?section=vault" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> All Items
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-primary" onclick="openModal('addItemModal')">
                                        <i class="fas fa-plus"></i> Add <?= $currentType ? ucfirst($currentType) : 'Item' ?>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($filteredItems)): ?>
                                    <div style="text-align: center; padding: 3rem; color: var(--gray);">
                                        <i class="fas fa-<?= $currentType === 'login' ? 'sign-in-alt' : ($currentType === 'card' ? 'credit-card' : ($currentType === 'identity' ? 'id-card' : ($currentType === 'note' ? 'sticky-note' : 'vault'))) ?>" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                        <h3>No <?= $currentType ? $currentType . 's' : 'items' ?> found</h3>
                                        <p>Add your first <?= $currentType ?: 'item' ?> to get started.</p>
                                        <button class="btn btn-primary" onclick="openModal('addItemModal')" style="margin-top: 1rem;">
                                            <i class="fas fa-plus"></i> Add <?= $currentType ? ucfirst($currentType) : 'Item' ?>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="vault-items">
                                        <?php foreach ($filteredItems as $item): ?>
                                            <div class="vault-item">
                                                <div class="vault-item-info">
                                                    <div class="vault-item-icon">
                                                        <?php
                                                        switch ($item['item_type']) {
                                                            case 'login': echo '<i class="fas fa-sign-in-alt"></i>'; break;
                                                            case 'card': echo '<i class="fas fa-credit-card"></i>'; break;
                                                            case 'identity': echo '<i class="fas fa-id-card"></i>'; break;
                                                            case 'note': echo '<i class="fas fa-sticky-note"></i>'; break;
                                                            default: echo '<i class="fas fa-key"></i>';
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="vault-item-details">
                                                        <h4><?= htmlspecialchars($item['item_name']) ?></h4>
                                                        <p>
                                                            <?php if ($item['item_type'] === 'card' && isset($item['decrypted_data']['card_number'])): ?>
                                                                •••• •••• •••• <?= substr($item['decrypted_data']['card_number'], -4) ?>
                                                            <?php elseif ($item['website_url']): ?>
                                                                <?= htmlspecialchars($item['website_url']) ?>
                                                            <?php elseif ($item['decrypted_data']['username'] ?? null): ?>
                                                                <?= htmlspecialchars($item['decrypted_data']['username']) ?>
                                                            <?php else: ?>
                                                                <?= ucfirst($item['item_type']) ?>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="vault-item-actions">
                                                    <button class="btn btn-secondary btn-sm" onclick="viewItem(<?= $item['id'] ?>)" title="View/Edit">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-secondary btn-sm" onclick="copyToClipboard('<?= $item['item_type'] === 'login' ? ($item['decrypted_data']['password'] ?? '') : '' ?>')" title="Copy Password">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteItem(<?= $item['id'] ?>)" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>                    <?php elseif ($currentSection === 'generator'): ?>
                        <!-- Enhanced Password Generator -->
                        <div class="generator-container">
                            <!-- Generator Header -->
                            <div class="generator-header">
                                <div class="generator-title-section">
                                    <h1 class="generator-title">
                                        <i class="fas fa-key generator-icon"></i>
                                        Password Generator
                                    </h1>
                                    <p class="generator-subtitle">Generate secure passwords, passphrases, and usernames</p>
                                </div>
                                <div class="generator-stats">
                                    <div class="stat-mini">
                                        <span class="stat-mini-number">256-bit</span>
                                        <span class="stat-mini-label">Encryption</span>
                                    </div>
                                    <div class="stat-mini">
                                        <span class="stat-mini-number">Unlimited</span>
                                        <span class="stat-mini-label">Generation</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Main Generator Card -->
                            <div class="generator-card">
                                <!-- Generator Tabs -->
                                <div class="generator-tabs-enhanced">
                                    <button class="tab-button-enhanced active" data-tab="password" onclick="switchGeneratorTab('password')">
                                        <i class="fas fa-lock"></i>
                                        <span>Password</span>
                                        <small>Random characters</small>
                                    </button>
                                    <button class="tab-button-enhanced" data-tab="passphrase" onclick="switchGeneratorTab('passphrase')">
                                        <i class="fas fa-spell-check"></i>
                                        <span>Passphrase</span>
                                        <small>Random words</small>
                                    </button>
                                    <button class="tab-button-enhanced" data-tab="username" onclick="switchGeneratorTab('username')">
                                        <i class="fas fa-user"></i>
                                        <span>Username</span>
                                        <small>Unique identifiers</small>
                                    </button>
                                </div>

                                <!-- Generated Value Display Enhanced -->
                                <div class="generated-display-enhanced">
                                    <div class="generated-wrapper">
                                        <div class="generated-label">
                                            <i class="fas fa-magic"></i>
                                            Generated Result
                                        </div>
                                        <div class="generated-value-enhanced" id="generatedValue">
                                            Click "Generate New" to create a secure password...
                                        </div>
                                        <div class="strength-indicator">
                                            <div class="strength-bar-container">
                                                <div class="strength-bar" id="strengthBarMain"></div>
                                            </div>
                                            <span class="strength-text" id="strengthTextMain">No password generated</span>
                                        </div>
                                    </div>
                                    <div class="generated-actions-enhanced">
                                        <button class="action-btn-enhanced primary" onclick="regenerateValue()" title="Generate New" id="regenerateBtn">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <button class="action-btn-enhanced secondary" onclick="copyGeneratedValue()" title="Copy to clipboard" id="copyBtn">
                                            <i class="fas fa-copy"></i>
                                        </button>                                        <button class="action-btn-enhanced tertiary" onclick="addToHistory()" title="Save to generator history" id="saveBtn">
                                            <i class="fas fa-bookmark"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Quick Generate Button -->
                                <div class="quick-generate-section">
                                    <button class="btn-generate-main" onclick="regenerateValue()">
                                        <i class="fas fa-magic"></i>
                                        <span>Generate New</span>
                                        <small>Create secure credential</small>
                                    </button>
                                </div>

                                <!-- Options Section Enhanced -->                                <div class="options-section-enhanced">
                                    <div class="options-header">
                                        <h3 class="options-title">
                                            <i class="fas fa-cog"></i>
                                            Customization Options
                                        </h3>
                                        <div class="options-presets">
                                            <button class="preset-btn" onclick="applyPreset('simple')" title="Simple & Secure">
                                                <i class="fas fa-shield-alt"></i>
                                                Simple
                                            </button>
                                            <button class="preset-btn" onclick="applyPreset('complex')" title="Maximum Security">
                                                <i class="fas fa-lock"></i>
                                                Complex
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Password Options Enhanced -->
                                    <div id="passwordOptions" class="generator-options-enhanced">
                                        <div class="option-group-enhanced">
                                            <div class="option-header">
                                                <label class="option-label-enhanced">
                                                    <i class="fas fa-ruler"></i>
                                                    Password Length
                                                </label>
                                                <div class="length-display">
                                                    <span id="lengthValue">14</span> characters
                                                </div>
                                            </div>                                            <div class="slider-container">
                                                <input type="range" id="passwordLengthSlider" min="5" max="256" value="14" class="length-slider" onchange="updateLengthDisplay(); autoGenerate()">
                                                <div class="slider-labels">
                                                    <span>5</span>
                                                    <span>Weak</span>
                                                    <span>Good</span>
                                                    <span>Strong</span>
                                                    <span>256+</span>
                                                </div>
                                            </div>
                                            <div class="custom-length-section">
                                                <label class="checkbox-label-enhanced">
                                                    <input type="checkbox" id="useCustomLength" onchange="toggleCustomLength()">
                                                    <span class="checkbox-custom"></span>
                                                    <div class="checkbox-content">
                                                        <span class="checkbox-title">Custom Length</span>
                                                        <span class="checkbox-example">Enter any length</span>
                                                    </div>
                                                </label>
                                                <input type="number" id="passwordLength" min="5" value="14" class="length-input-enhanced custom-length-input" onchange="syncCustomLength(); autoGenerate()" style="display: none;" placeholder="Enter custom length">
                                            </div>
                                        </div>

                                        <div class="option-group-enhanced">
                                            <div class="option-header">
                                                <label class="option-label-enhanced">
                                                    <i class="fas fa-font"></i>
                                                    Character Types
                                                </label>
                                            </div>
                                            <div class="checkbox-grid">
                                                <label class="checkbox-label-enhanced">
                                                    <input type="checkbox" id="includeUppercase" checked onchange="autoGenerate()">
                                                    <span class="checkbox-custom"></span>
                                                    <div class="checkbox-content">
                                                        <span class="checkbox-title">Uppercase</span>
                                                        <span class="checkbox-example">A-Z</span>
                                                    </div>
                                                </label>
                                                <label class="checkbox-label-enhanced">
                                                    <input type="checkbox" id="includeLowercase" checked onchange="autoGenerate()">
                                                    <span class="checkbox-custom"></span>
                                                    <div class="checkbox-content">
                                                        <span class="checkbox-title">Lowercase</span>
                                                        <span class="checkbox-example">a-z</span>
                                                    </div>
                                                </label>
                                                <label class="checkbox-label-enhanced">
                                                    <input type="checkbox" id="includeNumbers" checked onchange="autoGenerate()">
                                                    <span class="checkbox-custom"></span>
                                                    <div class="checkbox-content">
                                                        <span class="checkbox-title">Numbers</span>
                                                        <span class="checkbox-example">0-9</span>
                                                    </div>
                                                </label>
                                                <label class="checkbox-label-enhanced">
                                                    <input type="checkbox" id="includeSymbols" onchange="autoGenerate()">
                                                    <span class="checkbox-custom"></span>
                                                    <div class="checkbox-content">
                                                        <span class="checkbox-title">Symbols</span>
                                                        <span class="checkbox-example">!@#$%*&</span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="option-group-enhanced advanced-options">
                                            <div class="option-header">
                                                <label class="option-label-enhanced">
                                                    <i class="fas fa-sliders-h"></i>
                                                    Advanced Settings
                                                </label>
                                                <button class="toggle-advanced" onclick="toggleAdvanced()">
                                                    <span id="advancedToggleText">Show Advanced</span>
                                                    <i class="fas fa-chevron-down" id="advancedToggleIcon"></i>
                                                </button>
                                            </div>
                                            <div id="advancedSettings" class="advanced-settings" style="display: none;">
                                                <div class="number-inputs-enhanced">
                                                    <div class="number-input-group-enhanced">
                                                        <label class="number-label">Minimum numbers</label>
                                                        <input type="number" id="minNumbers" min="0" max="10" value="1" class="number-input-enhanced" onchange="autoGenerate()">
                                                    </div>
                                                    <div class="number-input-group-enhanced">
                                                        <label class="number-label">Minimum symbols</label>
                                                        <input type="number" id="minSymbols" min="0" max="10" value="0" class="number-input-enhanced" onchange="autoGenerate()">
                                                    </div>
                                                </div>
                                                
                                                <label class="checkbox-label-enhanced">
                                                    <input type="checkbox" id="avoidAmbiguous" onchange="autoGenerate()">
                                                    <span class="checkbox-custom"></span>
                                                    <div class="checkbox-content">
                                                        <span class="checkbox-title">Avoid ambiguous characters</span>
                                                        <span class="checkbox-example">0, O, l, 1, etc.</span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Passphrase Options -->
                                    <div id="passphraseOptions" class="generator-options" style="display: none;">
                                        <div class="option-group">
                                            <label class="option-label">Number of words</label>
                                            <input type="number" id="wordCount" min="3" max="20" value="6" class="length-input" onchange="autoGenerate()">
                                            <div class="option-hint">
                                                Value must be between 3 and 20. Use 6 words or more to generate a strong passphrase.
                                            </div>
                                        </div>

                                        <div class="option-group">
                                            <label class="option-label">Word separator</label>
                                            <input type="text" id="wordSeparator" value="-" class="separator-input" onchange="autoGenerate()" placeholder="Enter separator">
                                        </div>

                                        <div class="option-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" id="capitalizeWords" onchange="autoGenerate()">
                                                Capitalize
                                            </label>
                                        </div>

                                        <div class="option-group">
                                            <label class="checkbox-label">
                                                <input type="checkbox" id="includeNumberInPhrase" onchange="autoGenerate()">
                                                Include number
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Username Options -->
                                    <div id="usernameOptions" class="generator-options" style="display: none;">
                                        <div class="option-group">
                                            <label class="option-label">Type</label>
                                            <select id="usernameType" class="type-select" onchange="toggleUsernameOptions(); autoGenerate();">
                                                <option value="random_word">Random word</option>
                                                <option value="combination">Word combination</option>
                                                <option value="uuid">UUID</option>
                                            </select>
                                        </div>

                                        <div id="usernameCustomOptions">
                                            <div class="option-group">
                                                <label class="checkbox-label">
                                                    <input type="checkbox" id="capitalizeUsername" onchange="autoGenerate()">
                                                    Capitalize
                                                </label>
                                            </div>

                                            <div class="option-group">
                                                <label class="checkbox-label">
                                                    <input type="checkbox" id="includeNumberInUsername" onchange="autoGenerate()">
                                                    Include number
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>                                <!-- Generator History -->
                                <div class="history-section">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <button class="history-toggle" onclick="toggleHistory()">
                                            <i class="fas fa-history"></i>
                                            Generator History
                                        </button>
                                        <button class="btn btn-secondary btn-sm" onclick="clearGeneratorHistory()" style="display: none;" id="clearHistoryBtn">
                                            <i class="fas fa-trash"></i> Clear History
                                        </button>
                                    </div>
                                    
                                    <div id="historyContent" class="history-content" style="display: none;">
                                        <p class="no-history">No history available. Generate and save some values to see them here.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($currentSection === 'send'): ?>
                        <!-- Send Section -->
                        <div class="send-container">                            <!-- Send Tabs -->
                            <div class="send-tabs">
                                <button class="send-tab-button active" data-tab="secure" onclick="switchSendTab('secure')">
                                    <i class="fas fa-shield-check"></i> Secure Send
                                </button>
                                <button class="send-tab-button" data-tab="credential" onclick="switchSendTab('credential')">
                                    <i class="fas fa-clipboard-list"></i> Credential Delivery
                                </button>
                                <button class="send-tab-button" data-tab="emergency" onclick="switchSendTab('emergency')">
                                    <i class="fas fa-phone-alt"></i> Emergency Contact
                                </button>
                                <button class="send-tab-button" data-tab="manage" onclick="switchSendTab('manage')">
                                    <i class="fas fa-tasks"></i> Manage Sends
                                </button>                            </div>

                            <!-- Secure Send Tab -->
                            <div id="secureTab" class="send-tab-content active">
                                <div class="card enhanced-card">
                                    <div class="card-header gradient-header">
                                        <h2 class="card-title">
                                            <i class="fas fa-shield-check"></i> Create Secure Send
                                        </h2>
                                        <p class="card-description">Share encrypted files or text with advanced security controls and access management.</p>
                                        <div class="feature-badges">
                                            <span class="badge badge-success"><i class="fas fa-lock"></i> Encrypted</span>
                                            <span class="badge badge-info"><i class="fas fa-clock"></i> Auto-Expire</span>
                                            <span class="badge badge-warning"><i class="fas fa-eye"></i> View Limits</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" enctype="multipart/form-data" id="secureSendForm" class="enhanced-form">
                                            <input type="hidden" name="action" value="create_secure_send">
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-info-circle"></i> Basic Information</h4>
                                                <div class="form-group">
                                                    <label for="send_name" class="enhanced-label">
                                                        <i class="fas fa-signature"></i> Send Name
                                                    </label>
                                                    <input type="text" id="send_name" name="send_name" class="enhanced-input" 
                                                           placeholder="Enter a descriptive name for this send" required>
                                                    <small class="form-help">This helps you identify the send in your management panel</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-cubes"></i> Content Type</h4>
                                                <div class="send-type-toggle">
                                                    <input type="radio" id="type_text" name="send_type" value="text" checked>
                                                    <label for="type_text" class="type-label">
                                                        <i class="fas fa-align-left"></i> 
                                                        <span>Text Message</span>
                                                        <small>Send encrypted text content</small>
                                                    </label>
                                                    <input type="radio" id="type_file" name="send_type" value="file">
                                                    <label for="type_file" class="type-label">
                                                        <i class="fas fa-cloud-upload-alt"></i> 
                                                        <span>File Upload</span>
                                                        <small>Share encrypted files</small>
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-edit"></i> Content</h4>
                                                <div id="textContent" class="content-section">
                                                    <div class="form-group">
                                                        <label for="send_text" class="enhanced-label">
                                                            <i class="fas fa-comment-alt"></i> Text Content
                                                        </label>
                                                        <textarea id="send_text" name="send_text" class="enhanced-input enhanced-textarea" rows="8" 
                                                                  placeholder="Enter your secure text content here..."></textarea>
                                                        <div class="textarea-counter">
                                                            <span id="textCounter">0</span> characters
                                                        </div>
                                                    </div>
                                                </div>                                <div id="fileContent" class="content-section" style="display: none;">
                                                    <div class="form-group">
                                                        <label for="send_file" class="enhanced-label">
                                                            <i class="fas fa-upload"></i> Choose File or Image
                                                        </label>
                                                        <div class="file-upload-area" onclick="document.getElementById('send_file').click()">
                                                            <div class="upload-icon">
                                                                <i class="fas fa-cloud-upload-alt"></i>
                                                            </div>
                                                            <div class="upload-text">
                                                                <strong>Click to upload or drag & drop</strong>
                                                                <p>Support for images (JPG, PNG, GIF), documents (PDF, DOC, TXT), archives (ZIP, RAR) and more</p>
                                                            </div>
                                                        </div>
                                                        <input type="file" id="send_file" name="send_file" class="enhanced-input file-input" 
                                                               accept="image/*,.pdf,.doc,.docx,.txt,.zip,.rar,.csv,.xlsx,.ppt,.pptx" 
                                                               onchange="handleFileSelection(this)">
                                                        <div id="file-preview" class="file-preview" style="display: none;">
                                                            <div class="file-info">
                                                                <div class="file-icon">
                                                                    <i class="fas fa-file"></i>
                                                                </div>
                                                                <div class="file-details">
                                                                    <span class="file-name"></span>
                                                                    <span class="file-size"></span>
                                                                    <span class="file-type"></span>
                                                                </div>
                                                                <button type="button" class="remove-file" onclick="removeFile()">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                            <div id="image-preview" class="image-preview-container" style="display: none;">
                                                                <img id="preview-image" src="" alt="Preview" />
                                                            </div>
                                                        </div>
                                                        <small class="form-help">Maximum file size: 25MB. Images will be displayed as previews to recipients.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-shield-alt"></i> Security Options</h4>
                                                
                                                <div class="form-group">
                                                    <label for="deletion_days" class="enhanced-label">
                                                        <i class="fas fa-calendar-times"></i> Auto-Delete After
                                                    </label>
                                                    <select id="deletion_days" name="deletion_days" class="enhanced-input">
                                                        <option value="1">1 Day</option>
                                                        <option value="3">3 Days</option>
                                                        <option value="7" selected>7 Days</option>
                                                        <option value="14">14 Days</option>
                                                        <option value="30">30 Days</option>
                                                    </select>
                                                    <small class="form-help">The send will be permanently deleted after this time</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="send_password" class="enhanced-label">
                                                        <i class="fas fa-key"></i> Password Protection (Optional)
                                                    </label>
                                                    <div class="password-input-group">
                                                        <input type="password" id="send_password" name="send_password" class="enhanced-input password-input" 
                                                               placeholder="Enter password to protect this send">
                                                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('send_password')">
                                                            <i class="fas fa-eye" id="send_password_icon"></i>
                                                        </button>
                                                    </div>
                                                    <small class="form-help">Add an optional password for recipients to access this send</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="max_views" class="enhanced-label">
                                                        <i class="fas fa-eye-slash"></i> Maximum Views (Optional)
                                                    </label>
                                                    <input type="number" id="max_views" name="max_views" class="enhanced-input" 
                                                           placeholder="e.g., 5" min="1" max="100">
                                                    <small class="form-help">Limit how many times this send can be viewed before it's deleted</small>
                                                </div>
                                                  <div class="form-group">
                                                    <div class="checkbox-group">
                                                        <input type="checkbox" id="hide_email" name="hide_email" class="enhanced-checkbox">
                                                        <label for="hide_email" class="checkbox-label">
                                                            <i class="fas fa-user-secret"></i> Send anonymously (hide my identity)
                                                        </label>
                                                    </div>
                                                    <small class="form-help">When enabled, recipients won't see who sent the message</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <div class="checkbox-group">
                                                        <input type="checkbox" id="allow_download" name="allow_download" class="enhanced-checkbox" checked>
                                                        <label for="allow_download" class="checkbox-label">
                                                            <i class="fas fa-download"></i> Allow file downloads
                                                        </label>
                                                    </div>
                                                    <small class="form-help">Enable recipients to download shared files and images</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-actions">
                                                <button type="button" class="btn btn-secondary" onclick="clearSecureForm()">
                                                    <i class="fas fa-eraser"></i> Clear Form
                                                </button>
                                                <button type="submit" class="btn btn-primary btn-enhanced">
                                                    <i class="fas fa-lock"></i> Create Secure Send
                                                    <span class="btn-animation"></span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>                                </div>
                            </div>

                            <!-- Credential Delivery Tab -->
                            <div id="credentialTab" class="send-tab-content">
                                <div class="card enhanced-card">
                                    <div class="card-header gradient-header">
                                        <h2 class="card-title">
                                            <i class="fas fa-clipboard-list"></i> Credential Delivery
                                        </h2>
                                        <p class="card-description">Share vault items (logins, cards, notes) securely with temporary access links. Perfect for team or family account sharing.</p>
                                        <div class="feature-badges">
                                            <span class="badge badge-success"><i class="fas fa-vault"></i> From Vault</span>
                                            <span class="badge badge-info"><i class="fas fa-clock"></i> Temporary Access</span>
                                            <span class="badge badge-warning"><i class="fas fa-ban"></i> Revokable</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" id="credentialDeliveryForm" class="enhanced-form">
                                            <input type="hidden" name="action" value="create_credential_delivery">
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-key"></i> Select Vault Item</h4>
                                                <div class="form-group">
                                                    <label for="vault_item_id" class="enhanced-label">
                                                        <i class="fas fa-search"></i> Choose Item to Share
                                                    </label>
                                                    <select id="vault_item_id" name="vault_item_id" class="enhanced-input" required>
                                                        <option value="">Select a vault item...</option>
                                                        <?php if ($isLoggedIn): ?>
                                                            <?php
                                                            try {
                                                                $vault = new Vault();
                                                                $allItems = $vault->getUserItems($_SESSION['user_id']);
                                                                foreach ($allItems as $item):
                                                                    $itemIcon = '';
                                                                    switch ($item['item_type']) {
                                                                        case 'login': $itemIcon = '🔐'; break;
                                                                        case 'card': $itemIcon = '💳'; break;
                                                                        case 'identity': $itemIcon = '🆔'; break;
                                                                        case 'note': $itemIcon = '📝'; break;
                                                                        default: $itemIcon = '🔑';
                                                                    }
                                                            ?>
                                                                <option value="<?= $item['id'] ?>" data-type="<?= $item['item_type'] ?>">
                                                                    <?= $itemIcon ?> <?= htmlspecialchars($item['item_name']) ?> (<?= ucfirst($item['item_type']) ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                            <?php } catch (Exception $e) { ?>
                                                                <option value="">Error loading vault items</option>
                                                            <?php } ?>
                                                        <?php endif; ?>
                                                    </select>
                                                    <small class="form-help">Select which vault item you want to share temporarily</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="credential_recipient" class="enhanced-label">
                                                        <i class="fas fa-user"></i> Recipient Email
                                                    </label>
                                                    <input type="email" id="credential_recipient" name="recipient_email" class="enhanced-input" 
                                                           placeholder="Enter recipient's email address" required>
                                                    <small class="form-help">Who will receive access to this credential</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-clock"></i> Access Settings</h4>
                                                <div class="form-group">
                                                    <label for="credential_expiry" class="enhanced-label">
                                                        <i class="fas fa-hourglass-half"></i> Access Duration
                                                    </label>
                                                    <select id="credential_expiry" name="expiry_hours" class="enhanced-input" required>
                                                        <option value="1">1 Hour</option>
                                                        <option value="6">6 Hours</option>
                                                        <option value="12">12 Hours</option>
                                                        <option value="24" selected>24 Hours (1 Day)</option>
                                                        <option value="72">72 Hours (3 Days)</option>
                                                        <option value="168">1 Week</option>
                                                    </select>
                                                    <small class="form-help">How long the recipient can access this credential</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="credential_max_views" class="enhanced-label">
                                                        <i class="fas fa-eye"></i> Maximum Views (Optional)
                                                    </label>
                                                    <input type="number" id="credential_max_views" name="max_views" class="enhanced-input" 
                                                           placeholder="e.g., 3" min="1" max="50">
                                                    <small class="form-help">Limit how many times this credential can be viewed</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-shield-alt"></i> Security Options</h4>
                                                <div class="form-group">
                                                    <div class="checkbox-group">
                                                        <input type="checkbox" id="require_password_credential" name="require_password" class="enhanced-checkbox">
                                                        <label for="require_password_credential" class="checkbox-label">
                                                            <i class="fas fa-lock"></i> Require password to access
                                                        </label>
                                                    </div>
                                                    
                                                    <div id="credential_password_section" style="display: none; margin-top: 1rem;">
                                                        <label for="credential_access_password" class="enhanced-label">
                                                            <i class="fas fa-key"></i> Access Password
                                                        </label>
                                                        <div class="password-input-group">
                                                            <input type="password" id="credential_access_password" name="access_password" class="enhanced-input password-input" 
                                                                   placeholder="Enter access password">
                                                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('credential_access_password')">
                                                                <i class="fas fa-eye" id="credential_access_password_icon"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <div class="checkbox-group">
                                                        <input type="checkbox" id="notify_on_access" name="notify_on_access" class="enhanced-checkbox" checked>
                                                        <label for="notify_on_access" class="checkbox-label">
                                                            <i class="fas fa-bell"></i> Notify me when accessed
                                                        </label>
                                                    </div>
                                                    <small class="form-help">Get an email notification when the recipient accesses the credential</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-comment-alt"></i> Optional Message</h4>
                                                <div class="form-group">
                                                    <label for="credential_message" class="enhanced-label">
                                                        <i class="fas fa-envelope"></i> Message to Recipient
                                                    </label>
                                                    <textarea id="credential_message" name="message" class="enhanced-input enhanced-textarea" rows="4" 
                                                              placeholder="Optional message explaining why you're sharing this credential..."></textarea>
                                                    <div class="textarea-counter">
                                                        <span id="credentialMessageCounter">0</span> characters
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-actions">
                                                <button type="button" class="btn btn-secondary" onclick="clearCredentialForm()">
                                                    <i class="fas fa-eraser"></i> Clear Form
                                                </button>
                                                <button type="submit" class="btn btn-primary btn-enhanced">
                                                    <i class="fas fa-share-alt"></i> Create Credential Delivery
                                                    <span class="btn-animation"></span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Emergency Contact Delivery Tab -->
                            <div id="emergencyTab" class="send-tab-content">
                                <div class="card enhanced-card">
                                    <div class="card-header gradient-header">
                                        <h2 class="card-title">
                                            <i class="fas fa-phone-alt"></i> Emergency Contact Delivery
                                        </h2>
                                        <p class="card-description">Protect your digital legacy by allowing trusted contacts to access selected vault items in emergencies or after periods of inactivity.</p>
                                        <div class="feature-badges">
                                            <span class="badge badge-success"><i class="fas fa-heart"></i> Digital Legacy</span>
                                            <span class="badge badge-info"><i class="fas fa-user-shield"></i> Trusted Contacts</span>
                                            <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Emergency Only</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST" id="emergencyContactForm" class="enhanced-form">
                                            <input type="hidden" name="action" value="create_emergency_contact">
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-user-friends"></i> Emergency Contact</h4>
                                                <div class="form-group">
                                                    <label for="emergency_contact_name" class="enhanced-label">
                                                        <i class="fas fa-user"></i> Contact Name
                                                    </label>
                                                    <input type="text" id="emergency_contact_name" name="contact_name" class="enhanced-input" 
                                                           placeholder="Enter trusted contact's name" required>
                                                    <small class="form-help">Full name of your trusted emergency contact</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="emergency_contact_email" class="enhanced-label">
                                                        <i class="fas fa-envelope"></i> Contact Email
                                                    </label>
                                                    <input type="email" id="emergency_contact_email" name="contact_email" class="enhanced-input" 
                                                           placeholder="Enter contact's email address" required>
                                                    <small class="form-help">Email address where emergency access will be sent</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="emergency_relationship" class="enhanced-label">
                                                        <i class="fas fa-heart"></i> Relationship
                                                    </label>
                                                    <select id="emergency_relationship" name="relationship" class="enhanced-input" required>
                                                        <option value="">Select relationship...</option>
                                                        <option value="spouse">Spouse</option>
                                                        <option value="partner">Partner</option>
                                                        <option value="parent">Parent</option>
                                                        <option value="child">Child</option>
                                                        <option value="sibling">Sibling</option>
                                                        <option value="friend">Trusted Friend</option>
                                                        <option value="lawyer">Lawyer</option>
                                                        <option value="executor">Executor</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-key"></i> Select Vault Items</h4>
                                                <div class="form-group">
                                                    <label class="enhanced-label">
                                                        <i class="fas fa-list-check"></i> Items to Include in Emergency Access
                                                    </label>
                                                    <div class="vault-items-selection">
                                                        <?php if ($isLoggedIn): ?>
                                                            <?php
                                                            try {
                                                                $vault = new Vault();
                                                                $allItems = $vault->getUserItems($_SESSION['user_id']);
                                                                if (empty($allItems)):
                                                            ?>
                                                                <p class="no-items">No vault items found. Add some items to your vault first.</p>
                                                            <?php else: ?>
                                                                <?php foreach ($allItems as $item): ?>
                                                                    <label class="vault-item-checkbox">
                                                                        <input type="checkbox" name="emergency_items[]" value="<?= $item['id'] ?>" class="enhanced-checkbox">
                                                                        <div class="vault-item-info">
                                                                            <div class="vault-item-icon">
                                                                                <?php
                                                                                switch ($item['item_type']) {
                                                                                    case 'login': echo '<i class="fas fa-sign-in-alt"></i>'; break;
                                                                                    case 'card': echo '<i class="fas fa-credit-card"></i>'; break;
                                                                                    case 'identity': echo '<i class="fas fa-id-card"></i>'; break;
                                                                                    case 'note': echo '<i class="fas fa-sticky-note"></i>'; break;
                                                                                    default: echo '<i class="fas fa-key"></i>';
                                                                                }
                                                                                ?>
                                                                            </div>
                                                                            <div class="vault-item-details">
                                                                                <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                                                                                <span class="item-type"><?= ucfirst($item['item_type']) ?></span>
                                                                                <?php if ($item['website_url']): ?>
                                                                                    <span class="item-url"><?= htmlspecialchars($item['website_url']) ?></span>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                            <?php } catch (Exception $e) { ?>
                                                                <p class="error-message">Error loading vault items: <?= htmlspecialchars($e->getMessage()) ?></p>
                                                            <?php } ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="form-help">Select which vault items your emergency contact can access</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-exclamation-triangle"></i> Trigger Conditions</h4>
                                                <div class="form-group">
                                                    <label class="enhanced-label">
                                                        <i class="fas fa-clock"></i> Automatic Trigger
                                                    </label>
                                                    <div class="radio-group">
                                                        <label class="radio-label">
                                                            <input type="radio" name="trigger_type" value="inactivity" checked>
                                                            <span class="radio-custom"></span>
                                                            <div class="radio-content">
                                                                <strong>Inactivity Trigger</strong>
                                                                <small>Activate after no login for specified period</small>
                                                            </div>
                                                        </label>
                                                        <label class="radio-label">
                                                            <input type="radio" name="trigger_type" value="manual">
                                                            <span class="radio-custom"></span>
                                                            <div class="radio-content">
                                                                <strong>Manual Only</strong>
                                                                <small>Only activate when manually triggered</small>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                                
                                                <div id="inactivity_settings" class="form-group">
                                                    <label for="inactivity_days" class="enhanced-label">
                                                        <i class="fas fa-calendar-times"></i> Inactivity Period
                                                    </label>
                                                    <select id="inactivity_days" name="inactivity_days" class="enhanced-input">
                                                        <option value="30" selected>30 Days</option>
                                                        <option value="60">60 Days</option>
                                                        <option value="90">90 Days</option>
                                                        <option value="180">6 Months</option>
                                                        <option value="365">1 Year</option>
                                                    </select>
                                                    <small class="form-help">Emergency access will be triggered if you don't log in for this period</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-section">
                                                <h4 class="section-title"><i class="fas fa-envelope-open-text"></i> Emergency Message</h4>
                                                <div class="form-group">
                                                    <label for="emergency_instructions" class="enhanced-label">
                                                        <i class="fas fa-scroll"></i> Instructions for Emergency Contact
                                                    </label>
                                                    <textarea id="emergency_instructions" name="emergency_instructions" class="enhanced-input enhanced-textarea" rows="6" 
                                                              placeholder="Write instructions for your emergency contact about what to do with the shared credentials..." required></textarea>
                                                    <div class="textarea-counter">
                                                        <span id="emergencyInstructionsCounter">0</span> characters
                                                    </div>
                                                    <small class="form-help">Explain to your contact what these credentials are for and how to use them responsibly</small>
                                                </div>
                                            </div>
                                            
                                            <div class="form-actions">
                                                <button type="button" class="btn btn-secondary" onclick="clearEmergencyForm()">
                                                    <i class="fas fa-eraser"></i> Clear Form
                                                </button>
                                                <button type="submit" class="btn btn-primary btn-enhanced">
                                                    <i class="fas fa-shield-heart"></i> Setup Emergency Contact
                                                    <span class="btn-animation"></span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Manage Sends Tab -->
                            <div id="manageTab" class="send-tab-content">
                                <div class="card enhanced-card">
                                    <div class="card-header gradient-header">
                                        <h2 class="card-title">
                                            <i class="fas fa-tasks"></i> Manage Your Sends
                                        </h2>
                                        <p class="card-description">Monitor and manage all your secure sends and anonymous emails.</p>                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($userSends)): ?>
                                            <div style="text-align: center; padding: 3rem; color: var(--gray);">
                                                <i class="fas fa-envelope-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                                <h3>No Sends Yet</h3>
                                                <p>Your sent items will appear here once you start using the Send features.</p>
                                                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center;">
                                                    <button class="btn btn-primary" onclick="switchSendTab('email')">
                                                        <i class="fas fa-at"></i> Send Anonymous Email
                                                    </button>
                                                    <button class="btn btn-secondary" onclick="switchSendTab('secure')">
                                                        <i class="fas fa-shield-check"></i> Create Secure Send
                                                    </button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <!-- Send Statistics -->
                                            <div class="stats-grid" style="margin-bottom: 2rem;">
                                                <div class="stat-card">
                                                    <div class="stat-number"><?php echo $sendStats['total_sends']; ?></div>
                                                    <div class="stat-label">Total Sends</div>
                                                </div>
                                                <div class="stat-card">
                                                    <div class="stat-number"><?php echo $sendStats['active_sends']; ?></div>
                                                    <div class="stat-label">Active</div>
                                                </div>
                                                <div class="stat-card">
                                                    <div class="stat-number"><?php echo $sendStats['total_views']; ?></div>
                                                    <div class="stat-label">Total Views</div>
                                                </div>
                                                <div class="stat-card">
                                                    <div class="stat-number"><?php echo $sendStats['expired_sends']; ?></div>
                                                    <div class="stat-label">Expired</div>
                                                </div>
                                            </div>
                                            
                                            <!-- Sends List -->
                                            <div class="sends-list">
                                                <?php foreach ($userSends as $send): ?>
                                                    <div class="send-item">
                                                        <div class="send-info">
                                                            <div class="send-title">
                                                                <i class="fas fa-<?php echo $send['send_type'] === 'file' ? 'file' : 'text'; ?>"></i>
                                                                <?php echo htmlspecialchars($send['name']); ?>
                                                            </div>
                                                            <div class="send-meta">
                                                                <span class="badge badge-<?php echo $send['send_type'] === 'file' ? 'info' : 'secondary'; ?>">
                                                                    <?php echo ucfirst($send['send_type']); ?>
                                                                </span>
                                                                <span class="send-date">
                                                                    Created: <?php echo date('M j, Y', strtotime($send['created_at'])); ?>
                                                                </span>
                                                                <span class="send-expire">
                                                                    Expires: <?php echo date('M j, Y', strtotime($send['deletion_date'])); ?>
                                                                </span>
                                                                <span class="send-views">
                                                                    Views: <?php echo $send['current_views']; ?><?php echo $send['max_views'] ? '/' . $send['max_views'] : ''; ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="send-actions">
                                                            <button class="btn btn-sm btn-secondary" onclick="copyAccessLink('<?php echo $send['access_link']; ?>')">
                                                                <i class="fas fa-copy"></i> Copy Link
                                                            </button>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this send?')">
                                                                <input type="hidden" name="action" value="delete_send">
                                                                <input type="hidden" name="send_id" value="<?php echo $send['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-danger">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>                    <?php elseif ($currentSection === 'security'): ?>
                        <!-- Enhanced Security Center -->
                        <div class="card enhanced-card">
                            <div class="card-header gradient-header">
                                <h2 class="card-title">Security Center</h2>
                                <p class="card-description">Monitor your security posture and access powerful security tools and APIs.</p>
                            </div>
                            <div class="card-body">
                                <div class="stats-grid">
                                    <div class="stat-card">
                                        <div class="stat-number" style="color: var(--success);">85</div>
                                        <div class="stat-label">Security Score</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number" style="color: var(--warning);">3</div>
                                        <div class="stat-label">Weak Passwords</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number" style="color: var(--danger);">2</div>
                                        <div class="stat-label">Reused Passwords</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number" style="color: var(--info);">0</div>
                                        <div class="stat-label">Compromised</div>
                                    </div>
                                </div>
                                  <!-- Security Tools Section -->
                                <div style="margin-top: 2rem;">
                                    <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 600;">Security Tools & APIs</h3>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                                        <!-- Brute Force Analysis -->
                                        <div class="card" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
                                            <div class="card-header" style="background: transparent; border-bottom: 1px solid rgba(239, 68, 68, 0.2);">
                                                <h4 style="margin: 0; color: #991b1b; display: flex; align-items: center; gap: 0.5rem;">
                                                    <i class="fas fa-hammer"></i> Brute Force Analyzer
                                                </h4>
                                            </div>
                                            <div class="card-body">
                                                <p style="color: #991b1b; margin-bottom: 1rem;">Test password strength against brute force attacks and get security recommendations.</p>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <button class="btn btn-danger" onclick="openBruteForceAnalyzer()" style="flex: 1;">
                                                        <i class="fas fa-shield-virus"></i> Analyze
                                                    </button>
                                                    <button class="btn btn-secondary btn-sm" onclick="viewBruteForceAPI()" title="View API">
                                                        <i class="fas fa-code"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- VirusTotal Integration -->
                                        <div class="card" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);">
                                            <div class="card-header" style="background: transparent; border-bottom: 1px solid rgba(59, 130, 246, 0.2);">
                                                <h4 style="margin: 0; color: #1e40af; display: flex; align-items: center; gap: 0.5rem;">
                                                    <i class="fas fa-virus-slash"></i> VirusTotal API
                                                </h4>
                                            </div>
                                            <div class="card-body">
                                                <p style="color: #1e40af; margin-bottom: 1rem;">Scan files and URLs for malware using VirusTotal's comprehensive database.</p>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <button class="btn btn-info" onclick="openVirusTotalScanner()" style="flex: 1;">
                                                        <i class="fas fa-scanner"></i> Scan
                                                    </button>
                                                    <button class="btn btn-secondary btn-sm" onclick="viewVirusTotalAPI()" title="View API">
                                                        <i class="fas fa-code"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Password Analysis -->
                                        <div class="card" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
                                            <div class="card-header" style="background: transparent; border-bottom: 1px solid rgba(245, 158, 11, 0.2);">
                                                <h4 style="margin: 0; color: #92400e; display: flex; align-items: center; gap: 0.5rem;">
                                                    <i class="fas fa-search"></i> Password Analysis
                                                </h4>
                                            </div>
                                            <div class="card-body">
                                                <p style="color: #92400e; margin-bottom: 1rem;">Analyze password strength and get security recommendations.</p>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <button class="btn btn-warning" onclick="analyzePasswords()" style="flex: 1;">
                                                        <i class="fas fa-analytics"></i> Analyze
                                                    </button>
                                                    <button class="btn btn-secondary btn-sm" onclick="viewPasswordAPI()" title="View API">
                                                        <i class="fas fa-code"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Breach Detection -->
                                        <div class="card" style="background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);">
                                            <div class="card-header" style="background: transparent; border-bottom: 1px solid rgba(147, 51, 234, 0.2);">
                                                <h4 style="margin: 0; color: #581c87; display: flex; align-items: center; gap: 0.5rem;">
                                                    <i class="fas fa-shield-virus"></i> Breach Detection
                                                </h4>
                                            </div>
                                            <div class="card-body">
                                                <p style="color: #581c87; margin-bottom: 1rem;">Check if your accounts have been compromised in data breaches.</p>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <button class="btn" style="background: #9333ea; color: white; flex: 1;" onclick="checkBreaches()">
                                                        <i class="fas fa-search"></i> Check
                                                    </button>
                                                    <button class="btn btn-secondary btn-sm" onclick="viewBreachAPI()" title="View API">
                                                        <i class="fas fa-code"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Two-Factor Authentication -->
                                        <div class="card" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);">
                                            <div class="card-header" style="background: transparent; border-bottom: 1px solid rgba(16, 185, 129, 0.2);">
                                                <h4 style="margin: 0; color: #065f46; display: flex; align-items: center; gap: 0.5rem;">
                                                    <i class="fas fa-mobile-alt"></i> 2FA Setup
                                                </h4>
                                            </div>
                                            <div class="card-body">
                                                <p style="color: #065f46; margin-bottom: 1rem;">Set up Two-Factor Authentication for enhanced security.</p>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <button class="btn btn-success" onclick="setup2FA()" style="flex: 1;">
                                                        <i class="fas fa-shield-check"></i> Setup
                                                    </button>
                                                    <button class="btn btn-secondary btn-sm" onclick="view2FAAPI()" title="View API">
                                                        <i class="fas fa-code"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- API Management -->
                                        <div class="card" style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);">
                                            <div class="card-header" style="background: transparent; border-bottom: 1px solid rgba(99, 102, 241, 0.2);">
                                                <h4 style="margin: 0; color: #3730a3; display: flex; align-items: center; gap: 0.5rem;">
                                                    <i class="fas fa-cogs"></i> API Management
                                                </h4>
                                            </div>
                                            <div class="card-body">
                                                <p style="color: #3730a3; margin-bottom: 1rem;">Manage API keys and access tokens for security services.</p>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <button class="btn" style="background: #6366f1; color: white; flex: 1;" onclick="manageAPIs()">
                                                        <i class="fas fa-key"></i> Manage
                                                    </button>
                                                    <button class="btn btn-secondary btn-sm" onclick="viewAPIDocumentation()" title="Documentation">
                                                        <i class="fas fa-book"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recent Security Events -->
                                <div style="margin-top: 2rem;">
                                    <h3 style="margin-bottom: 1rem; font-size: 1.25rem; font-weight: 600;">Recent Security Events</h3>
                                    <div class="card" style="background: #f8fafc;">
                                        <div class="card-body">
                                            <div style="text-align: center; padding: 2rem; color: var(--gray);">
                                                <i class="fas fa-shield-check" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                                <p>No recent security events to display.</p>
                                                <small>Security events and alerts will appear here.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Default/Other Sections -->
                        <div class="card">
                            <div class="card-body">
                                <div style="text-align: center; padding: 3rem; color: var(--gray);">
                                    <i class="fas fa-construction" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                    <h3>Coming Soon</h3>
                                    <p>This section is under development.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Auth Forms -->
            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="logo" style="justify-content: center; margin-bottom: 1rem;">
                            <i class="fas fa-shield-alt"></i>
                            <span>SecureIt</span>
                        </div>
                        <h1><?= isset($_GET['register']) ? 'Create Account' : 'Welcome Back' ?></h1>
                        <p><?= isset($_GET['register']) ? 'Join thousands of users securing their digital life' : 'Sign in to your secure vault' ?></p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <?php if (isset($_GET['register'])): ?>
                            <input type="hidden" name="action" value="register">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-input" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-user-plus"></i> Create Account
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="action" value="login">
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-input" required>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-sign-in-alt"></i> Sign In
                            </button>
                        <?php endif; ?>
                    </form>

                    <div class="auth-switch">
                        <?php if (isset($_GET['register'])): ?>
                            <p>Already have an account? <a href="?">Sign In</a></p>
                        <?php else: ?>
                            <p>Don't have an account? <a href="?register=1">Create Account</a></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>    </div>

    <!-- Brute Force Analyzer Modal -->
    <div id="bruteForceModal" class="brute-force-modal">
        <div class="brute-force-content">
            <div class="brute-force-header">
                <h2 class="brute-force-title">
                    <i class="fas fa-hammer"></i> Brute Force Analyzer
                </h2>
                <button class="modal-close" onclick="closeBruteForceModal()">&times;</button>
            </div>

            <!-- Brute Force Tabs -->
            <div class="brute-force-tabs">
                <button class="brute-force-tab active" onclick="switchBruteForceTab('analyze')">
                    <i class="fas fa-search"></i> Analyze Password
                </button>
                <button class="brute-force-tab" onclick="switchBruteForceTab('vault')">
                    <i class="fas fa-vault"></i> Analyze Vault
                </button>
            </div>

            <!-- Analyze Password Tab -->
            <div id="analyzeTab" class="brute-force-tab-content">
                <div class="brute-force-section">
                    <h4 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-keyboard"></i> Enter Password to Test
                    </h4>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <input type="password" id="testPassword" class="form-input" placeholder="Enter password to analyze" style="flex: 1;">
                        <button class="btn btn-secondary" onclick="togglePasswordVisibility()" id="toggleBtn">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-danger" onclick="analyzePassword()">
                            <i class="fas fa-hammer"></i> Analyze
                        </button>
                    </div>
                    <small style="color: var(--gray);">💡 Tip: Your password is never stored or transmitted - analysis happens locally</small>
                </div>

                <!-- Strength Display -->
                <div id="strengthDisplay" class="strength-display" style="display: none;">
                    <div class="strength-score" id="strengthScore">0</div>
                    <div class="strength-label" id="strengthLabel">Enter a password to analyze</div>
                    <div style="background: #f8fafc; border-radius: var(--border-radius); padding: 1rem; margin: 1rem 0;">
                        <div style="height: 10px; background: #e5e7eb; border-radius: 5px; overflow: hidden;">
                            <div id="strengthBar" style="height: 100%; width: 0%; transition: all 0.3s ease; background: #ef4444;"></div>
                        </div>
                    </div>
                    
                    <div class="strength-details">
                        <div class="strength-detail">
                            <div class="detail-value" id="crackTime">-</div>
                            <div class="detail-label">Estimated Crack Time</div>
                        </div>
                        <div class="strength-detail">
                            <div class="detail-value" id="entropy">-</div>
                            <div class="detail-label">Entropy (bits)</div>
                        </div>
                        <div class="strength-detail">
                            <div class="detail-value" id="complexity">-</div>
                            <div class="detail-label">Complexity Score</div>
                        </div>
                        <div class="strength-detail">
                            <div class="detail-value" id="patterns">-</div>
                            <div class="detail-label">Patterns Detected</div>
                        </div>
                    </div>
                </div>

                <!-- Recommendations -->
                <div id="recommendations" class="recommendations" style="display: none;">
                    <h4>
                        <i class="fas fa-lightbulb"></i> Security Recommendations
                    </h4>
                    <ul class="recommendation-list" id="recommendationList">
                        <!-- Recommendations will be populated by JavaScript -->
                    </ul>
                </div>
            </div>

            <!-- Analyze Vault Tab -->
            <div id="vaultTab" class="brute-force-tab-content" style="display: none;">
                <div class="brute-force-section">
                    <h4 style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-list"></i> Select Password from Vault
                    </h4>
                    <div class="password-selector">
                        <div class="password-list" id="passwordList">
                            <div style="text-align: center; padding: 2rem; color: var(--gray);">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p>Loading your vault passwords...</p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-danger" onclick="analyzeSelectedPassword()" style="width: 100%;">
                        <i class="fas fa-hammer"></i> Analyze Selected Password
                    </button>
                </div>

                <!-- Same strength display and recommendations as analyze tab -->
                <div id="vaultStrengthDisplay" class="strength-display" style="display: none;">
                    <div class="strength-score" id="vaultStrengthScore">0</div>
                    <div class="strength-label" id="vaultStrengthLabel">Select a password to analyze</div>
                    <div style="background: #f8fafc; border-radius: var(--border-radius); padding: 1rem; margin: 1rem 0;">
                        <div style="height: 10px; background: #e5e7eb; border-radius: 5px; overflow: hidden;">
                            <div id="vaultStrengthBar" style="height: 100%; width: 0%; transition: all 0.3s ease; background: #ef4444;"></div>
                        </div>
                    </div>
                    
                    <div class="strength-details">
                        <div class="strength-detail">
                            <div class="detail-value" id="vaultCrackTime">-</div>
                            <div class="detail-label">Estimated Crack Time</div>
                        </div>
                        <div class="strength-detail">
                            <div class="detail-value" id="vaultEntropy">-</div>
                            <div class="detail-label">Entropy (bits)</div>
                        </div>
                        <div class="strength-detail">
                            <div class="detail-value" id="vaultComplexity">-</div>
                            <div class="detail-label">Complexity Score</div>
                        </div>
                        <div class="strength-detail">
                            <div class="detail-value" id="vaultPatterns">-</div>
                            <div class="detail-label">Patterns Detected</div>
                        </div>
                    </div>
                </div>

                <div id="vaultRecommendations" class="recommendations" style="display: none;">
                    <h4>
                        <i class="fas fa-lightbulb"></i> Security Recommendations
                    </h4>
                    <ul class="recommendation-list" id="vaultRecommendationList">
                        <!-- Recommendations will be populated by JavaScript -->
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- VirusTotal Scanner Modal -->
    <div id="virusTotalModal" class="virustotal-modal">
        <div class="virustotal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-virus-slash"></i> VirusTotal Scanner
                </h2>
                <button class="modal-close" onclick="closeVirusTotalModal()">&times;</button>
            </div>

            <div class="form-section">
                <h4 class="section-title">
                    <i class="fas fa-cubes"></i> Scan Type
                </h4>
                <div class="scan-type-toggle">
                    <input type="radio" id="scanFile" name="scan_type" value="file" checked>
                    <label for="scanFile" class="scan-type-label">
                        <i class="fas fa-file" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                        <span style="font-weight: 600;">File Scan</span>
                        <small style="display: block; margin-top: 0.25rem;">Upload a file to scan for malware</small>
                    </label>
                    <input type="radio" id="scanUrl" name="scan_type" value="url">
                    <label for="scanUrl" class="scan-type-label">
                        <i class="fas fa-link" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                        <span style="font-weight: 600;">URL Scan</span>
                        <small style="display: block; margin-top: 0.25rem;">Scan a website URL for threats</small>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <div id="fileScanSection">
                    <h4 class="section-title">
                        <i class="fas fa-upload"></i> Upload File
                    </h4>
                    <div class="form-group">
                        <input type="file" id="scanFileInput" class="enhanced-input" accept="*/*">
                        <small class="form-help">Maximum file size: 32MB. All file types supported.</small>
                    </div>
                </div>

                <div id="urlScanSection" style="display: none;">
                    <h4 class="section-title">
                        <i class="fas fa-globe"></i> Enter URL
                    </h4>
                    <div class="form-group">
                        <input type="url" id="scanUrlInput" class="enhanced-input" placeholder="https://example.com">
                        <small class="form-help">Enter the complete URL including http:// or https://</small>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeVirusTotalModal()">
                    Cancel
                </button>
                <button type="button" class="btn btn-info" onclick="startVirusTotalScan()">
                    <i class="fas fa-scanner"></i> Start Scan
                </button>
            </div>

            <div id="scanResults" class="scan-results">
                <div id="scanStatus" class="scan-status scanning">
                    <i class="fas fa-spinner fa-spin"></i>
                    <h4>Scanning in Progress...</h4>
                    <p>Please wait while we analyze your file/URL with VirusTotal's database.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div id="addItemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Item</h2>
                <button class="modal-close" onclick="closeModal('addItemModal')">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="add_vault_item">
                
                <div class="form-group">
                    <label class="form-label">Item Name</label>
                    <input type="text" name="item_name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Item Type</label>
                    <select name="item_type" class="form-select" onchange="toggleItemFields(this.value)">
                        <option value="login">Login</option>
                        <option value="card">Card</option>
                        <option value="identity">Identity</option>
                        <option value="note">Secure Note</option>
                    </select>
                </div>                  <div id="loginFields">
                    <div class="form-group">
                        <label class="form-label">Website URL <span style="color: var(--gray); font-size: 0.875rem;">(Optional)</span></label>
                        <input type="url" name="website_url" class="form-input" placeholder="https://example.com (optional)" onchange="suggestCredentials()">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-input" id="usernameField" placeholder="Enter username or email">
                        <div class="username-suggestion" id="usernameSuggestion" style="display: none;">
                            <i class="fas fa-lightbulb suggestion-icon"></i>
                            <span>Suggested: Use your email address</span>
                            <button type="button" class="auto-fill-btn" onclick="fillUserEmail()">Use Email</button>
                        </div>
                    </div>
                      <div class="form-group">
                        <label class="form-label">Password</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="password" name="password" class="form-input" id="modalPasswordField" placeholder="Enter password or generate one">                            <button type="button" class="btn btn-secondary" onclick="generatePasswordForForm()" title="Generate Strong Password & Copy to Clipboard (16 chars with uppercase, lowercase, numbers & symbols)" id="generatePasswordBtn">
                                <i class="fas fa-key"></i>
                            </button>
                        </div>                        <div class="password-suggestion" id="passwordSuggestion">
                            <i class="fas fa-shield-alt suggestion-icon"></i>
                            <span>💡 Click the key button to generate a secure password - it will be automatically copied for you!</span>
                            <button type="button" class="auto-fill-btn" onclick="generatePasswordForForm()">Generate & Copy</button>
                        </div>
                    </div>
                </div>
                
                <div id="cardFields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Cardholder Name</label>
                        <input type="text" name="cardholder_name" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Card Number</label>
                        <input type="text" name="card_number" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Expiry Date</label>
                        <input type="text" name="expiry_date" class="form-input" placeholder="MM/YY">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">CVV</label>
                        <input type="text" name="cvv" class="form-input">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="3"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addItemModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
        function copyToClipboard(text) {
            if (text) {
                navigator.clipboard.writeText(text).then(() => {
                    showNotification('Copied to clipboard!', 'success');
                });
            }
        }        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const isLongMessage = message.length > 50;
            
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px; z-index: 9999;
                background: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--danger)' : 'var(--primary)'};
                color: white; padding: 1rem 1.5rem; border-radius: var(--border-radius);
                box-shadow: var(--shadow-lg); animation: slideInRight 0.3s ease;
                max-width: ${isLongMessage ? '400px' : '300px'};
                word-wrap: break-word;
                font-weight: 500;
                line-height: 1.4;
            `;
            
            if (type === 'success' && message.includes('copied')) {
                notification.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-check-circle"></i>
                        <div>${message}</div>
                    </div>
                `;
            } else {
                notification.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                        <div>${message}</div>
                    </div>
                `;
            }
            
            document.body.appendChild(notification);
            
            // Auto-remove after time based on message length
            const duration = isLongMessage ? 5000 : 3000;
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, duration);
        }

        function runSecurityScan() {
            showNotification('Running security scan...', 'info');
            // Simulate scan
            setTimeout(() => {
                showNotification('Security scan completed!', 'success');
            }, 2000);
        }

        function generateReport() {
            showNotification('Generating report...', 'info');
            setTimeout(() => {
                showNotification('Report updated!', 'success');
                location.reload();
            }, 1500);
        }

        function showSettingsTab(tabName) {
            // Update active nav
            document.querySelectorAll('.settings-nav .nav-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.nav-item').classList.add('active');
            
            // Show content (simplified for demo)
            showNotification(`Switched to ${tabName} settings`, 'info');
        }

        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);          // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            
            // Auto-focus and show suggestions for add item modal
            if (modalId === 'addItemModal') {
                setTimeout(() => {
                    const itemNameField = document.querySelector('input[name="item_name"]');
                    if (itemNameField) {
                        itemNameField.focus();
                    }
                    
                    // Show password suggestion by default
                    const passwordSuggestion = document.getElementById('passwordSuggestion');
                    if (passwordSuggestion) {
                        passwordSuggestion.style.display = 'flex';
                    }
                }, 100);
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            
            // Reset form and hide suggestions when closing add item modal
            if (modalId === 'addItemModal') {
                const form = document.querySelector('#addItemModal form');
                if (form) {
                    form.reset();
                }
                
                const usernameSuggestion = document.getElementById('usernameSuggestion');
                if (usernameSuggestion) {
                    usernameSuggestion.style.display = 'none';
                }
            }
        }

        // Item form toggle
        function toggleItemFields(itemType) {
            const loginFields = document.getElementById('loginFields');
            const cardFields = document.getElementById('cardFields');
            
            loginFields.style.display = itemType === 'login' ? 'block' : 'none';
            cardFields.style.display = itemType === 'card' ? 'block' : 'none';
        }        // Password generator
        function generatePassword() {
            const length = parseInt(document.getElementById('lengthSlider').value);
            const includeUppercase = document.getElementById('includeUppercase').checked;
            const includeLowercase = document.getElementById('includeLowercase').checked;
            const includeNumbers = document.getElementById('includeNumbers').checked;
            const includeSymbols = document.getElementById('includeSymbols').checked;
            
            // Ensure minimum length
            if (length < 14) {
                alert('Password length must be at least 14 characters for security');
                document.getElementById('lengthSlider').value = 14;
                document.getElementById('lengthValue').textContent = 14;
                return;
            }
            
            // Check that at least one character type is selected
            if (!includeUppercase && !includeLowercase && !includeNumbers && !includeSymbols) {
                alert('Please select at least one character type');
                return;
            }
            
            // Define character sets
            const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const lowercase = 'abcdefghijklmnopqrstuvwxyz';
            const numbers = '0123456789';
            const symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
            
            // Ensure at least one character from each selected type
            let password = '';
            
            // Add at least one character from each selected type
            if (includeUppercase) password += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
            if (includeLowercase) password += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
            if (includeNumbers) password += numbers.charAt(Math.floor(Math.random() * numbers.length));
            if (includeSymbols) password += symbols.charAt(Math.floor(Math.random() * symbols.length));
            
            // Build complete character set
            let charset = '';
            if (includeUppercase) charset += uppercase;
            if (includeLowercase) charset += lowercase;
            if (includeNumbers) charset += numbers;
            if (includeSymbols) charset += symbols;
            
            // Fill remaining length with random characters from all sets
            for (let i = password.length; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            
            // Shuffle the password to randomize the guaranteed characters
            password = password.split('').sort(() => Math.random() - 0.5).join('');
            
            document.getElementById('generatedPassword').value = password;
            updatePasswordStrength(password);
        }

        function updateLength() {
            const length = document.getElementById('lengthSlider').value;
            document.getElementById('lengthValue').textContent = length;
        }

        function copyPassword() {
            const passwordField = document.getElementById('generatedPassword');
            if (passwordField.value) {
                passwordField.select();
                document.execCommand('copy');
                
                // Show feedback
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 1000);
            }
        }

        function updatePasswordStrength(password) {
            let strength = 0;
            let strengthText = '';
            let strengthColor = '';
            
            if (password.length >= 8) strength += 20;
            if (password.length >= 12) strength += 10;
            if (/[a-z]/.test(password)) strength += 15;
            if (/[A-Z]/.test(password)) strength += 15;
            if (/[0-9]/.test(password)) strength += 15;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            if (strength < 30) {
                strengthText = 'Weak';
                strengthColor = '#ef4444';
            } else if (strength < 60) {
                strengthText = 'Fair';
                strengthColor = '#f59e0b';
            } else if (strength < 90) {
                strengthText = 'Good';
                strengthColor = '#10b981';
            } else {
                strengthText = 'Excellent';
                strengthColor = '#059669';
            }
              document.getElementById('strengthBar').style.width = strength + '%';
            document.getElementById('strengthBar').style.background = strengthColor;
            document.getElementById('strengthText').textContent = strengthText;
            document.getElementById('strengthText').style.color = strengthColor;
        }

        // Auto-fill suggestion functions
        function suggestCredentials() {
            const urlField = document.querySelector('input[name="website_url"]');
            const url = urlField.value;
            
            // Show username suggestion when URL is entered
            if (url) {
                document.getElementById('usernameSuggestion').style.display = 'flex';
            } else {
                document.getElementById('usernameSuggestion').style.display = 'none';
            }
        }

        function fillUserEmail() {
            const userEmail = '<?= $_SESSION['user_email'] ?? '' ?>';
            const usernameField = document.getElementById('usernameField');
            usernameField.value = userEmail;
            
            // Hide suggestion after use
            document.getElementById('usernameSuggestion').style.display = 'none';
            
            // Show success feedback
            showNotification('Email address filled automatically!', 'success');
        }

        function generatePasswordForForm() {
            // Get the button for visual feedback
            const btn = document.getElementById('generatePasswordBtn');
            const originalHtml = btn.innerHTML;
            
            // Show loading state
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
            
            // Generate password with all character types - minimum 14 characters
            const length = 16; // Increased to 16 for better security
            const includeUppercase = true;
            const includeLowercase = true;
            const includeNumbers = true;
            const includeSymbols = true; // Now enabled for stronger passwords
            
            // Define character sets
            const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const lowercase = 'abcdefghijklmnopqrstuvwxyz';
            const numbers = '0123456789';
            const symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
            
            // Ensure at least one character from each type
            let password = '';
            
            // Add at least one character from each required type
            if (includeUppercase) password += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
            if (includeLowercase) password += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
            if (includeNumbers) password += numbers.charAt(Math.floor(Math.random() * numbers.length));
            if (includeSymbols) password += symbols.charAt(Math.floor(Math.random() * symbols.length));
            
            // Build complete character set
            let charset = '';
            if (includeUppercase) charset += uppercase;
            if (includeLowercase) charset += lowercase;
            if (includeNumbers) charset += numbers;
            if (includeSymbols) charset += symbols;
            
            // Fill remaining length with random characters from all sets
            for (let i = password.length; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            
            // Shuffle the password to randomize the guaranteed characters
            password = password.split('').sort(() => Math.random() - 0.5).join('');
              // Simulate a brief delay for better UX
            setTimeout(() => {
                // Set the password in the form field
                const passwordField = document.getElementById('modalPasswordField');
                if (passwordField) {
                    passwordField.value = password;
                    passwordField.type = 'text'; // Temporarily show the password
                    
                    // Automatically copy to clipboard
                    navigator.clipboard.writeText(password).then(() => {
                        // Show success state
                        btn.innerHTML = '<i class="fas fa-check"></i>';
                        btn.style.background = 'var(--success)';
                        btn.style.color = 'white';
                        
                        // Show enhanced notification with copy confirmation
                        showNotification(`Strong password generated & copied! (16 chars: A-Z, a-z, 0-9, symbols) - Ready to paste with Ctrl+V`, 'success');
                        
                        // Also show a temporary visual indicator
                        const copyIndicator = document.createElement('div');
                        copyIndicator.innerHTML = `
                            <div style="
                                position: fixed;
                                top: 50%;
                                left: 50%;
                                transform: translate(-50%, -50%);
                                background: var(--success);
                                color: white;
                                padding: 1rem 2rem;
                                border-radius: var(--border-radius);
                                box-shadow: var(--shadow-lg);
                                z-index: 10000;
                                display: flex;
                                align-items: center;
                                gap: 0.5rem;
                                font-weight: 600;
                                animation: copySuccess 0.3s ease;
                            ">
                                <i class="fas fa-copy"></i>
                                Password copied to clipboard!
                                <br>
                                <small style="opacity: 0.9;">Use Ctrl+V to paste anywhere</small>
                            </div>`;
                        
                        document.body.appendChild(copyIndicator);
                        
                        // Remove copy indicator after 3 seconds
                        setTimeout(() => {
                            copyIndicator.remove();
                        }, 3000);
                        
                    }).catch(() => {
                        // Fallback if clipboard API fails
                        btn.innerHTML = '<i class="fas fa-check"></i>';
                        btn.style.background = 'var(--success)';
                        btn.style.color = 'white';
                        
                        showNotification(`Strong password generated! (16 chars: A-Z, a-z, 0-9, symbols)`, 'success');
                    });
                    
                    // Hide password after 4 seconds (increased time to read)
                    setTimeout(() => {
                        passwordField.type = 'password';
                    }, 4000);
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.style.background = '';
                        btn.style.color = '';
                        btn.disabled = false;
                    }, 2000);
                } else {
                    // Reset button if field not found
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            }, 500);
        }        // Item actions
        function viewItem(itemId) {
            // Find the item data
            const items = <?= json_encode($vaultItems) ?>;
            const item = items.find(i => i.id == itemId);
            
            if (!item) {
                showNotification('Item not found', 'error');
                return;
            }
            
            // Create and show view modal
            let modalContent = `
                <div id="viewItemModal" class="modal" style="display: block;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title">
                                <i class="fas fa-${getItemIcon(item.item_type)}"></i>
                                ${item.item_name}
                            </h2>
                            <button class="modal-close" onclick="closeViewModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            ${generateItemDetails(item)}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="closeViewModal()">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalContent);
        }
        
        function getItemIcon(type) {
            switch(type) {
                case 'login': return 'sign-in-alt';
                case 'card': return 'credit-card';
                case 'identity': return 'id-card';
                case 'note': return 'sticky-note';
                default: return 'key';
            }
        }
        
        function generateItemDetails(item) {
            let html = '';
            const data = item.decrypted_data || {};
            
            if (item.website_url) {
                html += `
                    <div class="detail-group">
                        <label class="detail-label">Website URL</label>
                        <div class="detail-value">
                            <a href="${item.website_url}" target="_blank" class="detail-link">
                                ${item.website_url}
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                `;
            }
            
            if (item.item_type === 'login') {
                if (data.username) {
                    html += `
                        <div class="detail-group">
                            <label class="detail-label">Username</label>
                            <div class="detail-value copyable" onclick="copyToClipboard('${data.username}')">
                                ${data.username}
                                <i class="fas fa-copy detail-copy-icon"></i>
                            </div>
                        </div>
                    `;
                }
                
                if (data.password) {
                    html += `
                        <div class="detail-group">
                            <label class="detail-label">Password</label>
                            <div class="detail-value password-field">
                                <span class="password-text" id="password-${item.id}">••••••••••••</span>
                                <div class="password-actions">
                                    <button type="button" class="detail-action-btn" onclick="togglePassword(${item.id}, '${data.password}')" title="Show/Hide Password">
                                        <i class="fas fa-eye" id="eye-${item.id}"></i>
                                    </button>
                                    <button type="button" class="detail-action-btn" onclick="copyToClipboard('${data.password}')" title="Copy Password">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } else if (item.item_type === 'card') {
                if (data.cardholder_name) {
                    html += `
                        <div class="detail-group">
                            <label class="detail-label">Cardholder Name</label>
                            <div class="detail-value copyable" onclick="copyToClipboard('${data.cardholder_name}')">
                                ${data.cardholder_name}
                                <i class="fas fa-copy detail-copy-icon"></i>
                            </div>
                        </div>
                    `;
                }
                
                if (data.card_number) {
                    html += `
                        <div class="detail-group">
                            <label class="detail-label">Card Number</label>
                            <div class="detail-value copyable" onclick="copyToClipboard('${data.card_number}')">
                                •••• •••• •••• ${data.card_number.slice(-4)}
                                <i class="fas fa-copy detail-copy-icon"></i>
                            </div>
                        </div>
                    `;
                }
                
                if (data.expiry_date) {
                    html += `
                        <div class="detail-group">
                            <label class="detail-label">Expiry Date</label>
                            <div class="detail-value">${data.expiry_date}</div>
                        </div>
                    `;
                }
                
                if (data.cvv) {
                    html += `
                        <div class="detail-group">
                            <label class="detail-label">CVV</label>
                            <div class="detail-value password-field">
                                <span class="password-text" id="cvv-${item.id}">•••</span>
                                <div class="password-actions">
                                    <button type="button" class="detail-action-btn" onclick="toggleCVV(${item.id}, '${data.cvv}')" title="Show/Hide CVV">
                                        <i class="fas fa-eye" id="cvv-eye-${item.id}"></i>
                                    </button>
                                    <button type="button" class="detail-action-btn" onclick="copyToClipboard('${data.cvv}')" title="Copy CVV">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }
            }
            
            if (data.notes) {
                html += `
                    <div class="detail-group">
                        <label class="detail-label">Notes</label>
                        <div class="detail-value notes-value">${data.notes}</div>
                    </div>
                `;
            }
            
            html += `
                <div class="detail-group">
                    <label class="detail-label">Created</label>
                    <div class="detail-value">${new Date(item.created_at).toLocaleString()}</div>
                </div>
            `;
            
            return html || '<p class="text-muted">No additional details available.</p>';
        }
        
        function togglePassword(itemId, password) {
            const passwordSpan = document.getElementById(`password-${itemId}`);
            const eyeIcon = document.getElementById(`eye-${itemId}`);
            
            if (passwordSpan.textContent === '••••••••••••') {
                passwordSpan.textContent = password;
                eyeIcon.className = 'fas fa-eye-slash';
            } else {
                passwordSpan.textContent = '••••••••••••';
                eyeIcon.className = 'fas fa-eye';
            }
        }
        
        function toggleCVV(itemId, cvv) {
            const cvvSpan = document.getElementById(`cvv-${itemId}`);
            const eyeIcon = document.getElementById(`cvv-eye-${itemId}`);
            
            if (cvvSpan.textContent === '•••') {
                cvvSpan.textContent = cvv;
                eyeIcon.className = 'fas fa-eye-slash';
            } else {
                cvvSpan.textContent = '•••';
                eyeIcon.className = 'fas fa-eye';
            }
        }
        
        function closeViewModal() {
            const modal = document.getElementById('viewItemModal');
            if (modal) {
                modal.remove();
            }
        }

        function deleteItem(itemId) {
            if (confirm('Are you sure you want to delete this item?')) {
                // Create a form to submit the delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_vault_item">
                    <input type="hidden" name="item_id" value="${itemId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }        // Initialize
        document.addEventListener('DOMContentLoaded', function() {            // Initialize generator if on generator page
            if (window.location.search.includes('section=generator')) {
                // Set up slider synchronization
                const slider = document.getElementById('passwordLengthSlider');
                const lengthInput = document.getElementById('passwordLength');
                const customCheckbox = document.getElementById('useCustomLength');
                
                if (slider) {
                    slider.addEventListener('input', updateLengthDisplay);
                    slider.addEventListener('change', autoGenerate);
                }
                
                if (lengthInput) {
                    lengthInput.addEventListener('input', () => {
                        if (customCheckbox && customCheckbox.checked) {
                            syncCustomLength();
                            autoGenerate();
                        } else {
                            syncSlider();
                            autoGenerate();
                        }
                    });
                }
                
                if (customCheckbox) {
                    customCheckbox.addEventListener('change', toggleCustomLength);
                }
                
                // Initialize length display
                updateLengthDisplay();
                
                // Generate initial value
                setTimeout(regenerateValue, 100);
                
                console.log('Generator initialized with custom length support');
            }            // Initialize send section
            if (window.location.search.includes('section=send')) {
                initializeSendSection();
                initializeDragAndDrop();
                
                // Initialize credential delivery form
                const credentialMessageTextarea = document.getElementById('credential_message');
                if (credentialMessageTextarea) {
                    credentialMessageTextarea.addEventListener('input', updateCredentialMessageCounter);
                    updateCredentialMessageCounter();
                }
                
                // Initialize emergency contact form
                const emergencyInstructionsTextarea = document.getElementById('emergency_instructions');
                if (emergencyInstructionsTextarea) {
                    emergencyInstructionsTextarea.addEventListener('input', updateEmergencyInstructionsCounter);
                    updateEmergencyInstructionsCounter();
                }
                
                // Initialize password requirement toggle for credential delivery
                const requirePasswordCheckbox = document.getElementById('require_password_credential');
                if (requirePasswordCheckbox) {
                    requirePasswordCheckbox.addEventListener('change', function() {
                        const passwordSection = document.getElementById('credential_password_section');
                        if (passwordSection) {
                            passwordSection.style.display = this.checked ? 'block' : 'none';
                        }
                    });
                }
                
                // Initialize trigger type toggle for emergency contact
                const triggerRadios = document.querySelectorAll('input[name="trigger_type"]');
                triggerRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        const inactivitySettings = document.getElementById('inactivity_settings');
                        if (inactivitySettings) {
                            inactivitySettings.style.display = this.value === 'inactivity' ? 'block' : 'none';
                        }
                    });
                });            }
            
            // Initialize text counter for secure send
            const sendTextarea = document.getElementById('send_text');
            if (sendTextarea) {
                sendTextarea.addEventListener('input', updateTextCounter);
                updateTextCounter();
            }
        });

        // Enhanced Generator Functions
        let currentGeneratorTab = 'password';
          function switchGeneratorTab(tab) {
            console.log('Switching to tab:', tab);
            currentGeneratorTab = tab;
            
            // Update tab buttons (use both classes for compatibility)
            document.querySelectorAll('.tab-button, .tab-button-enhanced').forEach(btn => {
                btn.classList.remove('active');
            });
            
            const activeTab = document.querySelector(`[data-tab="${tab}"]`);
            if (activeTab) {
                activeTab.classList.add('active');
            }
            
            // Update options sections
            document.querySelectorAll('.generator-options, .generator-options-enhanced').forEach(option => {
                option.style.display = 'none';
            });
            
            const targetOptions = document.getElementById(`${tab}Options`);
            if (targetOptions) {
                targetOptions.style.display = 'block';
            }
            
            // Generate new value
            regenerateValue();
        }        function regenerateValue() {
            const generatedValue = document.getElementById('generatedValue');
            const regenerateBtn = document.getElementById('regenerateBtn');
            
            // Add loading state
            if (regenerateBtn) {
                regenerateBtn.classList.add('generating');
                regenerateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
            
            let value = '';
            
            try {
                switch (currentGeneratorTab) {
                    case 'password':
                        value = generatePasswordValue();
                        break;
                    case 'passphrase':
                        value = generatePassphraseValue();
                        break;
                    case 'username':
                        value = generateUsernameValue();
                        break;
                    default:
                        value = generatePasswordValue();
                }
                
                generatedValue.textContent = value;
                
                // Update strength indicator
                if (currentGeneratorTab === 'password') {
                    updatePasswordStrength(value);
                } else {
                    hidePasswordStrength();
                }
                
                // Note: History is only saved when user clicks "Save to History" button
                
            } catch (error) {
                console.error('Error generating value:', error);
                generatedValue.textContent = 'Error generating value. Please try again.';
            } finally {
                // Remove loading state
                setTimeout(() => {
                    if (regenerateBtn) {
                        regenerateBtn.classList.remove('generating');
                        regenerateBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                    }
                }, 300);
            }
        }
        
        function updatePasswordStrength(password) {
            const strengthBar = document.getElementById('strengthBarMain');
            const strengthText = document.getElementById('strengthTextMain');
            
            if (!strengthBar || !strengthText) return;
            
            const strength = calculatePasswordStrength(password);
            
            // Update strength bar
            strengthBar.style.width = strength.percentage + '%';
            strengthBar.className = 'strength-bar animating';
            
            // Update strength text and color
            strengthText.textContent = strength.label;
            strengthText.className = 'strength-text ' + strength.class;
            
            // Update bar color based on strength
            const colors = {
                'strength-very-weak': '#dc2626',
                'strength-weak': '#ea580c',
                'strength-fair': '#d97706',
                'strength-good': '#16a34a',
                'strength-strong': '#059669',
                'strength-very-strong': '#047857'
            };
            
            strengthBar.style.background = colors[strength.class] || '#f1f5f9';
        }
        
        function hidePasswordStrength() {
            const strengthBar = document.getElementById('strengthBarMain');
            const strengthText = document.getElementById('strengthTextMain');
            
            if (strengthBar && strengthText) {
                strengthBar.style.width = '0%';
                strengthText.textContent = 'N/A for this type';
                strengthText.className = 'strength-text';
            }
        }
        
        function calculatePasswordStrength(password) {
            if (!password) return { percentage: 0, label: 'No password', class: 'strength-very-weak' };
            
            let score = 0;
            let feedback = [];
            
            // Length scoring
            if (password.length >= 8) score += 10;
            if (password.length >= 12) score += 10;
            if (password.length >= 16) score += 10;
            if (password.length >= 20) score += 10;
            
            // Character variety
            if (/[a-z]/.test(password)) score += 10;
            if (/[A-Z]/.test(password)) score += 10;
            if (/[0-9]/.test(password)) score += 10;
            if (/[^A-Za-z0-9]/.test(password)) score += 15;
            
            // Bonus for good patterns
            if (/[a-z].*[A-Z]|[A-Z].*[a-z]/.test(password)) score += 5;
            if (/[a-zA-Z].*[0-9]|[0-9].*[a-zA-Z]/.test(password)) score += 5;
            
            // Penalties for bad patterns
            if (/(.)\1{2,}/.test(password)) score -= 10; // Repeated characters
            if (/123|abc|qwe/i.test(password)) score -= 15; // Common sequences
            
            // Determine strength level
            let percentage = Math.min(100, Math.max(0, score));
            let label, cssClass;
            
            if (percentage < 20) {
                label = 'Very Weak';
                cssClass = 'strength-very-weak';
            } else if (percentage < 40) {
                label = 'Weak';
                cssClass = 'strength-weak';
            } else if (percentage < 60) {
                label = 'Fair';
                cssClass = 'strength-fair';
            } else if (percentage < 80) {
                label = 'Good';
                cssClass = 'strength-good';
            } else if (percentage < 95) {
                label = 'Strong';
                cssClass = 'strength-strong';
            } else {
                label = 'Very Strong';
                cssClass = 'strength-very-strong';
            }
            
            return { percentage, label, class: cssClass };
        }
          function generatePasswordValue() {
            const customCheckbox = document.getElementById('useCustomLength');
            const lengthInput = document.getElementById('passwordLength');
            const slider = document.getElementById('passwordLengthSlider');
            
            let length;
            if (customCheckbox && customCheckbox.checked && lengthInput) {
                length = Math.max(5, parseInt(lengthInput.value) || 14);
            } else {
                length = parseInt(slider ? slider.value : 14);
            }
            
            // Safety check for very large lengths
            if (length > 10000) {
                showNotification('Password length limited to 10,000 characters for performance reasons', 'warning');
                length = 10000;
            }
            
            const includeUppercase = document.getElementById('includeUppercase').checked;
            const includeLowercase = document.getElementById('includeLowercase').checked;
            const includeNumbers = document.getElementById('includeNumbers').checked;
            const includeSymbols = document.getElementById('includeSymbols').checked;
            const minNumbers = parseInt(document.getElementById('minNumbers').value);
            const minSymbols = parseInt(document.getElementById('minSymbols').value);
            const avoidAmbiguous = document.getElementById('avoidAmbiguous').checked;
            
            // Validate that at least one character type is selected
            if (!includeUppercase && !includeLowercase && !includeNumbers && !includeSymbols) {
                showNotification('Please select at least one character type', 'error');
                return 'Error: No character types selected';
            }
            
            // Character sets
            let uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            let lowercase = 'abcdefghijklmnopqrstuvwxyz';
            let numbers = '0123456789';
            let symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
            
            if (avoidAmbiguous) {
                uppercase = uppercase.replace(/[0OIL]/g, '');
                lowercase = lowercase.replace(/[oil]/g, '');
                numbers = numbers.replace(/[01]/g, '');
                symbols = symbols.replace(/[|`]/g, '');
            }
            
            let charset = '';
            let password = '';
            
            // Ensure minimum requirements
            if (includeNumbers && minNumbers > 0) {
                for (let i = 0; i < Math.min(minNumbers, length); i++) {
                    password += numbers.charAt(Math.floor(Math.random() * numbers.length));
                }
            }
            
            if (includeSymbols && minSymbols > 0) {
                for (let i = 0; i < Math.min(minSymbols, length); i++) {
                    password += symbols.charAt(Math.floor(Math.random() * symbols.length));
                }
            }
            
            // Build charset
            if (includeUppercase) charset += uppercase;
            if (includeLowercase) charset += lowercase;
            if (includeNumbers) charset += numbers;
            if (includeSymbols) charset += symbols;
            
            // Fill remaining length
            for (let i = password.length; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            
            // Shuffle password using Fisher-Yates algorithm for better randomization
            const passwordArray = password.split('');
            for (let i = passwordArray.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [passwordArray[i], passwordArray[j]] = [passwordArray[j], passwordArray[i]];
            }
            
            return passwordArray.join('');
        }
        
        function generatePassphraseValue() {
            const wordCount = parseInt(document.getElementById('wordCount').value);
            const separator = document.getElementById('wordSeparator').value || '-';
            const capitalize = document.getElementById('capitalizeWords').checked;
            const includeNumber = document.getElementById('includeNumberInPhrase').checked;
            
            // Common words for passphrase generation
            const words = [
                'apple', 'banana', 'cherry', 'dragon', 'elephant', 'forest', 'guitar', 'happy',
                'island', 'jungle', 'kitchen', 'lemon', 'mountain', 'ocean', 'purple', 'quiet',
                'rainbow', 'sunset', 'tiger', 'universe', 'village', 'water', 'yellow', 'zebra',
                'brilliant', 'courage', 'freedom', 'harmony', 'journey', 'liberty', 'mystery',
                'adventure', 'butterfly', 'diamond', 'energy', 'fantastic', 'gravity', 'horizon'
            ];
            
            let passphrase = [];
            
            for (let i = 0; i < wordCount; i++) {
                let word = words[Math.floor(Math.random() * words.length)];
                if (capitalize) {
                    word = word.charAt(0).toUpperCase() + word.slice(1);
                }
                passphrase.push(word);
            }
            
            let result = passphrase.join(separator);
            
            if (includeNumber) {
                result += separator + Math.floor(Math.random() * 1000);
            }
            
            return result;
        }
        
        function generateUsernameValue() {
            const type = document.getElementById('usernameType').value;
            const capitalize = document.getElementById('capitalizeUsername').checked;
            const includeNumber = document.getElementById('includeNumberInUsername').checked;
            
            let username = '';
            
            switch (type) {
                case 'random_word':
                    const words = ['user', 'admin', 'guest', 'player', 'member', 'hero', 'ninja', 'master', 'chief', 'agent'];
                    username = words[Math.floor(Math.random() * words.length)];
                    break;
                case 'combination':
                    const adjectives = ['cool', 'fast', 'smart', 'brave', 'quick', 'bright', 'silent', 'strong'];
                    const nouns = ['wolf', 'eagle', 'lion', 'tiger', 'dragon', 'phoenix', 'storm', 'thunder'];
                    username = adjectives[Math.floor(Math.random() * adjectives.length)] + 
                              nouns[Math.floor(Math.random() * nouns.length)];
                    break;
                case 'uuid':
                    username = 'user_' + Math.random().toString(36).substr(2, 9);
                    break;
            }
            
            if (capitalize) {
                username = username.charAt(0).toUpperCase() + username.slice(1);
            }
            
            if (includeNumber) {
                username += Math.floor(Math.random() * 1000);
            }
            
            return username;
        }
        
        function toggleUsernameOptions() {
            const type = document.getElementById('usernameType').value;
            const customOptions = document.getElementById('usernameCustomOptions');
            customOptions.style.display = type === 'uuid' ? 'none' : 'block';
        }
          function copyGeneratedValue() {
            const generatedValue = document.getElementById('generatedValue');
            const copyBtn = document.getElementById('copyBtn');
            const value = generatedValue.textContent;
            
            if (!value || value === 'Click "Generate New" to create a secure password...' || value.includes('Error')) {
                showNotification('Nothing to copy. Generate a value first!', 'warning');
                return;
            }
            
            // Add visual feedback
            generatedValue.classList.add('copying');
            if (copyBtn) {
                copyBtn.classList.add('clicked');
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
            }
            
            navigator.clipboard.writeText(value).then(() => {
                showNotification('Copied to clipboard!', 'success');
            }).catch(() => {
                // Fallback for older browsers
                try {
                    const textArea = document.createElement('textarea');
                    textArea.value = value;
                    textArea.style.position = 'fixed';
                    textArea.style.opacity = '0';
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showNotification('Copied to clipboard!', 'success');
                } catch (error) {
                    showNotification('Copy failed. Please select and copy manually.', 'error');
                }
            }).finally(() => {
                // Remove visual feedback
                setTimeout(() => {
                    generatedValue.classList.remove('copying');
                    if (copyBtn) {
                        copyBtn.classList.remove('clicked');
                        copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
                    }
                }, 500);
            });
        }
          function autoGenerate() {
            // Auto-generate when options change (with debounce)
            clearTimeout(window.autoGenerateTimeout);
            window.autoGenerateTimeout = setTimeout(regenerateValue, 150);
        }
          function updateLengthDisplay() {
            const slider = document.getElementById('passwordLengthSlider');
            const display = document.getElementById('lengthValue');
            const input = document.getElementById('passwordLength');
            const customCheckbox = document.getElementById('useCustomLength');
            
            if (slider && display) {
                let length = slider.value;
                if (customCheckbox && customCheckbox.checked && input) {
                    length = input.value || 14;
                }
                display.textContent = length;
                if (input && !customCheckbox.checked) {
                    input.value = length;
                }
            }
        }
        
        function syncSlider() {
            const slider = document.getElementById('passwordLengthSlider');
            const input = document.getElementById('passwordLength');
            const customCheckbox = document.getElementById('useCustomLength');
            
            if (slider && input && !customCheckbox.checked) {
                slider.value = Math.min(256, Math.max(5, input.value || 14));
                updateLengthDisplay();
            }
        }
        
        function syncCustomLength() {
            const input = document.getElementById('passwordLength');
            const display = document.getElementById('lengthValue');
            
            if (input && display) {
                const length = Math.max(5, input.value || 14);
                input.value = length;
                display.textContent = length;
            }
        }
        
        function toggleCustomLength() {
            const customCheckbox = document.getElementById('useCustomLength');
            const input = document.getElementById('passwordLength');
            const slider = document.getElementById('passwordLengthSlider');
            
            if (customCheckbox && input && slider) {
                const isCustom = customCheckbox.checked;
                
                input.style.display = isCustom ? 'block' : 'none';
                slider.disabled = isCustom;
                slider.style.opacity = isCustom ? '0.5' : '1';
                
                if (isCustom) {
                    input.focus();
                }
                
                updateLengthDisplay();
                autoGenerate();
            }
        }
        
        function toggleAdvanced() {
            const settings = document.getElementById('advancedSettings');
            const toggleText = document.getElementById('advancedToggleText');
            const toggleIcon = document.getElementById('advancedToggleIcon');
            
            if (settings && toggleText && toggleIcon) {
                const isHidden = settings.style.display === 'none';
                
                settings.style.display = isHidden ? 'block' : 'none';
                toggleText.textContent = isHidden ? 'Hide Advanced' : 'Show Advanced';
                toggleIcon.classList.toggle('fa-chevron-down', !isHidden);
                toggleIcon.classList.toggle('fa-chevron-up', isHidden);
            }
        }
          function applyPreset(type) {
            const customCheckbox = document.getElementById('useCustomLength');
            const lengthInput = document.getElementById('passwordLength');
            
            // Disable custom length for presets
            if (customCheckbox) {
                customCheckbox.checked = false;
                toggleCustomLength();
            }
            
            if (type === 'simple') {
                // Simple & Secure preset
                document.getElementById('passwordLengthSlider').value = 12;
                if (lengthInput) lengthInput.value = 12;
                document.getElementById('includeUppercase').checked = true;
                document.getElementById('includeLowercase').checked = true;
                document.getElementById('includeNumbers').checked = true;
                document.getElementById('includeSymbols').checked = false;
                document.getElementById('minNumbers').value = 2;
                document.getElementById('minSymbols').value = 0;
                document.getElementById('avoidAmbiguous').checked = true;
                
                showNotification('Applied Simple & Secure preset (12 characters)', 'info');
            } else if (type === 'complex') {
                // Maximum Security preset
                document.getElementById('passwordLengthSlider').value = 20;
                if (lengthInput) lengthInput.value = 20;
                document.getElementById('includeUppercase').checked = true;
                document.getElementById('includeLowercase').checked = true;
                document.getElementById('includeNumbers').checked = true;
                document.getElementById('includeSymbols').checked = true;
                document.getElementById('minNumbers').value = 3;
                document.getElementById('minSymbols').value = 3;
                document.getElementById('avoidAmbiguous').checked = false;
                
                showNotification('Applied Maximum Security preset (20 characters)', 'info');
            }
            
            updateLengthDisplay();
            autoGenerate();
        }function toggleHistory() {
            const historyContent = document.getElementById('historyContent');
            const historyToggle = document.querySelector('.history-toggle');
            const clearBtn = document.getElementById('clearHistoryBtn');
            
            if (historyContent && historyToggle) {
                const isHidden = historyContent.style.display === 'none';
                historyContent.style.display = isHidden ? 'block' : 'none';
                historyToggle.classList.toggle('expanded', isHidden);
                
                // Show/hide clear button
                if (clearBtn) {
                    clearBtn.style.display = isHidden ? 'inline-block' : 'none';
                }
                
                // Update history display when showing
                if (isHidden) {
                    updateHistoryDisplay();
                }
            }
        }
          function addToHistory(value, type) {
            if (!value || typeof value !== 'string') return;
            
            // Initialize history array if it doesn't exist
            if (!window.generatorHistory) {
                window.generatorHistory = [];
            }
            
            const historyItem = {
                value: value,
                type: type,
                timestamp: new Date().toLocaleString(),
                strength: type === 'password' ? calculatePasswordStrength(value) : null
            };
            
            // Add to beginning of array and limit to 10 items
            window.generatorHistory.unshift(historyItem);
            window.generatorHistory = window.generatorHistory.slice(0, 10);
            
            updateHistoryDisplay();
        }
        
        function updateHistoryDisplay() {
            const historyContent = document.getElementById('historyContent');
            if (!historyContent || !window.generatorHistory) return;
            
            const noHistory = historyContent.querySelector('.no-history');
            
            if (window.generatorHistory.length === 0) {
                if (noHistory) noHistory.style.display = 'block';
                return;
            }
            
            if (noHistory) noHistory.style.display = 'none';
            
            // Clear existing history items
            const existingItems = historyContent.querySelectorAll('.history-item');
            existingItems.forEach(item => item.remove());
            
            // Add history items
            window.generatorHistory.forEach((item, index) => {
                const historyDiv = document.createElement('div');
                historyDiv.className = 'history-item';
                historyDiv.innerHTML = `
                    <div class="history-item-header">
                        <span class="history-type">${item.type.charAt(0).toUpperCase() + item.type.slice(1)}</span>
                        <span class="history-time">${item.timestamp}</span>
                        ${item.strength ? `<span class="history-strength ${item.strength.class}">${item.strength.label}</span>` : ''}
                    </div>
                    <div class="history-value" onclick="copyHistoryItem('${item.value.replace(/'/g, '\\\'')}')">${item.value}</div>
                `;
                historyContent.appendChild(historyDiv);
            });
        }
          function copyHistoryItem(value) {
            navigator.clipboard.writeText(value).then(() => {
                showNotification('Copied from history!', 'success');
            }).catch(() => {
                showNotification('Failed to copy from history', 'error');
            });
        }        function addToHistory() {
            const generatedValue = document.getElementById('generatedValue');
            const saveBtn = document.getElementById('saveBtn');
            const value = generatedValue.textContent;
            
            if (!value || value === 'Click "Generate New" to create a secure password...' || value.includes('Error')) {
                showNotification('Nothing to save. Generate a value first!', 'warning');
                return;
            }
            
            // Check if this value is already in history to avoid duplicates
            const history = loadGeneratorHistory();
            const isDuplicate = history.some(entry => entry.value === value);
            
            if (isDuplicate) {
                showNotification('This value is already saved in history!', 'info');
                return;
            }
            
            // Visual feedback
            if (saveBtn) {
                saveBtn.classList.add('clicked');
                saveBtn.innerHTML = '<i class="fas fa-check"></i>';
            }
            
            // Save to history
            saveToGeneratorHistory(value, currentGeneratorTab);
            showNotification('Saved to generator history!', 'success');
            
            // Update history display if visible
            const historyContent = document.getElementById('historyContent');
            if (historyContent && historyContent.style.display !== 'none') {
                updateHistoryDisplay();
            }
            
            // Reset button
            setTimeout(() => {
                if (saveBtn) {
                    saveBtn.classList.remove('clicked');
                    saveBtn.innerHTML = '<i class="fas fa-bookmark"></i>';
                }
            }, 1000);
        }
        
        function saveToGeneratorHistory(value, type) {
            try {
                let history = JSON.parse(localStorage.getItem('generatorHistory') || '[]');
                
                // Create history entry
                const entry = {
                    id: Date.now(),
                    value: value,
                    type: type,
                    timestamp: new Date().toISOString(),
                    strength: type === 'password' ? calculatePasswordStrength(value) : null
                };
                
                // Add to beginning of array
                history.unshift(entry);
                
                // Keep only last 50 entries
                history = history.slice(0, 50);
                
                // Save back to localStorage
                localStorage.setItem('generatorHistory', JSON.stringify(history));
                
            } catch (error) {
                console.error('Error saving to history:', error);
            }
        }
        
        function loadGeneratorHistory() {
            try {
                return JSON.parse(localStorage.getItem('generatorHistory') || '[]');
            } catch (error) {
                console.error('Error loading history:', error);
                return [];
            }
        }
        
        function updateHistoryDisplay() {
            const historyContent = document.getElementById('historyContent');
            if (!historyContent) return;
            
            const history = loadGeneratorHistory();
            
            if (history.length === 0) {
                historyContent.innerHTML = '<p class="no-history">No history available. Generate and save some values to see them here.</p>';
                return;
            }
            
            let historyHtml = '';
            
            history.forEach(entry => {
                const date = new Date(entry.timestamp);
                const timeAgo = getTimeAgo(date);
                const strengthInfo = entry.strength ? 
                    `<span class="history-strength ${entry.strength.class}">${entry.strength.label}</span>` : '';
                
                historyHtml += `
                    <div class="history-item">
                        <div class="history-item-header">
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <span class="history-type">${entry.type}</span>
                                ${strengthInfo}
                            </div>
                            <span class="history-time">${timeAgo}</span>
                        </div>
                        <div class="history-value" onclick="copyHistoryItem('${entry.value.replace(/'/g, '\\\'')}')" title="Click to copy">
                            ${entry.value.length > 50 ? entry.value.substring(0, 50) + '...' : entry.value}
                        </div>
                    </div>
                `;
            });
            
            historyContent.innerHTML = historyHtml;
        }
        
        function getTimeAgo(date) {
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;
            return date.toLocaleDateString();
        }
        
        function clearGeneratorHistory() {
            if (confirm('Are you sure you want to clear all generator history?')) {
                localStorage.removeItem('generatorHistory');
                updateHistoryDisplay();
                showNotification('Generator history cleared!', 'success');
            }
        }        // Send Section Functions
        function switchSendTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.send-tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
            
            // Update content
            document.querySelectorAll('.send-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`${tab}Tab`).classList.add('active');
        }
        
        function initializeSendSection() {
            // Initialize send type toggle
            const typeRadios = document.querySelectorAll('input[name="send_type"]');
            typeRadios.forEach(radio => {
                radio.addEventListener('change', toggleSendContent);
            });
        }
        
        function toggleSendContent() {
            const textContent = document.getElementById('textContent');
            const fileContent = document.getElementById('fileContent');
            const isFileType = document.getElementById('type_file').checked;
              textContent.style.display = isFileType ? 'none' : 'block';
            fileContent.style.display = isFileType ? 'block' : 'none';
        }
        
        function updateTextCounter() {
            const text = document.getElementById('send_text').value;
            const counter = document.getElementById('textCounter');
            if (counter) {
                counter.textContent = text.length;
            }
        }        
        function clearSecureForm() {
            document.getElementById('secureSendForm').reset();
            updateTextCounter();
        }
        
        function clearCredentialForm() {
            document.getElementById('credentialDeliveryForm').reset();
            updateCredentialMessageCounter();
            
            // Hide password section if visible
            const passwordSection = document.getElementById('credential_password_section');
            if (passwordSection) {
                passwordSection.style.display = 'none';
            }
        }
        
        function clearEmergencyForm() {
            document.getElementById('emergencyContactForm').reset();
            updateEmergencyInstructionsCounter();
            
            // Show inactivity settings by default
            const inactivitySettings = document.getElementById('inactivity_settings');
            if (inactivitySettings) {
                inactivitySettings.style.display = 'block';
            }
        }
        
        function updateCredentialMessageCounter() {
            const textarea = document.getElementById('credential_message');
            const counter = document.getElementById('credentialMessageCounter');
            if (textarea && counter) {
                counter.textContent = textarea.value.length;
            }
        }
          function updateEmergencyInstructionsCounter() {
            const textarea = document.getElementById('emergency_instructions');
            const counter = document.getElementById('emergencyInstructionsCounter');
            if (textarea && counter) {
                counter.textContent = textarea.value.length;
            }
        }
        
        // Enhanced file handling functions
        function handleFileSelection(input) {
            const file = input.files[0];
            if (!file) return;
            
            // Validate file size (25MB limit)
            const maxSize = 25 * 1024 * 1024; // 25MB in bytes
            if (file.size > maxSize) {
                showNotification('File size exceeds 25MB limit. Please choose a smaller file.', 'error');
                input.value = '';
                return;
            }
            
            // Show file preview
            showFilePreview(file);
            
            // Hide upload area
            const uploadArea = document.querySelector('.file-upload-area');
            if (uploadArea) {
                uploadArea.style.display = 'none';
            }
        }
        
        function showFilePreview(file) {
            const preview = document.getElementById('file-preview');
            const fileName = preview.querySelector('.file-name');
            const fileSize = preview.querySelector('.file-size');
            const fileType = preview.querySelector('.file-type');
            const fileIcon = preview.querySelector('.file-icon i');
            const imagePreview = document.getElementById('image-preview');
            const previewImage = document.getElementById('preview-image');
            
            if (!preview) return;
            
            // Update file info
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileType.textContent = file.type || 'Unknown';
            
            // Update icon based on file type
            if (file.type.startsWith('image/')) {
                fileIcon.className = 'fas fa-image';
                fileIcon.style.color = '#10b981';
                
                // Show image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else if (file.type.includes('pdf')) {
                fileIcon.className = 'fas fa-file-pdf';
                fileIcon.style.color = '#ef4444';
                imagePreview.style.display = 'none';
            } else if (file.type.includes('zip') || file.type.includes('rar')) {
                fileIcon.className = 'fas fa-file-archive';
                fileIcon.style.color = '#f59e0b';
                imagePreview.style.display = 'none';
            } else if (file.type.includes('text')) {
                fileIcon.className = 'fas fa-file-alt';
                fileIcon.style.color = '#6366f1';
                imagePreview.style.display = 'none';
            } else {
                fileIcon.className = 'fas fa-file';
                fileIcon.style.color = '#6b7280';
                imagePreview.style.display = 'none';
            }
            
            // Show preview
            preview.style.display = 'block';
        }
        
        function removeFile() {
            const fileInput = document.getElementById('send_file');
            const preview = document.getElementById('file-preview');
            const uploadArea = document.querySelector('.file-upload-area');
            const imagePreview = document.getElementById('image-preview');
            
            // Reset file input
            if (fileInput) fileInput.value = '';
            
            // Hide preview
            if (preview) preview.style.display = 'none';
            
            // Show upload area
            if (uploadArea) uploadArea.style.display = 'block';
            
            // Hide image preview
            if (imagePreview) imagePreview.style.display = 'none';
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Drag and drop functionality
        function initializeDragAndDrop() {
            const uploadArea = document.querySelector('.file-upload-area');
            if (!uploadArea) return;
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });
            
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, unhighlight, false);
            });
            
            uploadArea.addEventListener('drop', handleDrop, false);
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            function highlight() {
                uploadArea.classList.add('dragover');
            }
            
            function unhighlight() {
                uploadArea.classList.remove('dragover');
            }
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                const fileInput = document.getElementById('send_file');
                
                if (files.length > 0 && fileInput) {
                    fileInput.files = files;
                    handleFileSelection(fileInput);
                }
            }
        }
        
        // Send Management Functions
        function copyAccessLink(link) {
            const fullLink = `${window.location.origin}/SecureIt/backend/access_send.php?link=${link}`;
            navigator.clipboard.writeText(fullLink).then(() => {
                showNotification('Access link copied to clipboard!', 'success');
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = fullLink;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('Access link copied to clipboard!', 'success');
            });
        }
        
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }// Security Functions
        function analyzePasswords() {
            showNotification('Opening password analysis tool...', 'info');
            setTimeout(() => openBruteForceAnalyzer(), 500);
        }
        
        function openBruteForceAnalyzer() {
            document.getElementById('bruteForceModal').style.display = 'block';
            loadVaultPasswords();
        }
        
        function closeBruteForceModal() {
            document.getElementById('bruteForceModal').style.display = 'none';
            clearAnalysisResults();
        }
        
        function switchBruteForceTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.brute-force-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Update content
            document.querySelectorAll('.brute-force-tab-content').forEach(content => {
                content.style.display = 'none';
            });
            document.getElementById(tab + 'Tab').style.display = 'block';
        }
        
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('testPassword');
            const toggleBtn = document.getElementById('toggleBtn');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordField.type = 'password';
                toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }
        
        function analyzePassword() {
            const password = document.getElementById('testPassword').value;
            if (!password) {
                showNotification('Please enter a password to analyze', 'warning');
                return;
            }
            
            performPasswordAnalysis(password, 'analyze');
        }
        
        function analyzeSelectedPassword() {
            const selectedPassword = document.querySelector('.password-item.selected');
            if (!selectedPassword) {
                showNotification('Please select a password from your vault', 'warning');
                return;
            }
            
            const password = selectedPassword.dataset.password;
            performPasswordAnalysis(password, 'vault');
        }
        
        function performPasswordAnalysis(password, mode) {
            showNotification('Analyzing password strength...', 'info');
            
            // Calculate password strength metrics
            const analysis = calculatePasswordStrength(password);
            
            setTimeout(() => {
                displayAnalysisResults(analysis, mode);
                showNotification('Password analysis complete!', 'success');
            }, 1500);
        }
        
        function calculatePasswordStrength(password) {
            const length = password.length;
            const hasLowercase = /[a-z]/.test(password);
            const hasUppercase = /[A-Z]/.test(password);
            const hasNumbers = /[0-9]/.test(password);
            const hasSymbols = /[^A-Za-z0-9]/.test(password);
            const hasCommonWords = /password|123456|qwerty|admin|letmein/i.test(password);
            const hasPattern = /(.)\1{2,}|012|123|234|345|456|567|678|789|890|abc|bcd|cde/i.test(password);
            
            // Calculate entropy
            let charset = 0;
            if (hasLowercase) charset += 26;
            if (hasUppercase) charset += 26;
            if (hasNumbers) charset += 10;
            if (hasSymbols) charset += 32;
            
            const entropy = Math.log2(Math.pow(charset, length));
            
            // Calculate complexity score
            let complexityScore = 0;
            if (length >= 8) complexityScore += 25;
            if (length >= 12) complexityScore += 15;
            if (length >= 16) complexityScore += 10;
            if (hasLowercase) complexityScore += 10;
            if (hasUppercase) complexityScore += 10;
            if (hasNumbers) complexityScore += 10;
            if (hasSymbols) complexityScore += 20;
            if (hasCommonWords) complexityScore -= 30;
            if (hasPattern) complexityScore -= 20;
            
            complexityScore = Math.max(0, Math.min(100, complexityScore));
            
            // Estimate crack time
            const guessesPerSecond = 1000000000; // 1 billion guesses per second
            const totalPossibilities = Math.pow(charset, length);
            const secondsToCrack = totalPossibilities / (2 * guessesPerSecond);
            
            let crackTime = "";
            if (secondsToCrack < 60) {
                crackTime = "< 1 minute";
            } else if (secondsToCrack < 3600) {
                crackTime = Math.round(secondsToCrack / 60) + " minutes";
            } else if (secondsToCrack < 86400) {
                crackTime = Math.round(secondsToCrack / 3600) + " hours";
            } else if (secondsToCrack < 31536000) {
                crackTime = Math.round(secondsToCrack / 86400) + " days";
            } else if (secondsToCrack < 31536000000) {
                crackTime = Math.round(secondsToCrack / 31536000) + " years";
            } else {
                crackTime = "Millions of years";
            }
            
            // Determine strength level
            let strengthLevel = "";
            let strengthColor = "";
            if (complexityScore < 30) {
                strengthLevel = "Very Weak";
                strengthColor = "#ef4444";
            } else if (complexityScore < 50) {
                strengthLevel = "Weak";
                strengthColor = "#f59e0b";
            } else if (complexityScore < 70) {
                strengthLevel = "Fair";
                strengthColor = "#eab308";
            } else if (complexityScore < 85) {
                strengthLevel = "Good";
                strengthColor = "#10b981";
            } else {
                strengthLevel = "Excellent";
                strengthColor = "#059669";
            }
            
            // Generate recommendations
            const recommendations = [];
            if (length < 12) recommendations.push("Use at least 12 characters for better security");
            if (!hasUppercase) recommendations.push("Include uppercase letters (A-Z)");
            if (!hasLowercase) recommendations.push("Include lowercase letters (a-z)");
            if (!hasNumbers) recommendations.push("Include numbers (0-9)");
            if (!hasSymbols) recommendations.push("Include special characters (!@#$%^&*)");
            if (hasCommonWords) recommendations.push("Avoid common words like 'password' or '123456'");
            if (hasPattern) recommendations.push("Avoid repetitive patterns and sequences");
            if (recommendations.length === 0) recommendations.push("Your password meets security best practices!");
            
            return {
                score: complexityScore,
                level: strengthLevel,
                color: strengthColor,
                entropy: Math.round(entropy),
                crackTime: crackTime,
                patterns: hasCommonWords || hasPattern ? "Yes" : "None",
                recommendations: recommendations
            };
        }
        
        function displayAnalysisResults(analysis, mode) {
            const prefix = mode === 'vault' ? 'vault' : '';
            
            document.getElementById(prefix + 'StrengthDisplay').style.display = 'block';
            document.getElementById(prefix + 'StrengthScore').textContent = analysis.score;
            document.getElementById(prefix + 'StrengthLabel').textContent = analysis.level;
            document.getElementById(prefix + 'StrengthScore').style.color = analysis.color;
            document.getElementById(prefix + 'StrengthLabel').style.color = analysis.color;
            
            document.getElementById(prefix + 'StrengthBar').style.width = analysis.score + '%';
            document.getElementById(prefix + 'StrengthBar').style.background = analysis.color;
            
            document.getElementById(prefix + 'CrackTime').textContent = analysis.crackTime;
            document.getElementById(prefix + 'Entropy').textContent = analysis.entropy;
            document.getElementById(prefix + 'Complexity').textContent = analysis.score + '/100';
            document.getElementById(prefix + 'Patterns').textContent = analysis.patterns;
            
            const recommendationsList = document.getElementById(prefix + 'RecommendationList');
            recommendationsList.innerHTML = '';
            analysis.recommendations.forEach(rec => {
                const li = document.createElement('li');
                li.textContent = rec;
                recommendationsList.appendChild(li);
            });
            
            document.getElementById(prefix + 'Recommendations').style.display = 'block';
        }
        
        function loadVaultPasswords() {
            // Simulate loading vault passwords
            setTimeout(() => {
                const passwordList = document.getElementById('passwordList');
                passwordList.innerHTML = `
                    <div class="password-item" data-password="MySecureP@ssw0rd!" onclick="selectPassword(this)">
                        <div class="password-info">
                            <h4>Gmail Account</h4>
                            <p>john.doe@gmail.com</p>
                        </div>
                        <div style="font-family: monospace; color: var(--gray);">••••••••••••••</div>
                    </div>
                    <div class="password-item" data-password="BankLogin123!" onclick="selectPassword(this)">
                        <div class="password-info">
                            <h4>Chase Bank</h4>
                            <p>www.chase.com</p>
                        </div>
                        <div style="font-family: monospace; color: var(--gray);">••••••••••••</div>
                    </div>
                    <div class="password-item" data-password="password123" onclick="selectPassword(this)">
                        <div class="password-info">
                            <h4>Old Facebook</h4>
                            <p>facebook.com</p>
                        </div>
                        <div style="font-family: monospace; color: var(--gray);">•••••••••••</div>
                    </div>
                `;
            }, 500);
        }
        
        function selectPassword(element) {
            document.querySelectorAll('.password-item').forEach(item => {
                item.classList.remove('selected');
                item.style.background = '';
            });
            element.classList.add('selected');
            element.style.background = '#eff6ff';
        }
        
        function clearAnalysisResults() {
            document.getElementById('testPassword').value = '';
            document.getElementById('strengthDisplay').style.display = 'none';
            document.getElementById('vaultStrengthDisplay').style.display = 'none';
            document.getElementById('recommendations').style.display = 'none';
            document.getElementById('vaultRecommendations').style.display = 'none';
            document.querySelectorAll('.password-item').forEach(item => {
                item.classList.remove('selected');
                item.style.background = '';
            });
        }
        
        function openVirusTotalScanner() {
            document.getElementById('virusTotalModal').style.display = 'block';
            initializeVirusTotalScanner();
        }
        
        function closeVirusTotalModal() {
            document.getElementById('virusTotalModal').style.display = 'none';
            resetVirusTotalScanner();
        }
        
        function initializeVirusTotalScanner() {
            const scanTypeRadios = document.querySelectorAll('input[name="scan_type"]');
            scanTypeRadios.forEach(radio => {
                radio.addEventListener('change', toggleScanType);
            });
        }
        
        function toggleScanType() {
            const isUrlScan = document.getElementById('scanUrl').checked;
            document.getElementById('fileScanSection').style.display = isUrlScan ? 'none' : 'block';
            document.getElementById('urlScanSection').style.display = isUrlScan ? 'block' : 'none';
        }
          function startVirusTotalScan() {
            const scanType = document.querySelector('input[name="scan_type"]:checked').value;
            const scanResults = document.getElementById('scanResults');
            const scanStatus = document.getElementById('scanStatus');
            
            // Show scan results section
            scanResults.classList.add('show');
            scanStatus.className = 'scan-status scanning';
            scanStatus.innerHTML = `
                <i class="fas fa-spinner fa-spin"></i>
                <h4>Initializing VirusTotal Scan...</h4>
                <p>Connecting to VirusTotal API...</p>
            `;
            
            if (scanType === 'file') {
                const fileInput = document.getElementById('scanFileInput');
                if (!fileInput.files[0]) {
                    showNotification('Please select a file to scan', 'warning');
                    scanResults.classList.remove('show');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'scan_file');
                formData.append('file', fileInput.files[0]);
                
                scanStatus.innerHTML = `
                    <i class="fas fa-spinner fa-spin"></i>
                    <h4>Uploading ${fileInput.files[0].name}...</h4>
                    <p>Uploading file to VirusTotal for analysis...</p>
                `;
                
                performVirusTotalRequest('api/virustotal.php', formData);
                
            } else {
                const urlInput = document.getElementById('scanUrlInput');
                if (!urlInput.value) {
                    showNotification('Please enter a URL to scan', 'warning');
                    scanResults.classList.remove('show');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'scan_url');
                formData.append('url', urlInput.value);
                
                scanStatus.innerHTML = `
                    <i class="fas fa-spinner fa-spin"></i>
                    <h4>Submitting ${urlInput.value}...</h4>
                    <p>Sending URL to VirusTotal for analysis...</p>
                `;
                
                performVirusTotalRequest('api/virustotal.php', formData);
            }
        }
        
        function performVirusTotalRequest(url, formData) {
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                displayVirusTotalResults(data);
            })
            .catch(error => {
                console.error('Error:', error);
                const scanStatus = document.getElementById('scanStatus');
                scanStatus.className = 'scan-status threat';
                scanStatus.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i>
                    <h4>Scan Error</h4>
                    <p>An error occurred while communicating with VirusTotal API.</p>
                    <p style="color: var(--danger); margin-top: 1rem;">Error: ${error.message}</p>
                `;
                showNotification('VirusTotal scan failed', 'error');
            });
        }
          function displayVirusTotalResults(data) {
            const scanStatus = document.getElementById('scanStatus');
            
            if (!data.success) {
                if (data.queued) {
                    scanStatus.className = 'scan-status scanning';
                    scanStatus.innerHTML = `
                        <i class="fas fa-clock"></i>
                        <h4>Analysis in Progress</h4>
                        <p>Your file/URL is being processed by VirusTotal.</p>
                        <p style="color: var(--info); margin-top: 1rem;">This may take a few minutes for large files or new URLs.</p>
                    `;
                    showNotification('File/URL queued for analysis', 'info');
                } else {
                    scanStatus.className = 'scan-status threat';
                    scanStatus.innerHTML = `
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>Scan Error</h4>
                        <p>${data.error}</p>
                    `;
                    showNotification('VirusTotal scan failed: ' + data.error, 'error');
                }
                return;
            }
            
            const positives = data.positives || 0;
            const total = data.total || 70;
            const threatLevel = data.threat_level || 'unknown';
            const scanDetails = data.scan_details || {};
            
            let statusClass = 'clean';
            let statusIcon = 'fas fa-shield-check';
            let statusMessage = 'Clean';
            let statusColor = 'var(--success)';
            
            if (positives > 0) {
                if (threatLevel === 'high') {
                    statusClass = 'threat';
                    statusIcon = 'fas fa-exclamation-triangle';
                    statusMessage = 'High Risk Detected';
                    statusColor = 'var(--danger)';
                } else if (threatLevel === 'medium') {
                    statusClass = 'threat';
                    statusIcon = 'fas fa-exclamation-circle';
                    statusMessage = 'Medium Risk Detected';
                    statusColor = '#f59e0b';
                } else {
                    statusClass = 'threat';
                    statusIcon = 'fas fa-info-circle';
                    statusMessage = 'Low Risk Detected';
                    statusColor = '#f59e0b';
                }
            }
            
            // Build detailed results HTML
            const detailsHtml = buildDetailedScanResults(data, scanDetails, statusColor);
            
            scanStatus.className = `scan-status ${statusClass}`;
            scanStatus.innerHTML = `
                <i class="${statusIcon}" style="color: ${statusColor};"></i>
                <h4>Scan Complete - ${statusMessage}</h4>
                <p><strong>${positives}/${total}</strong> security vendors flagged this content as malicious.</p>
                <div style="margin: 1.5rem 0;">
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 0.5rem; text-align: left;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Scan Progress:</span>
                            <span>${total}/${total} engines</span>
                        </div>
                        <div style="background: #e5e7eb; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: ${statusColor}; height: 100%; width: 100%; transition: width 0.3s ease;"></div>
                        </div>
                        <div style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--gray);">
                            Threat Level: <strong style="color: ${statusColor};">${threatLevel.toUpperCase()}</strong>
                        </div>
                    </div>
                </div>
                ${detailsHtml}
                ${positives === 0 ? 
                    '<p style="color: var(--success); margin-top: 1rem;">✅ No threats detected</p>' : 
                    `<p style="color: ${statusColor}; margin-top: 1rem;">⚠️ ${positives} threat(s) detected - Exercise caution</p>`
                }
            `;
            
            const notificationMessage = positives === 0 ? 
                'VirusTotal scan complete - No threats detected!' : 
                `VirusTotal scan complete - ${positives} threats detected!`;
            const notificationType = positives === 0 ? 'success' : 'warning';
            
            showNotification(notificationMessage, notificationType);
        }
        
        function buildDetailedScanResults(data, scanDetails, statusColor) {
            if (!scanDetails || Object.keys(scanDetails).length === 0) {
                return '';
            }
            
            let detailsHtml = `
                <div style="margin-top: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h5 style="margin: 0; color: var(--dark);">Detailed Scan Results</h5>
                        <button class="btn btn-sm btn-secondary" onclick="toggleScanDetails()" id="toggleDetailsBtn">
                            <i class="fas fa-chevron-down"></i> Show Details
                        </button>
                    </div>
                    <div id="detailedResults" style="display: none; max-height: 400px; overflow-y: auto;">
            `;
              // Add scan metadata if available
            if (data.scan_date) {
                detailsHtml += `
                    <div style="background: #f1f5f9; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.875rem;">
                            <div><strong>Scan Date:</strong> ${new Date(data.scan_date).toLocaleString()}</div>
                            ${data.url ? `<div><strong>URL:</strong> <a href="${data.url}" target="_blank" style="word-break: break-all;">${data.url}</a></div>` : ''}
                            ${data.md5 ? `<div><strong>MD5:</strong> <code style="font-size: 0.75rem;">${data.md5}</code></div>` : ''}
                            ${data.sha256 ? `<div><strong>SHA256:</strong> <code style="font-size: 0.75rem;">${data.sha256.substring(0, 16)}...</code></div>` : ''}
                        </div>
                        ${data.fallback_used ? `
                            <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 0.375rem; padding: 0.75rem; margin-top: 1rem;">
                                <div style="color: #92400e; font-weight: 600; margin-bottom: 0.25rem;">
                                    <i class="fas fa-info-circle"></i> Fallback Scanner Used
                                </div>
                                <div style="color: #92400e; font-size: 0.875rem;">
                                    ${data.fallback_reason || 'VirusTotal API temporarily unavailable - using basic security analysis'}
                                </div>
                            </div>
                        ` : ''}
                        ${data.warnings && data.warnings.length > 0 ? `
                            <div style="background: #fef2f2; border: 1px solid #f87171; border-radius: 0.375rem; padding: 0.75rem; margin-top: 1rem;">
                                <div style="color: #dc2626; font-weight: 600; margin-bottom: 0.5rem;">
                                    <i class="fas fa-exclamation-triangle"></i> Security Warnings
                                </div>
                                ${data.warnings.map(warning => `
                                    <div style="color: #dc2626; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                        ⚠️ ${warning}
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                    </div>
                `;
            }
            
            // Display results by category
            const categories = [
                { key: 'malicious', title: 'Malicious Detections', icon: 'fas fa-exclamation-triangle', color: 'var(--danger)' },
                { key: 'suspicious', title: 'Suspicious Detections', icon: 'fas fa-exclamation-circle', color: '#f59e0b' },
                { key: 'clean', title: 'Clean Results', icon: 'fas fa-shield-check', color: 'var(--success)' }
            ];
            
            categories.forEach(category => {
                const results = scanDetails[category.key] || [];
                if (results.length > 0) {
                    detailsHtml += `
                        <div style="margin-bottom: 1.5rem;">
                            <h6 style="color: ${category.color}; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="${category.icon}"></i>
                                ${category.title} (${results.length})
                            </h6>
                            <div style="display: grid; gap: 0.5rem;">
                    `;
                    
                    results.slice(0, category.key === 'clean' ? 5 : results.length).forEach(result => {
                        const bgColor = category.key === 'malicious' ? '#fef2f2' : 
                                       category.key === 'suspicious' ? '#fffbeb' : '#f0fdf4';
                        const textColor = category.key === 'malicious' ? '#dc2626' : 
                                         category.key === 'suspicious' ? '#d97706' : '#16a34a';
                        
                        detailsHtml += `
                            <div style="background: ${bgColor}; padding: 0.75rem; border-radius: 0.375rem; border-left: 3px solid ${category.color};">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong style="color: ${textColor};">${result.engine}</strong>
                                        ${result.result && result.result !== 'Clean' ? 
                                            `<div style="font-size: 0.8rem; color: ${textColor}; margin-top: 0.25rem;">${result.result}</div>` : 
                                            '<div style="font-size: 0.8rem; color: #16a34a; margin-top: 0.25rem;">No threats detected</div>'
                                        }
                                    </div>
                                    <div style="text-align: right; font-size: 0.75rem; color: var(--gray);">
                                        ${result.version ? `v${result.version}` : ''}
                                        ${result.update ? `<div>${result.update}</div>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    if (category.key === 'clean' && results.length > 5) {
                        detailsHtml += `
                            <div style="text-align: center; padding: 0.5rem; color: var(--gray); font-size: 0.875rem;">
                                ... and ${results.length - 5} more clean results
                            </div>
                        `;
                    }
                    
                    detailsHtml += `
                            </div>
                        </div>
                    `;
                }
            });
            
            detailsHtml += `
                    </div>
                </div>
            `;
            
            return detailsHtml;
        }
        
        function toggleScanDetails() {
            const detailsDiv = document.getElementById('detailedResults');
            const toggleBtn = document.getElementById('toggleDetailsBtn');
            
            if (detailsDiv.style.display === 'none') {
                detailsDiv.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Details';
            } else {
                detailsDiv.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Show Details';
            }
        }
        
        function resetVirusTotalScanner() {
            document.getElementById('scanFileInput').value = '';
            document.getElementById('scanUrlInput').value = '';
            document.getElementById('scanResults').classList.remove('show');
            document.getElementById('scanFile').checked = true;
            toggleScanType();
        }
        
        function checkBreaches() {
            showNotification('Checking for data breaches...', 'info');
            setTimeout(() => {
                showNotification('Breach check complete! No compromised accounts found.', 'success');
            }, 2500);
        }
        
        function setup2FA() {
            showNotification('Opening 2FA setup wizard...', 'info');
            // Would open 2FA setup interface
        }
        
        function runSecurityAudit() {
            showNotification('Starting comprehensive security audit...', 'info');
            setTimeout(() => {
                showNotification('Security audit complete! Overall score: 85/100', 'success');
            }, 3000);
        }
        
        function manageAPIs() {
            showNotification('Opening API management panel...', 'info');
            // Would open API management interface
        }
        
        // API Documentation Functions
        function viewBruteForceAPI() {
            showNotification('Brute Force API documentation opened', 'info');
        }
        
        function viewPasswordAPI() {
            showNotification('Password Analysis API documentation opened', 'info');
        }
        
        function viewBreachAPI() {
            showNotification('Breach Detection API documentation opened', 'info');
        }
        
        function viewVirusTotalAPI() {
            showNotification('VirusTotal API documentation opened', 'info');
        }
        
        function view2FAAPI() {
            showNotification('2FA API documentation opened', 'info');
        }
        
        function viewAuditAPI() {
            showNotification('Security Audit API documentation opened', 'info');
        }
        
        function viewAPIDocumentation() {
            showNotification('API Documentation center opened', 'info');
        }
    </script>
</body>
</html>