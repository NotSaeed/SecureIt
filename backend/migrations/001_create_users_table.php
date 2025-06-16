<?php
/**
 * Create Users Table Migration
 */

require_once 'Migration.php';

class CreateUsersTable extends Migration {
    
    public function up() {
        $sql = "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(255) UNIQUE NOT NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `name` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `last_login` TIMESTAMP NULL,
            `security_score` INT DEFAULT 0,
            `two_factor_enabled` BOOLEAN DEFAULT FALSE,
            `two_factor_secret` VARCHAR(255) NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_email` (`email`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($this->execute($sql)) {
            echo "✅ Users table created successfully\n";
        } else {
            echo "❌ Failed to create users table\n";
        }
    }
    
    public function down() {
        $this->dropTable('users');
        echo "✅ Users table dropped\n";
    }
}
?>
