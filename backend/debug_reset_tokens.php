<?php
// Debug the password reset token issue
require_once 'classes/Database.php';
require_once 'classes/User.php';

echo "<h2>Debug Password Reset Token Issue</h2>\n";

try {
    $db = new Database();
    $user = new User();
    
    // Check what's in the password_resets table
    echo "<h3>Current Reset Tokens:</h3>\n";
    $tokens = $db->fetchAll("SELECT * FROM password_resets ORDER BY created_at DESC LIMIT 5");
    
    if (empty($tokens)) {
        echo "<p>No reset tokens found.</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>ID</th><th>User ID</th><th>Token (first 20 chars)</th><th>Expires At</th><th>Created At</th><th>Used At</th></tr>\n";
        
        foreach ($tokens as $token) {
            $tokenPreview = substr($token['token'], 0, 20) . '...';
            $expiryStatus = (strtotime($token['expires_at']) > time()) ? 'Valid' : 'Expired';
            $usedStatus = $token['used_at'] ? 'Used' : 'Available';
            
            echo "<tr>";
            echo "<td>{$token['id']}</td>";
            echo "<td>{$token['user_id']}</td>";
            echo "<td>{$tokenPreview}</td>";
            echo "<td>{$token['expires_at']} ($expiryStatus)</td>";
            echo "<td>{$token['created_at']}</td>";
            echo "<td>" . ($token['used_at'] ?: 'NULL') . " ($usedStatus)</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Test with the latest token
        $latestToken = $tokens[0];
        $testToken = $latestToken['token'];
        
        echo "<h3>Testing Latest Token: " . substr($testToken, 0, 20) . "...</h3>\n";
        
        // Test validateResetToken
        echo "<h4>1. Testing validateResetToken:</h4>\n";
        $isValid = $user->validateResetToken($testToken);
        echo "<p>Result: " . ($isValid ? '✅ VALID' : '❌ INVALID') . "</p>\n";
        
        // Check the query manually
        echo "<h4>2. Manual Query Check:</h4>\n";
        $manualCheck = $db->fetchOne(
            "SELECT pr.*, u.email FROM password_resets pr 
             JOIN users u ON pr.user_id = u.id 
             WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used_at IS NULL",
            [$testToken]
        );
        
        if ($manualCheck) {
            echo "<p style='color: green;'>✅ Manual query found valid token for user: {$manualCheck['email']}</p>\n";
            
            // Test resetPassword if token is valid
            if (!$latestToken['used_at']) {
                echo "<h4>3. Testing resetPassword:</h4>\n";
                
                try {
                    $newPassword = 'TestNewPassword123!';
                    $resetResult = $user->resetPassword($testToken, $newPassword);
                    
                    if ($resetResult) {
                        echo "<p style='color: green;'>✅ Password reset successful!</p>\n";
                        
                        // Check if token is now marked as used
                        $updatedToken = $db->fetchOne("SELECT used_at FROM password_resets WHERE token = ?", [$testToken]);
                        echo "<p>Token used_at: " . ($updatedToken['used_at'] ?: 'NULL') . "</p>\n";
                        
                    } else {
                        echo "<p style='color: red;'>❌ Password reset failed</p>\n";
                    }
                    
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Password reset error: " . $e->getMessage() . "</p>\n";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ Token already used, skipping reset test</p>\n";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Manual query found no valid token</p>\n";
            
            // Debug: Check individual conditions
            echo "<h5>Debug individual conditions:</h5>\n";
            
            $tokenExists = $db->fetchOne("SELECT COUNT(*) as count FROM password_resets WHERE token = ?", [$testToken]);
            echo "<p>Token exists: " . ($tokenExists['count'] > 0 ? '✅' : '❌') . "</p>\n";
            
            $notExpired = $db->fetchOne("SELECT COUNT(*) as count FROM password_resets WHERE token = ? AND expires_at > NOW()", [$testToken]);
            echo "<p>Not expired: " . ($notExpired['count'] > 0 ? '✅' : '❌') . "</p>\n";
            
            $notUsed = $db->fetchOne("SELECT COUNT(*) as count FROM password_resets WHERE token = ? AND used_at IS NULL", [$testToken]);
            echo "<p>Not used: " . ($notUsed['count'] > 0 ? '✅' : '❌') . "</p>\n";
            
            $userJoins = $db->fetchOne("SELECT COUNT(*) as count FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ?", [$testToken]);
            echo "<p>User join works: " . ($userJoins['count'] > 0 ? '✅' : '❌') . "</p>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>
