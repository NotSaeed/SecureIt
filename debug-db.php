<?php
/**
 * Direct database check for vault items
 */
header('Content-Type: text/html');

require_once 'backend/classes/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Database Connection Test</h2>";
    
    if (!$db) {
        echo "<p style='color: red;'>Failed to connect to database</p>";
        exit;
    }
    
    echo "<p style='color: green;'>Database connected successfully!</p>";
    
    // Check if users table exists and has data
    echo "<h3>Users Table</h3>";
    $query = "SELECT id, email, created_at FROM users LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($users);
    echo "</pre>";
      // Check what tables exist
    echo "<h3>Available Tables</h3>";
    $query = "SHOW TABLES";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<pre>";
    print_r($tables);
    echo "</pre>";
    
    // Check if vaults table exists and has data
    echo "<h3>Vaults Table</h3>";
    try {
        $query = "SELECT id, user_id, item_name, item_type, created_at FROM vaults LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<pre>";
        print_r($items);
        echo "</pre>";
        
        // Check vaults table structure
        echo "<h3>Vaults Table Structure</h3>";
        $query = "DESCRIBE vaults";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<pre>";
        print_r($structure);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Vaults table error: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
