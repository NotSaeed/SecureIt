<?php
/**
 * Add access_password column to sends table
 * Run this once to update the database schema
 */

require_once 'classes/Database.php';

try {
    $db = new Database();
    
    // Check if column already exists
    $checkSql = "SHOW COLUMNS FROM sends LIKE 'access_password'";
    $columnExists = $db->fetchOne($checkSql);
    
    if (!$columnExists) {
        // Add the access_password column
        $sql = "ALTER TABLE sends ADD COLUMN access_password TEXT NULL AFTER password_hash";
        $result = $db->query($sql);
        
        if ($result) {
            echo "✅ Successfully added access_password column to sends table.\n";
        } else {
            echo "❌ Failed to add access_password column.\n";
        }
    } else {
        echo "ℹ️ access_password column already exists in sends table.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error updating database: " . $e->getMessage() . "\n";
}
?>
