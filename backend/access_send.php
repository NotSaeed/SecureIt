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
            $send = $sendManager->getSend($access_link, $_POST['password']);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } else {
        // Check if send exists and if password is required
        try {
            $sendManager = new SendManager();
            $send = $sendManager->getSend($access_link);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'password') !== false) {
                // Password required, show password form
                $error = '';
            } else {
                $error = $e->getMessage();
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
            <?php elseif ($send): ?>
                <!-- Display Send Content -->
                <div class="send-content">
                    <h2><?php echo htmlspecialchars($send['name']); ?></h2>
                    
                    <div class="send-meta">
                        <span class="badge badge-<?php echo $send['send_type'] === 'file' ? 'info' : 'secondary'; ?>">
                            <?php echo ucfirst($send['send_type']); ?>
                        </span>
                        <span>Created: <?php echo date('M j, Y g:i A', strtotime($send['created_at'])); ?></span>
                        <span>Expires: <?php echo date('M j, Y g:i A', strtotime($send['deletion_date'])); ?></span>
                        <span>Views: <?php echo $send['current_views']; ?><?php echo $send['max_views'] ? '/' . $send['max_views'] : ''; ?></span>
                    </div>
                    
                    <?php if ($send['send_type'] === 'text'): ?>
                        <div class="send-text">
                            <?php echo htmlspecialchars($send['content']); ?>
                        </div>
                    <?php else: ?>
                        <div class="file-download">
                            <i class="fas fa-download"></i>
                            <a href="download.php?send=<?php echo $send['access_link']; ?>" class="btn">
                                Download File
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$send['hide_email'] && $send['sender_email']): ?>
                        <div class="sender-info">
                            <i class="fas fa-user"></i> Sent by: <?php echo htmlspecialchars($send['sender_email']); ?>
                        </div>
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
</body>
</html>
