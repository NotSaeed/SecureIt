<?php
require_once 'classes/Database.php';

echo "<!DOCTYPE html><html><head><title>Database Fix</title></head><body>";
echo "<h1>Fixing Database Structure</h1>";

try {
    $db = new Database();
    
    // First, let's see what columns exist
    echo "<h2>Current sends table structure:</h2>";
    $columns = $db->fetchAll("DESCRIBE sends");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    
    // Add missing columns one by one
    $alterQueries = [
        "ALTER TABLE sends ADD COLUMN type ENUM('text', 'file') NOT NULL DEFAULT 'text' AFTER user_id",
        "ALTER TABLE sends ADD COLUMN file_path VARCHAR(500) NULL",
        "ALTER TABLE sends ADD COLUMN file_name VARCHAR(255) NULL", 
        "ALTER TABLE sends ADD COLUMN file_size BIGINT NULL"
    ];
    
    echo "<h2>Adding missing columns:</h2>";
    
    foreach ($alterQueries as $query) {
        try {
            $db->query($query);
            echo "<p style='color: green;'>✓ " . htmlspecialchars($query) . "</p>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<p style='color: orange;'>⚠ Column already exists: " . htmlspecialchars($query) . "</p>";
            } else {
                echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    // Show updated structure
    echo "<h2>Updated sends table structure:</h2>";
    $columns = $db->fetchAll("DESCRIBE sends");
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h3 style='color: green;'>Database update complete!</h3>";
    echo "<p><a href='main_vault.php?section=send'>Test file upload now</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    
    // If sends table doesn't exist, create it
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        echo "<p>Creating sends table from scratch...</p>";
        try {
            $createSQL = "
            CREATE TABLE sends (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                access_token VARCHAR(128) UNIQUE NOT NULL,
                content TEXT,
                password_hash VARCHAR(255),
                expires_at DATETIME NOT NULL,
                max_views INT DEFAULT 10,
                view_count INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_accessed DATETIME,
                type ENUM('text', 'file') NOT NULL DEFAULT 'text',
                file_path VARCHAR(500) NULL,
                file_name VARCHAR(255) NULL,
                file_size BIGINT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            
            $db->query($createSQL);
            echo "<p style='color: green;'>✓ Sends table created successfully!</p>";
            echo "<p><a href='main_vault.php?section=send'>Test file upload now</a></p>";
        } catch (Exception $createError) {
            echo "<p style='color: red;'>Failed to create table: " . htmlspecialchars($createError->getMessage()) . "</p>";
        }
    }
}

echo "</body></html>";
?>
