<?php
/**
 * Update sends table to support BLOB storage for images
 */
require_once 'classes/Database.php';

try {
    $db = new Database();
    
    echo "<h2>Updating Sends Table Schema</h2>\n";
    
    // Add file_data BLOB column for storing image data
    $sql = "ALTER TABLE sends ADD COLUMN file_data LONGBLOB NULL AFTER file_size";
    
    try {
        $db->query($sql);
        echo "<p style='color: green;'>✓ Added file_data LONGBLOB column successfully</p>\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠ file_data column already exists</p>\n";
        } else {
            throw $e;
        }
    }
    
    // Add storage_type column to distinguish between file and blob storage
    $sql = "ALTER TABLE sends ADD COLUMN storage_type ENUM('file', 'blob') DEFAULT 'file' AFTER file_data";
    
    try {
        $db->query($sql);
        echo "<p style='color: green;'>✓ Added storage_type column successfully</p>\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠ storage_type column already exists</p>\n";
        } else {
            throw $e;
        }
    }
    
    echo "<h3>Updated Table Structure:</h3>\n";
    
    // Show updated table structure
    $result = $db->query("DESCRIBE sends");
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $style = '';
        if ($row['Field'] === 'file_data' || $row['Field'] === 'storage_type') {
            $style = " style='background-color: #e6ffe6;'"; // Highlight new columns
        }
        
        echo "<tr$style>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<p style='color: green;'><strong>Database schema updated successfully!</strong></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th { background-color: #f0f0f0; padding: 8px; }
td { padding: 8px; }
</style>
