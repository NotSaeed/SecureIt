<?php
// Debug the timestamp issue
require_once 'classes/Database.php';

echo "Debugging timestamp issue...\n";

try {
    $db = new Database();    // Get the current MySQL time
    $result = $db->fetchOne("SELECT NOW() as now_time");
    echo "Current MySQL time: " . $result['now_time'] . "\n";
    
    // Get the most recent send
    $sql = "SELECT access_token, expires_at FROM sends ORDER BY created_at DESC LIMIT 1";
    $send = $db->fetchOne($sql);
    
    if ($send) {
        echo "Send expires at: " . $send['expires_at'] . "\n";
        echo "Comparison: expires_at > NOW() = ";
        
        $comparisonResult = $db->fetchOne("SELECT (? > NOW()) as result", [$send['expires_at']]);
        echo ($comparisonResult['result'] ? 'TRUE' : 'FALSE') . "\n";
        
        // Try to find the send with the exact query
        $searchResult = $db->fetchOne("SELECT * FROM sends WHERE access_token = ? AND expires_at > NOW()", [$send['access_token']]);
        echo "Direct search result: " . ($searchResult ? 'FOUND' : 'NOT FOUND') . "\n";
        
        // Try without time check
        $searchResult2 = $db->fetchOne("SELECT * FROM sends WHERE access_token = ?", [$send['access_token']]);
        echo "Search without time check: " . ($searchResult2 ? 'FOUND' : 'NOT FOUND') . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
