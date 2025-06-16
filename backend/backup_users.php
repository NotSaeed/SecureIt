<?php
/**
 * Database Backup Script
 * Creates a backup of the users table before migration
 */

require_once 'classes/Database.php';

echo "SecureIt Database Backup\n";
echo "========================\n";

try {
    $db = new Database();
    
    // Create backup table
    echo "Creating backup of users table...\n";
    
    $db->query("DROP TABLE IF EXISTS users_backup_" . date('Y_m_d_H_i_s'));
    $db->query("CREATE TABLE users_backup_" . date('Y_m_d_H_i_s') . " AS SELECT * FROM users");
    
    $count = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
    
    echo "✓ Backup completed successfully!\n";
    echo "✓ Backed up {$count} user records\n";
    echo "✓ Backup table: users_backup_" . date('Y_m_d_H_i_s') . "\n";
    
} catch (Exception $e) {
    echo "❌ Backup failed: " . $e->getMessage() . "\n";
}
?>
