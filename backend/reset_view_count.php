<?php
/**
 * Reset view count for testing purposes
 */
require_once 'classes/Database.php';

$accessToken = '298d95afab19e704b438d9467bd13590075871caa8a45c7c282b009434f937a8';

try {
    $db = new Database();
    
    // Reset view count to 0 so we can test properly
    $sql = "UPDATE sends SET view_count = 0 WHERE access_token = :token";
    $db->query($sql, ['token' => $accessToken]);
    
    // Check current state
    $sql = "SELECT view_count, max_views, password_hash FROM sends WHERE access_token = :token";
    $result = $db->fetchOne($sql, ['token' => $accessToken]);
    
    echo "✅ Reset complete!\n";
    echo "View Count: {$result['view_count']}\n";
    echo "Max Views: {$result['max_views']}\n";
    echo "Has Password: " . ($result['password_hash'] ? 'Yes' : 'No') . "\n";
    echo "\nNow you can test:\n";
    echo "1. Go to: http://localhost/SecureIt/backend/access_send.php?send=$accessToken\n";
    echo "2. The image should display correctly\n";
    echo "3. The download button should work\n";
    echo "4. No 'Send not found or expired' errors should occur\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
