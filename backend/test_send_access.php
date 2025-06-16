<?php
// Test accessing a send
session_start();
require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

echo "Testing send access...\n";

try {
    $sendManager = new SendManager();
      // Get a recent send to test with
    $db = new Database();
    $sql = "SELECT access_token, type, password_hash, expires_at, max_views, view_count FROM sends ORDER BY created_at DESC LIMIT 1";
    $send = $db->fetchOne($sql);
    
    if (!$send) {
        echo "No sends found in database\n";
        exit;
    }
    
    echo "Testing with token: " . substr($send['access_token'], 0, 20) . "...\n";
    echo "Type: " . $send['type'] . "\n";
    echo "Has password: " . (empty($send['password_hash']) ? 'No' : 'Yes') . "\n";
    echo "Expires at: " . $send['expires_at'] . "\n";
    echo "Max views: " . ($send['max_views'] ?? 'Unlimited') . "\n";
    echo "Current views: " . $send['view_count'] . "\n";
    echo "Is expired: " . (strtotime($send['expires_at']) < time() ? 'Yes' : 'No') . "\n";
    echo "Is exhausted: " . (($send['max_views'] !== null && $send['view_count'] >= $send['max_views']) ? 'Yes' : 'No') . "\n";
    
    // Try to get the send
    $retrievedSend = $sendManager->getSend($send['access_token']);
    
    if ($retrievedSend) {
        echo "SUCCESS: Send retrieved successfully!\n";
        echo "Send name: " . $retrievedSend['name'] . "\n";
        echo "Type: " . $retrievedSend['type'] . "\n";
        echo "Max views: " . ($retrievedSend['max_views'] ?? 'Unlimited') . "\n";
        echo "Current views: " . $retrievedSend['view_count'] . "\n";
    } else {
        echo "ERROR: Could not retrieve send\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
