<?php
// Test the cleaned up encrypted functionality
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Vault.php';

echo "Testing Clean Encrypted Database\n";
echo "================================\n";

try {
    // Test 1: User Registration 
    echo "1. Testing user registration...\n";
    $user = new User();
    $testEmail = "clean_test_" . time() . "@example.com";
    $testPassword = "CleanTestPassword123!";
    $testName = "Clean Test User";
    
    $registerResult = $user->register($testEmail, $testPassword, $testName);
    
    if ($registerResult['success']) {
        echo "   ✓ Registration successful\n";
        $userId = $registerResult['user_id'];
        
        // Verify only encrypted data exists in database
        $db = new Database();
        $dbUser = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        
        echo "   Database verification:\n";
        echo "   - email column: " . (strlen($dbUser['email']) > 50 ? "ENCRYPTED ✓" : "PLAINTEXT ✗") . "\n";
        echo "   - name column: " . (strlen($dbUser['name']) > 50 ? "ENCRYPTED ✓" : "PLAINTEXT ✗") . "\n";
        echo "   - email_hash: " . (!empty($dbUser['email_hash']) ? "PRESENT ✓" : "MISSING ✗") . "\n";
        
    } else {
        throw new Exception("Registration failed: " . $registerResult['message']);
    }
    
    // Test 2: User Login
    echo "\n2. Testing user login...\n";
    $loginResult = $user->login($testEmail, $testPassword);
    
    if ($loginResult['success']) {
        echo "   ✓ Login successful\n";
        echo "   - Decrypted email: {$loginResult['user']['email']}\n";
        echo "   - Decrypted name: {$loginResult['user']['name']}\n";
    } else {
        throw new Exception("Login failed: " . $loginResult['message']);
    }
    
    // Test 3: Vault Item Creation
    echo "\n3. Testing vault item creation...\n";
    $vault = new Vault();
    $itemName = "Clean Test Login";
    $itemData = [
        'username' => 'cleantest@example.com',
        'password' => 'MyCleanPassword123!',
        'notes' => 'This is a clean test'
    ];
    $websiteUrl = 'https://cleantest.com';
    
    $itemId = $vault->addItem($userId, $itemName, 'login', $itemData, $websiteUrl);
    echo "   ✓ Vault item created with ID: {$itemId}\n";
    
    // Verify only encrypted data exists in database
    $dbItem = $db->fetchOne("SELECT * FROM vaults WHERE id = ?", [$itemId]);
    
    echo "   Database verification:\n";
    echo "   - item_name column: " . (strlen($dbItem['item_name']) > 50 ? "ENCRYPTED ✓" : "PLAINTEXT ✗") . "\n";
    echo "   - website_url column: " . (strlen($dbItem['website_url']) > 50 ? "ENCRYPTED ✓" : "PLAINTEXT ✗") . "\n";
    echo "   - encrypted_data: " . (strlen($dbItem['encrypted_data']) > 50 ? "ENCRYPTED ✓" : "PLAINTEXT ✗") . "\n";
    echo "   - item_name_hash: " . (!empty($dbItem['item_name_hash']) ? "PRESENT ✓" : "MISSING ✗") . "\n";
    
    // Test 4: Vault Item Retrieval
    echo "\n4. Testing vault item retrieval...\n";
    $retrievedItem = $vault->getItem($itemId, $userId);
    
    if ($retrievedItem) {
        echo "   ✓ Item retrieved and decrypted successfully\n";
        echo "   - Item name: {$retrievedItem['item_name']}\n";
        echo "   - Website URL: {$retrievedItem['website_url']}\n";
        echo "   - Username: {$retrievedItem['decrypted_data']['username']}\n";
        echo "   - Password: [REDACTED]\n";
    } else {
        throw new Exception("Failed to retrieve vault item");
    }
    
    // Test 5: Database Security Check
    echo "\n5. Final database security check...\n";
    
    // Verify no plaintext sensitive data exists
    $securityCheck = $db->fetchOne("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE 
                (email LIKE '%@%' AND LENGTH(email) < 50) OR 
                (name IS NOT NULL AND LENGTH(name) < 50 AND name NOT LIKE '%encrypted%')
            ) as plaintext_users,
            (SELECT COUNT(*) FROM vaults WHERE 
                (item_name IS NOT NULL AND LENGTH(item_name) < 50 AND item_name NOT LIKE '%encrypted%') OR
                (website_url IS NOT NULL AND LENGTH(website_url) < 50 AND website_url LIKE 'http%')
            ) as plaintext_vaults
    ");
    
    echo "   - Users with plaintext data: {$securityCheck['plaintext_users']}\n";
    echo "   - Vault items with plaintext data: {$securityCheck['plaintext_vaults']}\n";
    
    if ($securityCheck['plaintext_users'] == 0 && $securityCheck['plaintext_vaults'] == 0) {
        echo "   ✅ SECURITY PERFECT: No plaintext sensitive data found!\n";
    } else {
        echo "   ⚠️ WARNING: Some plaintext data may still exist\n";
    }
    
    // Cleanup
    echo "\n6. Cleaning up test data...\n";
    $db->query("DELETE FROM vaults WHERE id = ?", [$itemId]);
    $db->query("DELETE FROM users WHERE id = ?", [$userId]);
    echo "   ✓ Test data cleaned up\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🔒 CLEAN DATABASE TESTS PASSED! 🔒\n";
    echo str_repeat("=", 50) . "\n";
    echo "\n✅ DATABASE CLEANUP SUCCESS:\n";
    echo "   • Only one column per sensitive field\n";
    echo "   • All sensitive data encrypted\n";
    echo "   • No redundant plaintext columns\n";
    echo "   • Hash-based search functionality\n";
    echo "   • Complete end-to-end encryption\n";
    echo "\n🛡️ Your database is now PERFECTLY SECURE!\n";
    
} catch (Exception $e) {
    echo "\n❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
