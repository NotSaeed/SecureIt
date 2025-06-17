<?php
// Secure Send Access Page
session_start();
require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

$error = '';
$send = null;
$access_link = $_GET['link'] ?? '';

if (empty($access_link)) {
    $error = 'Invalid access link';
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        // User submitted password
        try {
            $sendManager = new SendManager();
            $result = $sendManager->accessSend($access_link, $_POST['password']);
            
            if ($result['success']) {
                $send = $result['send'];
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        // Check if send exists and if password is required
        try {
            $sendManager = new SendManager();
            $tempSend = $sendManager->getSend($access_link);
            
            if (!$tempSend) {
                $error = 'Send not found or expired';
            } else if ($tempSend['password_hash']) {
                // Password required, show password form
                $error = '';
                $send = null;
            } else {
                // No password required, access the send
                $result = $sendManager->accessSend($access_link);
                if ($result['success']) {
                    $send = $result['send'];
                } else {
                    $error = $result['message'];
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureIt - Access Secure Send</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .content {
            padding: 2rem;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #c33;
        }
        
        .success {
            background: #efe;
            color: #3c3;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #3c3;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: transform 0.2s;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .send-content {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .send-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-info {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-secondary {
            background: #e0e0e0;
            color: #424242;
        }
        
        .send-text {
            white-space: pre-wrap;
            line-height: 1.6;
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
          .sender-info {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Credential Display Styles */
        .credential-display {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .credential-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            color: #333;
        }
        
        .credential-type {
            background: #e9ecef;
            color: #495057;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: normal;
        }
        
        .credential-message {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .credential-message i {
            color: #0c5460;
            margin-top: 0.2rem;
        }
        
        .credential-message p {
            margin: 0;
            color: #0c5460;
        }
        
        .credential-data {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #dee2e6;
        }
        
        .credential-field {
            margin-bottom: 1rem;
        }
        
        .credential-field:last-child {
            margin-bottom: 0;
        }
        
        .credential-field label {
            display: block;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .credential-field label i {
            margin-right: 0.5rem;
            color: #6c757d;
        }
        
        .credential-value {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            word-break: break-all;
        }
        
        .credential-value span {
            flex: 1;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.9rem;
        }
        
        .password-hidden {
            letter-spacing: 2px;
            font-size: 1.2rem;
        }
        
        .copy-btn, .toggle-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.4rem 0.6rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: background-color 0.2s;
        }
        
        .copy-btn:hover, .toggle-btn:hover {
            background: #5a67d8;
        }
        
        .copy-btn:active, .toggle-btn:active {
            transform: scale(0.95);
        }
        
        .credential-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .credential-warning i {
            color: #856404;
            margin-top: 0.2rem;
        }
        
        .credential-warning p {
            margin: 0;
            color: #856404;
            font-size: 0.9rem;
        }
        
        .credential-value a {
            color: #667eea;
            text-decoration: none;
        }          .credential-value a:hover {
            text-decoration: underline;
        }
        
        /* File Display Styles */
            padding-left: 1.2rem;
            color: #856404;
            font-size: 0.85rem;
        }
          .warning-content li {
            margin-bottom: 0.3rem;
        }
        
        /* Image display styles */
        .image-display {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin: 1rem 0;
        }
        
        .image-container {
            margin-bottom: 1rem;
        }
        
        .image-info {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .image-info h4 {
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
          .image-info p {
            color: #718096;
            margin: 0.25rem 0;
            font-size: 0.9rem;
        }
        
        .download-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            display: inline-block;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-download:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            text-decoration: none;
            color: white;
        }
        
        /* File download styles */
        .file-download {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin: 1rem 0;
        }
        
        .file-info {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
        }
        
        .file-info h4 {
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .file-info p {
            color: #718096;
            margin: 0.25rem 0;
            font-size: 0.9rem;
        }
        
        /* Multiple Credentials Styles */
        .multi-credential {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .credentials-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .credential-item {
            background: white;
            border-radius: 12px;
            border: 1px solid #dee2e6;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        
        .credential-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .item-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .item-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        
        .item-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #2d3748;
            flex: 1;
        }
        
        .item-type {
            background: #e2e8f0;
            color: #4a5568;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .note-content {
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shield-check"></i> SecureIt</h1>
            <p>Secure Send Access</p>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$send && !$error): ?>
                <!-- Password Form -->
                <form method="POST">
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-key"></i> Password Required
                        </label>
                        <input type="password" id="password" name="password" placeholder="Enter password to access this send" required>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-unlock"></i> Access Send
                    </button>
                </form>
            <?php elseif ($send): ?>                <!-- Display Send Content -->
                <div class="send-content">
                    <h2>
                        <?php if (!empty($send['anonymous'])): ?>
                            <i class="fas fa-user-secret"></i> Anonymous Send
                        <?php else: ?>
                            <?php echo htmlspecialchars($send['name']); ?>
                        <?php endif; ?>
                    </h2><div class="send-meta">                        <span class="badge badge-<?php echo $send['type'] === 'file' ? 'info' : ($send['type'] === 'credential' ? 'warning' : 'secondary'); ?>">
                            <?php echo ucfirst($send['type']); ?>
                        </span>
                        <span>Created: <?php echo date('M j, Y g:i A', strtotime($send['created_at'])); ?></span>
                        <span>Expires: <?php echo date('M j, Y g:i A', strtotime($send['expires_at'])); ?></span>
                        <span>Views: <?php echo $send['view_count']; ?><?php echo $send['max_views'] ? '/' . $send['max_views'] : ''; ?></span>
                    </div>
                      <?php if ($send['type'] === 'text'): ?>
                        <div class="send-text">
                            <?php echo htmlspecialchars($send['content']); ?>
                        </div>
                    <?php elseif ($send['type'] === 'credential'): ?>
                        <?php 
                        $credentialData = json_decode($send['content'], true);
                        if ($credentialData):
                            // Check if this is a multi-item credential delivery
                            if (isset($credentialData['items']) && is_array($credentialData['items'])):
                                // Multiple items
                                $items = $credentialData['items'];
                                $itemCount = count($items);
                        ?>
                        <div class="credential-display multi-credential">
                            <div class="credential-header">
                                <i class="fas fa-key"></i> 
                                <strong>Credential Package (<?php echo $itemCount; ?> items)</strong>
                            </div>
                            
                            <?php if (!empty($credentialData['message'])): ?>
                                <div class="credential-message">
                                    <i class="fas fa-comment"></i>
                                    <p><?php echo htmlspecialchars($credentialData['message']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="credentials-container">
                                <?php foreach ($items as $index => $item): ?>
                                    <div class="credential-item" id="item-<?php echo $index; ?>">
                                        <div class="item-header">
                                            <div class="item-number"><?php echo $index + 1; ?></div>
                                            <h3>
                                                <?php 
                                                $itemIcon = '';
                                                switch ($item['item_type']) {
                                                    case 'login': $itemIcon = '🔐'; break;
                                                    case 'card': $itemIcon = '💳'; break;
                                                    case 'identity': $itemIcon = '🆔'; break;
                                                    case 'note': $itemIcon = '📝'; break;
                                                    default: $itemIcon = '🔑';
                                                }
                                                echo $itemIcon . ' ' . htmlspecialchars($item['item_name']);
                                                ?>
                                            </h3>
                                            <span class="item-type"><?php echo ucfirst($item['item_type']); ?></span>
                                        </div>
                                        
                                        <div class="credential-data">
                                            <?php 
                                            $itemData = $item['data']; // Already decrypted in SendManager
                                            if ($item['item_type'] === 'login'):
                                            ?>
                                                <div class="credential-field">
                                                    <label><i class="fas fa-user"></i> Username:</label>
                                                    <div class="credential-value">
                                                        <span id="username-value-<?php echo $index; ?>"><?php echo htmlspecialchars($itemData['username'] ?? 'N/A'); ?></span>
                                                        <button type="button" onclick="copyToClipboard('username-value-<?php echo $index; ?>')" class="copy-btn">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="credential-field">
                                                    <label><i class="fas fa-lock"></i> Password:</label>
                                                    <div class="credential-value">
                                                        <span id="password-value-<?php echo $index; ?>" class="password-hidden"><?php echo str_repeat('•', strlen($itemData['password'] ?? '')); ?></span>
                                                        <span id="password-revealed-<?php echo $index; ?>" style="display: none;"><?php echo htmlspecialchars($itemData['password'] ?? 'N/A'); ?></span>
                                                        <button type="button" onclick="togglePasswordItem(<?php echo $index; ?>)" class="toggle-btn">
                                                            <i class="fas fa-eye" id="password-toggle-icon-<?php echo $index; ?>"></i>
                                                        </button>
                                                        <button type="button" onclick="copyToClipboard('password-revealed-<?php echo $index; ?>')" class="copy-btn">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <?php if (!empty($item['website_url'])): ?>
                                                <div class="credential-field">
                                                    <label><i class="fas fa-globe"></i> Website:</label>
                                                    <div class="credential-value">
                                                        <a href="<?php echo htmlspecialchars($item['website_url']); ?>" target="_blank" rel="noopener noreferrer">
                                                            <?php echo htmlspecialchars($item['website_url']); ?>
                                                        </a>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($itemData['notes'])): ?>
                                                <div class="credential-field">
                                                    <label><i class="fas fa-sticky-note"></i> Notes:</label>
                                                    <div class="credential-value">
                                                        <span id="notes-value-<?php echo $index; ?>"><?php echo htmlspecialchars($itemData['notes']); ?></span>
                                                        <button type="button" onclick="copyToClipboard('notes-value-<?php echo $index; ?>')" class="copy-btn">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            <?php elseif ($item['item_type'] === 'card'): ?>
                                                <div class="credential-field">
                                                    <label><i class="fas fa-user"></i> Cardholder Name:</label>
                                                    <div class="credential-value">
                                                        <span id="cardholder-value-<?php echo $index; ?>"><?php echo htmlspecialchars($itemData['cardholder_name'] ?? 'N/A'); ?></span>
                                                        <button type="button" onclick="copyToClipboard('cardholder-value-<?php echo $index; ?>')" class="copy-btn">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="credential-field">
                                                    <label><i class="fas fa-credit-card"></i> Card Number:</label>
                                                    <div class="credential-value">
                                                        <span id="cardnumber-value-<?php echo $index; ?>"><?php echo htmlspecialchars($itemData['card_number'] ?? 'N/A'); ?></span>
                                                        <button type="button" onclick="copyToClipboard('cardnumber-value-<?php echo $index; ?>')" class="copy-btn">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="credential-field">
                                                    <label><i class="fas fa-calendar"></i> Expiry Date:</label>
                                                    <div class="credential-value">
                                                        <span id="expiry-value-<?php echo $index; ?>"><?php echo htmlspecialchars($itemData['expiry_date'] ?? 'N/A'); ?></span>
                                                        <button type="button" onclick="copyToClipboard('expiry-value-<?php echo $index; ?>')" class="copy-btn">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="credential-field">
                                                    <label><i class="fas fa-lock"></i> CVV:</label>
                                                    <div class="credential-value">
                                                        <span id="cvv-value-<?php echo $index; ?>" class="password-hidden"><?php echo str_repeat('•', strlen($itemData['cvv'] ?? '')); ?></span>
                                                        <span id="cvv-revealed-<?php echo $index; ?>" style="display: none;"><?php echo htmlspecialchars($itemData['cvv'] ?? 'N/A'); ?></span>
                                                        <button type="button" onclick="toggleCVVItem(<?php echo $index; ?>)" class="toggle-btn">
                                                            <i class="fas fa-eye" id="cvv-toggle-icon-<?php echo $index; ?>"></i>
                                                        </button>
                                                        <button type="button" onclick="copyToClipboard('cvv-revealed-<?php echo $index; ?>')" class="copy-btn">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php elseif ($item['item_type'] === 'note'): ?>
                                                <div class="credential-field">
                                                    <label><i class="fas fa-sticky-note"></i> Note Content:</label>
                                                    <div class="credential-value note-content">
                                                        <span id="note-content-<?php echo $index; ?>"><?php echo nl2br(htmlspecialchars($itemData['note_content'] ?? 'N/A')); ?></span>
                                                        <button type="button" onclick="copyToClipboard('note-content-<?php echo $index; ?>')" class="copy-btn">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="credential-field">
                                                    <label><i class="fas fa-info-circle"></i> Content:</label>
                                                    <div class="credential-value">
                                                        <span id="content-value-<?php echo $index; ?>"><?php echo htmlspecialchars(json_encode($itemData, JSON_PRETTY_PRINT)); ?></span>
                                                        <button type="button" onclick="copyToClipboard('content-value-<?php echo $index; ?>')" class="copy-btn">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else:
                            // Single item (legacy format)
                            $itemData = json_decode($credentialData['data'], true);
                        ?>
                        <div class="credential-display">
                            <div class="credential-header">
                                <i class="fas fa-key"></i> 
                                <strong><?php echo htmlspecialchars($credentialData['item_name']); ?></strong>
                                <span class="credential-type">(<?php echo ucfirst($credentialData['item_type']); ?>)</span>
                            </div>
                            
                            <?php if (!empty($credentialData['message'])): ?>
                                <div class="credential-message">
                                    <i class="fas fa-comment"></i>
                                    <p><?php echo htmlspecialchars($credentialData['message']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="credential-data">
                                <?php if ($credentialData['item_type'] === 'login'): ?>
                                    <div class="credential-field">
                                        <label><i class="fas fa-user"></i> Username:</label>
                                        <div class="credential-value">
                                            <span id="username-value"><?php echo htmlspecialchars($itemData['username'] ?? 'N/A'); ?></span>
                                            <button type="button" onclick="copyToClipboard('username-value')" class="copy-btn">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="credential-field">
                                        <label><i class="fas fa-lock"></i> Password:</label>
                                        <div class="credential-value">
                                            <span id="password-value" class="password-hidden"><?php echo str_repeat('•', strlen($itemData['password'] ?? '')); ?></span>
                                            <span id="password-revealed" style="display: none;"><?php echo htmlspecialchars($itemData['password'] ?? 'N/A'); ?></span>
                                            <button type="button" onclick="togglePassword()" class="toggle-btn">
                                                <i class="fas fa-eye" id="password-toggle-icon"></i>
                                            </button>
                                            <button type="button" onclick="copyToClipboard('password-revealed')" class="copy-btn">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php if (!empty($itemData['notes'])): ?>
                                    <div class="credential-field">
                                        <label><i class="fas fa-sticky-note"></i> Notes:</label>
                                        <div class="credential-value">
                                            <span id="notes-value"><?php echo htmlspecialchars($itemData['notes']); ?></span>
                                            <button type="button" onclick="copyToClipboard('notes-value')" class="copy-btn">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                            <span id="username-value"><?php echo htmlspecialchars($itemData['username'] ?? 'N/A'); ?></span>
                                            <button type="button" onclick="copyToClipboard('username-value')" class="copy-btn">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="credential-field">
                                        <label><i class="fas fa-lock"></i> Password:</label>
                                        <div class="credential-value">
                                            <span id="password-value" class="password-hidden"><?php echo str_repeat('•', strlen($itemData['password'] ?? '')); ?></span>
                                            <span id="password-revealed" style="display: none;"><?php echo htmlspecialchars($itemData['password'] ?? 'N/A'); ?></span>
                                            <button type="button" onclick="togglePassword()" class="toggle-btn">
                                                <i class="fas fa-eye" id="password-toggle-icon"></i>
                                            </button>
                                            <button type="button" onclick="copyToClipboard('password-revealed')" class="copy-btn">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php if (!empty($itemData['notes'])): ?>
                                    <div class="credential-field">
                                        <label><i class="fas fa-sticky-note"></i> Notes:</label>
                                        <div class="credential-value">
                                            <span id="notes-value"><?php echo htmlspecialchars($itemData['notes']); ?></span>
                                            <button type="button" onclick="copyToClipboard('notes-value')" class="copy-btn">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php elseif ($credentialData['item_type'] === 'card'): ?>
                                    <div class="credential-field">
                                        <label><i class="fas fa-user"></i> Cardholder Name:</label>
                                        <div class="credential-value">
                                            <span id="cardholder-value"><?php echo htmlspecialchars($itemData['cardholder_name'] ?? 'N/A'); ?></span>
                                            <button type="button" onclick="copyToClipboard('cardholder-value')" class="copy-btn">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="credential-field">
                                        <label><i class="fas fa-credit-card"></i> Card Number:</label>
                                        <div class="credential-value">
                                            <span id="cardnumber-value"><?php echo htmlspecialchars($itemData['card_number'] ?? 'N/A'); ?></span>
                                            <button type="button" onclick="copyToClipboard('cardnumber-value')" class="copy-btn">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="credential-field">
                                        <label><i class="fas fa-calendar"></i> Expiry Date:</label>
                                        <div class="credential-value">
                                            <span id="expiry-value"><?php echo htmlspecialchars($itemData['expiry_date'] ?? 'N/A'); ?></span>
                                            <button type="button" onclick="copyToClipboard('expiry-value')" class="copy-btn">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="credential-field">
                                        <label><i class="fas fa-lock"></i> CVV:</label>
                                        <div class="credential-value">
                                            <span id="cvv-value" class="password-hidden"><?php echo str_repeat('•', strlen($itemData['cvv'] ?? '')); ?></span>
                                            <span id="cvv-revealed" style="display: none;"><?php echo htmlspecialchars($itemData['cvv'] ?? 'N/A'); ?></span>
                                            <button type="button" onclick="toggleCVV()" class="toggle-btn">
                                                <i class="fas fa-eye" id="cvv-toggle-icon"></i>
                                            </button>
                                            <button type="button" onclick="copyToClipboard('cvv-revealed')" class="copy-btn">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="credential-field">
                                        <label><i class="fas fa-info-circle"></i> Content:</label>
                                        <div class="credential-value">
                                            <span id="content-value"><?php echo htmlspecialchars($itemData['content'] ?? json_encode($itemData, JSON_PRETTY_PRINT)); ?></span>
                                            <button type="button" onclick="copyToClipboard('content-value')" class="copy-btn">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($credentialData['website_url'])): ?>
                                <div class="credential-field">
                                    <label><i class="fas fa-globe"></i> Website:</label>
                                    <div class="credential-value">
                                        <a href="<?php echo htmlspecialchars($credentialData['website_url']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($credentialData['website_url']); ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>                            <div class="credential-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>These credentials have been securely shared with you. 
                                   Keep this information secure and do not share it with others.</p>
                            </div></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($send['storage_type'] === 'blob'): ?>
                            <!-- Image stored as BLOB - display inline with download option -->
                            <div class="image-display">
                                <div class="image-container">
                                    <img src="view_image.php?send=<?php echo $send['access_token']; ?>" 
                                         alt="<?php echo htmlspecialchars($send['file_name']); ?>"
                                         style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                </div>
                                <div class="image-info">
                                    <h4><i class="fas fa-image"></i> <?php echo htmlspecialchars($send['file_name']); ?></h4>
                                    <p><strong>Size:</strong> <?php echo number_format($send['file_size']); ?> bytes</p>
                                    <p><strong>Type:</strong> <?php echo htmlspecialchars($send['mime_type']); ?></p>
                                      <!-- Download button for image -->
                                    <div class="download-actions">
                                        <?php 
                                        // Store access token in session for seamless downloads
                                        $_SESSION['temp_download_access_' . $send['access_token']] = true;
                                        ?>
                                        <a href="download_image.php?send=<?php echo $send['access_token']; ?>" class="btn btn-download">
                                            <i class="fas fa-download"></i> Download Image
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- File stored as downloadable - show download link -->
                            <div class="file-download">
                                <i class="fas fa-download"></i>
                                <div class="file-info">
                                    <h4><?php echo htmlspecialchars($send['file_name']); ?></h4>
                                    <p><strong>Size:</strong> <?php echo number_format($send['file_size']); ?> bytes</p>
                                    <p><strong>Type:</strong> <?php echo htmlspecialchars($send['mime_type']); ?></p>
                                </div>
                                <?php 
                                // Store access token in session for seamless downloads
                                $_SESSION['temp_download_access_' . $send['access_token']] = true;
                                ?>
                                <a href="download.php?send=<?php echo $send['access_token']; ?>" class="btn">
                                    <i class="fas fa-download"></i> Download File
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                      </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <p style="color: #666; font-size: 0.9rem;">
                        <i class="fas fa-shield-alt"></i> This message is encrypted and secured by SecureIt
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        function togglePassword() {
            const hiddenSpan = document.getElementById('password-value');
            const revealedSpan = document.getElementById('password-revealed');
            const icon = document.getElementById('password-toggle-icon');
            
            if (hiddenSpan.style.display === 'none') {
                hiddenSpan.style.display = 'inline';
                revealedSpan.style.display = 'none';
                icon.className = 'fas fa-eye';
            } else {
                hiddenSpan.style.display = 'none';
                revealedSpan.style.display = 'inline';
                icon.className = 'fas fa-eye-slash';
            }
        }
        
        function toggleCVV() {
            const hiddenSpan = document.getElementById('cvv-value');
            const revealedSpan = document.getElementById('cvv-revealed');
            const icon = document.getElementById('cvv-toggle-icon');
            
            if (hiddenSpan.style.display === 'none') {
                hiddenSpan.style.display = 'inline';
                revealedSpan.style.display = 'none';
                icon.className = 'fas fa-eye';
            } else {
                hiddenSpan.style.display = 'none';
                revealedSpan.style.display = 'inline';
                icon.className = 'fas fa-eye-slash';
            }
        }
        
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent || element.innerText;
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopyFeedback();
                }).catch(err => {
                    fallbackCopyTextToClipboard(text);
                });
            } else {
                fallbackCopyTextToClipboard(text);
            }
        }
        
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopyFeedback();
                }
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
            }
            
            document.body.removeChild(textArea);
        }
          function showCopyFeedback() {
            // Create temporary feedback element
            const feedback = document.createElement('div');
            feedback.textContent = 'Copied!';
            feedback.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 4px;
                z-index: 10000;
                font-size: 0.9rem;
            `;
            
            document.body.appendChild(feedback);
            
            setTimeout(() => {
                document.body.removeChild(feedback);
            }, 2000);        }
        
        // Functions for multiple credential items
        function togglePasswordItem(index) {
            const hiddenSpan = document.getElementById('password-value-' + index);
            const revealedSpan = document.getElementById('password-revealed-' + index);
            const icon = document.getElementById('password-toggle-icon-' + index);
            
            if (hiddenSpan.style.display === 'none') {
                hiddenSpan.style.display = 'inline';
                revealedSpan.style.display = 'none';
                icon.className = 'fas fa-eye';
            } else {
                hiddenSpan.style.display = 'none';
                revealedSpan.style.display = 'inline';
                icon.className = 'fas fa-eye-slash';
            }
        }
        
        function toggleCVVItem(index) {
            const hiddenSpan = document.getElementById('cvv-value-' + index);
            const revealedSpan = document.getElementById('cvv-revealed-' + index);
            const icon = document.getElementById('cvv-toggle-icon-' + index);
            
            if (hiddenSpan.style.display === 'none') {
                hiddenSpan.style.display = 'inline';
                revealedSpan.style.display = 'none';
                icon.className = 'fas fa-eye';
            } else {
                hiddenSpan.style.display = 'none';
                revealedSpan.style.display = 'inline';
                icon.className = 'fas fa-eye-slash';
            }
        }
    </script>
</body>
</html>
