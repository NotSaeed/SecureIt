<?php
// Setup sends table for secure send functionality
require_once 'classes/Database.php';

try {
    $db = new Database();
    
    echo "Setting up sends table...\n<br>";
    
    // Create sends table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS sends (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        send_type ENUM('text', 'file') NOT NULL DEFAULT 'text',
        name VARCHAR(255) NOT NULL,
        content TEXT,
        file_path VARCHAR(500),
        access_link VARCHAR(64) UNIQUE NOT NULL,
        password_hash VARCHAR(255),
        deletion_date DATETIME NOT NULL,
        max_views INT DEFAULT NULL,
        current_views INT DEFAULT 0,
        hide_email BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_access_link (access_link),
        INDEX idx_user_id (user_id),
        INDEX idx_deletion_date (deletion_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->query($createTableSQL);
    echo "✅ Sends table created successfully!\n<br>";
    
    // Verify table structure
    $columns = $db->fetchAll("SHOW COLUMNS FROM sends");
    echo "<h3>Sends Table Structure:</h3>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<br>🎉 Sends table setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up sends table: " . $e->getMessage() . "\n<br>";
}
?>
