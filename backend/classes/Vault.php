<?php
/**
 * Vault Class
 * Manages storage and retrieval of password entries and sensitive data
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/EncryptionHelper.php';

class Vault {
    private $db;
    private $encryption;
    
    public function __construct() {
        $this->db = new Database();
        $this->encryption = new EncryptionHelper();
    }    /**
     * Add new item to vault
     */
    public function addItem($user_id, $item_name, $item_type, $data, $website_url = null, $folder_id = null) {
        // Validate item type
        $validTypes = ['login', 'card', 'identity', 'note', 'ssh_key'];
        if (!in_array($item_type, $validTypes)) {
            throw new Exception("Invalid item type");
        }

        // Validate required data based on type
        $this->validateItemData($item_type, $data);        // Encrypt sensitive fields
        $encrypted_data = $this->encryption->encrypt(json_encode($data));
        $encrypted_name = $this->encryption->encrypt($item_name);
        $encrypted_url = $website_url ? $this->encryption->encrypt($website_url) : null;
        
        // Create search hash for item name (for search functionality)
        $name_hash = hash('sha256', strtolower(trim($item_name)));
        
        $sql = "INSERT INTO vaults (user_id, item_name, item_name_hash, item_type, encrypted_data, website_url, folder_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [$user_id, $encrypted_name, $name_hash, $item_type, $encrypted_data, $encrypted_url, $folder_id]);
        
        return $this->db->lastInsertId();
    }    /**
     * Get all items for a user
     */
    public function getUserItems($user_id) {
        $sql = "SELECT v.*, f.name as folder_name FROM vaults v 
                LEFT JOIN folders f ON v.folder_id = f.id 
                WHERE v.user_id = ? ORDER BY v.updated_at DESC";
        
        $items = $this->db->fetchAll($sql, [$user_id]);
          // Decrypt data for each item
        foreach ($items as &$item) {
            try {
                $item['decrypted_data'] = json_decode($this->encryption->decrypt($item['encrypted_data']), true);
                
                // Decrypt item name and URL
                $item['item_name'] = $item['item_name'] ? $this->encryption->decrypt($item['item_name']) : null;
                $item['website_url'] = $item['website_url'] ? $this->encryption->decrypt($item['website_url']) : null;
                
            } catch (Exception $e) {
                // Skip items that can't be decrypted
                $item['decrypted_data'] = null;
                $item['decrypt_error'] = true;
                $item['item_name'] = 'Encrypted Item';
                $item['website_url'] = null;
            }
        }
        
        return $items;
    }    /**
     * Get specific item by ID
     */
    public function getItem($id, $user_id) {
        $sql = "SELECT v.*, f.name as folder_name FROM vaults v 
                LEFT JOIN folders f ON v.folder_id = f.id 
                WHERE v.id = ? AND v.user_id = ?";
        
        $item = $this->db->fetchOne($sql, [$id, $user_id]);
        
        if ($item) {            try {
                $item['decrypted_data'] = json_decode($this->encryption->decrypt($item['encrypted_data']), true);
                
                // Decrypt item name and URL
                $item['item_name'] = $item['item_name'] ? $this->encryption->decrypt($item['item_name']) : null;
                $item['website_url'] = $item['website_url'] ? $this->encryption->decrypt($item['website_url']) : null;
                
            } catch (Exception $e) {
                throw new Exception("Unable to decrypt item data");
            }
        }
        
        return $item;
    }    /**
     * Update existing item
     */
    public function updateItem($id, $user_id, $item_name, $data, $website_url = null) {
        // Check if item exists and belongs to user
        $existing = $this->db->fetchOne("SELECT item_type FROM vaults WHERE id = ? AND user_id = ?", [$id, $user_id]);
        if (!$existing) {
            throw new Exception("Item not found");
        }

        // Validate data based on item type
        $this->validateItemData($existing['item_type'], $data);        // Encrypt sensitive fields
        $encrypted_data = $this->encryption->encrypt(json_encode($data));
        $encrypted_name = $this->encryption->encrypt($item_name);
        $encrypted_url = $website_url ? $this->encryption->encrypt($website_url) : null;
        
        // Create search hash for item name
        $name_hash = hash('sha256', strtolower(trim($item_name)));
        
        $sql = "UPDATE vaults SET item_name = ?, item_name_hash = ?, encrypted_data = ?, website_url = ?, updated_at = NOW() 
                WHERE id = ? AND user_id = ?";
        
        return $this->db->query($sql, [$encrypted_name, $name_hash, $encrypted_data, $encrypted_url, $id, $user_id]);
    }

    /**
     * Delete item from vault
     */
    public function deleteItem($id, $user_id) {
        $sql = "DELETE FROM vaults WHERE id = ? AND user_id = ?";
        return $this->db->query($sql, [$id, $user_id]);
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite($id, $user_id) {
        $sql = "UPDATE vaults SET is_favorite = NOT is_favorite WHERE id = ? AND user_id = ?";
        return $this->db->query($sql, [$id, $user_id]);
    }

    /**
     * Search items by query
     */
    public function searchItems($user_id, $query) {
        $sql = "SELECT v.*, f.name as folder_name FROM vaults v 
                LEFT JOIN folders f ON v.folder_id = f.id 
                WHERE v.user_id = ? AND (v.item_name LIKE ? OR v.website_url LIKE ?) 
                ORDER BY v.updated_at DESC";
        
        $searchTerm = "%{$query}%";
        $items = $this->db->fetchAll($sql, [$user_id, $searchTerm, $searchTerm]);
        
        foreach ($items as &$item) {
            try {
                $item['decrypted_data'] = json_decode($this->encryption->decrypt($item['encrypted_data']), true);
            } catch (Exception $e) {
                $item['decrypted_data'] = null;
                $item['decrypt_error'] = true;
            }
        }
        
        return $items;
    }

    /**
     * Get items by type
     */
    public function getItemsByType($user_id, $item_type) {
        $sql = "SELECT v.*, f.name as folder_name FROM vaults v 
                LEFT JOIN folders f ON v.folder_id = f.id 
                WHERE v.user_id = ? AND v.item_type = ? 
                ORDER BY v.updated_at DESC";
        
        $items = $this->db->fetchAll($sql, [$user_id, $item_type]);
        
        foreach ($items as &$item) {
            try {
                $item['decrypted_data'] = json_decode($this->encryption->decrypt($item['encrypted_data']), true);
            } catch (Exception $e) {
                $item['decrypted_data'] = null;
                $item['decrypt_error'] = true;
            }
        }
        
        return $items;
    }

    /**
     * Get favorite items
     */
    public function getFavoriteItems($user_id) {
        $sql = "SELECT v.*, f.name as folder_name FROM vaults v 
                LEFT JOIN folders f ON v.folder_id = f.id 
                WHERE v.user_id = ? AND v.is_favorite = 1 
                ORDER BY v.updated_at DESC";
        
        $items = $this->db->fetchAll($sql, [$user_id]);
        
        foreach ($items as &$item) {
            try {
                $item['decrypted_data'] = json_decode($this->encryption->decrypt($item['encrypted_data']), true);
            } catch (Exception $e) {
                $item['decrypted_data'] = null;
                $item['decrypt_error'] = true;
            }
        }
        
        return $items;
    }

    /**
     * Validate item data based on type
     */
    private function validateItemData($item_type, $data) {
        switch ($item_type) {
            case 'login':
                if (!isset($data['username']) && !isset($data['email'])) {
                    throw new Exception("Login items must have either username or email");
                }
                break;
            
            case 'card':
                if (!isset($data['card_number']) || !isset($data['cardholder_name'])) {
                    throw new Exception("Card items must have card number and cardholder name");
                }
                break;
            
            case 'identity':
                if (!isset($data['full_name'])) {
                    throw new Exception("Identity items must have full name");
                }
                break;
            
            case 'ssh_key':
                if (!isset($data['private_key'])) {
                    throw new Exception("SSH key items must have private key");
                }
                break;
        }
    }

    /**
     * Get vault statistics for user
     */
    public function getVaultStats($user_id) {
        $sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN item_type = 'login' THEN 1 ELSE 0 END) as login_items,
                    SUM(CASE WHEN item_type = 'card' THEN 1 ELSE 0 END) as card_items,
                    SUM(CASE WHEN item_type = 'identity' THEN 1 ELSE 0 END) as identity_items,
                    SUM(CASE WHEN item_type = 'note' THEN 1 ELSE 0 END) as note_items,
                    SUM(CASE WHEN item_type = 'ssh_key' THEN 1 ELSE 0 END) as ssh_key_items,
                    SUM(CASE WHEN is_favorite = 1 THEN 1 ELSE 0 END) as favorite_items
                FROM vaults WHERE user_id = ?";

        return $this->db->fetchOne($sql, [$user_id]);
    }
}
?>