<?php
// Test file upload for secure send
session_start();
require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

echo "Testing file upload for secure send...\n";

// Set up test user session
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@example.com';

try {
    // Create a test file
    $testFileName = 'test_image.txt';
    $testFilePath = $testFileName;
    $testContent = "This is a test file content for secure send upload.";
    
    file_put_contents($testFilePath, $testContent);
    
    echo "Created test file: $testFileName\n";
    echo "File size: " . filesize($testFilePath) . " bytes\n";
    
    $sendManager = new SendManager();
    
    // Test the createSend method with file
    $options = [
        'expiration_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'password' => 'testpass123',
        'max_views' => 5,
        'file_path' => $testFilePath
    ];
    
    $result = $sendManager->createSend(
        $_SESSION['user_id'],
        'file',
        'Test File Send',
        $testFileName, // Original filename as content
        $options
    );
    
    echo "SUCCESS: File send created!\n";
    echo "Send ID: " . $result['id'] . "\n";
    echo "Access Link: " . $result['access_link'] . "\n";
    echo "Type: " . $result['type'] . "\n";
    echo "Expires: " . $result['expires_at'] . "\n";
    
    // Clean up
    unlink($testFilePath);
    echo "Test file cleaned up.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    // Clean up on error
    if (file_exists($testFilePath)) {
        unlink($testFilePath);
    }
}
?>
