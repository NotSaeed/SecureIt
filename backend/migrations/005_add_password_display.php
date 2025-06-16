<?php
/**
 * Migration: Add password display column to sends table
 */

require_once __DIR__ . '/../classes/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Adding password_display column to sends table...\n";
    
    // Add the password_display column for storing encrypted password for display
    $sql = "ALTER TABLE sends ADD COLUMN password_display TEXT NULL AFTER password_hash";
    $conn->query($sql);
    
    echo "✅ Password display column added successfully\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
