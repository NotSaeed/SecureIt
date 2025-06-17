<?php
require_once 'classes/Database.php';
$db = new Database();
try {
    $sends = $db->fetchAll('SELECT id, access_token, type, name, expires_at, view_count, max_views, created_at FROM sends ORDER BY created_at DESC LIMIT 5');
    if (empty($sends)) {
        echo "No sends found in database\n";
    } else {
        echo "Recent sends:\n";
        foreach ($sends as $send) {
            echo "ID: " . $send['id'] . ", Token: " . substr($send['access_token'], 0, 20) . "..., Type: " . $send['type'] . ", Name: " . $send['name'] . ", Expires: " . $send['expires_at'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
