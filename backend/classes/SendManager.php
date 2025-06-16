<?php
/**
 * SendManager Class
 * Manages secure sending of encrypted messages or files
 */

require_once 'Database.php';
require_once 'EncryptionHelper.php';

class SendManager {
    private $db;
    private $encryption;

    public function __construct() {
        $this->db = new Database();
        $this->encryption = new EncryptionHelper();
    }

    /**
     * Create a new send
     */
    public function createSend($user_id, $type, $name, $content, $options = []) {
        $options = array_merge([
            'deletion_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'password' => null,
            'max_views' => null,
            'hide_email' => false,
            'file_path' => null
        ], $options);

        // Validate send type
        if (!in_array($type, ['text', 'file'])) {
            throw new Exception("Invalid send type");
        }

        // Validate deletion date
        $deletionTime = strtotime($options['deletion_date']);
        if ($deletionTime === false || $deletionTime <= time()) {
            throw new Exception("Invalid deletion date");
        }

        // Generate unique access link
        $access_link = $this->generateAccessLink();
        
        // Encrypt content
        $encrypted_content = $this->encryption->encrypt($content);
        
        // Hash password if provided
        $password_hash = $options['password'] ? 
            password_hash($options['password'], PASSWORD_DEFAULT) : null;

        $sql = "INSERT INTO sends (user_id, send_type, name, content, file_path, access_link, 
                password_hash, deletion_date, max_views, hide_email) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, [
            $user_id, 
            $type, 
            $name, 
            $encrypted_content, 
            $options['file_path'],
            $access_link, 
            $password_hash, 
            $options['deletion_date'], 
            $options['max_views'], 
            $options['hide_email'] ? 1 : 0
        ]);

        return [
            'id' => $this->db->lastInsertId(),
            'access_link' => $access_link,
            'deletion_date' => $options['deletion_date']
        ];
    }

    /**
     * Retrieve a send by access link
     */
    public function getSend($access_link, $password = null) {
        $sql = "SELECT s.*, u.email as sender_email FROM sends s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.access_link = ? AND s.deletion_date > NOW()";
        
        $send = $this->db->fetchOne($sql, [$access_link]);

        if (!$send) {
            throw new Exception('Send not found or expired');
        }

        // Check password if required
        if ($send['password_hash'] && (!$password || !password_verify($password, $send['password_hash']))) {
            throw new Exception('Invalid password required to access this send');
        }

        // Check view limit
        if ($send['max_views'] && $send['current_views'] >= $send['max_views']) {
            throw new Exception('Send has reached its maximum view limit');
        }

        // Increment view count
        $this->incrementViewCount($send['id']);

        // Decrypt content
        try {
            $send['content'] = $this->encryption->decrypt($send['content']);
        } catch (Exception $e) {
            throw new Exception('Failed to decrypt send content');
        }

        // Hide email if requested
        if ($send['hide_email']) {
            $send['sender_email'] = null;
        }

        return $send;
    }

    /**
     * Get all sends for a user
     */
    public function getUserSends($user_id) {
        $sql = "SELECT id, send_type, name, access_link, deletion_date, max_views, 
                current_views, hide_email, created_at 
                FROM sends 
                WHERE user_id = ? AND deletion_date > NOW() 
                ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, [$user_id]);
    }

    /**
     * Get send statistics for a user
     */
    public function getSendStats($user_id) {
        $sql = "SELECT 
                    COUNT(*) as total_sends,
                    SUM(CASE WHEN send_type = 'text' THEN 1 ELSE 0 END) as text_sends,
                    SUM(CASE WHEN send_type = 'file' THEN 1 ELSE 0 END) as file_sends,
                    SUM(current_views) as total_views,
                    COUNT(CASE WHEN deletion_date <= NOW() THEN 1 END) as expired_sends
                FROM sends 
                WHERE user_id = ?";
        
        $stats = $this->db->fetchOne($sql, [$user_id]);
        
        return [
            'total_sends' => (int)$stats['total_sends'],
            'text_sends' => (int)$stats['text_sends'],
            'file_sends' => (int)$stats['file_sends'],
            'total_views' => (int)$stats['total_views'],
            'expired_sends' => (int)$stats['expired_sends'],
            'active_sends' => (int)$stats['total_sends'] - (int)$stats['expired_sends']
        ];
    }

    /**
     * Delete a send
     */
    public function deleteSend($id, $user_id) {
        // Get send info before deletion for cleanup
        $send = $this->db->fetchOne("SELECT file_path FROM sends WHERE id = ? AND user_id = ?", [$id, $user_id]);
        
        if (!$send) {
            throw new Exception("Send not found");
        }

        // Delete from database
        $sql = "DELETE FROM sends WHERE id = ? AND user_id = ?";
        $result = $this->db->query($sql, [$id, $user_id]);

        // Clean up file if it exists
        if ($send['file_path'] && file_exists($send['file_path'])) {
            unlink($send['file_path']);
        }

        return $result;
    }

    /**
     * Update send (limited fields)
     */
    public function updateSend($id, $user_id, $data) {
        $allowedFields = ['name', 'deletion_date', 'max_views'];
        $updates = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($updates)) {
            throw new Exception("No valid fields to update");
        }

        $params[] = $id;
        $params[] = $user_id;

        $sql = "UPDATE sends SET " . implode(', ', $updates) . " WHERE id = ? AND user_id = ?";
        return $this->db->query($sql, $params);
    }

    /**
     * Generate unique access link
     */
    private function generateAccessLink() {
        do {
            $link = bin2hex(random_bytes(16));
            $existing = $this->db->fetchOne("SELECT id FROM sends WHERE access_link = ?", [$link]);
        } while ($existing);

        return $link;
    }

    /**
     * Increment view count for a send
     */
    private function incrementViewCount($send_id) {
        $sql = "UPDATE sends SET current_views = current_views + 1 WHERE id = ?";
        $this->db->query($sql, [$send_id]);
    }

    /**
     * Clean up expired sends
     */
    public function cleanupExpiredSends() {
        // Get expired sends with file paths for cleanup
        $expiredSends = $this->db->fetchAll(
            "SELECT id, file_path FROM sends WHERE deletion_date <= NOW() AND file_path IS NOT NULL"
        );

        // Delete files
        foreach ($expiredSends as $send) {
            if (file_exists($send['file_path'])) {
                unlink($send['file_path']);
            }
        }

        // Delete expired sends from database
        $sql = "DELETE FROM sends WHERE deletion_date <= NOW()";
        $result = $this->db->query($sql);

        return [
            'deleted_sends' => $result->rowCount(),
            'deleted_files' => count($expiredSends)
        ];
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
}
?>
