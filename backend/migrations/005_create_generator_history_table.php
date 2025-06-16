<?php
/**
 * Create Generator History Table Migration
 */

require_once 'Migration.php';

class CreateGeneratorHistoryTable extends Migration {
    
    public function up() {
        $sql = "
        CREATE TABLE IF NOT EXISTS `generator_history` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `generated_type` ENUM('password', 'passphrase', 'username') NOT NULL,
            `generated_value` VARCHAR(500) NOT NULL,
            `options` JSON NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_generated_type` (`generated_type`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($this->execute($sql)) {
            echo "✅ Generator history table created successfully\n";
        } else {
            echo "❌ Failed to create generator history table\n";
        }
    }
    
    public function down() {
        $this->dropTable('generator_history');
        echo "✅ Generator history table dropped\n";
    }
}
?>
