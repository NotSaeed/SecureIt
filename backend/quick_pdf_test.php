<?php
/**
 * Quick test to create a PDF send
 */
require_once 'classes/Database.php';
require_once 'classes/SendManager.php';

try {
    $sendManager = new SendManager();
    
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
    
    $pdfResult = $sendManager->createSend(1, 'file', 'Test PDF Download', 'test_document.pdf', $options);
    
    if ($pdfResult) {
        echo "PDF send created successfully!\n";
        echo "Access Link: " . $pdfResult['access_link'] . "\n";
        echo "URL: http://localhost/SecureIt/backend/access_send.php?link=" . $pdfResult['access_link'] . "\n";
    } else {
        echo "Failed to create PDF send\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Clean up temporary files on error
    if (isset($tempPdfPath) && file_exists($tempPdfPath)) {
        unlink($tempPdfPath);
    }
}
?>
