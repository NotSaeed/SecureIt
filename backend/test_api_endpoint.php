<?php
// Test the API endpoint directly
session_start();
$_SESSION['user_id'] = 1; // Simulate logged in user

$_POST['action'] = 'scan_url';
$_POST['url'] = 'https://www.google.com';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capture output
ob_start();
include 'api/virustotal.php';
$output = ob_get_clean();

echo "API Endpoint Response:\n<br>";
echo "<pre style='background: #f5f5f5; padding: 1rem; border-radius: 4px;'>";
echo htmlspecialchars($output);
echo "</pre>\n";

// Check if it's valid JSON
$decoded = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<br>✅ Valid JSON response\n<br>";
    echo "<h3>Parsed Response:</h3>\n";
    echo "<pre style='background: #f0f8ff; padding: 1rem; border-radius: 4px;'>";
    echo htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT));
    echo "</pre>\n";
} else {
    echo "<br>❌ Invalid JSON: " . json_last_error_msg() . "\n<br>";
}
?>
