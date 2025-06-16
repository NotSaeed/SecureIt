<?php
// Test Secure Send Functionality
echo "<h1>Secure Send Test</h1>\n";

try {
    require_once 'classes/Database.php';
    require_once 'classes/SendManager.php';
    require_once 'classes/User.php';
    
    // Create test user if not exists
    $db = new Database();
    $user = $db->fetchOne("SELECT id FROM users WHERE email = 'test@example.com'");
    
    if (!$user) {
        echo "Creating test user...\n<br>";
        $userClass = new User();
        $testUser = $userClass->create('test@example.com', 'password123', 'Test User');
        $userId = $testUser->id;
    } else {
        $userId = $user['id'];
    }
    
    echo "✅ Test user ready (ID: $userId)\n<br><br>";
    
    // Test 1: Create secure send without password
    echo "<h2>Test 1: Creating secure send without password</h2>\n";
    $sendManager = new SendManager();
    
    $options = [
        'deletion_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'password' => null,
        'max_views' => 5,
        'hide_email' => false
    ];
    
    $result = $sendManager->createSend($userId, 'text', 'Test Send', 'Hello, this is a test message!', $options);
    echo "✅ Send created successfully!\n<br>";
    echo "📋 Access Link: " . $result['access_link'] . "\n<br>";
    
    // Test 2: Create secure send with password
    echo "<h2>Test 2: Creating secure send with password</h2>\n";
    $options['password'] = 'secret123';
    
    $result2 = $sendManager->createSend($userId, 'text', 'Password Protected Send', 'This is a password protected message!', $options);
    echo "✅ Password protected send created!\n<br>";
    echo "📋 Access Link: " . $result2['access_link'] . "\n<br>";
    echo "🔐 Password: secret123\n<br>";
    
    // Test 3: Retrieve send without password
    echo "<h2>Test 3: Retrieving send without password</h2>\n";
    try {
        $send = $sendManager->getSend($result['access_link']);
        echo "✅ Send retrieved successfully!\n<br>";
        echo "📝 Name: " . $send['name'] . "\n<br>";
        echo "📄 Content: " . $send['content'] . "\n<br>";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n<br>";
    }
    
    // Test 4: Try to retrieve password protected send without password
    echo "<h2>Test 4: Trying to access password protected send without password</h2>\n";
    try {
        $send = $sendManager->getSend($result2['access_link']);
        echo "❌ This should have failed!\n<br>";
    } catch (Exception $e) {
        echo "✅ Correctly blocked: " . $e->getMessage() . "\n<br>";
    }
    
    // Test 5: Retrieve password protected send with correct password
    echo "<h2>Test 5: Accessing password protected send with correct password</h2>\n";
    try {
        $send = $sendManager->getSend($result2['access_link'], 'secret123');
        echo "✅ Password protected send accessed successfully!\n<br>";
        echo "📝 Name: " . $send['name'] . "\n<br>";
        echo "📄 Content: " . $send['content'] . "\n<br>";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n<br>";
    }
    
    // Test 6: Get user sends
    echo "<h2>Test 6: Getting user sends</h2>\n";
    $userSends = $sendManager->getUserSends($userId);
    echo "✅ Found " . count($userSends) . " sends\n<br>";
    
    foreach ($userSends as $send) {
        echo "📋 " . $send['name'] . " (Type: " . $send['send_type'] . ", Views: " . $send['current_views'] . ")\n<br>";
    }
    
    // Test 7: Get send stats
    echo "<h2>Test 7: Getting send statistics</h2>\n";
    $stats = $sendManager->getSendStats($userId);
    echo "✅ Statistics retrieved:\n<br>";
    echo "📊 Total sends: " . $stats['total_sends'] . "\n<br>";
    echo "📊 Active sends: " . $stats['active_sends'] . "\n<br>";
    echo "📊 Total views: " . $stats['total_views'] . "\n<br>";
    
    echo "<br><h2>🎉 All tests completed successfully!</h2>\n";
    echo "<p><strong>Access links for testing:</strong></p>\n";
    echo "<p>🔓 <a href='access_send.php?link=" . $result['access_link'] . "' target='_blank'>Open unprotected send</a></p>\n";
    echo "<p>🔐 <a href='access_send.php?link=" . $result2['access_link'] . "' target='_blank'>Open password protected send</a> (Password: secret123)</p>\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "\n<br>";
}
?>
