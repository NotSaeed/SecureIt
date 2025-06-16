<?php
/**
 * Create Migrations Tracking Table Migration
 */

require_once 'Migration.php';

class CreateMigrationsTable extends Migration {
    
    public function up() {
        $sql = "
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        if ($this->execute($sql)) {
            echo "✅ Migrations tracking table created successfully\n";
        } else {
            echo "❌ Failed to create migrations tracking table\n";
        }
    }
    
    public function down() {
        $this->dropTable('migrations');
        echo "✅ Migrations tracking table dropped\n";
    }
}
?>
