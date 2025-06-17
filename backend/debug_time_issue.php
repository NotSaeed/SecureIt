<?php
require_once 'classes/Database.php';
$db = new Database();
$accessToken = 'e0b146862bae1c4e12705f1accf4d08e722165e0be85ad22fa04b9daf0802ebd';

try {
    echo "Current server time: " . date('Y-m-d H:i:s') . "\n";
    echo "Current MySQL time: ";    $mysqlTime = $db->fetchOne('SELECT NOW() as current_time');
    echo $mysqlTime['current_time'] . "\n\n";
    
    // Test the exact query used in getSend method
    $sql = "SELECT * FROM sends WHERE access_token = :access_token AND expires_at > NOW()";
    $send = $db->fetchOne($sql, ['access_token' => $accessToken]);
    
    if (!$send) {
        echo "Query with NOW() filter: FAILED (send not found)\n";
        
        // Test without NOW() filter
        $sqlWithoutTime = "SELECT * FROM sends WHERE access_token = :access_token";
        $sendWithoutTime = $db->fetchOne($sqlWithoutTime, ['access_token' => $accessToken]);
        
        if ($sendWithoutTime) {
            echo "Send exists but expires_at filter is blocking it\n";
            echo "Send expires_at: " . $sendWithoutTime['expires_at'] . "\n";
            echo "MySQL NOW(): " . $mysqlTime['current_time'] . "\n";
            
            // Compare times
            $expiresTime = new DateTime($sendWithoutTime['expires_at']);
            $currentTime = new DateTime($mysqlTime['current_time']);
            
            if ($expiresTime < $currentTime) {
                echo "*** SEND HAS EXPIRED ***\n";
                echo "Time difference: " . $currentTime->diff($expiresTime)->format('%h hours, %i minutes ago') . "\n";
            }
        }
    } else {
        echo "Query with NOW() filter: SUCCESS\n";
        echo "Send ID: " . $send['id'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
