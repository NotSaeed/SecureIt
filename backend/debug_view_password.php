<?php
session_start();
// Simple test to verify the View Password functionality

// Mock user session
$_SESSION['user_id'] = 1;

echo "<h1>Debug View Password Button</h1>";

// Test if the JavaScript function exists when page loads
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug View Password</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-btn { 
            padding: 10px 20px; 
            background: #0ea5e9; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            margin: 10px;
        }
        .test-btn:hover { background: #0284c7; }
        .debug-box { 
            background: #f0f9ff; 
            border: 1px solid #0ea5e9; 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px; 
        }
    </style>
</head>
<body>

<div class="debug-box">
    <h3>🔍 Debug Test</h3>
    <p>Testing the View Password button functionality:</p>
    
    <!-- Simulate the actual button from manage sends -->
    <button class="test-btn" onclick="viewSendPassword('1')" id="viewPasswordBtn_1">
        <i class="fas fa-eye"></i> View Password (Test)
    </button>
    
    <button class="test-btn" onclick="testBasicClick()">
        Test Basic Click
    </button>
</div>

<div class="debug-box">
    <h3>📋 Results</h3>
    <div id="results">Waiting for test...</div>
</div>

<script>
// Basic click test
function testBasicClick() {
    document.getElementById('results').innerHTML += '<br>✅ Basic click works!';
    console.log('Basic click test passed');
}

// Mock functions for testing
function viewSendPassword(sendId) {
    document.getElementById('results').innerHTML += '<br>🔑 viewSendPassword called with ID: ' + sendId;
    console.log('viewSendPassword called with ID:', sendId);
    
    // Test the AJAX call
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_send_password&send_id=${sendId}`
    })
    .then(response => response.json())
    .then(data => {
        console.log('AJAX response:', data);
        document.getElementById('results').innerHTML += '<br>📡 AJAX Response: ' + JSON.stringify(data);
        
        if (data.success) {
            document.getElementById('results').innerHTML += '<br>✅ Password retrieved: ' + data.password;
        } else {
            document.getElementById('results').innerHTML += '<br>❌ Error: ' + (data.message || 'Unknown error');
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        document.getElementById('results').innerHTML += '<br>❌ AJAX Error: ' + error.message;
    });
}

function showNotification(message, type) {
    document.getElementById('results').innerHTML += '<br>🔔 Notification (' + type + '): ' + message;
    console.log('Notification:', message, type);
}

// Test on page load
window.onload = function() {
    document.getElementById('results').innerHTML = '🚀 Page loaded. Ready for testing.';
    console.log('Debug page loaded');
};
</script>

</body>
</html>

<?php
// Handle the AJAX request if it comes in
if ($_POST['action'] ?? '' === 'get_send_password') {
    try {
        // For testing, return a mock password
        $mockPassword = 'TestPassword123!';
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'password' => $mockPassword]);
        exit();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Test error: ' . $e->getMessage()]);
        exit();
    }
}
?>
