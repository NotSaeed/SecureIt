<?php
/**
 * Database Migration Base Class
 */

require_once __DIR__ . '/../config/database.php';

abstract class Migration {
    protected $pdo;
    
    public function __construct() {
        $this->pdo = DatabaseConfig::getConnection();
    }
    
    /**
     * Run the migration
     */
    abstract public function up();
    
    /**
     * Reverse the migration
     */
    abstract public function down();
    
    /**
     * Execute SQL query
     */
    protected function execute($sql) {
        try {
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            echo "Error executing query: " . $e->getMessage() . "\n";
            echo "SQL: " . $sql . "\n";
            return false;
        }
    }
    
    /**
     * Check if table exists
     */
    protected function tableExists($tableName) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
        $stmt->execute([DatabaseConfig::DB_NAME, $tableName]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Drop table if exists
     */
    protected function dropTable($tableName) {
        $this->execute("DROP TABLE IF EXISTS `$tableName`");
    }
}
?>
