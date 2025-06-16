<?php
/**
 * Create a test anonymous send
 */
require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

try {
    $sendManager = new SendManager();
    
    $options = [
        'expiration_date' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
        'max_views' => 5,
        'anonymous' => true,
        'hide_email' => true
    ];
    
    $result = $sendManager->createSend(1, 'text', 'Test Anonymous Message', 'This is a secret anonymous message!', $options);
    
    if ($result) {
        echo "Anonymous send created successfully!\n";
        echo "Access Link: " . $result['access_link'] . "\n";
        echo "URL: http://localhost/SecureIt/backend/access_send.php?link=" . $result['access_link'] . "\n";
    } else {
        echo "Failed to create anonymous send\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
