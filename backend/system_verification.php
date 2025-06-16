<?php
echo "=== SecureIt System Verification ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    require_once 'classes/Database.php';
    $db = new Database();
    $connection = $db->getConnection();
    echo "   ✅ Database connection successful\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
}

// Test 2: Send Access Functionality
echo "\n2. Testing Send Access Functionality...\n";
try {
    require_once 'classes/SendManager.php';
    $sendManager = new SendManager();
    
    // Test with known send ID
    $testSendId = 'c9e39581d24c5edfc3486b680832ce79';
    $testPassword = '123';
    
    $send = $sendManager->getSend($testSendId, $testPassword);
    echo "   ✅ Send access with password successful\n";
    echo "   📁 Send name: " . $send['name'] . "\n";
    echo "   📋 Send type: " . $send['send_type'] . "\n";
    
    if ($send['send_type'] === 'file' && $send['file_path']) {
        $fileExists = file_exists($send['file_path']);
        echo "   📄 File exists: " . ($fileExists ? 'YES' : 'NO') . "\n";
        if ($fileExists) {
            echo "   📊 File size: " . formatBytes(filesize($send['file_path'])) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Send access failed: " . $e->getMessage() . "\n";
}

// Test 3: Email Configuration
echo "\n3. Testing Email Configuration...\n";
try {
    require_once 'classes/EmailHelper.php';
    $emailHelper = new EmailHelper();
    $config = include 'config/email_config.php';
    
    echo "   📧 SMTP Host: " . $config['SMTP_HOST'] . "\n";
    echo "   🔐 SMTP Username: " . $config['SMTP_USERNAME'] . "\n";
    echo "   🎭 Demo Mode: " . ($config['DEMO_MODE'] ? 'ON' : 'OFF') . "\n";
    
    if ($config['SMTP_USERNAME'] === 'your-email@gmail.com') {
        echo "   ⚠️  Gmail address needs to be configured\n";
    } else {
        echo "   ✅ Gmail address configured\n";
    }
    
    // Test email sending
    $emailResult = $emailHelper->sendAnonymousEmail(
        'test@example.com',
        'recipient@example.com',
        'Test Email',
        'This is a test message',
        true
    );
    
    echo "   📬 Email test: " . ($emailResult ? 'SUCCESS' : 'FAILED') . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Email test failed: " . $e->getMessage() . "\n";
}

// Test 4: File Upload Directory
echo "\n4. Testing File Upload Directory...\n";
$uploadDir = 'uploads/sends/';
if (!is_dir($uploadDir)) {
    echo "   📁 Creating upload directory...\n";
    mkdir($uploadDir, 0755, true);
}

if (is_dir($uploadDir) && is_writable($uploadDir)) {
    echo "   ✅ Upload directory exists and is writable\n";
    
    // Count files in directory
    $files = glob($uploadDir . '*');
    echo "   📄 Files in upload directory: " . count($files) . "\n";
} else {
    echo "   ❌ Upload directory not writable\n";
}

// Test 5: Recent Sends
echo "\n5. Checking Recent Sends...\n";
try {
    $query = "SELECT COUNT(*) as count FROM sends WHERE deletion_date > NOW()";
    $result = $db->fetchOne($query);
    echo "   📊 Active sends: " . $result['count'] . "\n";
    
    $query = "SELECT COUNT(*) as count FROM sends WHERE deletion_date <= NOW()";
    $result = $db->fetchOne($query);
    echo "   ⏰ Expired sends: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "   ❌ Failed to check sends: " . $e->getMessage() . "\n";
}

echo "\n=== Verification Complete ===\n";
echo "📖 See FIXES_COMPLETE.md for detailed setup instructions\n";

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}
?>
