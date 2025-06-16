<?php
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gmail = trim($_POST['gmail'] ?? '');
    $appPassword = trim($_POST['app_password'] ?? '');
    
    if (empty($gmail) || !filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid Gmail address';
    } elseif (empty($appPassword)) {
        $error = 'Please enter your Gmail App Password';
    } else {
        // Update the config file
        $configPath = 'config/email_config.php';
        $configContent = file_get_contents($configPath);
        
        // Replace the username and password
        $configContent = str_replace(
            "'SMTP_USERNAME' => 'your-email@gmail.com',",
            "'SMTP_USERNAME' => '" . $gmail . "',",
            $configContent
        );
        
        $configContent = str_replace(
            "'SMTP_PASSWORD' => 'cokl ohjp vxhp naor',",
            "'SMTP_PASSWORD' => '" . $appPassword . "',",
            $configContent
        );
        
        if (file_put_contents($configPath, $configContent)) {
            $success = 'Email configuration updated successfully! You can now send anonymous emails.';
        } else {
            $error = 'Failed to update configuration file. Please check file permissions.';
        }
    }
}

// Check current configuration
$config = include 'config/email_config.php';
$isConfigured = $config['SMTP_USERNAME'] !== 'your-email@gmail.com' && !empty($config['SMTP_PASSWORD']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Setup - SecureIt</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .content {
            padding: 2rem;
        }

        .status-indicator {
            margin-bottom: 2rem;
            padding: 1rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 500;
        }

        .status-configured {
            background: #d1fae5;
            border: 2px solid #34d399;
            color: #065f46;
        }

        .status-not-configured {
            background: #fee2e2;
            border: 2px solid #f87171;
            color: #991b1b;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6b7280;
            margin-right: 1rem;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #d1fae5;
            border: 1px solid #34d399;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #f87171;
            color: #991b1b;
        }

        .help-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 2rem;
        }

        .help-section h3 {
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .help-section ol {
            margin-left: 1.5rem;
            color: #4b5563;
        }

        .help-section li {
            margin-bottom: 0.5rem;
        }

        .help-section a {
            color: #3b82f6;
            text-decoration: none;
        }

        .help-section a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope-open-text"></i> Email Setup</h1>
            <p>Configure Gmail SMTP for anonymous email sending</p>
        </div>

        <div class="content">
            <?php if ($isConfigured): ?>
                <div class="status-indicator status-configured">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong>Email is configured!</strong><br>
                        Currently using: <?= htmlspecialchars($config['SMTP_USERNAME']) ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="status-indicator status-not-configured">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Email not configured</strong><br>
                        Anonymous emails will only be logged, not sent
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="gmail">
                        <i class="fas fa-envelope"></i> Your Gmail Address
                    </label>
                    <input type="email" id="gmail" name="gmail" class="form-control" 
                           placeholder="your-email@gmail.com" 
                           value="<?= $isConfigured ? htmlspecialchars($config['SMTP_USERNAME']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label for="app_password">
                        <i class="fas fa-key"></i> Gmail App Password
                    </label>
                    <input type="password" id="app_password" name="app_password" class="form-control" 
                           placeholder="Your 16-character app password"
                           value="<?= $isConfigured ? htmlspecialchars($config['SMTP_PASSWORD']) : '' ?>" required>
                </div>

                <div style="text-align: center;">
                    <a href="main_vault.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Vault
                    </a>
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>
                </div>
            </form>

            <div class="help-section">
                <h3><i class="fas fa-question-circle"></i> How to get Gmail App Password:</h3>
                <ol>
                    <li>Go to your <a href="https://myaccount.google.com/security" target="_blank">Google Account Security</a></li>
                    <li>Enable <strong>2-Factor Authentication</strong> if not already enabled</li>
                    <li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a></li>
                    <li>Select "Mail" and your device/app name</li>
                    <li>Copy the generated 16-character password</li>
                    <li>Paste it in the field above</li>
                </ol>
                <p><strong>Note:</strong> Use your App Password, not your regular Gmail password!</p>
            </div>
        </div>
    </div>
</body>
</html>
