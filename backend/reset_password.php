<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\reset_password.php
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
$token = '';
$validToken = false;

// Get token from URL
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    try {
        $user = new User();
        $validToken = $user->validateResetToken($token);
        
        if (!$validToken) {
            $error = 'This password reset link is invalid or has expired. If you have an account with us, please try requesting a new password reset.';
        }
    } catch (Exception $e) {
        $error = 'This password reset link is invalid or has expired. If you have an account with us, please try requesting a new password reset.';
        error_log('Token validation error: ' . $e->getMessage());
    }
} else {
    $error = 'No reset token provided. Please use the password reset feature from the login page.';
}

// Enhanced password validation function
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 14) {
        $errors[] = 'Password must be at least 14 characters long';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $resetToken = $_POST['token'] ?? '';
    
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Validate password strength
        $passwordErrors = validatePassword($password);
        if (!empty($passwordErrors)) {
            $error = implode('. ', $passwordErrors);
        } else {
            try {
                $user = new User();
                $resetResult = $user->resetPassword($resetToken, $password);
                  if ($resetResult) {
                    $success = 'Your password has been successfully reset! You can now sign in with your new password.';
                    $validToken = false; // Prevent further password resets
                } else {
                    $error = 'Password reset failed. This link may have expired or been used already. Please request a new password reset if you have an account with us.';
                }
            } catch (Exception $e) {
                $error = 'Password reset failed: ' . $e->getMessage();
                error_log('Password reset error: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SecureIt</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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

        .password-strength {
            margin-top: 12px;
        }

        .strength-bar {
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            margin: 8px 0;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            background: #ef4444;
            transition: all 0.3s ease;
            width: 0%;
            border-radius: 3px;
        }

        .strength-fill.weak { background: #ef4444; width: 20%; }
        .strength-fill.fair { background: #f59e0b; width: 40%; }
        .strength-fill.good { background: #eab308; width: 60%; }
        .strength-fill.strong { background: #22c55e; width: 80%; }
        .strength-fill.very-strong { background: #16a34a; width: 100%; }

        .strength-text {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .requirements {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }

        .requirements h4 {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: #374151;
        }

        .requirements ul {
            list-style: none;
            padding: 0;
        }

        .requirements li {
            padding: 4px 0;
            font-size: 0.75rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirements li i {
            width: 12px;
            font-size: 0.625rem;
        }

        .requirement-met {
            color: #16a34a;
        }

        .requirement-unmet {
            color: #dc2626;
        }

        .password-match {
            margin-top: 8px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .match-success {
            color: #16a34a;
        }

        .match-error {
            color: #dc2626;
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
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #9ca3af;
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
                <p>Create your new password</p>
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

            <?php if ($validToken && !$success): ?>
                <form method="POST" id="resetPasswordForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Create a strong new password" required>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthBar"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Password strength will appear here</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   placeholder="Confirm your new password" required>
                        </div>
                        <div class="password-match" id="passwordMatch"></div>
                    </div>

                    <div class="requirements">
                        <h4>Password Requirements:</h4>
                        <ul id="requirements">
                            <li id="req-length"><i class="fas fa-times requirement-unmet"></i> At least 14 characters</li>
                            <li id="req-upper"><i class="fas fa-times requirement-unmet"></i> One uppercase letter</li>
                            <li id="req-lower"><i class="fas fa-times requirement-unmet"></i> One lowercase letter</li>
                            <li id="req-number"><i class="fas fa-times requirement-unmet"></i> One number</li>
                            <li id="req-special"><i class="fas fa-times requirement-unmet"></i> One special character</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn" id="submitBtn" disabled>
                        <i class="fas fa-save"></i>
                        Reset Password
                    </button>
                </form>
            <?php endif; ?>

            <div class="divider">
                <span><?php echo $success ? 'Ready to sign in?' : 'Need help?'; ?></span>
            </div>

            <div class="links">
                <?php if ($success): ?>
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt"></i> Sign In Now
                    </a>
                <?php else: ?>
                    <a href="forgot_password.php">
                        <i class="fas fa-arrow-left"></i> Request New Reset Link
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="features">
            <div class="features-header">
                <h2>Secure Password Reset</h2>
                <p>Your security is our top priority</p>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-key"></i>
                </div>
                <div class="feature-content">
                    <h3>Strong Requirements</h3>
                    <p>Your new password must be at least 14 characters with mixed case letters, numbers, and symbols.</p>
                </div>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="feature-content">
                    <h3>Encrypted Storage</h3>
                    <p>Passwords are hashed using industry-standard encryption before being stored in our database.</p>
                </div>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="feature-content">
                    <h3>One-Time Use</h3>
                    <p>Reset tokens can only be used once and expire automatically for maximum security.</p>
                </div>
            </div>

            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="feature-content">
                    <h3>Instant Protection</h3>
                    <p>Your new password takes effect immediately and secures your entire vault.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            const passwordMatch = document.getElementById('passwordMatch');
            const submitBtn = document.getElementById('submitBtn');

            if (passwordInput && confirmPasswordInput) {
                // Password strength checking
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    const strength = checkPasswordStrength(password);
                    updatePasswordStrength(strength, password);
                    updateRequirements(password);
                    checkFormValidity();
                });

                // Password confirmation checking
                confirmPasswordInput.addEventListener('input', function() {
                    const password = passwordInput.value;
                    const confirmPassword = this.value;
                    
                    if (confirmPassword.length > 0) {
                        if (password === confirmPassword) {
                            passwordMatch.innerHTML = '<i class="fas fa-check"></i> Passwords match';
                            passwordMatch.className = 'password-match match-success';
                        } else {
                            passwordMatch.innerHTML = '<i class="fas fa-times"></i> Passwords do not match';
                            passwordMatch.className = 'password-match match-error';
                        }
                    } else {
                        passwordMatch.innerHTML = '';
                        passwordMatch.className = 'password-match';
                    }
                    checkFormValidity();
                });

                function checkPasswordStrength(password) {
                    let score = 0;
                    let bonusScore = 0;
                    
                    // Basic requirements
                    if (password.length >= 14) score++;
                    if (/[a-z]/.test(password)) score++;
                    if (/[A-Z]/.test(password)) score++;
                    if (/[0-9]/.test(password)) score++;
                    if (/[^A-Za-z0-9]/.test(password)) score++;
                    
                    // Bonus points for extra length and complexity
                    if (password.length >= 20) bonusScore++;
                    if ((password.match(/[^A-Za-z0-9]/g) || []).length >= 2) bonusScore++;
                    if ((password.match(/[0-9]/g) || []).length >= 2) bonusScore++;
                    
                    return Math.min(5, score + bonusScore);
                }

                function updatePasswordStrength(score, password) {
                    const strengthClasses = ['', 'weak', 'fair', 'good', 'strong', 'very-strong'];
                    const strengthTexts = ['', 'Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
                    const strengthColors = ['#6b7280', '#ef4444', '#f59e0b', '#eab308', '#22c55e', '#16a34a'];
                    
                    strengthBar.className = 'strength-fill ' + (strengthClasses[score] || '');
                    
                    if (password.length === 0) {
                        strengthText.textContent = 'Password strength will appear here';
                        strengthText.style.color = '#6b7280';
                    } else {
                        strengthText.textContent = strengthTexts[score] || 'Very Weak';
                        strengthText.style.color = strengthColors[score] || '#ef4444';
                    }
                }

                function updateRequirements(password) {
                    const requirements = [
                        { id: 'req-length', test: password.length >= 14 },
                        { id: 'req-upper', test: /[A-Z]/.test(password) },
                        { id: 'req-lower', test: /[a-z]/.test(password) },
                        { id: 'req-number', test: /[0-9]/.test(password) },
                        { id: 'req-special', test: /[^A-Za-z0-9]/.test(password) }
                    ];

                    requirements.forEach(req => {
                        const element = document.getElementById(req.id);
                        if (element) {
                            const icon = element.querySelector('i');
                            
                            if (req.test) {
                                icon.className = 'fas fa-check requirement-met';
                            } else {
                                icon.className = 'fas fa-times requirement-unmet';
                            }
                        }
                    });
                }

                function checkFormValidity() {
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    
                    const isPasswordValid = password.length >= 14 && 
                                          /[a-z]/.test(password) && 
                                          /[A-Z]/.test(password) && 
                                          /[0-9]/.test(password) && 
                                          /[^A-Za-z0-9]/.test(password);
                    
                    const isFormValid = isPasswordValid && password === confirmPassword;
                    
                    if (submitBtn) {
                        submitBtn.disabled = !isFormValid;
                    }
                }

                // Form validation
                const form = document.getElementById('resetPasswordForm');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const password = passwordInput.value;
                        const confirmPassword = confirmPasswordInput.value;
                        
                        if (password !== confirmPassword) {
                            e.preventDefault();
                            passwordMatch.innerHTML = '<i class="fas fa-times"></i> Passwords do not match';
                            passwordMatch.className = 'password-match match-error';
                            return;
                        }
                        
                        // Check password requirements
                        if (password.length < 14 || 
                            !/[a-z]/.test(password) || 
                            !/[A-Z]/.test(password) || 
                            !/[0-9]/.test(password) || 
                            !/[^A-Za-z0-9]/.test(password)) {
                            e.preventDefault();
                            alert('Please ensure your password meets all requirements.');
                            return;
                        }

                        // Show loading state
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting Password...';
                    });
                }

                // Initialize form validation
                checkFormValidity();
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
