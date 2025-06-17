<?php
require_once 'classes/SendManager.php';

$accessToken = 'e0b146862bae1c4e12705f1accf4d08e722165e0be85ad22fa04b9daf0802ebd';
$sendManager = new SendManager();

echo "Testing fixed getSend method...\n";

try {
    $send = $sendManager->getSend($accessToken);
    
    if ($send) {
        echo "SUCCESS! Send found:\n";
        echo "ID: " . $send['id'] . "\n";
        echo "Type: " . $send['type'] . "\n";
        echo "Name: " . $send['name'] . "\n";
        echo "Expires: " . $send['expires_at'] . "\n";
        echo "View count: " . $send['view_count'] . "/" . $send['max_views'] . "\n";
        
        // Test expiration calculation
        $currentTime = new DateTime();
        $expirationTime = new DateTime($send['expires_at']);
        echo "Current time: " . $currentTime->format('Y-m-d H:i:s') . "\n";
        echo "Expires time: " . $expirationTime->format('Y-m-d H:i:s') . "\n";
        
        if ($expirationTime > $currentTime) {
            $diff = $currentTime->diff($expirationTime);
            echo "Time remaining: " . $diff->format('%h hours, %i minutes') . "\n";
        }
    } else {
        echo "FAILED: Send not found or expired\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
