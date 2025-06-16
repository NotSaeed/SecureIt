<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\register.php
session_start();

// If already logged in, redirect to vault
if (isset($_SESSION['user_id'])) {
    header('Location: vault.php');
    exit();
}

require_once 'classes/Database.php';
require_once 'classes/User.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        try {
            $user = new User();
            $registerResult = $user->register($email, $password, $name);
            
            if ($registerResult['success']) {
                $success = 'Account created successfully! You can now log in.';
                // Clear form data
                $name = $email = '';
            } else {
                $error = $registerResult['message'];
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SecureIt Password Manager</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            padding: 20px;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 3rem;
            background: linear-gradient(45deg, #48bb78, #38a169);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .logo h1 {
            font-size: 2rem;
            margin-bottom: 5px;
            background: linear-gradient(45deg, #ffffff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo p {
            opacity: 0.8;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #e2e8f0;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #f093fb;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 20px rgba(240, 147, 251, 0.3);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
        }

        .input-group .form-control {
            padding-left: 50px;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 0.9rem;
        }

        .strength-bar {
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin: 5px 0;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            background: #e53e3e;
            transition: all 0.3s ease;
            width: 0%;
        }

        .strength-fill.weak { background: #e53e3e; width: 25%; }
        .strength-fill.fair { background: #ed8936; width: 50%; }
        .strength-fill.good { background: #ecc94b; width: 75%; }
        .strength-fill.strong { background: #48bb78; width: 100%; }

        .btn {
            width: 100%;
            background: linear-gradient(45deg, #f093fb, #f5576c);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(240, 147, 251, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(229, 62, 62, 0.2);
            border: 1px solid rgba(229, 62, 62, 0.5);
            color: #feb2b2;
        }

        .alert-success {
            background: rgba(72, 187, 120, 0.2);
            border: 1px solid rgba(72, 187, 120, 0.5);
            color: #c6f6d5;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
        }

        .divider span {
            background: rgba(255, 255, 255, 0.1);
            padding: 0 15px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #90cdf4;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #ffffff;
        }

        .requirements {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .requirements h4 {
            font-size: 1rem;
            margin-bottom: 10px;
            color: #e2e8f0;
        }

        .requirements ul {
            list-style: none;
            padding: 0;
        }

        .requirements li {
            padding: 3px 0;
            font-size: 0.9rem;
            opacity: 0.8;
            display: flex;
            align-items: center;
        }

        .requirements li i {
            margin-right: 8px;
            width: 16px;
        }

        .requirement-met {
            color: #48bb78;
        }

        .requirement-unmet {
            color: #e53e3e;
        }

        @media (max-width: 480px) {
            .register-container {
                margin: 20px;
                padding: 30px 25px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <i class="fas fa-shield-alt"></i>
            <h1>SecureIt</h1>
            <p>Create your secure account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="name">Full Name</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="name" name="name" class="form-control" 
                           placeholder="Enter your full name" required value="<?php echo htmlspecialchars($name ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Create a strong password" required>
                </div>
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthBar"></div>
                    </div>
                    <span id="strengthText">Password strength will appear here</span>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           placeholder="Confirm your password" required>
                </div>
                <div id="passwordMatch" style="margin-top: 8px; font-size: 0.9rem;"></div>
            </div>

            <button type="submit" class="btn" id="submitBtn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="requirements">
            <h4>Password Requirements:</h4>
            <ul id="requirements">
                <li id="req-length"><i class="fas fa-times requirement-unmet"></i> At least 8 characters</li>
                <li id="req-upper"><i class="fas fa-times requirement-unmet"></i> One uppercase letter</li>
                <li id="req-lower"><i class="fas fa-times requirement-unmet"></i> One lowercase letter</li>
                <li id="req-number"><i class="fas fa-times requirement-unmet"></i> One number</li>
                <li id="req-special"><i class="fas fa-times requirement-unmet"></i> One special character</li>
            </ul>
        </div>

        <div class="divider">
            <span>Already have an account?</span>
        </div>

        <div class="links">
            <a href="login.php">
                <i class="fas fa-sign-in-alt"></i> Sign in to your account
            </a>
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

            // Password strength checking
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);
                updatePasswordStrength(strength, password.length);
                updateRequirements(password);
            });

            // Password confirmation checking
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = this.value;
                
                if (confirmPassword.length > 0) {
                    if (password === confirmPassword) {
                        passwordMatch.innerHTML = '<span style="color: #48bb78;"><i class="fas fa-check"></i> Passwords match</span>';
                    } else {
                        passwordMatch.innerHTML = '<span style="color: #e53e3e;"><i class="fas fa-times"></i> Passwords do not match</span>';
                    }
                } else {
                    passwordMatch.innerHTML = '';
                }
            });

            function checkPasswordStrength(password) {
                let score = 0;
                
                if (password.length >= 8) score++;
                if (/[a-z]/.test(password)) score++;
                if (/[A-Z]/.test(password)) score++;
                if (/[0-9]/.test(password)) score++;
                if (/[^A-Za-z0-9]/.test(password)) score++;
                
                return score;
            }

            function updatePasswordStrength(score, length) {
                const strengthClasses = ['', 'weak', 'fair', 'good', 'strong'];
                const strengthTexts = ['', 'Weak password', 'Fair password', 'Good password', 'Strong password'];
                
                strengthBar.className = 'strength-fill ' + (strengthClasses[score] || '');
                
                if (length === 0) {
                    strengthText.textContent = 'Password strength will appear here';
                    strengthText.style.color = 'rgba(255, 255, 255, 0.6)';
                } else {
                    strengthText.textContent = strengthTexts[score] || 'Very weak password';
                    strengthText.style.color = score >= 4 ? '#48bb78' : score >= 3 ? '#ecc94b' : '#e53e3e';
                }
            }

            function updateRequirements(password) {
                const requirements = [
                    { id: 'req-length', test: password.length >= 8 },
                    { id: 'req-upper', test: /[A-Z]/.test(password) },
                    { id: 'req-lower', test: /[a-z]/.test(password) },
                    { id: 'req-number', test: /[0-9]/.test(password) },
                    { id: 'req-special', test: /[^A-Za-z0-9]/.test(password) }
                ];

                requirements.forEach(req => {
                    const element = document.getElementById(req.id);
                    const icon = element.querySelector('i');
                    
                    if (req.test) {
                        icon.className = 'fas fa-check requirement-met';
                    } else {
                        icon.className = 'fas fa-times requirement-unmet';
                    }
                });
            }

            // Form validation
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    passwordMatch.innerHTML = '<span style="color: #e53e3e;"><i class="fas fa-times"></i> Passwords do not match</span>';
                }
            });

            // Interactive effects
            const inputs = document.querySelectorAll('.form-control');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>
