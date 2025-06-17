<?php
// Debug file upload issues
echo "Debugging file upload...\n";

// Check if uploads directory exists and is writable
$uploadDir = 'uploads/sends/';
echo "Upload directory: " . realpath($uploadDir) . "\n";
echo "Directory exists: " . (is_dir($uploadDir) ? 'Yes' : 'No') . "\n";
echo "Directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "\n";

// Check directory permissions
if (is_dir($uploadDir)) {
    $perms = fileperms($uploadDir);
    echo "Directory permissions: " . substr(sprintf('%o', $perms), -4) . "\n";
}

// Check if we can create a test file
$testFile = $uploadDir . 'test_write.txt';
try {
    file_put_contents($testFile, 'test');
    echo "Can write to directory: Yes\n";
    unlink($testFile);
} catch (Exception $e) {
    echo "Can write to directory: No - " . $e->getMessage() . "\n";
}

// Check PHP upload settings
echo "\nPHP Upload Settings:\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'On' : 'Off') . "\n";
echo "upload_tmp_dir: " . ini_get('upload_tmp_dir') . "\n";

// Check if tmp directory exists and is writable
$tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
echo "Temp directory exists: " . (is_dir($tmpDir) ? 'Yes' : 'No') . "\n";
echo "Temp directory writable: " . (is_writable($tmpDir) ? 'Yes' : 'No') . "\n";
?>
