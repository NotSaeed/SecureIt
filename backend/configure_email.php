<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration - SecureIt</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #7c3aed 0%, #06b6d4 100%);
            color: white;
            border-radius: 12px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .btn {
            background: #7c3aed;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem 0.5rem 0.5rem 0;
        }
        
        .btn:hover {
            background: #6d28d9;
        }
        
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            color: #155724;
        }
        
        .code-block {
            background: #f1f3f4;
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-cog"></i> Email Configuration</h1>
        <p>Final step: Add your Gmail address</p>
    </div>

    <div class="card">
        <h2><i class="fas fa-info-circle"></i> Configuration Status</h2>
        
        <?php
        $configFile = 'config/email_config.php';
        $config = file_exists($configFile) ? include $configFile : [];
        
        $hasUsername = isset($config['SMTP_USERNAME']) && $config['SMTP_USERNAME'] !== 'your-email@gmail.com';
        $hasPassword = isset($config['SMTP_PASSWORD']) && $config['SMTP_PASSWORD'] !== 'your-app-password';
        $demoMode = $config['DEMO_MODE'] ?? true;
        ?>
        
        <ul style="list-style: none; padding: 0;">
            <li style="margin: 0.5rem 0;">
                <i class="fas fa-<?= $hasUsername ? 'check-circle' : 'times-circle' ?>" 
                   style="color: <?= $hasUsername ? '#28a745' : '#dc3545' ?>; margin-right: 0.5rem;"></i>
                Gmail Address: <?= $hasUsername ? 'Configured' : 'Not Set' ?>
            </li>
            <li style="margin: 0.5rem 0;">
                <i class="fas fa-<?= $hasPassword ? 'check-circle' : 'times-circle' ?>" 
                   style="color: <?= $hasPassword ? '#28a745' : '#dc3545' ?>; margin-right: 0.5rem;"></i>
                App Password: <?= $hasPassword ? 'Configured ✓' : 'Not Set' ?>
            </li>
            <li style="margin: 0.5rem 0;">
                <i class="fas fa-<?= !$demoMode ? 'check-circle' : 'times-circle' ?>" 
                   style="color: <?= !$demoMode ? '#28a745' : '#dc3545' ?>; margin-right: 0.5rem;"></i>
                Demo Mode: <?= $demoMode ? 'Enabled (emails will be logged)' : 'Disabled (real emails will be sent)' ?>
            </li>
        </ul>
    </div>

    <?php if ($hasPassword && !$hasUsername): ?>
    <div class="card">
        <h2><i class="fas fa-envelope"></i> Add Your Gmail Address</h2>
        <p>Your App Password is configured! Now just add your Gmail address to complete the setup.</p>
        
        <form method="POST">
            <div class="form-group">
                <label for="gmail_address">Your Gmail Address:</label>
                <input type="email" id="gmail_address" name="gmail_address" required 
                       placeholder="your-email@gmail.com">
            </div>
            
            <button type="submit" name="update_config" class="btn">
                <i class="fas fa-save"></i> Save Configuration
            </button>
        </form>
    </div>
    <?php endif; ?>

    <?php
    if (isset($_POST['update_config']) && isset($_POST['gmail_address'])) {
        $gmailAddress = trim($_POST['gmail_address']);
        
        if (filter_var($gmailAddress, FILTER_VALIDATE_EMAIL) && strpos($gmailAddress, '@gmail.com') !== false) {
            $configContent = "<?php
/**
 * Email Configuration
 * 
 * Gmail SMTP configuration for SecureIt anonymous email sending.
 * This configuration is now ready for production use.
 */

return [
    // Gmail SMTP Configuration
    'SMTP_USERNAME' => '$gmailAddress',              // Your Gmail address
    'SMTP_PASSWORD' => 'cokl ohjp vxhp naor',        // Your Gmail App Password
    
    // Optional: Custom sender info
    'SMTP_FROM_NAME' => 'SecureIt Anonymous',       // Display name for anonymous emails
    
    // SMTP Settings (usually don't need to change these for Gmail)
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => 587,
    'SMTP_SECURE' => 'tls',
    
    // Feature flags
    'DEMO_MODE' => false,                            // Real emails will be sent
];";
            
            if (file_put_contents($configFile, $configContent)) {
                echo '<div class="success">';
                echo '<i class="fas fa-check-circle"></i> <strong>Configuration Updated Successfully!</strong><br>';
                echo 'Gmail address has been saved. Your email system is now ready to send real emails.';
                echo '</div>';
                
                // Refresh the page to show updated status
                echo '<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>';
            } else {
                echo '<div class="alert alert-danger">Failed to update configuration file. Please check file permissions.</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Please enter a valid Gmail address.</div>';
        }
    }
    ?>

    <?php if ($hasUsername && $hasPassword && !$demoMode): ?>
    <div class="card">
        <div class="success">
            <h3><i class="fas fa-check-circle"></i> Configuration Complete!</h3>
            <p>Your email system is fully configured and ready to send real emails.</p>
            <ul>
                <li>Gmail Address: <?= htmlspecialchars($config['SMTP_USERNAME']) ?></li>
                <li>App Password: Configured ✓</li>
                <li>Demo Mode: Disabled (real emails enabled)</li>
            </ul>
        </div>
        
        <h3><i class="fas fa-rocket"></i> Next Steps</h3>
        <p>Your SecureIt system is now ready!</p>
        
        <a href="test_email.php" class="btn">
            <i class="fas fa-vial"></i> Test Email System
        </a>
        
        <a href="main_vault.php?section=send" class="btn">
            <i class="fas fa-paper-plane"></i> Go to Send Feature
        </a>
        
        <a href="main_vault.php" class="btn" style="background: #6c757d;">
            <i class="fas fa-arrow-left"></i> Back to Vault
        </a>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-shield-alt"></i> Security Notes</h2>
        <ul>
            <li><strong>App Password Security:</strong> Your App Password is stored securely and only used for SMTP authentication.</li>
            <li><strong>Anonymous Sending:</strong> Emails are sent through Gmail's SMTP but appear to come from the specified sender address.</li>
            <li><strong>Privacy:</strong> No email content is stored permanently on the server.</li>
            <li><strong>Rate Limits:</strong> Gmail has sending limits - typically 500 emails per day for regular accounts.</li>
        </ul>
    </div>
</body>
</html>
