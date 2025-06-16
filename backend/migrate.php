<?php
/**
 * Migration Runner for SecureIt Database
 * 
 * Usage:
 * php migrate.php up    - Run all pending migrations
 * php migrate.php down  - Rollback last migration
 * php migrate.php fresh - Drop all tables and re-run migrations
 * php migrate.php status - Show migration status
 */

require_once __DIR__ . '/migrations/Migration.php';
require_once __DIR__ . '/config/database.php';

class MigrationRunner {
    private $pdo;
    private $migrationsPath;
    private $migrations = [];
    
    public function __construct() {
        // First, create the database if it doesn't exist
        DatabaseConfig::createDatabase();
        
        $this->pdo = DatabaseConfig::getConnection();
        $this->migrationsPath = __DIR__ . '/migrations/';
        $this->loadMigrations();
    }
    
    /**
     * Load all migration files
     */
    private function loadMigrations() {
        $files = glob($this->migrationsPath . '*.php');
        sort($files);
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            if ($filename !== 'Migration') {
                $this->migrations[] = $filename;
            }
        }
    }
    
    /**
     * Run all pending migrations
     */
    public function up() {
        echo "🚀 Starting database migration...\n\n";
        
        // Create migrations table if it doesn't exist
        $this->createMigrationsTable();
        
        $executed = $this->getExecutedMigrations();
        $pending = array_diff($this->migrations, $executed);
        
        if (empty($pending)) {
            echo "✅ No pending migrations found.\n";
            return;
        }
        
        echo "📋 Found " . count($pending) . " pending migration(s):\n";
        foreach ($pending as $migration) {
            echo "   • $migration\n";
        }
        echo "\n";
        
        foreach ($pending as $migration) {
            echo "⏳ Running migration: $migration\n";
            
            if ($this->runMigration($migration, 'up')) {
                $this->markAsExecuted($migration);
                echo "✅ Migration completed: $migration\n\n";
            } else {
                echo "❌ Migration failed: $migration\n";
                break;
            }
        }
        
        echo "🎉 Database migration completed successfully!\n";
    }
    
    /**
     * Rollback the last migration
     */
    public function down() {
        echo "⏪ Rolling back last migration...\n\n";
        
        $executed = $this->getExecutedMigrations();
        if (empty($executed)) {
            echo "❌ No migrations to rollback.\n";
            return;
        }
        
        $lastMigration = end($executed);
        echo "⏳ Rolling back: $lastMigration\n";
        
        if ($this->runMigration($lastMigration, 'down')) {
            $this->removeFromExecuted($lastMigration);
            echo "✅ Rollback completed: $lastMigration\n";
        } else {
            echo "❌ Rollback failed: $lastMigration\n";
        }
    }
    
    /**
     * Drop all tables and re-run migrations
     */
    public function fresh() {
        echo "🔄 Running fresh migration (dropping all tables)...\n\n";
        
        // Drop all tables in reverse order
        $this->dropAllTables();
        
        // Clear migrations table
        $this->pdo->exec("DROP TABLE IF EXISTS migrations");
        
        // Re-run all migrations
        $this->up();
    }
    
    /**
     * Show migration status
     */
    public function status() {
        echo "📊 Migration Status\n";
        echo "==================\n\n";
        
        $this->createMigrationsTable();
        $executed = $this->getExecutedMigrations();
        
        foreach ($this->migrations as $migration) {
            $status = in_array($migration, $executed) ? '✅ Executed' : '⏳ Pending';
            echo sprintf("%-50s %s\n", $migration, $status);
        }
        
        echo "\n";
        echo "Total migrations: " . count($this->migrations) . "\n";
        echo "Executed: " . count($executed) . "\n";
        echo "Pending: " . (count($this->migrations) - count($executed)) . "\n";
    }
    
    /**
     * Create migrations tracking table
     */
    private function createMigrationsTable() {
        require_once $this->migrationsPath . '000_create_migrations_table.php';
        $migration = new CreateMigrationsTable();
        $migration->up();
    }
    
    /**
     * Get list of executed migrations
     */
    private function getExecutedMigrations() {
        try {
            $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY id");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Run a specific migration
     */
    private function runMigration($migration, $direction) {
        $file = $this->migrationsPath . $migration . '.php';
        
        if (!file_exists($file)) {
            echo "❌ Migration file not found: $file\n";
            return false;
        }
        
        require_once $file;
        
        // Extract class name from filename
        $className = $this->getClassNameFromFile($migration);
        
        if (!class_exists($className)) {
            echo "❌ Migration class not found: $className\n";
            return false;
        }
        
        try {
            $migrationInstance = new $className();
            
            if ($direction === 'up') {
                $migrationInstance->up();
            } else {
                $migrationInstance->down();
            }
            
            return true;
        } catch (Exception $e) {
            echo "❌ Error running migration: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Extract class name from migration filename
     */
    private function getClassNameFromFile($filename) {
        // Remove number prefix and convert to PascalCase
        $parts = explode('_', $filename);
        array_shift($parts); // Remove number
        
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        
        return $className;
    }
    
    /**
     * Mark migration as executed
     */
    private function markAsExecuted($migration) {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migration]);
    }
    
    /**
     * Remove migration from executed list
     */
    private function removeFromExecuted($migration) {
        $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
    }
    
    /**
     * Drop all tables
     */
    private function dropAllTables() {
        $tables = [
            'security_reports',
            'generator_history', 
            'sends',
            'vaults',
            'folders',
            'users',
            'migrations'
        ];
        
        // Disable foreign key checks
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        foreach ($tables as $table) {
            echo "🗑️ Dropping table: $table\n";
            $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
        
        // Re-enable foreign key checks
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        echo "\n";
    }
}

// Command line interface
if ($argc < 2) {
    echo "Usage: php migrate.php [command]\n";
    echo "Commands:\n";
    echo "  up     - Run all pending migrations\n";
    echo "  down   - Rollback last migration\n";
    echo "  fresh  - Drop all tables and re-run migrations\n";
    echo "  status - Show migration status\n";
    exit(1);
}

$command = $argv[1];
$runner = new MigrationRunner();

switch ($command) {
    case 'up':
        $runner->up();
        break;
    case 'down':
        $runner->down();
        break;
    case 'fresh':
        $runner->fresh();
        break;
    case 'status':
        $runner->status();
        break;
    default:
        echo "❌ Unknown command: $command\n";
        echo "Available commands: up, down, fresh, status\n";
        exit(1);
}
?>
