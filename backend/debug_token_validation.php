<?php
// Debug the token validation logic
require_once 'classes/Database.php';
require_once 'classes/User.php';

echo "<h1>Debug Token Validation</h1>\n";

try {
    $user = new User();
    $db = new Database();
    
    // Clear old tokens for clean test
    $db->query("DELETE FROM password_resets WHERE expires_at < NOW()");
    
    // Test with existing email
    $existingUser = $db->fetchOne("SELECT email FROM users LIMIT 1");
    if ($existingUser) {
        $existingEmail = $existingUser['email'];
        echo "<h2>Test with existing email: " . htmlspecialchars($existingEmail) . "</h2>\n";
        
        $token1 = $user->requestPasswordReset($existingEmail);
        echo "Generated token: " . $token1 . "<br>\n";
        
        // Check if token is in database
        $storedToken = $db->fetchOne("SELECT * FROM password_resets WHERE token = ?", [$token1]);
        echo "Token in database: " . ($storedToken ? "YES" : "NO") . "<br>\n";
        if ($storedToken) {
            echo "User ID: " . $storedToken['user_id'] . "<br>\n";
            echo "Expires at: " . $storedToken['expires_at'] . "<br>\n";
        }
        
        // Test validation
        $isValid = $user->validateResetToken($token1);
        echo "Token validation: " . ($isValid ? "VALID" : "INVALID") . "<br><br>\n";
    }
    
    // Test with non-existing email  
    $nonExistingEmail = "fake" . time() . "@example.com";
    echo "<h2>Test with non-existing email: " . htmlspecialchars($nonExistingEmail) . "</h2>\n";
    
    $token2 = $user->requestPasswordReset($nonExistingEmail);
    echo "Generated token: " . $token2 . "<br>\n";
    
    // Check if token is in database
    $storedToken2 = $db->fetchOne("SELECT * FROM password_resets WHERE token = ?", [$token2]);
    echo "Token in database: " . ($storedToken2 ? "YES" : "NO") . "<br>\n";
    if ($storedToken2) {
        echo "User ID: " . $storedToken2['user_id'] . "<br>\n";
        echo "Expires at: " . $storedToken2['expires_at'] . "<br>\n";
    }
    
    // Test validation
    $isValid2 = $user->validateResetToken($token2);
    echo "Token validation: " . ($isValid2 ? "VALID" : "INVALID") . "<br><br>\n";
    
    // Show all tokens in database
    $allTokens = $db->fetchAll("SELECT pr.*, u.email FROM password_resets pr LEFT JOIN users u ON pr.user_id = u.id ORDER BY pr.created_at DESC LIMIT 10");
    echo "<h2>Recent tokens in database:</h2>\n";
    foreach ($allTokens as $tokenData) {
        echo "Token: " . substr($tokenData['token'], 0, 16) . "... | ";
        echo "Email: " . ($tokenData['email'] ?: 'NULL') . " | ";
        echo "Expires: " . $tokenData['expires_at'] . " | ";
        echo "Used: " . ($tokenData['used_at'] ?: 'No') . "<br>\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>\n";
}
?>
