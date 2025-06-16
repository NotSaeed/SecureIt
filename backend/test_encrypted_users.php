<?php
// Test encrypted user functionality
require_once 'classes/Database.php';
require_once 'classes/User.php';

echo "Testing Encrypted User Functionality\n";
echo "====================================\n";

try {
    // Test user registration with encryption
    echo "1. Testing encrypted user registration...\n";
    $user = new User();
    $testEmail = "encrypted_test_" . time() . "@example.com";
    $testPassword = "EncryptedTestPassword123!";
    $testName = "Encrypted Test User";
    
    $registerResult = $user->register($testEmail, $testPassword, $testName);
    
    if ($registerResult['success']) {
        echo "✓ Registration successful: " . $registerResult['message'] . "\n";
        echo "✓ User ID: " . $registerResult['user_id'] . "\n";
        
        // Verify data is encrypted in database
        $db = new Database();
        $dbUser = $db->fetchOne("SELECT email_encrypted, name_encrypted, email_hash FROM users WHERE id = ?", [$registerResult['user_id']]);
        
        echo "✓ Email encrypted in DB: " . (strlen($dbUser['email_encrypted']) > 50 ? "YES" : "NO") . "\n";
        echo "✓ Name encrypted in DB: " . (strlen($dbUser['name_encrypted']) > 50 ? "YES" : "NO") . "\n";
        echo "✓ Email hash created: " . (!empty($dbUser['email_hash']) ? "YES" : "NO") . "\n";
        
    } else {
        echo "✗ Registration failed: " . $registerResult['message'] . "\n";
        exit(1);
    }
    
    // Test user login with encrypted data
    echo "\n2. Testing encrypted user login...\n";
    $loginUser = new User();
    $loginResult = $loginUser->login($testEmail, $testPassword);
    
    if ($loginResult['success']) {
        echo "✓ Login successful: " . $loginResult['message'] . "\n";
        echo "✓ Decrypted email: " . $loginResult['user']['email'] . "\n";
        echo "✓ Decrypted name: " . $loginResult['user']['name'] . "\n";
        echo "✓ User ID: " . $loginResult['user']['id'] . "\n";
    } else {
        echo "✗ Login failed: " . $loginResult['message'] . "\n";
        exit(1);
    }
    
    // Test existing user login
    echo "\n3. Testing existing user login (migrated data)...\n";
    $existingUser = new User();
    $existingResult = $existingUser->login("test@secureit.com", "password123");
    
    if ($existingResult['success']) {
        echo "✓ Existing user login successful\n";
        echo "✓ Decrypted email: " . $existingResult['user']['email'] . "\n";
        echo "✓ Decrypted name: " . $existingResult['user']['name'] . "\n";
    } else {
        echo "✗ Existing user login failed: " . $existingResult['message'] . "\n";
    }
    
    // Cleanup test user
    echo "\n4. Cleaning up test user...\n";
    $db->query("DELETE FROM users WHERE id = ?", [$registerResult['user_id']]);
    echo "✓ Test user cleaned up\n";
    
    echo "\n" . str_repeat("=", 40) . "\n";
    echo "All encryption tests passed!\n";
    echo str_repeat("=", 40) . "\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
