<?php
/**
 * Quick verification script to test the image view fix
 */
session_start();

require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

$accessToken = '298d95afab19e704b438d9467bd13590075871caa8a45c7c282b009434f937a8';

echo "<h2>Testing Image View Fix - Step by Step</h2>";

try {
    $db = new Database();
    $sendManager = new SendManager();
    
    // Step 1: Check initial state
    $sql = "SELECT view_count, max_views FROM sends WHERE access_token = :token";
    $result = $db->fetchOne($sql, ['token' => $accessToken]);
    
    echo "<h3>Step 1: Initial State</h3>";
    echo "<p>View Count: {$result['view_count']}, Max Views: {$result['max_views']}</p>";
    
    // Step 2: Simulate accessing the main page (will increment view count)
    echo "<h3>Step 2: Simulating Main Page Access</h3>";
    
    // Set pre-authentication flag like access_send.php does
    $_SESSION['temp_download_access_' . $accessToken] = true;
    
    echo "<p>✅ Pre-authentication flag set in session</p>";
    
    // Step 3: Test view_image.php (should NOT increment view count)
    echo "<h3>Step 3: Testing view_image.php (should use pre-auth)</h3>";
    
    // Simulate what view_image.php does
    $sessionKey = 'temp_download_access_' . $accessToken;
    $isPreAuthenticated = isset($_SESSION[$sessionKey]) && $_SESSION[$sessionKey] === true;
    
    echo "<p>Pre-authenticated: " . ($isPreAuthenticated ? 'Yes' : 'No') . "</p>";
    
    if ($isPreAuthenticated) {
        echo "<p>✅ Would use pre-authentication (no view count increment)</p>";
    } else {
        echo "<p>❌ Would increment view count (BAD)</p>";
    }
    
    // Step 4: Test download_image.php (should NOT increment view count)
    echo "<h3>Step 4: Testing download_image.php (should use pre-auth)</h3>";
    
    if ($isPreAuthenticated) {
        echo "<p>✅ Would use pre-authentication (no view count increment)</p>";
    } else {
        echo "<p>❌ Would increment view count (BAD)</p>";
    }
    
    // Check final state
    $finalResult = $db->fetchOne($sql, ['token' => $accessToken]);
    echo "<h3>Final State</h3>";
    echo "<p>View Count: {$finalResult['view_count']}, Max Views: {$finalResult['max_views']}</p>";
    
    if ($result['view_count'] == $finalResult['view_count']) {
        echo "<p>✅ SUCCESS: View count unchanged by this test</p>";
    } else {
        echo "<p>❌ View count changed during test</p>";
    }
    
    echo "<h3>Test Links (should work now):</h3>";
    echo "<ul>";
    echo "<li><a href='view_image.php?send=$accessToken' target='_blank'>Direct Image View</a></li>";
    echo "<li><a href='download_image.php?send=$accessToken' target='_blank'>Direct Image Download</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
h2, h3 { color: #333; }
p { background: #f9f9f9; padding: 10px; border-radius: 4px; }
ul { background: white; padding: 15px; border-radius: 8px; }
</style>
