<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\forgot_password.php
session_start();

// If already logged in, redirect to main vault
if (isset($_SESSION['user_id'])) {
    header('Location: main_vault.php');
    exit();
}

require_once 'classes/Database.php';
require_once 'classes/User.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            $user = new User();
            $resetToken = $user->requestPasswordReset($email);
            
            if ($resetToken) {
                // Redirect to reset password page with token
                // This happens regardless of whether email exists for security
                header('Location: reset_password.php?token=' . urlencode($resetToken));
                exit();
            } else {
                $error = 'Password reset request failed. Please try again later.';
            }
        } catch (Exception $e) {
            $error = 'Password reset request failed. Please try again later.';
            error_log('Password reset error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SecureIt</title>
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

        .auth-container {
            display: flex;
            max-width: 1200px;
            width: 100%;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            min-height: 600px;
        }

        .auth-card {
            flex: 1;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 480px;
        }

        .features {
            flex: 1;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
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

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #374151;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.875rem;
        }

        .input-group .form-control {
            padding-left: 40px;
        }

        .btn {
            width: 100%;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s ease;
            margin-bottom: 24px;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #9ca3af;
            transform: none;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .divider {
            text-align: center;
            margin: 24px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e2e8f0;
        }

        .divider span {
            background: #ffffff;
            padding: 0 16px;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .links {
            text-align: center;
        }

        .links a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }

        .links a:hover {
            color: #2563eb;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 32px;
            position: relative;
            z-index: 1;
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            flex-shrink: 0;
        }

        .feature-icon i {
            font-size: 1.25rem;
            color: white;
        }

        .feature-content h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: white;
        }

        .feature-content p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .features-header {
            text-align: center;
            margin-bottom: 48px;
            position: relative;
            z-index: 1;
        }

        .features-header h2 {
            font-size: 1.875rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .features-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
        }

        .help-text {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
            color: #475569;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .help-text i {
            color: #3b82f6;
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                margin: 16px;
            }
            
            .auth-card, .features {
                padding: 32px 24px;
            }
            
            .features {
                min-height: 300px;
            }
            
            .logo h1 {
                font-size: 1.5rem;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <h1>SecureIt</h1>
                <p>Reset your password</p>
            </div>

            <div class="help-text">
                <i class="fas fa-info-circle"></i>
                Enter your email address and we'll send you a link to reset your password. 
                The reset link will be valid for 1 hour.
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="forgotPasswordForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="Enter your email address" required 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    Send Reset Link
                </button>
            </form>

            <div class="divider">
                <span>Remember your password?</span>
            </div>

            <div class="links">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Back to Sign In
                </a>
            </div>
        </div>

        <div class="features">
            <div class="features-header">
                <h2>Password Recovery</h2>
                <p>Secure and simple password reset process</p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="feature-content">
                    <h3>Secure Process</h3>
                    <p>Our password reset process uses secure tokens that expire automatically for your protection.</p>
                </div>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="feature-content">
                    <h3>Time Limited</h3>
                    <p>Reset links are valid for only 1 hour to ensure maximum security for your account.</p>
                </div>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-envelope-open"></i>
                </div>
                <div class="feature-content">
                    <h3>Email Verification</h3>
                    <p>We'll send the reset link to your registered email address to verify your identity.</p>
                </div>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="feature-content">
                    <h3>Strong Requirements</h3>
                    <p>New passwords must meet our strong security requirements to protect your vault.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('forgotPasswordForm');
            const submitBtn = document.getElementById('submitBtn');
            const emailInput = document.getElementById('email');

            // Form submission with loading state
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            });

            // Email validation
            emailInput.addEventListener('input', function() {
                const email = this.value.trim();
                const isValid = email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                submitBtn.disabled = !isValid;
            });

            // Initial validation
            const initialEmail = emailInput.value.trim();
            if (initialEmail) {
                const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(initialEmail);
                submitBtn.disabled = !isValid;
            } else {
                submitBtn.disabled = true;
            }

            // Add animations on load
            window.addEventListener('load', function() {
                const authCard = document.querySelector('.auth-card');
                const features = document.querySelector('.features');
                
                authCard.style.animation = 'fadeInUp 0.6s ease-out';
                features.style.animation = 'fadeInUp 0.8s ease-out';
            });
        });
    </script>
</body>
</html>
