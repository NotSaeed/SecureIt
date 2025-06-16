<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration Guide - SecureIt</title>
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
        
        .step {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .step-number {
            background: #7c3aed;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
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
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .test-button {
            background: #7c3aed;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem 0;
        }
        
        .test-button:hover {
            background: #6d28d9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-shield-alt"></i> SecureIt Email Setup</h1>
        <p>Configure anonymous email sending with Gmail SMTP</p>
    </div>

    <div class="card">
        <h2><i class="fas fa-cog"></i> Configuration Steps</h2>
        
        <div class="step">
            <div class="step-number">1</div>
            <div>
                <h3>Enable 2-Factor Authentication</h3>
                <p>Go to your Gmail account settings and enable 2-Factor Authentication if not already enabled.</p>
            </div>
        </div>
        
        <div class="step">
            <div class="step-number">2</div>
            <div>
                <h3>Generate App Password</h3>
                <p>Visit <a href="https://myaccount.google.com/apppasswords" target="_blank">Google App Passwords</a> and create a new app password for "Mail" or "Other (Custom name)".</p>
            </div>
        </div>
        
        <div class="step">
            <div class="step-number">3</div>
            <div>
                <h3>Update Configuration File</h3>
                <p>Edit the file: <code>backend/config/email_config.php</code></p>
                <div class="code-block">
return [
    'SMTP_USERNAME' => 'your-actual-email@gmail.com',
    'SMTP_PASSWORD' => 'your-16-char-app-password',
    'DEMO_MODE' => false,  // Set to false to enable real sending
    // ... other settings
];
                </div>
            </div>
        </div>
        
        <div class="step">
            <div class="step-number">4</div>
            <div>
                <h3>Security Notes</h3>
                <div class="warning">
                    <strong><i class="fas fa-exclamation-triangle"></i> Important:</strong>
                    <ul>
                        <li>Never commit real credentials to version control</li>
                        <li>Use App Passwords, not your regular Gmail password</li>
                        <li>Consider using environment variables in production</li>
                        <li>Keep your App Password secure and private</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-vial"></i> Test Email Functionality</h2>
        <p>Once configured, test the email sending functionality:</p>
        
        <a href="demo_live_send.php" class="test-button">
            <i class="fas fa-paper-plane"></i> Test Send Feature
        </a>
        
        <a href="logs/email_demo.log" class="test-button" target="_blank">
            <i class="fas fa-file-alt"></i> View Email Logs
        </a>
    </div>

    <div class="card">
        <h2><i class="fas fa-question-circle"></i> Current Status</h2>
        <?php
        require_once 'classes/EmailHelper.php';
        
        try {
            $emailHelper = new EmailHelper();
            
            // Check if SMTP is configured by trying to create an instance
            $configFile = 'config/email_config.php';
            $config = file_exists($configFile) ? include $configFile : [];
            
            $isConfigured = isset($config['SMTP_USERNAME']) && 
                           $config['SMTP_USERNAME'] !== 'your-email@gmail.com' &&
                           isset($config['SMTP_PASSWORD']) && 
                           $config['SMTP_PASSWORD'] !== 'your-app-password';
            
            $demoMode = $config['DEMO_MODE'] ?? true;
            
            if ($isConfigured && !$demoMode) {
                echo '<div class="success">';
                echo '<i class="fas fa-check-circle"></i> <strong>SMTP Configured:</strong> Ready to send real emails!';
                echo '</div>';
            } else {
                echo '<div class="warning">';
                echo '<i class="fas fa-info-circle"></i> <strong>Demo Mode:</strong> Emails will be logged instead of sent.';
                echo '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="warning">';
            echo '<i class="fas fa-exclamation-triangle"></i> <strong>Configuration Error:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
    </div>

    <div class="card">
        <h2><i class="fas fa-rocket"></i> Next Steps</h2>
        <p>After configuring email:</p>
        <ol>
            <li>Test the Send feature from the main vault</li>
            <li>Check your email inbox for delivered messages</li>
            <li>Monitor the logs for any errors</li>
            <li>Adjust settings as needed</li>
        </ol>
        
        <a href="main_vault.php" class="test-button">
            <i class="fas fa-arrow-left"></i> Back to Vault
        </a>
    </div>
</body>
</html>
