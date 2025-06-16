<?php
/**
 * Add anonymous field to sends table
 */
require_once 'classes/Database.php';

try {
    $db = new Database();
    
    echo "<h2>Adding Anonymous Field to Sends Table</h2>\n";
    
    // Add anonymous column
    $sql = "ALTER TABLE sends ADD COLUMN anonymous TINYINT(1) DEFAULT 0 AFTER hide_email";
    
    try {
        $db->query($sql);
        echo "<p style='color: green;'>✓ Added anonymous column successfully</p>\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠ anonymous column already exists</p>\n";
        } else {
            throw $e;
        }
    }
    
    echo "<p style='color: green;'><strong>Database schema updated for anonymous sends!</strong></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
