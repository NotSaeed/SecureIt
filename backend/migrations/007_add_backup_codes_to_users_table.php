<?php
/**
 * Add Backup Codes Column to Users Table Migration
 */

require_once 'Migration.php';

class AddBackupCodesToUsersTable extends Migration {
    
    public function up() {
        $sql = "
        ALTER TABLE `users` 
        ADD COLUMN `backup_codes` JSON NULL AFTER `two_factor_secret`
        ";
        
        if ($this->execute($sql)) {
            echo "✅ Backup codes column added to users table successfully\n";
        } else {
            echo "❌ Failed to add backup codes column to users table\n";
        }
    }
    
    public function down() {
        $sql = "ALTER TABLE `users` DROP COLUMN `backup_codes`";
        $this->execute($sql);
        echo "✅ Backup codes column removed from users table\n";
    }
}
?>
