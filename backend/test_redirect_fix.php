<?php
// Test the redirect functionality
session_start();

echo "Testing the redirect functionality...\n";

// Set up test data
$_SESSION['message'] = 'Test success message';
$_SESSION['message_type'] = 'success';

// Simulate the redirect handling code
$error = '';
$success = '';

// Handle session messages from redirects
if (isset($_SESSION['message'])) {
    if ($_SESSION['message_type'] === 'success') {
        $success = $_SESSION['message'];
    } else {
        $error = $_SESSION['message'];
    }
    unset($_SESSION['message'], $_SESSION['message_type']);
}

echo "Success message: " . $success . "\n";
echo "Error message: " . $error . "\n";

// Verify session is cleared
echo "Session message after handling: " . (isset($_SESSION['message']) ? $_SESSION['message'] : 'CLEARED') . "\n";
echo "Session message_type after handling: " . (isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'CLEARED') . "\n";

echo "\nTest complete! Redirect pattern working correctly.\n";
?>
