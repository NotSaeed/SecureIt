<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\landing.php
session_start();
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Vault.php';

// Get system stats for display
try {
    $db = new Database();
    $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
    $vaultCount = $db->fetchOne("SELECT COUNT(*) as count FROM vaults")['count'] ?? 0;
    $systemStatus = 'online';
} catch (Exception $e) {
    $userCount = 0;
    $vaultCount = 0;
    $systemStatus = 'offline';
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureIt - Professional Password Manager</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --secondary: #3b82f6;
            --accent: #06b6d4;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #020617;
            --dark-light: #334155;
            --gray: #64748b;
            --gray-light: #f8fafc;
            --white: #ffffff;
            --gradient-1: #0f172a;
            --gradient-2: #1e293b;
            --gradient-3: #334155;
            --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --shadow-blue: 0 20px 25px -5px rgba(59, 130, 246, 0.1), 0 10px 10px -5px rgba(59, 130, 246, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background: linear-gradient(135deg, var(--gradient-1) 0%, var(--gradient-2) 35%, var(--gradient-3) 100%);
            background-attachment: fixed;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 25% 25%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(6, 182, 212, 0.1) 0%, transparent 50%);
            z-index: -1;
            pointer-events: none;
        }        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(15, 23, 42, 0.1);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo i {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.8rem;
        }
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .logo i {
            font-size: 2rem;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: var(--white);
            box-shadow: var(--shadow-blue);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 25px 50px -12px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: var(--white);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 25px 50px -12px rgba(239, 68, 68, 0.4);
        }
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: var(--white);
        }

        .btn-success {
            background: linear-gradient(45deg, var(--secondary), #059669);
            color: var(--white);
        }

        .btn-danger {
            background: linear-gradient(45deg, var(--danger), #dc2626);
            color: var(--white);
        }

        /* Hero Section */        .hero {
            margin-top: 100px;
            padding: 6rem 0;
            text-align: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 30% 20%, rgba(59, 130, 246, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(6, 182, 212, 0.2) 0%, transparent 50%);
            z-index: -1;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 50%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.1;
            letter-spacing: -0.02em;
        }

        .hero .highlight {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 900;
        }

        .hero p {
            font-size: 1.375rem;
            margin-bottom: 3rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 400;
            line-height: 1.7;
        }

        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 4rem;
        }

        .hero-buttons .btn {
            padding: 1rem 2rem;
            font-size: 1.125rem;
            min-width: 200px;
        }        /* Stats Section */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 5rem;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 2.5rem 2rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(6, 182, 212, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ffffff 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
            font-weight: 500;
            color: var(--white);
            position: relative;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.8;
        }

        /* Features Section */        .features {
            padding: 8rem 0;
            background: linear-gradient(180deg, var(--white) 0%, var(--gray-light) 100%);
            position: relative;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(6, 182, 212, 0.05) 0%, transparent 40%);
            pointer-events: none;
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .section-header {
            text-align: center;
            margin-bottom: 5rem;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }

        .section-subtitle {
            font-size: 1.25rem;
            color: var(--gray);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.7;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
        }

        .feature-card {
            background: var(--white);
            border-radius: 1.5rem;
            padding: 3rem 2.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            border: 1px solid rgba(59, 130, 246, 0.1);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary), var(--accent));
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .feature-icon {
            width: 5rem;
            height: 5rem;
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            border-radius: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
        }

        .feature-icon i {
            font-size: 2rem;
            color: var(--white);
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .feature-description {
            color: var(--gray);
            line-height: 1.7;
            font-size: 1.125rem;
        }

        /* Security Section */
        .security {
            padding: 6rem 0;
            background: linear-gradient(135deg, var(--dark) 0%, var(--dark-light) 100%);
            color: var(--white);
        }

        .security-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .security-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .security-icon {
            width: 3rem;
            height: 3rem;
            background: var(--secondary);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .security-icon i {
            color: var(--white);
            font-size: 1.25rem;
        }

        .security-text h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .security-text p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Dashboard Section */
        .dashboard {
            padding: 6rem 0;
            background: var(--gray-light);
        }

        .dashboard-preview {
            background: var(--white);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            margin-top: 3rem;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .dashboard-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            background: var(--gray-light);
            color: var(--gray);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab.active {
            background: var(--primary);
            color: var(--white);
        }

        .vault-items {
            display: grid;
            gap: 1rem;
        }

        .vault-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--gray-light);
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .vault-item:hover {
            background: #e5e7eb;
        }

        .vault-icon {
            width: 2.5rem;
            height: 2.5rem;
            background: var(--primary);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: var(--white);
            padding: 4rem 0 2rem;
            margin-top: 5rem;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 80% 80%, rgba(6, 182, 212, 0.1) 0%, transparent 40%);
            pointer-events: none;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-section h3,
        .footer-section h4 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--white);
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .footer-logo i {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.8rem;
        }

        .footer-section p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .footer-stats {
            display: flex;
            gap: 2rem;
        }

        .footer-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            min-width: 80px;
        }

        .footer-stat .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
        }

        .footer-stat .stat-label {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.75rem;
        }

        .footer-section ul li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }

        .footer-section ul li a:hover {
            color: var(--accent);
        }

        .footer-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-bottom-left p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.5rem;
        }

        .footer-links {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--accent);
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .social-links a:hover {
            background: var(--accent);
            color: var(--white);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .footer-stats {
                justify-content: center;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-links {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <a href="#" class="logo">
                <i class="fas fa-shield-alt"></i>
                <span>SecureIt</span>
            </a>
            
            <div class="nav-links">
                <?php if ($isLoggedIn): ?>                    <a href="vault.php" class="nav-link">
                        <i class="fas fa-vault"></i> Dashboard
                    </a>
                    <span class="nav-link">Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']) ?></span>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="#features" class="nav-link">Features</a>
                    <a href="#security" class="nav-link">Security</a>
                    <a href="#pricing" class="nav-link">Pricing</a>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero fade-in">
        <div class="hero-container">
            <h1>Your Digital Life, <br><span class="highlight">Completely Secure</span></h1>
            <p>SecureIt is the most trusted password manager for individuals, teams, and enterprises. Store, share, and sync your passwords across all devices with military-grade security.</p>
            
            <div class="hero-buttons">
                <?php if ($isLoggedIn): ?>                    <a href="vault.php" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                    <a href="vault.php?action=generate" class="btn btn-secondary">
                        <i class="fas fa-key"></i> Generate Password
                    </a>
                <?php else: ?>                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Start Free Trial
                    </a>
                    <a href="#features" class="btn btn-secondary">
                        <i class="fas fa-play"></i> Watch Demo
                    </a>
                <?php endif; ?>
            </div>

            <!-- Live Stats -->
            <div class="stats slide-up">
                <div class="stat-card">
                    <div class="stat-number" id="userCount"><?= number_format($userCount) ?></div>
                    <div class="stat-label">Trusted Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="vaultCount"><?= number_format($vaultCount) ?></div>
                    <div class="stat-label">Passwords Protected</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">256-bit</div>
                    <div class="stat-label">AES Encryption</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Uptime SLA</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="features-container">
            <div class="section-header">
                <h2 class="section-title">Everything You Need to Stay Secure</h2>
                <p class="section-subtitle">Powerful features designed to protect your digital identity and make password management effortless.</p>
            </div>

            <div class="features-grid">
                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-vault"></i>
                    </div>
                    <h3 class="feature-title">Secure Password Vault</h3>
                    <p class="feature-description">Store unlimited passwords, credit cards, and secure notes with bank-level encryption. Access your vault from anywhere, anytime.</p>
                </div>

                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <h3 class="feature-title">Password Generator</h3>
                    <p class="feature-description">Create strong, unique passwords instantly. Customize length, complexity, and character types to meet any requirement.</p>
                </div>

                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-sync"></i>
                    </div>
                    <h3 class="feature-title">Cross-Platform Sync</h3>
                    <p class="feature-description">Seamlessly sync your passwords across all devices - Windows, Mac, Linux, iOS, and Android. Always have access when you need it.</p>
                </div>

                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Advanced Security</h3>
                    <p class="feature-description">Two-factor authentication, biometric unlock, and security audits keep your accounts safe from breaches and attacks.</p>
                </div>

                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-share-alt"></i>
                    </div>
                    <h3 class="feature-title">Secure Sharing</h3>
                    <p class="feature-description">Share passwords and sensitive information securely with family, friends, or team members. Control access and revoke anytime.</p>
                </div>

                <div class="feature-card fade-in">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Security Reports</h3>
                    <p class="feature-description">Get detailed insights about your password health. Identify weak, reused, or compromised passwords with actionable recommendations.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Security Section -->
    <section class="security" id="security">
        <div class="features-container">
            <div class="section-header">
                <h2 class="section-title" style="color: white;">Bank-Level Security You Can Trust</h2>
                <p class="section-subtitle" style="color: rgba(255,255,255,0.8);">Your security is our top priority. We use industry-leading encryption and security practices to protect your data.</p>
            </div>

            <div class="security-grid">
                <div class="security-item">
                    <div class="security-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="security-text">
                        <h4>AES-256 Encryption</h4>
                        <p>Military-grade encryption protects your data</p>
                    </div>
                </div>

                <div class="security-item">
                    <div class="security-icon">
                        <i class="fas fa-eye-slash"></i>
                    </div>
                    <div class="security-text">
                        <h4>Zero-Knowledge Architecture</h4>
                        <p>We can't see your passwords, even if we wanted to</p>
                    </div>
                </div>

                <div class="security-item">
                    <div class="security-icon">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <div class="security-text">
                        <h4>Biometric Authentication</h4>
                        <p>Unlock with face, fingerprint, or voice</p>
                    </div>
                </div>

                <div class="security-item">
                    <div class="security-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="security-text">
                        <h4>Secure Infrastructure</h4>
                        <p>Hosted on enterprise-grade cloud servers</p>
                    </div>
                </div>

                <div class="security-item">
                    <div class="security-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="security-text">
                        <h4>SOC 2 Type II Certified</h4>
                        <p>Independently audited security controls</p>
                    </div>
                </div>

                <div class="security-item">
                    <div class="security-icon">
                        <i class="fas fa-bug"></i>
                    </div>
                    <div class="security-text">
                        <h4>Bug Bounty Program</h4>
                        <p>Continuous security testing by experts</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Preview Section -->
    <section class="dashboard">
        <div class="features-container">
            <div class="section-header">
                <h2 class="section-title">Powerful Dashboard, Simple Interface</h2>
                <p class="section-subtitle">Manage all your passwords, secure notes, and digital assets from one beautiful, intuitive dashboard.</p>
            </div>

            <div class="dashboard-preview">
                <div class="dashboard-header">
                    <h3>
                        <i class="fas fa-tachometer-alt" style="color: var(--primary);"></i>
                        Your Security Dashboard
                    </h3>
                    <div class="status-indicator status-<?= $systemStatus ?>">
                        <i class="fas fa-circle"></i>
                        System <?= ucfirst($systemStatus) ?>
                    </div>
                </div>

                <div class="dashboard-tabs">
                    <button class="tab active" onclick="showTab('passwords')">
                        <i class="fas fa-key"></i> Passwords
                    </button>
                    <button class="tab" onclick="showTab('cards')">
                        <i class="fas fa-credit-card"></i> Cards
                    </button>
                    <button class="tab" onclick="showTab('notes')">
                        <i class="fas fa-sticky-note"></i> Notes
                    </button>
                    <button class="tab" onclick="showTab('reports')">
                        <i class="fas fa-chart-bar"></i> Reports
                    </button>
                </div>

                <div class="vault-items" id="dashboard-content">
                    <!-- Sample vault items -->
                    <div class="vault-item">
                        <div class="vault-icon">
                            <i class="fab fa-google"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4>Google Account</h4>
                            <p style="color: var(--gray); font-size: 0.9rem;">john.doe@gmail.com</p>
                        </div>
                        <div style="color: var(--secondary);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>

                    <div class="vault-item">
                        <div class="vault-icon">
                            <i class="fab fa-github"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4>GitHub</h4>
                            <p style="color: var(--gray); font-size: 0.9rem;">johndoe_dev</p>
                        </div>
                        <div style="color: var(--accent);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>

                    <div class="vault-item">
                        <div class="vault-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4>Main Credit Card</h4>
                            <p style="color: var(--gray); font-size: 0.9rem;">•••• •••• •••• 4532</p>
                        </div>
                        <div style="color: var(--secondary);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-shield-alt"></i>
                        <span>SecureIt</span>
                    </div>
                    <p>The most trusted password manager for individuals, teams, and enterprises worldwide.</p>
                    
                    <div class="footer-stats">
                        <div class="footer-stat">
                            <div class="stat-number" id="userCountFooter"><?= number_format($userCount) ?></div>
                            <div class="stat-label">Users</div>
                        </div>
                        <div class="footer-stat">
                            <div class="stat-number" id="vaultCountFooter"><?= number_format($vaultCount) ?></div>
                            <div class="stat-label">Vaults</div>
                        </div>
                    </div>
                </div>

                <div class="footer-section">
                    <h3>Product</h3>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#security">Security</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                        <li><a href="#download">Downloads</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Company</h3>
                    <ul class="footer-links">
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#careers">Careers</a></li>
                        <li><a href="#press">Press</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Resources</h3>
                    <ul class="footer-links">
                        <li><a href="#help">Help Center</a></li>
                        <li><a href="#blog">Blog</a></li>
                        <li><a href="#api">API Documentation</a></li>
                        <li><a href="#status">System Status</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-bottom-left">
                    <p>&copy; 2025 SecureIt. All rights reserved.</p>
                    <p><a href="#privacy" style="color: var(--gray);">Privacy Policy</a> | <a href="#terms" style="color: var(--gray);">Terms of Service</a></p>
                </div>
                
                <div class="social-links">
                    <a href="#" style="color: var(--gray); font-size: 1.2rem;"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color: var(--gray); font-size: 1.2rem;"><i class="fab fa-facebook"></i></a>
                    <a href="#" style="color: var(--gray); font-size: 1.2rem;"><i class="fab fa-linkedin"></i></a>
                    <a href="#" style="color: var(--gray); font-size: 1.2rem;"><i class="fab fa-github"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Navigation scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Dashboard tab switching
        function showTab(tabName) {
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Update content based on tab
            const content = document.getElementById('dashboard-content');
            const sampleContent = {
                'passwords': `
                    <div class="vault-item">
                        <div class="vault-icon"><i class="fab fa-google"></i></div>
                        <div style="flex: 1;">
                            <h4>Google Account</h4>
                            <p style="color: var(--gray); font-size: 0.9rem;">john.doe@gmail.com</p>
                        </div>
                        <div style="color: var(--secondary);"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <div class="vault-item">
                        <div class="vault-icon"><i class="fab fa-github"></i></div>
                        <div style="flex: 1;">
                            <h4>GitHub</h4>
                            <p style="color: var(--gray); font-size: 0.9rem;">johndoe_dev</p>
                        </div>
                        <div style="color: var(--accent);"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                `,
                'cards': `
                    <div class="vault-item">
                        <div class="vault-icon"><i class="fas fa-credit-card"></i></div>
                        <div style="flex: 1;">
                            <h4>Main Credit Card</h4>
                            <p style="color: var(--gray); font-size: 0.9rem;">•••• •••• •••• 4532</p>
                        </div>
                        <div style="color: var(--secondary);"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <div class="vault-item">
                        <div class="vault-icon"><i class="fas fa-university"></i></div>
                        <div style="flex: 1;">
                            <h4>Business Account</h4>
                            <p style="color: var(--gray); font-size: 0.9rem;">•••• •••• •••• 8901</p>
                        </div>
                        <div style="color: var(--secondary);"><i class="fas fa-check-circle"></i></div>
                    </div>
                `,
                'notes': `
                    <div class="vault-item">
                        <div class="vault-icon"><i class="fas fa-sticky-note"></i></div>
                        <div style="flex: 1;">
                            <h4>Server SSH Keys</h4>
                            <p style="color: var(--gray); font-size: 0.9rem;">Production server access keys</p>
                        </div>
                        <div style="color: var(--secondary);"><i class="fas fa-lock"></i></div>
                    </div>
                    <div class="vault-item">
                        <div class="vault-icon"><i class="fas fa-file-alt"></i></div>
                        <div style="flex: 1;">
                            <h4>Important Documents</h4>
                            <p style="color: var(--gray); font-size: 0.9rem;">Insurance and legal documents</p>
                        </div>
                        <div style="color: var(--secondary);"><i class="fas fa-lock"></i></div>
                    </div>
                `,
                'reports': `
                    <div class="vault-item">
                        <div class="vault-icon"><i class="fas fa-chart-line"></i></div>
                        <div style="flex: 1;">
                            <h4>Password Health Score</h4>
                            <p style="color: var(--secondary); font-size: 0.9rem;">85/100 - Good security posture</p>
                        </div>
                        <div style="color: var(--secondary);"><i class="fas fa-thumbs-up"></i></div>
                    </div>
                    <div class="vault-item">
                        <div class="vault-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div style="flex: 1;">
                            <h4>Security Alerts</h4>
                            <p style="color: var(--accent); font-size: 0.9rem;">3 weak passwords detected</p>
                        </div>
                        <div style="color: var(--accent);"><i class="fas fa-warning"></i></div>
                    </div>
                `
            };
            
            content.innerHTML = sampleContent[tabName] || sampleContent['passwords'];
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);        // Observe all feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });

        // Live stats update
        function updateStats() {
            fetch('api/stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('userCount').textContent = data.stats.total_users.toLocaleString();
                        document.getElementById('vaultCount').textContent = data.stats.total_vault_items.toLocaleString();
                        document.getElementById('userCountFooter').textContent = data.stats.total_users.toLocaleString();
                        document.getElementById('vaultCountFooter').textContent = data.stats.total_vault_items.toLocaleString();
                    }
                })
                .catch(error => console.log('Stats update failed:', error));
        }

        // Update stats every 30 seconds
        setInterval(updateStats, 30000);
        
        // Initial stats load
        updateStats();

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>