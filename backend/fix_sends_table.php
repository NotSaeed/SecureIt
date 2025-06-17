<?php
require_once 'classes/Database.php';

try {
    $db = new Database();
    
    echo "<h2>Fixing sends table structure...</h2>";
    
    // Check if sends table exists
    $result = $db->query("SHOW TABLES LIKE 'sends'");
    if (!$result) {
        echo "<p>Creating sends table...</p>";
        $createTable = "
        CREATE TABLE sends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('text', 'file') NOT NULL DEFAULT 'text',
            name VARCHAR(255) NOT NULL,
            access_token VARCHAR(128) UNIQUE NOT NULL,
            content TEXT,
            file_path VARCHAR(500),
            file_name VARCHAR(255),
            file_size BIGINT,
            password_hash VARCHAR(255),
            expires_at DATETIME NOT NULL,
            max_views INT DEFAULT 10,
            view_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_accessed DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_access_token (access_token),
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at)
        )";
        $db->query($createTable);
        echo "<p style='color: green;'>✓ Table created successfully</p>";
    } else {
        echo "<p>Table exists, checking columns...</p>";
        
        // Check if type column exists
        $columns = $db->fetchAll("SHOW COLUMNS FROM sends LIKE 'type'");
        if (empty($columns)) {
            echo "<p>Adding type column...</p>";
            $db->query("ALTER TABLE sends ADD COLUMN type ENUM('text', 'file') NOT NULL DEFAULT 'text' AFTER user_id");
            echo "<p style='color: green;'>✓ Type column added</p>";
        }
        
        // Check other missing columns
        $requiredColumns = [
            'file_path' => "VARCHAR(500)",
            'file_name' => "VARCHAR(255)", 
            'file_size' => "BIGINT"
        ];
        
        foreach ($requiredColumns as $column => $type) {
            $columns = $db->fetchAll("SHOW COLUMNS FROM sends LIKE '$column'");
            if (empty($columns)) {
                echo "<p>Adding $column column...</p>";
                $db->query("ALTER TABLE sends ADD COLUMN $column $type");
                echo "<p style='color: green;'>✓ $column column added</p>";
            }
        }
    }
    
    echo "<h3 style='color: green;'>All fixes applied successfully!</h3>";
    echo "<p><a href='main_vault.php'>Go back to main vault</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
}
?>
