<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/SendManager.php';
require_once 'classes/EmailHelper.php';

// Simulate logged in user
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Live Send Demo - SecureIt</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .demo-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #fafafa; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; }
        .access-link { background: #e7f3ff; padding: 15px; border-radius: 6px; margin: 10px 0; border: 1px solid #bee5eb; }
        .access-link a { color: #007cba; font-weight: bold; text-decoration: none; }
        .access-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 Live Send Feature Demo</h1>
        <p>This demo creates actual secure sends and shows how they work.</p>";

try {
    $sendManager = new SendManager();
    
    echo "<div class='demo-section'>";
    echo "<h2>📝 Creating a Text-Based Secure Send</h2>";
      // Create a text-based secure send
    $textContent = "🔒 This is a secret message!\n\nThis secure send was created by the SecureIt Send feature demo.\n\nFeatures demonstrated:\n• Password protection\n• Expiration control\n• View limits\n• Secure access URLs\n\nIf you can read this, the Send feature is working perfectly! 🎉";
    
    $textOptions = [
        'password' => 'demo123',
        'deletion_date' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'max_views' => 3
    ];
    
    $textResult = $sendManager->createSend($_SESSION['user_id'], 'text', 'Demo Text Send', $textContent, $textOptions);
    
    if ($textResult) {
        echo "<p class='success'>✅ Text secure send created successfully!</p>";
        echo "<p><strong>Send ID:</strong> " . $textResult['id'] . "</p>";
        echo "<p><strong>Access Token:</strong> " . $textResult['access_link'] . "</p>";
        echo "<p><strong>Password:</strong> demo123</p>";
        echo "<p><strong>Expires:</strong> " . $textResult['deletion_date'] . "</p>";
        
        $accessUrl = "http://localhost/SecureIt/SecureIT/backend/send_access.php?id=" . $textResult['access_link'];
        echo "<div class='access-link'>";
        echo "<p><strong>🔗 Access URL:</strong></p>";
        echo "<p><a href='$accessUrl' target='_blank'>$accessUrl</a></p>";
        echo "<p><small>Click the link above to test accessing the secure send. Use password: <strong>demo123</strong></small></p>";
        echo "</div>";
    } else {
        echo "<p class='error'>❌ Failed to create text secure send</p>";
    }
    echo "</div>";
    
    echo "<div class='demo-section'>";
    echo "<h2>📄 Creating a File-Based Secure Send</h2>";
    
    // Create a sample file for demonstration
    $sampleContent = "SecureIt Send Feature Demo File\n\nThis is a demonstration file created by the Send feature.\n\nFeatures:\n- Secure file sharing\n- Password protection\n- Download tracking\n- Automatic cleanup\n\nCreated: " . date('Y-m-d H:i:s') . "\nUser: " . $_SESSION['user_name'];
    
    $fileName = 'demo_file_' . time() . '.txt';
    $filePath = __DIR__ . '/uploads/sends/' . $fileName;
    
    if (!is_dir(dirname($filePath))) {
        mkdir(dirname($filePath), 0755, true);
    }
    
    if (file_put_contents($filePath, $sampleContent)) {
        echo "<p class='success'>✅ Sample file created: $fileName</p>";
          // Create file-based secure send
        $fileOptions = [
            'password' => 'filepass456',
            'deletion_date' => date('Y-m-d H:i:s', strtotime('+2 hours')),
            'max_views' => 5,
            'file_path' => $filePath
        ];
        
        $fileResult = $sendManager->createSend($_SESSION['user_id'], 'file', 'Demo File Send', 'SecureIt_Demo.txt', $fileOptions);
        
        if ($fileResult) {
            echo "<p class='success'>✅ File secure send created successfully!</p>";
            echo "<p><strong>Send ID:</strong> " . $fileResult['id'] . "</p>";
            echo "<p><strong>File:</strong> SecureIt_Demo.txt</p>";
            echo "<p><strong>Password:</strong> filepass456</p>";
            
            $fileAccessUrl = "http://localhost/SecureIt/SecureIT/backend/send_access.php?id=" . $fileResult['access_link'];
            echo "<div class='access-link'>";
            echo "<p><strong>🔗 File Access URL:</strong></p>";
            echo "<p><a href='$fileAccessUrl' target='_blank'>$fileAccessUrl</a></p>";
            echo "<p><small>Click the link above to test file download. Use password: <strong>filepass456</strong></small></p>";
            echo "</div>";
        } else {
            echo "<p class='error'>❌ Failed to create file secure send</p>";
        }
    } else {
        echo "<p class='error'>❌ Failed to create sample file</p>";
    }
    echo "</div>";
    
    echo "<div class='demo-section'>";
    echo "<h2>📊 User's Sends Summary</h2>";
    
    $userSends = $sendManager->getUserSends($_SESSION['user_id']);
    echo "<p class='info'>Found " . count($userSends) . " sends for current user:</p>";
    
    if (!empty($userSends)) {
        echo "<table border='1' style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Type</th><th>Created</th><th>Expires</th><th>Views</th><th>Max Views</th><th>Status</th></tr>";
        
        foreach ($userSends as $send) {
            $isExpired = strtotime($send['expires_at']) < time();
            $isExhausted = $send['view_count'] >= $send['max_views'];
            $status = $isExpired ? 'Expired' : ($isExhausted ? 'Exhausted' : 'Active');
            $statusColor = $status === 'Active' ? '#28a745' : '#dc3545';
            
            echo "<tr>";
            echo "<td>" . $send['id'] . "</td>";
            echo "<td>" . ucfirst($send['type']) . "</td>";
            echo "<td>" . $send['created_at'] . "</td>";
            echo "<td>" . $send['expires_at'] . "</td>";
            echo "<td>" . $send['view_count'] . "</td>";
            echo "<td>" . $send['max_views'] . "</td>";
            echo "<td style='color: $statusColor; font-weight: bold;'>$status</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    echo "<div class='demo-section'>";
    echo "<h2>🎯 Next Steps</h2>";
    echo "<ul>";
    echo "<li><strong>Test Access:</strong> Click the access URLs above to test password-protected content</li>";
    echo "<li><strong>Main Vault:</strong> <a href='main_vault.php' target='_blank'>Go to Main Vault</a> to use the full Send interface</li>";
    echo "<li><strong>Email Setup:</strong> Configure Gmail SMTP in <code>classes/EmailHelper.php</code> for live email sending</li>";
    echo "<li><strong>Production:</strong> Update security settings and cleanup policies for production use</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='demo-section'>";
    echo "<p class='error'>❌ Demo Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and ensure all required tables exist.</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>
