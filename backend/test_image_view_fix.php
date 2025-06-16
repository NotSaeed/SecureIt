<?php
/**
 * Test script to verify that image viewing doesn't cause "Send not found or expired" error
 */
session_start();

require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

echo "<h2>Testing Image View Fix</h2>";

try {
    $db = new Database();
    
    // Find a file send with BLOB storage (image)
    $sql = "SELECT * FROM sends WHERE type = 'file' AND storage_type = 'blob' AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1";
    $imageSend = $db->fetchOne($sql);
    
    if (!$imageSend) {
        echo "<p>❌ No image sends found. Please create an image send first.</p>";
        exit;
    }
    
    echo "<h3>Found Image Send:</h3>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> " . $imageSend['id'] . "</li>";
    echo "<li><strong>Access Token:</strong> " . $imageSend['access_token'] . "</li>";
    echo "<li><strong>File Name:</strong> " . htmlspecialchars($imageSend['file_name']) . "</li>";
    echo "<li><strong>View Count:</strong> " . $imageSend['view_count'] . "</li>";
    echo "<li><strong>Max Views:</strong> " . $imageSend['max_views'] . "</li>";
    echo "<li><strong>Has Password:</strong> " . ($imageSend['password_hash'] ? 'Yes' : 'No') . "</li>";
    echo "</ul>";
    
    $accessToken = $imageSend['access_token'];
    
    echo "<h3>Test Links:</h3>";
    echo "<ul>";
    echo "<li><a href='access_send.php?send=$accessToken' target='_blank'>Main Access Page</a></li>";
    echo "<li><a href='view_image.php?send=$accessToken' target='_blank'>Direct Image View</a></li>";
    echo "<li><a href='download_image.php?send=$accessToken' target='_blank'>Direct Image Download</a></li>";
    echo "</ul>";
    
    echo "<h3>Expected Behavior:</h3>";
    echo "<ol>";
    echo "<li>Access the main page → view count should increment to " . ($imageSend['view_count'] + 1) . "</li>";
    echo "<li>Image should display inline (via view_image.php) → view count should NOT increment again</li>";
    echo "<li>Download button should work → view count should NOT increment again</li>";
    echo "<li>No 'Send not found or expired' errors should occur</li>";
    echo "</ol>";
    
    // Check current view count after this script runs
    $sql = "SELECT view_count FROM sends WHERE access_token = :token";
    $currentCount = $db->fetchOne($sql, ['token' => $accessToken]);
    echo "<p><strong>Current view count:</strong> " . $currentCount['view_count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #f5f5f5;
}
h2, h3 {
    color: #333;
}
ul, ol {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
li {
    margin: 5px 0;
}
a {
    color: #7c3aed;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
