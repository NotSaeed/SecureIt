<?php
/**
 * Create a test image send without password for easy testing
 */
session_start();

require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

// Fake user ID for testing
$userId = 1;

try {
    $sendManager = new SendManager();
      // Create a simple test image (1x1 red pixel PNG)
    $imageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');
    
    $result = $sendManager->createSend(
        $userId,
        'file', 
        'Test Image Fix',
        null, // content is null for files
        [
            'file_name' => 'test-image.png',
            'file_data' => $imageData,
            'file_size' => strlen($imageData),
            'mime_type' => 'image/png',
            'storage_type' => 'blob',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'max_views' => 1, // Critical: Only 1 view to test the fix
            'password' => null, // No password for easy testing
            'anonymous' => false
        ]
    );
    
    if ($result['success']) {
        $accessToken = $result['access_token'];
        
        echo "✅ Test image send created successfully!\n";
        echo "Access Token: $accessToken\n";
        echo "Max Views: 1 (perfect for testing)\n";
        echo "No Password Required\n\n";
        
        echo "Test URLs:\n";
        echo "1. Main Access: http://localhost/SecureIt/backend/access_send.php?send=$accessToken\n";
        echo "2. Direct View: http://localhost/SecureIt/backend/view_image.php?send=$accessToken\n";
        echo "3. Direct Download: http://localhost/SecureIt/backend/download_image.php?send=$accessToken\n\n";
        
        echo "Expected behavior:\n";
        echo "- Access main page → view count = 1\n";
        echo "- Image displays inline → view count stays 1 (pre-auth used)\n";
        echo "- Download works → view count stays 1 (pre-auth used)\n";
        echo "- No 'Send not found or expired' errors\n";
        
    } else {
        echo "❌ Failed to create test send: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
