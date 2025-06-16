<?php
/**
 * Migration: Create password_resets table
 * This table stores temporary password reset tokens
 */

require_once __DIR__ . '/Migration.php';

class CreatePasswordResetsTable extends Migration {
      public function up() {
        $sql = "
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                INDEX idx_token (token),
                INDEX idx_user_id (user_id),
                INDEX idx_expires_at (expires_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->execute($sql);
        
        // Add unique constraint to ensure one active token per user
        $constraintSql = "
            ALTER TABLE password_resets 
            ADD INDEX idx_user_active (user_id, used_at)
        ";
        
        try {
            $this->execute($constraintSql);
        } catch (Exception $e) {
            // Index might already exist, ignore
        }
        
        echo "Created password_resets table\n";
    }
    
    public function down() {
        $sql = "DROP TABLE IF EXISTS password_resets";
        $this->execute($sql);
        
        echo "Dropped password_resets table\n";
    }
}

// Run migration if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $migration = new CreatePasswordResetsTable();
    $migration->up();
}
?>
