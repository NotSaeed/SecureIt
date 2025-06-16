<?php
require_once 'classes/Database.php';

echo "=== DEBUG: Sends Table Structure ===\n";
try {
    $db = new Database();
    
    // Check table structure
    $result = $db->fetchAll('DESCRIBE sends');
    foreach($result as $row) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\n=== Recent Sends ===\n";
    $sends = $db->fetchAll('SELECT access_link, deletion_date, send_type, name, created_at FROM sends ORDER BY created_at DESC LIMIT 5');
    foreach($sends as $send) {
        echo "Link: " . $send['access_link'] . "\n";
        echo "Expires: " . $send['deletion_date'] . "\n";
        echo "Type: " . $send['send_type'] . "\n";
        echo "Name: " . $send['name'] . "\n";
        echo "Created: " . $send['created_at'] . "\n";
        echo "---\n";
    }
    
    echo "\n=== Test Access Link ===\n";
    $testLink = 'c9e39581d24c5edfc3486b680832ce79';
    $testSend = $db->fetchOne('SELECT * FROM sends WHERE access_link = ?', [$testLink]);
    if ($testSend) {
        echo "Found send: " . $testSend['name'] . "\n";
        echo "Deletion date: " . $testSend['deletion_date'] . "\n";
        echo "Current time: " . date('Y-m-d H:i:s') . "\n";
        echo "Expired? " . (strtotime($testSend['deletion_date']) < time() ? "YES" : "NO") . "\n";
    } else {
        echo "Send not found with access link: $testLink\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
