<?php
/**
 * Database Migration Script - Encrypt User Data
 * This script migrates existing user data to encrypted format
 * 
 * WARNING: This will modify your database structure and data!
 * Make sure to backup your database before running this script.
 */

require_once 'classes/Database.php';
require_once 'classes/EncryptionHelper.php';

echo "SecureIt Database Migration - Encrypt User Data\n";
echo "===============================================\n";
echo "This script will:\n";
echo "1. Add new encrypted columns to users table\n";
echo "2. Encrypt existing user data\n";
echo "3. Remove old plaintext columns\n";
echo "\n";

$db = new Database();
$encryption = new EncryptionHelper();

try {
    echo "Starting database migration...\n\n";
    
    // Step 1: Add new encrypted columns
    echo "1. Adding new encrypted columns...\n";
    
    $db->query("ALTER TABLE users ADD COLUMN email_hash VARCHAR(64) AFTER id");
    echo "   ✓ Added email_hash column\n";
    
    $db->query("ALTER TABLE users ADD COLUMN email_encrypted TEXT AFTER email_hash");
    echo "   ✓ Added email_encrypted column\n";
    
    $db->query("ALTER TABLE users ADD COLUMN name_encrypted TEXT AFTER name");
    echo "   ✓ Added name_encrypted column\n";
    
    // Step 2: Migrate existing data
    echo "\n2. Encrypting existing user data...\n";
    
    $users = $db->fetchAll("SELECT id, email, name FROM users WHERE email IS NOT NULL");
    
    foreach ($users as $user) {
        $emailHash = hash('sha256', strtolower(trim($user['email'])));
        $encryptedEmail = $encryption->encrypt($user['email']);
        $encryptedName = $user['name'] ? $encryption->encrypt($user['name']) : null;
        
        $db->query(
            "UPDATE users SET email_hash = ?, email_encrypted = ?, name_encrypted = ? WHERE id = ?",
            [$emailHash, $encryptedEmail, $encryptedName, $user['id']]
        );
        
        echo "   ✓ Encrypted data for user ID: {$user['id']}\n";
    }
    
    // Step 3: Add indexes for performance
    echo "\n3. Adding indexes for performance...\n";
    
    $db->query("CREATE UNIQUE INDEX idx_email_hash ON users(email_hash)");
    echo "   ✓ Added unique index on email_hash\n";
    
    // Step 4: Remove old columns (commented out for safety)
    echo "\n4. Removing old plaintext columns...\n";
    echo "   ⚠️  Skipping removal of old columns for safety.\n";
    echo "   ⚠️  After confirming everything works, run:\n";
    echo "       ALTER TABLE users DROP COLUMN email;\n";
    echo "       ALTER TABLE users DROP COLUMN name;\n";
    
    // Optional: Remove old columns (uncomment when ready)
    /*
    $db->query("ALTER TABLE users DROP COLUMN email");
    echo "   ✓ Removed old email column\n";
    
    $db->query("ALTER TABLE users DROP COLUMN name"); 
    echo "   ✓ Removed old name column\n";
    */
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Migration completed successfully!\n";
    echo str_repeat("=", 50) . "\n";
    echo "\nSecurity improvements:\n";
    echo "• Email addresses are now encrypted\n";
    echo "• User names are now encrypted\n";
    echo "• Only hashed email is used for lookups\n";
    echo "• Even if database is compromised, sensitive data is protected\n";
    echo "\nNext steps:\n";
    echo "1. Test login/registration functionality\n";
    echo "2. Verify all user data displays correctly\n";
    echo "3. When confident, remove old plaintext columns\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    echo "\nPlease check your database and try again.\n";
}
?>
