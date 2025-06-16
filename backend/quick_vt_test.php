<?php
require_once 'classes/VirusTotalHelper.php';

try {
    $vt = new VirusTotalHelper();
    echo "Demo mode: " . ($vt->isDemoMode() ? 'YES' : 'NO') . "\n";
    $config = $vt->getConfigStatus();
    echo "Config: " . json_encode($config) . "\n";
    
    // Test URL scan
    echo "Testing URL scan...\n";
    $result = $vt->scanUrl('https://www.google.com');
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
