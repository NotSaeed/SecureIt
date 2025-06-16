<?php
// Direct test of VirusTotal API endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to capture any unwanted output
ob_start();

// Simulate the exact conditions from the web interface
session_start();
$_SESSION['user_id'] = 1; // Simulate logged in user

// Test the check_config action first
$_POST['action'] = 'check_config';
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "Testing VirusTotal API endpoint...\n";
echo "==================================\n";

// Include the API file and capture its output
include 'virustotal_api.php';

$output = ob_get_clean();

echo "Raw output from API:\n";
echo "Length: " . strlen($output) . " characters\n";
echo "Content:\n";
echo "'" . $output . "'\n\n";

// Test if it's valid JSON
$decoded = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✅ Valid JSON response\n";
    print_r($decoded);
} else {
    echo "❌ Invalid JSON - Error: " . json_last_error_msg() . "\n";
    echo "First 500 characters of output:\n";
    echo substr($output, 0, 500) . "\n";
    
    // Look for HTML or PHP errors
    if (strpos($output, '<') !== false) {
        echo "\n⚠️  HTML content detected in response\n";
    }
    if (strpos($output, 'PHP') !== false) {
        echo "\n⚠️  PHP error detected in response\n";
    }
}
?>
