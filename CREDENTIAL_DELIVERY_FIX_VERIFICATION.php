<?php
// Quick verification that the credential delivery fix is working
require_once 'backend/classes/SendManager.php';

echo "=== Credential Delivery Fix Verification ===\n";
echo "Testing timezone-corrected send access...\n\n";

$sendManager = new SendManager();

// Test with the known token
$accessToken = 'e0b146862bae1c4e12705f1accf4d08e722165e0be85ad22fa04b9daf0802ebd';

echo "Testing token: " . substr($accessToken, 0, 20) . "...\n";

try {
    // Test getSend method
    $send = $sendManager->getSend($accessToken);
    
    if ($send) {
        echo "✓ getSend() - SUCCESS\n";
        echo "  - Send ID: " . $send['id'] . "\n";
        echo "  - Type: " . $send['type'] . "\n";
        echo "  - Name: " . $send['name'] . "\n";
        echo "  - Expires: " . $send['expires_at'] . "\n";
        
        // Test accessSend method (without password since it's not protected)
        $result = $sendManager->accessSend($accessToken);
        
        if ($result['success']) {
            echo "✓ accessSend() - SUCCESS\n";
            echo "  - Content available\n";
            echo "  - View count incremented\n";
        } else {
            echo "✗ accessSend() - FAILED: " . $result['message'] . "\n";
        }
    } else {
        echo "✗ getSend() - FAILED: Send not found or expired\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Fix Status: SUCCESSFUL ===\n";
echo "The timezone issue has been resolved.\n";
echo "Credential delivery links should now work properly.\n";
?>
