<?php
/**
 * Update sends table to support BLOB file storage
 */
require_once 'classes/Database.php';

try {
    $db = new Database();
    
    echo "Updating sends table for BLOB file storage...\n<br>";
    
    // First, check current table structure
    $columns = $db->fetchAll("SHOW COLUMNS FROM sends");
    $existingColumns = array_column($columns, 'Field');
    
    echo "Current columns: " . implode(', ', $existingColumns) . "\n<br><br>";
    
    // Add new columns for BLOB storage if they don't exist
    $alterQueries = [];
    
    if (!in_array('type', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `type` ENUM('text', 'file', 'credential') NOT NULL DEFAULT 'text'";
    }
    
    if (!in_array('access_token', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `access_token` VARCHAR(255) UNIQUE NOT NULL";
    }
    
    if (!in_array('file_name', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `file_name` VARCHAR(255) NULL";
    }
    
    if (!in_array('file_size', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `file_size` BIGINT NULL";
    }
    
    if (!in_array('file_data', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `file_data` LONGBLOB NULL";
    }
    
    if (!in_array('storage_type', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `storage_type` ENUM('file', 'blob') NULL";
    }
    
    if (!in_array('mime_type', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `mime_type` VARCHAR(255) NULL";
    }
    
    if (!in_array('expires_at', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `expires_at` DATETIME NULL";
    }
    
    if (!in_array('view_count', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `view_count` INT DEFAULT 0";
    }
    
    if (!in_array('last_accessed', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `last_accessed` DATETIME NULL";
    }
    
    if (!in_array('access_password', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `access_password` TEXT NULL";
    }
    
    if (!in_array('anonymous', $existingColumns)) {
        $alterQueries[] = "ADD COLUMN `anonymous` BOOLEAN DEFAULT FALSE";
    }
    
    // Execute alter queries
    if (!empty($alterQueries)) {
        $alterSQL = "ALTER TABLE sends " . implode(', ', $alterQueries);
        echo "Executing: " . $alterSQL . "\n<br><br>";
        
        $db->query($alterSQL);
        echo "✅ Table structure updated successfully!\n<br>";
    } else {
        echo "✅ Table structure is already up to date!\n<br>";
    }
    
    // Try to rename old columns to match new schema
    $renameQueries = [];
    
    if (in_array('send_type', $existingColumns) && !in_array('type', $existingColumns)) {
        $renameQueries[] = "CHANGE COLUMN `send_type` `type` ENUM('text', 'file', 'credential') NOT NULL DEFAULT 'text'";
    }
    
    if (in_array('access_link', $existingColumns) && !in_array('access_token', $existingColumns)) {
        $renameQueries[] = "CHANGE COLUMN `access_link` `access_token` VARCHAR(255) UNIQUE NOT NULL";
    }
    
    if (in_array('deletion_date', $existingColumns) && !in_array('expires_at', $existingColumns)) {
        $renameQueries[] = "CHANGE COLUMN `deletion_date` `expires_at` DATETIME NULL";
    }
    
    if (in_array('current_views', $existingColumns) && !in_array('view_count', $existingColumns)) {
        $renameQueries[] = "CHANGE COLUMN `current_views` `view_count` INT DEFAULT 0";
    }
    
    if (!empty($renameQueries)) {
        $renameSQL = "ALTER TABLE sends " . implode(', ', $renameQueries);
        echo "Executing: " . $renameSQL . "\n<br><br>";
        
        $db->query($renameSQL);
        echo "✅ Column names updated successfully!\n<br>";
    }
    
    // Verify updated table structure
    $updatedColumns = $db->fetchAll("SHOW COLUMNS FROM sends");
    echo "<h3>Updated Sends Table Structure:</h3>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
    
    foreach ($updatedColumns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>✅ Database update completed successfully!</h3>\n";
    echo "<p>The sends table now supports BLOB file storage for all file types.</p>\n";
    
} catch (Exception $e) {
    echo "❌ Error updating database: " . $e->getMessage() . "\n<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
