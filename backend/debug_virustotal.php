<?php
// Debug VirusTotal API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== VirusTotal API Debug ===\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "\n";
echo "POST Data: " . print_r($_POST, true) . "\n";
echo "Files: " . print_r($_FILES, true) . "\n";

session_start();
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";

try {
    require_once 'config/db_config.php';
    echo "Database config loaded successfully\n";
} catch (Exception $e) {
    echo "Database config error: " . $e->getMessage() . "\n";
}

try {
    require_once 'classes/VirusTotalHelper.php';
    echo "VirusTotalHelper loaded successfully\n";
    
    $virustotal = new VirusTotalHelper();
    echo "VirusTotalHelper instantiated successfully\n";
    
    $status = $virustotal->getConfigStatus();
    echo "Config Status: " . print_r($status, true) . "\n";
    
} catch (Exception $e) {
    echo "VirusTotalHelper error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "=== End Debug ===\n";
?>
