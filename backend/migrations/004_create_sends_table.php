<?php
/**
 * Create Sends Table Migration
 */

require_once 'Migration.php';

class CreateSendsTable extends Migration {
    
    public function up() {
        $sql = "
        CREATE TABLE IF NOT EXISTS `sends` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `send_type` ENUM('text', 'file') NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `content` TEXT NULL,
            `file_path` VARCHAR(500) NULL,
            `access_link` VARCHAR(255) UNIQUE NOT NULL,
            `password_hash` VARCHAR(255) NULL,
            `deletion_date` TIMESTAMP NOT NULL,
            `max_views` INT NULL,
            `current_views` INT DEFAULT 0,
            `hide_email` BOOLEAN DEFAULT FALSE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_access_link` (`access_link`),
            INDEX `idx_deletion_date` (`deletion_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($this->execute($sql)) {
            echo "✅ Sends table created successfully\n";
        } else {
            echo "❌ Failed to create sends table\n";
        }
    }
    
    public function down() {
        $this->dropTable('sends');
        echo "✅ Sends table dropped\n";
    }
}
?>
