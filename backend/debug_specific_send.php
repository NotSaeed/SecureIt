<?php
require_once 'classes/Database.php';
$db = new Database();

$accessToken = 'e0b146862bae1c4e12705f1accf4d08e722165e0be85ad22fa04b9daf0802ebd';

try {
    echo "Looking for token: $accessToken\n\n";
    
    // Check if send exists
    $send = $db->fetchOne('SELECT * FROM sends WHERE access_token = :access_token', ['access_token' => $accessToken]);
    
    if (!$send) {
        echo "Send not found with this exact token\n";
        
        // Check for partial matches
        $partialMatches = $db->fetchAll('SELECT id, access_token, expires_at FROM sends WHERE access_token LIKE :partial_token', 
            ['partial_token' => substr($accessToken, 0, 20) . '%']);
        
        if (!empty($partialMatches)) {
            echo "Found sends with similar tokens:\n";
            foreach ($partialMatches as $match) {
                echo "ID: " . $match['id'] . ", Token: " . $match['access_token'] . ", Expires: " . $match['expires_at'] . "\n";
            }
        }
    } else {
        echo "Send found!\n";
        echo "ID: " . $send['id'] . "\n";
        echo "Type: " . $send['type'] . "\n";
        echo "Name: " . $send['name'] . "\n";
        echo "Expires: " . $send['expires_at'] . "\n";
        echo "View count: " . $send['view_count'] . "/" . $send['max_views'] . "\n";
        echo "Created: " . $send['created_at'] . "\n";
        
        // Check if expired
        $now = new DateTime();
        $expires = new DateTime($send['expires_at']);
        if ($expires < $now) {
            echo "*** SEND IS EXPIRED ***\n";
        } else {
            echo "Send is still valid\n";
        }
        
        // Check view limit
        if ($send['max_views'] !== null && $send['view_count'] >= $send['max_views']) {
            echo "*** MAX VIEWS EXCEEDED ***\n";
        }
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
