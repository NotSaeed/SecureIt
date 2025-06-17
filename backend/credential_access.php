<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/EncryptionHelper.php';
require_once 'classes/Vault.php';

$error = '';
$credentials = [];
$sendInfo = null;
$requiresPassword = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $db = new Database();
        $encryptionHelper = new EncryptionHelper();
        
        // Get send information
        $sql = "SELECT * FROM sends WHERE access_token = :token AND type = 'credential'";
        $send = $db->fetchOne($sql, ['token' => $token]);
        
        if (!$send) {
            throw new Exception('Invalid or expired credential link');
        }
        
        // Check if expired
        if (strtotime($send['expires_at']) < time()) {
            throw new Exception('This credential link has expired');
        }
        
        // Check max views
        if ($send['max_views'] && $send['view_count'] >= $send['max_views']) {
            throw new Exception('This credential link has reached its maximum view limit');
        }
        
        $sendInfo = $send;
        $requiresPassword = !empty($send['password_hash']);
        
        // Handle password verification
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access_password'])) {
            if (!$requiresPassword) {
                throw new Exception('No password required for this credential');
            }
            
            if (!password_verify($_POST['access_password'], $send['password_hash'])) {
                throw new Exception('Invalid password');
            }
            
            // Password is correct, proceed to show credentials
            $requiresPassword = false;
        }
        
        // If no password required or password verified, show credentials
        if (!$requiresPassword) {
            // Decrypt content
            $decryptedContent = $encryptionHelper->decrypt($send['content']);
            $credentialData = json_decode($decryptedContent, true);
            
            if ($credentialData && isset($credentialData['items'])) {
                $credentials = $credentialData['items'];
                
                // Update view count
                $updateSql = "UPDATE sends SET view_count = view_count + 1, last_accessed = NOW() WHERE id = :id";
                $db->query($updateSql, ['id' => $send['id']]);
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    $error = 'No credential link provided';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credential Access - SecureIt</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 800px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }
        
        .password-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .credential-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .credential-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .credential-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .credential-name {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .credential-type {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: auto;
        }
        
        .credential-data {
            display: grid;
            gap: 10px;
        }
        
        .data-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .data-label {
            font-weight: 600;
            color: #555;
        }
        
        .data-value {
            font-family: monospace;
            background: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            max-width: 300px;
        }
        
        .copy-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .copy-btn:hover {
            background: #218838;
        }
        
        .message-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .send-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-key"></i> Credential Access</h1>
            <p>Secure credential sharing via SecureIt</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($sendInfo && $requiresPassword): ?>
            <div class="password-form">
                <h3><i class="fas fa-lock"></i> Password Required</h3>
                <p>This credential delivery is password protected. Please enter the access password to view the shared credentials.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="access_password">Access Password</label>
                        <input type="password" id="access_password" name="access_password" required>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-unlock"></i> Access Credentials
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($sendInfo && !$requiresPassword && !empty($credentials)): ?>
            <?php if (isset($credentialData['message']) && !empty($credentialData['message'])): ?>
                <div class="message-box">
                    <h4><i class="fas fa-comment"></i> Message from sender:</h4>
                    <p><?= htmlspecialchars($credentialData['message']) ?></p>
                </div>
            <?php endif; ?>
            
            <div class="send-info">
                <strong>Credential Delivery:</strong> <?= htmlspecialchars($sendInfo['name']) ?><br>
                <strong>Expires:</strong> <?= date('M j, Y \a\t g:i A', strtotime($sendInfo['expires_at'])) ?><br>
                <strong>Views:</strong> <?= $sendInfo['view_count'] ?><?= $sendInfo['max_views'] ? '/' . $sendInfo['max_views'] : '' ?>
            </div>
            
            <?php foreach ($credentials as $credential): ?>
                <div class="credential-item">
                    <div class="credential-header">
                        <span class="credential-icon">
                            <?php
                            switch ($credential['item_type']) {
                                case 'login': echo '🔐'; break;
                                case 'card': echo '💳'; break;
                                case 'identity': echo '🆔'; break;
                                case 'note': echo '📝'; break;
                                default: echo '🔑';
                            }
                            ?>
                        </span>
                        <span class="credential-name"><?= htmlspecialchars($credential['item_name']) ?></span>
                        <span class="credential-type"><?= ucfirst($credential['item_type']) ?></span>
                    </div>
                    
                    <div class="credential-data">
                        <?php if ($credential['item_type'] === 'login'): ?>
                            <?php if (!empty($credential['website_url'])): ?>
                                <div class="data-item">
                                    <span class="data-label">Website:</span>
                                    <span class="data-value">
                                        <a href="<?= htmlspecialchars($credential['website_url']) ?>" target="_blank">
                                            <?= htmlspecialchars($credential['website_url']) ?>
                                        </a>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($credential['data']['username'])): ?>
                                <div class="data-item">
                                    <span class="data-label">Username:</span>
                                    <span class="data-value"><?= htmlspecialchars($credential['data']['username']) ?></span>
                                    <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($credential['data']['username']) ?>')">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($credential['data']['password'])): ?>
                                <div class="data-item">
                                    <span class="data-label">Password:</span>
                                    <span class="data-value"><?= htmlspecialchars($credential['data']['password']) ?></span>
                                    <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($credential['data']['password']) ?>')">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($credential['data']['notes'])): ?>
                                <div class="data-item">
                                    <span class="data-label">Notes:</span>
                                    <span class="data-value"><?= htmlspecialchars($credential['data']['notes']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                        <?php elseif ($credential['item_type'] === 'card'): ?>
                            <?php if (!empty($credential['data']['cardholder_name'])): ?>
                                <div class="data-item">
                                    <span class="data-label">Cardholder Name:</span>
                                    <span class="data-value"><?= htmlspecialchars($credential['data']['cardholder_name']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($credential['data']['card_number'])): ?>
                                <div class="data-item">
                                    <span class="data-label">Card Number:</span>
                                    <span class="data-value"><?= htmlspecialchars($credential['data']['card_number']) ?></span>
                                    <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($credential['data']['card_number']) ?>')">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($credential['data']['expiry_date'])): ?>
                                <div class="data-item">
                                    <span class="data-label">Expiry Date:</span>
                                    <span class="data-value"><?= htmlspecialchars($credential['data']['expiry_date']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($credential['data']['cvv'])): ?>
                                <div class="data-item">
                                    <span class="data-label">CVV:</span>
                                    <span class="data-value"><?= htmlspecialchars($credential['data']['cvv']) ?></span>
                                    <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($credential['data']['cvv']) ?>')">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                        <?php elseif ($credential['item_type'] === 'note'): ?>
                            <div class="data-item">
                                <span class="data-label">Note Content:</span>
                                <div class="data-value" style="max-width: 100%; white-space: pre-wrap;">
                                    <?= htmlspecialchars($credential['data']['notes'] ?? '') ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="send-info">
                <small><i class="fas fa-info-circle"></i> This credential access will expire on <?= date('M j, Y \a\t g:i A', strtotime($sendInfo['expires_at'])) ?>. Please save any needed information before this time.</small>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Create a temporary notification
                const notification = document.createElement('div');
                notification.textContent = 'Copied to clipboard!';
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #28a745;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 5px;
                    z-index: 1000;
                    font-size: 14px;
                `;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy to clipboard');
            });
        }
    </script>
</body>
</html>
