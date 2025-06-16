<?php
// Send Access Page - for accessing shared content
session_start();

require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

$error = '';
$success = '';
$send = null;
$requiresPassword = false;
$accessLink = $_GET['id'] ?? '';

if (empty($accessLink)) {
    $error = 'Invalid access link';
} else {
    try {
        $sendManager = new SendManager();
        
        // Check for password in both POST and GET parameters
        $password = $_POST['password'] ?? $_GET['password'] ?? null;
        
        if ($password) {
            // Attempt to access with password (from POST or GET)
            $send = $sendManager->getSend($accessLink, $password);
        } else {
            // Check if password is required
            try {
                $send = $sendManager->getSend($accessLink);
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'password') !== false) {
                    $requiresPassword = true;
                } else {
                    $error = $e->getMessage();
                }
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureIt Send - Access Content</title>
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

        .access-container {
            max-width: 600px;
            width: 100%;
            background: #ffffff;
            border-radius: 24px;
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
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .content {
            padding: 2rem;
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

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
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
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #6b7280;
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

        .send-meta {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .send-meta h3 {
            margin-bottom: 0.5rem;
            color: #1e293b;
        }

        .meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .meta-label {
            color: #64748b;
        }

        .meta-value {
            color: #1e293b;
            font-weight: 500;
        }

        .content-display {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .content-text {
            white-space: pre-wrap;
            line-height: 1.6;
            color: #374151;
        }

        .file-download {
            text-align: center;
            padding: 2rem;
        }

        .file-icon {
            font-size: 3rem;
            color: #3b82f6;
            margin-bottom: 1rem;
        }

        .download-btn {
            background: #10b981;
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        .download-btn:hover {
            background: #059669;
        }

        .footer {
            text-align: center;
            padding: 1rem;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.9rem;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .access-container {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="access-container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> SecureIt Send</h1>
            <p>Secure content sharing</p>
        </div>

        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <div style="text-align: center;">
                    <a href="main_vault.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Go to Vault
                    </a>
                </div>
            <?php elseif ($requiresPassword): ?>
                <h2>Password Required</h2>
                <p>This content is password protected. Please enter the password to access it.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Enter the password" required autofocus>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-unlock"></i> Access Content
                    </button>
                </form>
            <?php elseif ($send): ?>
                <div class="send-meta">
                    <h3><?= htmlspecialchars($send['name']) ?></h3>
                    <div class="meta-item">
                        <span class="meta-label">Type:</span>
                        <span class="meta-value"><?= ucfirst($send['send_type']) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Views:</span>
                        <span class="meta-value"><?= $send['current_views'] ?><?= $send['max_views'] ? ' / ' . $send['max_views'] : '' ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Expires:</span>
                        <span class="meta-value"><?= date('M j, Y g:i A', strtotime($send['deletion_date'])) ?></span>
                    </div>
                    <?php if ($send['sender_email'] && !$send['hide_email']): ?>
                        <div class="meta-item">
                            <span class="meta-label">From:</span>
                            <span class="meta-value"><?= htmlspecialchars($send['sender_email']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($send['send_type'] === 'text'): ?>
                    <div class="content-display">
                        <div class="content-text"><?= htmlspecialchars($send['content']) ?></div>
                    </div>
                    <div style="text-align: center;">
                        <button onclick="copyToClipboard()" class="btn">
                            <i class="fas fa-copy"></i> Copy Text
                        </button>
                    </div>                <?php elseif ($send['send_type'] === 'file'): ?>
                    <div class="file-download">
                        <div class="file-icon">
                            <i class="fas fa-file-download"></i>
                        </div>
                        <h3><?= htmlspecialchars($send['content']) ?></h3>
                        <p>Click the button below to download this file</p>                        <?php 
                        $downloadUrl = "download.php?id=" . urlencode($accessLink);
                        // Pass password from either POST or GET
                        $passedPassword = $_POST['password'] ?? $_GET['password'] ?? null;
                        if ($passedPassword) {
                            $downloadUrl .= "&password=" . urlencode($passedPassword);
                        }
                        ?>
                        <a href="<?= $downloadUrl ?>" class="btn download-btn">
                            <i class="fas fa-download"></i> Download File
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>Powered by SecureIt - Your privacy is protected</p>
        </div>
    </div>

    <script>
        function copyToClipboard() {
            const contentText = document.querySelector('.content-text');
            if (contentText) {
                const text = contentText.textContent;
                navigator.clipboard.writeText(text).then(() => {
                    showNotification('Content copied to clipboard!', 'success');
                }).catch(() => {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showNotification('Content copied to clipboard!', 'success');
                });
            }
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : '#3b82f6'};
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                font-weight: 500;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Auto-focus password field
        document.addEventListener('DOMContentLoaded', function() {
            const passwordField = document.getElementById('password');
            if (passwordField) {
                passwordField.focus();
            }
        });
    </script>
</body>
</html>
