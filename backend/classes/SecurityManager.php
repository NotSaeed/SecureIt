<?php
/**
 * SecurityManager Class
 * Handles real-time password security checks and integration with APIs
 */

require_once 'Database.php';

class SecurityManager {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Check if password has been breached using Have I Been Pwned API
     */
    public function checkPasswordBreach($password) {
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);
        
        try {
            $url = "https://api.pwnedpasswords.com/range/{$prefix}";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'SecureIt Password Manager'
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                return ['breached' => false, 'count' => 0, 'error' => 'API unavailable'];
            }
            
            $lines = explode("\n", $response);
            foreach ($lines as $line) {
                $parts = explode(':', trim($line));
                if (count($parts) === 2 && $parts[0] === $suffix) {
                    return ['breached' => true, 'count' => (int)$parts[1]];
                }
            }
            
            return ['breached' => false, 'count' => 0];
            
        } catch (Exception $e) {
            return ['breached' => false, 'count' => 0, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Assess overall security score for user's vault
     */
    public function assessSecurityScore($user_id) {
        $sql = "SELECT encrypted_data FROM vaults WHERE user_id = ? AND item_type = 'login'";
        $items = $this->db->fetchAll($sql, [$user_id]);
        
        if (empty($items)) {
            return 100; // No items to assess
        }
        
        $totalScore = 0;
        $itemCount = count($items);
        $encryption = new EncryptionHelper();
        
        foreach ($items as $item) {
            try {
                $data = json_decode($encryption->decrypt($item['encrypted_data']), true);
                $password = $data['password'] ?? '';
                
                $score = $this->calculatePasswordScore($password);
                $totalScore += $score;
                
            } catch (Exception $e) {
                // Skip items that can't be decrypted
                $itemCount--;
            }
        }
        
        return $itemCount > 0 ? round($totalScore / $itemCount) : 100;
    }
    
    /**
     * Calculate individual password strength score
     */
    public function calculatePasswordScore($password) {
        if (empty($password)) {
            return 0;
        }
        
        $score = 0;
        $length = strlen($password);
        
        // Length scoring
        if ($length >= 8) $score += 25;
        if ($length >= 12) $score += 15;
        if ($length >= 16) $score += 10;
        
        // Character variety scoring
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 20;
        
        // Penalty for common patterns
        if (preg_match('/123|abc|password|qwerty/i', $password)) $score -= 20;
        if (preg_match('/(.)\1{2,}/', $password)) $score -= 10; // Repeated characters
        
        return max(0, min(100, $score));
    }
    
    /**
     * Find duplicate passwords in vault
     */
    public function findDuplicatePasswords($user_id) {
        $sql = "SELECT id, item_name, encrypted_data FROM vaults WHERE user_id = ? AND item_type = 'login'";
        $items = $this->db->fetchAll($sql, [$user_id]);
        
        $passwords = [];
        $duplicates = [];
        $encryption = new EncryptionHelper();
        
        foreach ($items as $item) {
            try {
                $data = json_decode($encryption->decrypt($item['encrypted_data']), true);
                $password = $data['password'] ?? '';
                
                if (!empty($password)) {
                    $hash = hash('sha256', $password);
                    
                    if (isset($passwords[$hash])) {
                        $duplicates[] = [
                            'password_hash' => $hash,
                            'items' => array_merge($passwords[$hash], [$item])
                        ];
                    } else {
                        $passwords[$hash] = [$item];
                    }
                }
                
            } catch (Exception $e) {
                // Skip items that can't be decrypted
            }
        }
        
        return $duplicates;
    }
    
    /**
     * Find weak passwords in vault
     */
    public function findWeakPasswords($user_id, $threshold = 50) {
        $sql = "SELECT id, item_name, encrypted_data FROM vaults WHERE user_id = ? AND item_type = 'login'";
        $items = $this->db->fetchAll($sql, [$user_id]);
        
        $weakPasswords = [];
        $encryption = new EncryptionHelper();
        
        foreach ($items as $item) {
            try {
                $data = json_decode($encryption->decrypt($item['encrypted_data']), true);
                $password = $data['password'] ?? '';
                
                if (!empty($password)) {
                    $score = $this->calculatePasswordScore($password);
                    
                    if ($score < $threshold) {
                        $weakPasswords[] = [
                            'item' => $item,
                            'score' => $score
                        ];
                    }
                }
                
            } catch (Exception $e) {
                // Skip items that can't be decrypted
            }
        }
        
        return $weakPasswords;
    }
    
    /**
     * Find old passwords that need updating
     */
    public function findOldPasswords($user_id, $months = 6) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$months} months"));
        
        $sql = "SELECT id, item_name, updated_at FROM vaults 
                WHERE user_id = ? AND item_type = 'login' AND updated_at < ?";
        
        return $this->db->fetchAll($sql, [$user_id, $cutoffDate]);
    }
}
?>
