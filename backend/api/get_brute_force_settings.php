<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\api\get_brute_force_settings.php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    $db = new Database();
    $pdo = $db->getConnection();
    $userId = $_SESSION['user_id'];
    
    // Get user's brute force settings
    $sql = "
        SELECT max_attempts, time_window, lockout_duration, progressive_lockout, 
               rate_limiting, requests_per_minute, email_notifications, notify_on_lockout,
               updated_at
        FROM user_brute_force_settings 
        WHERE user_id = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no settings found, return defaults
    if (!$settings) {
        $settings = [
            'max_attempts' => 5,
            'time_window' => 15,
            'lockout_duration' => 30,
            'progressive_lockout' => 1,
            'rate_limiting' => 1,
            'requests_per_minute' => 10,
            'email_notifications' => 1,
            'notify_on_lockout' => 1,
            'updated_at' => null
        ];
    }
    
    // Convert to strings for form compatibility
    $formattedSettings = [
        'maxAttempts' => strval($settings['max_attempts']),
        'timeWindow' => strval($settings['time_window']),
        'lockoutDuration' => strval($settings['lockout_duration']),
        'progressiveLockout' => strval($settings['progressive_lockout']),
        'rateLimiting' => strval($settings['rate_limiting']),
        'requestsPerMinute' => strval($settings['requests_per_minute']),
        'emailNotifications' => strval($settings['email_notifications']),
        'notifyOnLockout' => strval($settings['notify_on_lockout']),
        'lastUpdated' => $settings['updated_at']
    ];
    
    echo json_encode([
        'success' => true,
        'settings' => $formattedSettings
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error loading settings: ' . $e->getMessage()
    ]);
}
?>
