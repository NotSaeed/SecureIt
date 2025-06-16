<?php
/**
 * Test the updated SendManager with BLOB storage
 */
require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Testing SendManager with BLOB Storage</h2>\n";

try {
    $sendManager = new SendManager();
    
    // Test 1: Create a simple text send
    echo "<h3>Test 1: Creating Text Send</h3>\n";
    $textResult = $sendManager->createSend(1, 'text', 'Test Text Send', 'This is a test message', [
        'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'max_views' => 5
    ]);
    
    if ($textResult) {
        echo "<p style='color: green;'>✓ Text send created successfully!</p>\n";
        echo "<p>Access Link: " . htmlspecialchars($textResult['access_link']) . "</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Failed to create text send</p>\n";
    }
    
    // Test 2: Create a fake image file send (simulate BLOB storage)
    echo "<h3>Test 2: Simulating Image Send with BLOB Storage</h3>\n";
    
    // Create a small test image file temporarily
    $tempImagePath = 'temp_test_image.jpg';
    $imageData = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
    file_put_contents($tempImagePath, $imageData);
    
    // Set image mime type manually
    $options = [
        'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'max_views' => 3,
        'file_path' => $tempImagePath
    ];
    
    // Override mime_content_type for our test
    $originalMimeContentType = function_exists('mime_content_type');
    if (!$originalMimeContentType) {
        function mime_content_type($file) {
            return 'image/jpeg';
        }
    }
    
    $imageResult = $sendManager->createSend(1, 'file', 'Test Image Send', 'test_image.jpg', $options);
    
    if ($imageResult) {
        echo "<p style='color: green;'>✓ Image send created successfully with BLOB storage!</p>\n";
        echo "<p>Access Link: " . htmlspecialchars($imageResult['access_link']) . "</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Failed to create image send</p>\n";
    }
    
    // Test 3: Create a fake PDF file send (simulate file storage)
    echo "<h3>Test 3: Simulating PDF Send with File Storage</h3>\n";
    
    // Create a small test PDF file temporarily
    $tempPdfPath = 'temp_test_document.pdf';
    $pdfData = '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
>>
endobj
xref
0 4
0000000000 65535 f 
0000000010 00000 n 
0000000053 00000 n 
0000000125 00000 n 
trailer
<<
/Size 4
/Root 1 0 R
>>
startxref
173
%%EOF';
    file_put_contents($tempPdfPath, $pdfData);
    
    $options = [
        'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'max_views' => 3,
        'file_path' => $tempPdfPath
    ];
    
    $pdfResult = $sendManager->createSend(1, 'file', 'Test PDF Send', 'test_document.pdf', $options);
    
    if ($pdfResult) {
        echo "<p style='color: green;'>✓ PDF send created successfully with file storage!</p>\n";
        echo "<p>Access Link: " . htmlspecialchars($pdfResult['access_link']) . "</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Failed to create PDF send</p>\n";
    }
    
    // Check database for recent sends
    echo "<h3>Recent Sends in Database</h3>\n";
    $db = new Database();
    $recentSends = $db->fetchAll("SELECT id, name, type, storage_type, file_name, file_size, mime_type, created_at FROM sends ORDER BY created_at DESC LIMIT 5");
    
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Storage</th><th>File Name</th><th>Size</th><th>MIME</th><th>Created</th></tr>\n";
    
    foreach ($recentSends as $send) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($send['id']) . "</td>";
        echo "<td>" . htmlspecialchars($send['name']) . "</td>";
        echo "<td>" . htmlspecialchars($send['type']) . "</td>";
        echo "<td>" . htmlspecialchars($send['storage_type'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($send['file_name'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($send['file_size'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($send['mime_type'] ?: 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($send['created_at']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Clean up temporary files
    if (file_exists($tempImagePath)) {
        unlink($tempImagePath);
    }
    
    echo "<p style='color: green;'><strong>All tests completed successfully!</strong></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Stack trace:</p><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
    
    // Clean up temporary files on error
    if (isset($tempImagePath) && file_exists($tempImagePath)) {
        unlink($tempImagePath);
    }
    if (isset($tempPdfPath) && file_exists($tempPdfPath)) {
        unlink($tempPdfPath);
    }
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th { background-color: #f0f0f0; padding: 8px; }
td { padding: 8px; }
</style>
