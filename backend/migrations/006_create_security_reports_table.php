<?php
/**
 * Create Security Reports Table Migration
 */

require_once 'Migration.php';

class CreateSecurityReportsTable extends Migration {
    
    public function up() {
        $sql = "
        CREATE TABLE IF NOT EXISTS `security_reports` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `report_type` VARCHAR(100) NOT NULL,
            `report_data` JSON NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_report_type` (`report_type`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($this->execute($sql)) {
            echo "✅ Security reports table created successfully\n";
        } else {
            echo "❌ Failed to create security reports table\n";
        }
    }
    
    public function down() {
        $this->dropTable('security_reports');
        echo "✅ Security reports table dropped\n";
    }
}
?>
