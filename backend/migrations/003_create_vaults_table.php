<?php
/**
 * Create Vaults Table Migration
 */

require_once 'Migration.php';

class CreateVaultsTable extends Migration {
    
    public function up() {
        $sql = "
        CREATE TABLE IF NOT EXISTS `vaults` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `item_name` VARCHAR(255) NOT NULL,
            `item_type` ENUM('login', 'card', 'identity', 'note', 'ssh_key') NOT NULL,
            `encrypted_data` TEXT NOT NULL,
            `website_url` VARCHAR(500) NULL,
            `folder_id` INT NULL,
            `is_favorite` BOOLEAN DEFAULT FALSE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`folder_id`) REFERENCES `folders`(`id`) ON DELETE SET NULL,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_item_type` (`item_type`),
            INDEX `idx_folder_id` (`folder_id`),
            INDEX `idx_is_favorite` (`is_favorite`),
            FULLTEXT `idx_item_name` (`item_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($this->execute($sql)) {
            echo "✅ Vaults table created successfully\n";
        } else {
            echo "❌ Failed to create vaults table\n";
        }
    }
    
    public function down() {
        $this->dropTable('vaults');
        echo "✅ Vaults table dropped\n";
    }
}
?>
