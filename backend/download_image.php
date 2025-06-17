<?php
/**
 * Download handler for BLOB-stored images
 */
session_start();

require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

$accessLink = $_GET['send'] ?? $_GET['link'] ?? $_GET['id'] ?? '';
$password = $_POST['password'] ?? $_GET['password'] ?? $_SESSION['temp_password'] ?? '';

// Check if user was already authenticated for this send
$sessionKey = 'temp_download_access_' . $accessLink;
$isPreAuthenticated = isset($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] === true;

if (empty($accessLink)) {
    http_response_code(404);
    die('Invalid access link');
}

try {
    $sendManager = new SendManager();
    
    // First check if send exists and if password is required
    $tempSend = $sendManager->getSend($accessLink);
    
    if (!$tempSend) {
        http_response_code(404);
        die('Send not found or expired');
    }    // Check if password is required and user is not pre-authenticated
    if ($tempSend['password_hash'] && empty($password) && !$isPreAuthenticated) {
        showPasswordForm($accessLink, 'Password required to download this image');
        exit;
    }
      // Access the send
    if ($isPreAuthenticated) {
        // User is pre-authenticated, get send directly without incrementing view count
        $result = ['success' => true, 'send' => $tempSend];
    } else {
        // Normal access (will increment view count)
        $result = $sendManager->accessSend($accessLink, $password);
    }
    
    if (!$result['success']) {
        if (strpos($result['message'], 'password') !== false) {
            showPasswordForm($accessLink, $result['message']);
            exit;
        } else {
            http_response_code(400);
            die($result['message']);
        }
    }
    
    $send = $result['send'];
    
    if ($send['type'] !== 'file' || $send['storage_type'] !== 'blob') {
        http_response_code(400);
        die('This send is not a downloadable image');
    }
    
    // Get image data from database
    $db = new Database();
    $sql = "SELECT file_data, mime_type, file_name FROM sends WHERE access_token = :token";
    $imageData = $db->fetchOne($sql, ['token' => $accessLink]);
    
    if (!$imageData || !$imageData['file_data']) {
        http_response_code(404);
        die('Image data not found');
    }
    
    // Clean filename for download
    $fileName = $imageData['file_name'] ?: 'image';
    $cleanFileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
    
    // Set headers for download
    header('Content-Type: ' . $imageData['mime_type']);
    header('Content-Disposition: attachment; filename="' . $cleanFileName . '"');
    header('Content-Length: ' . strlen($imageData['file_data']));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
      // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Clean up session flag after successful download
    if (isset($_SESSION[$sessionKey])) {
        unset($_SESSION[$sessionKey]);
    }
    
    // Output the image data
    echo $imageData['file_data'];
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    die('Download failed: ' . $e->getMessage());
}

function showPasswordForm($accessLink, $error) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Required - SecureIt</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 20px;
            }
            .password-container {
                background: white;
                border-radius: 12px;
                padding: 2rem;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                max-width: 400px;
                width: 100%;
                text-align: center;
            }
            .password-container h2 {
                color: #374151;
                margin-bottom: 1rem;
            }
            .password-container p {
                color: #6b7280;
                margin-bottom: 1.5rem;
            }
            .form-group {
                margin-bottom: 1rem;
                text-align: left;
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
            }
            .btn:hover {
                background: #6d28d9;
            }
            .error {
                color: #ef4444;
                font-size: 0.9rem;
                margin-bottom: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="password-container">
            <h2>🔐 Password Required</h2>
            <p>This image download is password protected. Please enter the password to download it.</p>
            
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required autofocus>
                </div>
                <button type="submit" class="btn">Download Image</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}
?>
