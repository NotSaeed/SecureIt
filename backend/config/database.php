<?php
/**
 * Database Configuration for SecureIt
 */

class DatabaseConfig {
    // Database credentials
    const DB_HOST = 'localhost';
    const DB_NAME = 'secureit';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';
    
    // PDO connection instance
    private static $connection = null;
    
    /**
     * Get database connection
     */
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
                self::$connection = new PDO($dsn, self::DB_USER, self::DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$connection;
    }
    
    /**
     * Create database if it doesn't exist
     */
    public static function createDatabase() {
        try {
            $dsn = "mysql:host=" . self::DB_HOST . ";charset=" . self::DB_CHARSET;
            $pdo = new PDO($dsn, self::DB_USER, self::DB_PASS);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . self::DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "Database '" . self::DB_NAME . "' created successfully or already exists.\n";
        } catch (PDOException $e) {
            die("Database creation failed: " . $e->getMessage());
        }
    }
}
?>
