<?php
/**
 * Create a test image with password protection
 */
require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

try {
    $sendManager = new SendManager();
    
    // Create a small test image file temporarily
    $tempImagePath = 'temp_test_secure_image.jpg';
    $imageData = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
    file_put_contents($tempImagePath, $imageData);
    
    $options = [
        'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'max_views' => 5,
        'password' => 'test123', // Add password protection
        'file_path' => $tempImagePath
    ];
    
    $imageResult = $sendManager->createSend(1, 'file', 'Secure Image Test', 'secure_test_image.jpg', $options);
    
    if ($imageResult) {
        echo "Password-protected image send created successfully!\n";
        echo "Access Link: " . $imageResult['access_link'] . "\n";
        echo "Password: test123\n";
        echo "URL: http://localhost/SecureIt/backend/access_send.php?link=" . $imageResult['access_link'] . "\n";
    } else {
        echo "Failed to create image send\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Clean up temporary files on error
    if (isset($tempImagePath) && file_exists($tempImagePath)) {
        unlink($tempImagePath);
    }
}
?>
