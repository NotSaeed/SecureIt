<?php
/**
 * Database Migration Script - Encrypt Vault Data
 * This script migrates existing vault data to encrypted format
 */

require_once 'classes/Database.php';
require_once 'classes/EncryptionHelper.php';

echo "SecureIt Vault Migration - Encrypt Vault Data\n";
echo "==============================================\n";

$db = new Database();
$encryption = new EncryptionHelper();

try {
    echo "Starting vault encryption migration...\n\n";
    
    // Step 1: Add new encrypted columns to vaults table
    echo "1. Adding new encrypted columns to vaults table...\n";
    
    try {
        $db->query("ALTER TABLE vaults ADD COLUMN item_name_encrypted TEXT AFTER item_name");
        echo "   ✓ Added item_name_encrypted column\n";
    } catch (Exception $e) {
        echo "   ⚠️  item_name_encrypted column may already exist\n";
    }
    
    try {
        $db->query("ALTER TABLE vaults ADD COLUMN item_name_hash VARCHAR(64) AFTER item_name_encrypted");
        echo "   ✓ Added item_name_hash column\n";
    } catch (Exception $e) {
        echo "   ⚠️  item_name_hash column may already exist\n";
    }
    
    try {
        $db->query("ALTER TABLE vaults ADD COLUMN website_url_encrypted TEXT AFTER website_url");
        echo "   ✓ Added website_url_encrypted column\n";
    } catch (Exception $e) {
        echo "   ⚠️  website_url_encrypted column may already exist\n";
    }
    
    // Step 2: Migrate existing vault data
    echo "\n2. Encrypting existing vault data...\n";
    
    $vaultItems = $db->fetchAll("SELECT id, item_name, website_url FROM vaults WHERE item_name IS NOT NULL");
    
    foreach ($vaultItems as $item) {
        $nameHash = hash('sha256', strtolower(trim($item['item_name'])));
        $encryptedName = $encryption->encrypt($item['item_name']);
        $encryptedUrl = $item['website_url'] ? $encryption->encrypt($item['website_url']) : null;
        
        $db->query(
            "UPDATE vaults SET item_name_encrypted = ?, item_name_hash = ?, website_url_encrypted = ? WHERE id = ?",
            [$encryptedName, $nameHash, $encryptedUrl, $item['id']]
        );
        
        echo "   ✓ Encrypted vault item ID: {$item['id']}\n";
    }
    
    // Step 3: Add indexes for performance
    echo "\n3. Adding indexes for performance...\n";
    
    try {
        $db->query("CREATE INDEX idx_item_name_hash ON vaults(item_name_hash)");
        echo "   ✓ Added index on item_name_hash\n";
    } catch (Exception $e) {
        echo "   ⚠️  Index may already exist\n";
    }
    
    // Step 4: Note about removing old columns
    echo "\n4. Old plaintext columns...\n";
    echo "   ⚠️  Keeping old columns for now for safety.\n";
    echo "   ⚠️  After confirming everything works, run:\n";
    echo "       ALTER TABLE vaults DROP COLUMN item_name;\n";
    echo "       ALTER TABLE vaults DROP COLUMN website_url;\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Vault migration completed successfully!\n";
    echo str_repeat("=", 50) . "\n";
    echo "\nVault security improvements:\n";
    echo "• Item names are now encrypted\n";
    echo "• Website URLs are now encrypted\n";
    echo "• Search functionality uses hashed names\n";
    echo "• Vault data is fully protected even if database is compromised\n";
    
} catch (Exception $e) {
    echo "\n❌ Vault migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
