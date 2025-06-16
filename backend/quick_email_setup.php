<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Email Setup - SecureIt</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #7c3aed 0%, #06b6d4 100%);
            min-height: 100vh;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: #7c3aed;
            margin-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #7c3aed;
        }
        
        .btn {
            background: #7c3aed;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn:hover {
            background: #6d28d9;
        }
        
        .success {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            color: #065f46;
        }
        
        .current-config {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .links {
            text-align: center;
            margin-top: 2rem;
        }
        
        .links a {
            display: inline-block;
            margin: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .links a:hover {
            background: #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-envelope-open-text"></i> Quick Email Setup</h1>
            <p>Just enter your Gmail address to complete the configuration</p>
        </div>

        <?php
        $configFile = __DIR__ . '/config/email_config.php';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gmail_address'])) {
            $gmailAddress = trim($_POST['gmail_address']);
            
            if (filter_var($gmailAddress, FILTER_VALIDATE_EMAIL) && strpos($gmailAddress, '@gmail.com') !== false) {
                $configContent = "<?php
/**
 * Email Configuration - SecureIt
 * Gmail SMTP configuration for anonymous email sending.
 */

return [
    // Gmail SMTP Configuration
    'SMTP_USERNAME' => '$gmailAddress',
    'SMTP_PASSWORD' => 'cokl ohjp vxhp naor',
    
    // Display settings
    'SMTP_FROM_NAME' => 'SecureIt Anonymous',
    
    // SMTP Settings
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => 587,
    'SMTP_SECURE' => 'tls',
    
    // Enable real email sending
    'DEMO_MODE' => false,
];";
                
                if (file_put_contents($configFile, $configContent)) {
                    echo '<div class="success">';
                    echo '<i class="fas fa-check-circle"></i> <strong>Configuration Complete!</strong><br>';
                    echo 'Gmail address saved successfully. Your email system is now ready!';
                    echo '</div>';
                    
                    echo '<div class="current-config">';
                    echo '<strong>Current Configuration:</strong><br>';
                    echo 'Gmail: ' . htmlspecialchars($gmailAddress) . '<br>';
                    echo 'App Password: cokl ohjp vxhp naor<br>';
                    echo 'Demo Mode: Disabled (real emails enabled)<br>';
                    echo 'Status: ✅ Ready to send emails';
                    echo '</div>';
                    
                    echo '<div class="links">';
                    echo '<a href="test_email.php"><i class="fas fa-vial"></i> Test Email</a>';
                    echo '<a href="main_vault.php?section=send"><i class="fas fa-paper-plane"></i> Send Feature</a>';
                    echo '<a href="main_vault.php"><i class="fas fa-home"></i> Main Vault</a>';
                    echo '</div>';
                } else {
                    echo '<div style="background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; padding: 1rem; margin: 1rem 0; color: #991b1b;">';
                    echo '<i class="fas fa-exclamation-triangle"></i> Failed to update configuration. Please check file permissions.';
                    echo '</div>';
                }
            } else {
                echo '<div style="background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; padding: 1rem; margin: 1rem 0; color: #991b1b;">';
                echo '<i class="fas fa-exclamation-triangle"></i> Please enter a valid Gmail address.';
                echo '</div>';
            }
        } else {
            // Show the form
            $config = file_exists($configFile) ? include $configFile : [];
            $hasPassword = isset($config['SMTP_PASSWORD']) && $config['SMTP_PASSWORD'] === 'cokl ohjp vxhp naor';
            
            if ($hasPassword) {
                echo '<div class="current-config">';
                echo '<strong>App Password:</strong> ✅ Configured<br>';
                echo '<strong>Gmail Address:</strong> ' . ($config['SMTP_USERNAME'] ?? 'Not set') . '<br>';
                echo '<strong>Status:</strong> ' . (($config['SMTP_USERNAME'] ?? '') !== 'your-email@gmail.com' ? '✅ Ready' : '⚠️ Needs Gmail address');
                echo '</div>';
            }
        ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="gmail_address">
                    <i class="fas fa-envelope"></i> Your Gmail Address
                </label>
                <input type="email" 
                       id="gmail_address" 
                       name="gmail_address" 
                       placeholder="your-email@gmail.com" 
                       required
                       value="<?= htmlspecialchars($config['SMTP_USERNAME'] ?? '') ?>">
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Save & Enable Email System
            </button>
        </form>
        
        <div class="links">
            <a href="email_setup_guide.php"><i class="fas fa-book"></i> Setup Guide</a>
            <a href="main_vault.php"><i class="fas fa-arrow-left"></i> Back to Vault</a>
        </div>
        
        <?php } ?>
    </div>
</body>
</html>
