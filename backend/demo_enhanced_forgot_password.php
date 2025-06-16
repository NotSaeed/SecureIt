<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Forgot Password Flow Demo - SecureIt</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #334155;
        }

        .demo-container {
            max-width: 1200px;
            width: 100%;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            padding: 48px;
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo i {
            font-size: 2.5rem;
            color: #3b82f6;
            margin-bottom: 16px;
            display: block;
        }

        .logo h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .logo p {
            color: #64748b;
            font-size: 0.875rem;
        }

        .section {
            margin-bottom: 32px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
        }

        .section h2 {
            color: #1e293b;
            margin-bottom: 16px;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section h2 i {
            color: #3b82f6;
        }

        .flow-step {
            margin-bottom: 16px;
            padding: 12px;
            background: white;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }

        .flow-step h3 {
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .flow-step p {
            color: #64748b;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .demo-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .demo-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .demo-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .demo-btn.secondary {
            background: #6b7280;
        }

        .demo-btn.secondary:hover {
            background: #4b5563;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: #374151;
            font-size: 0.875rem;
        }

        .feature-list li i {
            color: #16a34a;
            font-size: 0.75rem;
        }

        .security-info {
            background: #dbeafe;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 16px;
            border-radius: 8px;
            margin-top: 16px;
        }

        .security-info h4 {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .security-info ul {
            margin-left: 20px;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .demo-container {
                padding: 24px;
                margin: 16px;
            }
            
            .demo-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="logo">
            <i class="fas fa-shield-alt"></i>
            <h1>SecureIt</h1>
            <p>Enhanced Forgot Password Flow - Demo</p>
        </div>

        <div class="section">
            <h2><i class="fas fa-route"></i> New User Experience Flow</h2>
            
            <div class="flow-step">
                <h3>1. User Enters Email</h3>
                <p>User submits their email address on the forgot password page. The form validates the email format in real-time.</p>
            </div>
            
            <div class="flow-step">
                <h3>2. Automatic Navigation</h3>
                <p><strong>NEW:</strong> Instead of showing a message, the user is automatically redirected to the reset password page with a secure token.</p>
            </div>
            
            <div class="flow-step">
                <h3>3. Password Reset Form</h3>
                <p>User sees the password reset form with the same strong validation rules as registration (14+ chars, mixed case, numbers, symbols).</p>
            </div>
            
            <div class="flow-step">
                <h3>4. Immediate Access</h3>
                <p>After successfully resetting the password, the user can immediately log in with their new credentials.</p>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-shield-alt"></i> Security Features</h2>
            <ul class="feature-list">
                <li><i class="fas fa-check-circle"></i> Tokens generated for all email submissions (prevents email enumeration)</li>
                <li><i class="fas fa-check-circle"></i> Only valid email addresses get working tokens stored in database</li>
                <li><i class="fas fa-check-circle"></i> Tokens expire after 1 hour automatically</li>
                <li><i class="fas fa-check-circle"></i> Single-use tokens (marked as used after password reset)</li>
                <li><i class="fas fa-check-circle"></i> Strong password requirements enforced (same as registration)</li>
                <li><i class="fas fa-check-circle"></i> No disclosure of whether email exists in system</li>
                <li><i class="fas fa-check-circle"></i> Secure password hashing with industry standards</li>
            </ul>
            
            <div class="security-info">
                <h4><i class="fas fa-info-circle"></i> How It Works Securely</h4>
                <p>The system generates tokens for all email submissions to prevent attackers from determining which emails are registered. However, only tokens for valid emails are stored in the database, so invalid tokens will show an appropriate error message on the reset page.</p>
                <ul>
                    <li>Valid email → Token stored in DB → Reset form works</li>
                    <li>Invalid email → Token not stored → Reset form shows helpful error</li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2><i class="fas fa-key"></i> Password Requirements</h2>
            <p>The reset password form enforces the same strong requirements as registration:</p>
            <ul class="feature-list">
                <li><i class="fas fa-check-circle"></i> Minimum 14 characters long</li>
                <li><i class="fas fa-check-circle"></i> At least one uppercase letter (A-Z)</li>
                <li><i class="fas fa-check-circle"></i> At least one lowercase letter (a-z)</li>
                <li><i class="fas fa-check-circle"></i> At least one number (0-9)</li>
                <li><i class="fas fa-check-circle"></i> At least one special character (!@#$%^&*)</li>
            </ul>
        </div>

        <div class="demo-buttons">
            <a href="forgot_password.php" class="demo-btn">
                <i class="fas fa-key"></i> Try Forgot Password Flow
            </a>
            <a href="login.php" class="demo-btn secondary">
                <i class="fas fa-sign-in-alt"></i> Go to Login Page
            </a>
            <a href="register.php" class="demo-btn secondary">
                <i class="fas fa-user-plus"></i> Register New Account
            </a>
            <a href="test_enhanced_forgot_password.php" class="demo-btn secondary">
                <i class="fas fa-flask"></i> Run System Tests
            </a>
        </div>

        <?php
        // Show some quick stats
        try {
            require_once 'classes/Database.php';
            $db = new Database();
            
            $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
            $activeTokens = $db->fetchOne("SELECT COUNT(*) as count FROM password_resets WHERE expires_at > NOW() AND used_at IS NULL")['count'];
            
            echo "<div class='section'>";
            echo "<h2><i class='fas fa-chart-bar'></i> System Status</h2>";
            echo "<ul class='feature-list'>";
            echo "<li><i class='fas fa-users'></i> Registered Users: $userCount</li>";
            echo "<li><i class='fas fa-key'></i> Active Reset Tokens: $activeTokens</li>";
            echo "<li><i class='fas fa-database'></i> Database: Connected</li>";
            echo "<li><i class='fas fa-shield-alt'></i> Security: Active</li>";
            echo "</ul>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div class='section'>";
            echo "<h2><i class='fas fa-exclamation-triangle'></i> System Status</h2>";
            echo "<p style='color: #dc2626;'>Database connection error. Please check your configuration.</p>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>
