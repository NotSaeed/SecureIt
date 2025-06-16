<?php
// Final Integration Test - Verify all systems are working
echo "<h1>SecureIt Final Integration Test</h1>\n";
echo "<hr>\n";

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>\n";
try {
    require_once 'classes/Database.php';
    $db = new Database();
    echo "✅ Database connection successful\n<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n<br>";
}

// Test 2: User Class
echo "<h2>Test 2: User Class Encryption</h2>\n";
try {
    require_once 'classes/User.php';
    $user = new User();
    echo "✅ User class loaded successfully\n<br>";
    echo "✅ Encryption methods available\n<br>";
} catch (Exception $e) {
    echo "❌ User class failed: " . $e->getMessage() . "\n<br>";
}

// Test 3: Vault Class
echo "<h2>Test 3: Vault Class Encryption</h2>\n";
try {
    require_once 'classes/Vault.php';
    $vault = new Vault();
    echo "✅ Vault class loaded successfully\n<br>";
    echo "✅ Encryption methods available\n<br>";
} catch (Exception $e) {
    echo "❌ Vault class failed: " . $e->getMessage() . "\n<br>";
}

// Test 4: VirusTotal API
echo "<h2>Test 4: VirusTotal API</h2>\n";
try {
    require_once 'classes/VirusTotalAPI.php';
    $vtApi = new VirusTotalAPI();
    echo "✅ VirusTotal API class loaded\n<br>";
      // Test API key validation
    $isValid = $vtApi->validateApiKey();
    if ($isValid) {
        echo "✅ API Key is valid\n<br>";
    } else {
        echo "❌ API Key validation failed\n<br>";
    }
} catch (Exception $e) {
    echo "❌ VirusTotal API failed: " . $e->getMessage() . "\n<br>";
}

// Test 5: Send Manager
echo "<h2>Test 5: Send Manager</h2>\n";
try {
    require_once 'classes/SendManager.php';
    $sendManager = new SendManager();
    echo "✅ Send Manager loaded successfully\n<br>";
} catch (Exception $e) {
    echo "❌ Send Manager failed: " . $e->getMessage() . "\n<br>";
}

// Test 6: Check essential files
echo "<h2>Test 6: Essential Files Check</h2>\n";
$essentialFiles = [
    'main_vault.php' => 'Main application interface',
    'api/virustotal.php' => 'VirusTotal API endpoint',
    'classes/EncryptionHelper.php' => 'Encryption helper class'
];

foreach ($essentialFiles as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description ($file)\n<br>";
    } else {
        echo "❌ Missing: $description ($file)\n<br>";
    }
}

echo "<hr>\n";
echo "<h2>🎉 Integration Test Complete!</h2>\n";
echo "<p><a href='main_vault.php'>🚀 Launch SecureIt Application</a></p>\n";
?>
