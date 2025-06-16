<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\api\save_brute_force_settings.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

try {
    require_once '../classes/Database.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate settings
    $allowedSettings = [
        'maxAttempts' => ['3', '5', '7', '10'],
        'timeWindow' => ['5', '10', '15', '30', '60'],
        'lockoutDuration' => ['15', '30', '60', '120', '240', '1440'],
        'progressiveLockout' => ['0', '1'],
        'rateLimiting' => ['0', '1'],
        'requestsPerMinute' => ['5', '10', '15', '20'],
        'emailNotifications' => ['0', '1'],
        'notifyOnLockout' => ['0', '1']
    ];
    
    $validatedSettings = [];
    foreach ($allowedSettings as $key => $allowedValues) {
        if (isset($input[$key])) {
            if (in_array($input[$key], $allowedValues)) {
                $validatedSettings[$key] = $input[$key];
            } else {
                throw new Exception("Invalid value for setting: $key");
            }
        }
    }
    
    // Save to database
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Create settings table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS user_brute_force_settings (
            user_id INT PRIMARY KEY,
            max_attempts INT DEFAULT 5,
            time_window INT DEFAULT 15,
            lockout_duration INT DEFAULT 30,
            progressive_lockout TINYINT DEFAULT 1,
            rate_limiting TINYINT DEFAULT 1,
            requests_per_minute INT DEFAULT 10,
            email_notifications TINYINT DEFAULT 1,
            notify_on_lockout TINYINT DEFAULT 1,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $pdo->exec($createTableSQL);
    
    // Prepare data for insertion/update
    $userId = $_SESSION['user_id'];
    $maxAttempts = intval($validatedSettings['maxAttempts'] ?? 5);
    $timeWindow = intval($validatedSettings['timeWindow'] ?? 15);
    $lockoutDuration = intval($validatedSettings['lockoutDuration'] ?? 30);
    $progressiveLockout = intval($validatedSettings['progressiveLockout'] ?? 1);
    $rateLimiting = intval($validatedSettings['rateLimiting'] ?? 1);
    $requestsPerMinute = intval($validatedSettings['requestsPerMinute'] ?? 10);
    $emailNotifications = intval($validatedSettings['emailNotifications'] ?? 1);
    $notifyOnLockout = intval($validatedSettings['notifyOnLockout'] ?? 1);
    
    // Use INSERT ... ON DUPLICATE KEY UPDATE for MySQL/MariaDB
    $sql = "
        INSERT INTO user_brute_force_settings 
        (user_id, max_attempts, time_window, lockout_duration, progressive_lockout, 
         rate_limiting, requests_per_minute, email_notifications, notify_on_lockout) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        max_attempts = VALUES(max_attempts),
        time_window = VALUES(time_window),
        lockout_duration = VALUES(lockout_duration),
        progressive_lockout = VALUES(progressive_lockout),
        rate_limiting = VALUES(rate_limiting),
        requests_per_minute = VALUES(requests_per_minute),
        email_notifications = VALUES(email_notifications),
        notify_on_lockout = VALUES(notify_on_lockout),
        updated_at = CURRENT_TIMESTAMP
    ";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $userId, 
        $maxAttempts, 
        $timeWindow, 
        $lockoutDuration, 
        $progressiveLockout,
        $rateLimiting, 
        $requestsPerMinute, 
        $emailNotifications, 
        $notifyOnLockout
    ]);
    
    if ($result) {
        // Log the settings change
        $logSQL = "
            INSERT INTO security_reports (user_id, report_type, status, details, created_at)
            VALUES (?, 'brute_force_config', 'info', ?, NOW())
        ";
        $logStmt = $pdo->prepare($logSQL);
        $logDetails = json_encode([
            'action' => 'settings_updated',
            'settings' => $validatedSettings,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        $logStmt->execute([$userId, $logDetails]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Brute force protection settings saved successfully',
            'settings' => $validatedSettings
        ]);
    } else {
        throw new Exception('Failed to save settings to database');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error saving settings: ' . $e->getMessage()
    ]);
}
?>
