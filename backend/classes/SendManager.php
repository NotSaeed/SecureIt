<?php
/**
 * SendManager Class
 * Manages secure sending of encrypted messages or files
 */

require_once 'Database.php';
require_once 'EncryptionHelper.php';
require_once 'Vault.php';

class SendManager {
    private $db;
    private $encryptionHelper;
      public function __construct() {
        $this->db = new Database();
        $this->encryptionHelper = new EncryptionHelper();
    }

    /**
     * Generate a secure access token for sends
     */
    private function generateAccessToken() {
        return bin2hex(random_bytes(32)); // 64 character hex string
    }
    
    /**
     * Create a new send
     */
    public function createSend($userId, $type, $name, $content, $options = []) {
        try {
            // Generate unique access token
            $accessToken = $this->generateAccessToken();
            
            // Set default expiration (7 days from now)
            $expirationDate = $options['expiration_date'] ?? date('Y-m-d H:i:s', strtotime('+7 days'));
            
            // Validate expiration date format
            if (!strtotime($expirationDate)) {
                $expirationDate = date('Y-m-d H:i:s', strtotime('+7 days'));
            }            // Prepare data for database
            $sendData = [
                'user_id' => $userId,
                'type' => $type,
                'name' => $name,
                'access_token' => $accessToken,
                'expires_at' => $expirationDate,
                'max_views' => $options['max_views'] ?? 10,
                'password_hash' => null,                'content' => null,
                'file_path' => null,
                'file_name' => null,
                'file_size' => null,
                'file_data' => null,
                'storage_type' => null,
                'mime_type' => null,
                'anonymous' => $options['anonymous'] ?? false,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Handle password protection
            if (!empty($options['password'])) {
                $sendData['password_hash'] = password_hash($options['password'], PASSWORD_ARGON2ID);
            }            // Handle content based on type
            if ($type === 'file') {
                // File upload handling
                if (isset($options['file_path'])) {
                    if (file_exists($options['file_path'])) {
                        $sendData['file_name'] = $content; // Original filename
                        $sendData['file_size'] = filesize($options['file_path']);
                        $sendData['mime_type'] = mime_content_type($options['file_path']);
                        
                        // Determine if this is an image that should be stored as BLOB
                        $imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
                        $isImage = in_array($sendData['mime_type'], $imageTypes);
                        
                        if ($isImage) {
                            // Store image as BLOB for security
                            $sendData['file_data'] = file_get_contents($options['file_path']);
                            $sendData['storage_type'] = 'blob';
                            $sendData['file_path'] = null; // Don't store file path for images
                            
                            // Delete the uploaded file since we're storing it in database
                            unlink($options['file_path']);
                            
                            // Store metadata as content
                            $sendData['content'] = json_encode([
                                'original_name' => $content,
                                'file_size' => $sendData['file_size'],
                                'mime_type' => $sendData['mime_type'],
                                'storage_type' => 'blob'
                            ]);
                        } else {
                            // Store other files (PDFs, documents) as downloadable files
                            $sendData['file_path'] = $options['file_path'];
                            $sendData['storage_type'] = 'file';
                            $sendData['file_data'] = null;
                            
                            // Store metadata as content
                            $sendData['content'] = json_encode([
                                'original_name' => $content,
                                'file_size' => $sendData['file_size'],
                                'mime_type' => $sendData['mime_type'],
                                'storage_type' => 'file'
                            ]);
                        }
                    } else {
                        throw new Exception('File not found at path: ' . $options['file_path']);
                    }
                } else {
                    throw new Exception('File path not provided in options');
                }
            } else {
                // Text content
                $sendData['content'] = $this->encryptionHelper->encrypt($content);
                $sendData['storage_type'] = null;
            }            // Insert into database
            $sql = "INSERT INTO sends (user_id, type, name, access_token, content, file_path, file_name, file_size, file_data, storage_type, mime_type, password_hash, expires_at, max_views, view_count, anonymous, created_at) 
                    VALUES (:user_id, :type, :name, :access_token, :content, :file_path, :file_name, :file_size, :file_data, :storage_type, :mime_type, :password_hash, :expires_at, :max_views, 0, :anonymous, :created_at)";
            
            $result = $this->db->query($sql, $sendData);
            
            if ($result) {
                $sendId = $this->db->lastInsertId();
                
                return [
                    'id' => $sendId,
                    'access_link' => $accessToken,
                    'expires_at' => $expirationDate,
                    'type' => $type,
                    'name' => $name
                ];
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("SendManager::createSend error: " . $e->getMessage());
            throw $e;
        }
    }
      /**
     * Get a send by access token
     */    public function getSend($accessToken) {
        try {
            // Get send without time filter first to handle timezone issues
            $sql = "SELECT * FROM sends WHERE access_token = :access_token";
            $send = $this->db->fetchOne($sql, ['access_token' => $accessToken]);
            
            if (!$send) {
                return null;
            }
            
            // Check expiration using PHP time to avoid timezone issues
            $currentTime = new DateTime();
            $expirationTime = new DateTime($send['expires_at']);
            
            if ($expirationTime < $currentTime) {
                return null; // Send has expired
            }
            
            // Check if max views exceeded (only if max_views is set)
            if ($send['max_views'] !== null && $send['view_count'] >= $send['max_views']) {
                return null;
            }
            
            return $send;
        } catch (Exception $e) {
            error_log("SendManager::getSend error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Access a send (increment view count)
     */
    public function accessSend($accessToken, $password = null) {
        try {
            $send = $this->getSend($accessToken);
            
            if (!$send) {
                return ['success' => false, 'message' => 'Send not found or expired'];
            }
            
            // Check password if required
            if ($send['password_hash'] && !password_verify($password, $send['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid password'];
            }
              // Increment view count
            $sql = "UPDATE sends SET view_count = view_count + 1, last_accessed = NOW() WHERE id = :id";
            $this->db->query($sql, ['id' => $send['id']]);              // Decrypt content if text or credential
            if ($send['type'] === 'text' || $send['type'] === 'credential') {
                $send['content'] = $this->encryptionHelper->decrypt($send['content']);
            }
            
            return ['success' => true, 'send' => $send];
            
        } catch (Exception $e) {
            error_log("SendManager::accessSend error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error accessing send'];
        }
    }
      /**
     * Get all sends for a user
     */
    public function getUserSends($userId) {
        try {
            $sql = "SELECT id, name, type, access_token, created_at, expires_at, max_views, view_count, 
                           file_name, file_size, storage_type, mime_type, password_hash
                    FROM sends 
                    WHERE user_id = :user_id 
                    ORDER BY created_at DESC";
            
            $sends = $this->db->fetchAll($sql, ['user_id' => $userId]);
            
            // Add status information
            foreach ($sends as &$send) {
                $send['is_expired'] = strtotime($send['expires_at']) < time();
                $send['is_exhausted'] = $send['view_count'] >= $send['max_views'];
                $send['has_password'] = !empty($send['password_hash']);
                $send['is_image'] = $send['storage_type'] === 'blob';
                
                // Remove sensitive data
                unset($send['password_hash']);
            }
            
            return $sends;
            
        } catch (Exception $e) {
            error_log("SendManager::getUserSends error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete a send
     */
    public function deleteSend($sendId, $userId) {
        try {
            // Get send info first to delete file if needed
            $sql = "SELECT file_path FROM sends WHERE id = :id AND user_id = :user_id";
            $send = $this->db->fetchOne($sql, ['id' => $sendId, 'user_id' => $userId]);
            
            if (!$send) {
                return false;
            }
            
            // Delete file if exists
            if ($send['file_path'] && file_exists($send['file_path'])) {
                unlink($send['file_path']);
            }
            
            // Delete from database
            $sql = "DELETE FROM sends WHERE id = :id AND user_id = :user_id";
            return $this->db->query($sql, ['id' => $sendId, 'user_id' => $userId]);
            
        } catch (Exception $e) {
            error_log("SendManager::deleteSend error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up expired sends
     */
    public function cleanupExpiredSends() {
        try {
            // Get expired sends with files
            $sql = "SELECT id, file_path FROM sends WHERE expires_at < NOW() AND file_path IS NOT NULL";
            $expiredSends = $this->db->fetchAll($sql);
            
            // Delete files
            foreach ($expiredSends as $send) {
                if (file_exists($send['file_path'])) {
                    unlink($send['file_path']);
                }
            }
            
            // Delete from database - use expires_at not deletion_date
            $sql = "DELETE FROM sends WHERE expires_at < NOW()";
            return $this->db->query($sql);
            
        } catch (Exception $e) {
            error_log("SendManager::cleanupExpiredSends error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get send access log (if implemented)
     */
    public function getSendAccessLog($send_id, $user_id) {
        // First verify ownership
        $send = $this->db->fetchOne("SELECT id FROM sends WHERE id = ? AND user_id = ?", [$send_id, $user_id]);
        if (!$send) {
            throw new Exception("Send not found");
        }

        // For now, return basic info - could be extended with access logging table
        $sql = "SELECT current_views, created_at FROM sends WHERE id = ?";
        return $this->db->fetchOne($sql, [$send_id]);
    }

    /**
     * Validate send options
     */
    private function validateSendOptions($options) {
        // Max views validation
        if (isset($options['max_views']) && $options['max_views'] !== null) {
            if (!is_int($options['max_views']) || $options['max_views'] < 1 || $options['max_views'] > 1000) {
                throw new Exception("Max views must be between 1 and 1000");
            }
        }

        // Deletion date validation
        if (isset($options['deletion_date'])) {
            $deletionTime = strtotime($options['deletion_date']);
            $maxTime = strtotime('+30 days');
            
            if ($deletionTime === false || $deletionTime <= time() || $deletionTime > $maxTime) {
                throw new Exception("Deletion date must be between now and 30 days from now");
            }
        }

        return true;
    }

    /**
     * Get send statistics for a user
     */    public function getSendStats($userId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_sends,
                        SUM(CASE WHEN expires_at > NOW() AND (max_views IS NULL OR view_count < max_views) THEN 1 ELSE 0 END) as active_sends,
                        SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as expired_sends,
                        SUM(CASE WHEN max_views IS NOT NULL AND view_count >= max_views THEN 1 ELSE 0 END) as exhausted_sends,
                        COALESCE(SUM(view_count), 0) as total_views
                    FROM sends 
                    WHERE user_id = :user_id";
            
            $stats = $this->db->fetchOne($sql, ['user_id' => $userId]);
            
            return [
                'total_sends' => (int)($stats['total_sends'] ?? 0),
                'active_sends' => (int)($stats['active_sends'] ?? 0),
                'expired_sends' => (int)($stats['expired_sends'] ?? 0),
                'exhausted_sends' => (int)($stats['exhausted_sends'] ?? 0),
                'total_views' => (int)($stats['total_views'] ?? 0)
            ];
            
        } catch (Exception $e) {
            error_log("SendManager::getSendStats error: " . $e->getMessage());
            return [
                'total_sends' => 0,
                'active_sends' => 0,
                'expired_sends' => 0,
                'exhausted_sends' => 0,
                'total_views' => 0
            ];
        }
    }
    
    /**
     * Create a credential delivery send for sharing vault items
     */
    public function createCredentialDelivery($userId, $vaultItemId, $recipientEmail, $options = []) {
        try {
            // Generate unique access token
            $accessToken = $this->generateAccessToken();
            
            // Calculate expiration based on hours
            $expiryHours = $options['expiry_hours'] ?? 24;
            $expirationDate = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));
            
            // Get vault item data
            $vault = new Vault();
            $vaultItem = $vault->getItem($vaultItemId, $userId);
            
            if (!$vaultItem) {
                throw new Exception('Vault item not found or access denied');
            }
              // Prepare credential content
            $credentialContent = json_encode([
                'item_name' => $vaultItem['item_name'],
                'item_type' => $vaultItem['item_type'],
                'data' => $vaultItem['encrypted_data'], // Use encrypted_data field
                'website_url' => $vaultItem['website_url'] ?? null,
                'recipient_email' => $recipientEmail,
                'message' => $options['message'] ?? null
            ]);
            
            // Prepare data for database
            $sendData = [
                'user_id' => $userId,
                'type' => 'credential',
                'name' => 'Credential: ' . $vaultItem['item_name'],
                'access_token' => $accessToken,                'expires_at' => $expirationDate,
                'max_views' => $options['max_views'] ?? null,
                'password_hash' => null,
                'metadata' => json_encode([
                    'recipient_email' => $recipientEmail,
                    'vault_item_id' => $vaultItemId,
                    'original_item_name' => $vaultItem['item_name']
                ])
            ];
            
            // Hash password if provided
            if (!empty($options['access_password'])) {
                $sendData['password_hash'] = password_hash($options['access_password'], PASSWORD_DEFAULT);
            }            // Encrypt the credential content
            $encryptedContent = $this->encryptionHelper->encrypt($credentialContent);
            $sendData['content'] = $encryptedContent;
            
            // Insert into database
            $sql = "INSERT INTO sends (user_id, type, name, access_token, expires_at, max_views, password_hash, metadata, content, view_count) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
            
            $this->db->query($sql, [
                $sendData['user_id'],
                $sendData['type'],
                $sendData['name'],
                $sendData['access_token'],
                $sendData['expires_at'],
                $sendData['max_views'],
                $sendData['password_hash'],
                
                $sendData['metadata'],
                $sendData['content']
            ]);
            
            $sendId = $this->db->lastInsertId();
            
            if (!$sendId) {
                throw new Exception('Failed to create credential delivery');
            }
            
            return [
                'id' => $sendId,
                'access_link' => $accessToken,
                'expires_at' => $expirationDate,
                'recipient_email' => $recipientEmail
            ];
            
        } catch (Exception $e) {
            error_log("SendManager::createCredentialDelivery - " . $e->getMessage());
            throw $e;
        }
    }}
?>
