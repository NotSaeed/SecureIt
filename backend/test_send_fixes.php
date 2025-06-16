<?php
// Quick test for the fixed functionality
session_start();
require_once 'classes/Database.php';
require_once 'classes/SendManager.php';
require_once 'classes/Vault.php';

try {
    echo "Testing Send Management Fixes..." . PHP_EOL;
    
    $sendManager = new SendManager();
    
    // Test with a fake user ID (this would normally come from session)
    $testUserId = 1;
    
    echo "Testing getSendStats..." . PHP_EOL;
    $stats = $sendManager->getSendStats($testUserId);
    
    echo "Send Statistics:" . PHP_EOL;
    foreach ($stats as $key => $value) {
        echo "  {$key}: {$value}" . PHP_EOL;
    }
    
    echo "Testing getUserSends..." . PHP_EOL;
    $sends = $sendManager->getUserSends($testUserId);
    
    echo "Number of sends found: " . count($sends) . PHP_EOL;
    
    if (!empty($sends)) {
        echo "Sample send fields:" . PHP_EOL;
        $firstSend = $sends[0];
        foreach ($firstSend as $key => $value) {
            echo "  {$key}: " . (is_string($value) ? substr($value, 0, 50) : $value) . PHP_EOL;
        }
    }
    
    echo "All tests completed successfully!" . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
