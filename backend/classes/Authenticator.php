<?php
/**
 * Authenticator Class
 * Handles Two-Factor Authentication setup and validation
 */

require_once 'Database.php';

class Authenticator {
    private $db;
    
    // RFC 6238 compliant TOTP implementation
    const TOTP_PERIOD = 30; // 30 seconds
    const TOTP_DIGITS = 6;   // 6 digit codes
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Generate a new secret key for TOTP
     */
    public function generateSecret($length = 32) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
        $secret = '';
        
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $secret;
    }
    
    /**
     * Generate QR code data for authenticator app setup
     */
    public function generateQRCodeData($user_email, $secret, $issuer = 'SecureIt') {
        $label = urlencode($issuer . ':' . $user_email);
        $qrCodeUrl = "otpauth://totp/{$label}?secret={$secret}&issuer=" . urlencode($issuer);
        
        return $qrCodeUrl;
    }
    
    /**
     * Enable 2FA for a user
     */
    public function enable2FA($user_id, $secret, $verification_code) {
        // Verify the code first
        if (!$this->verifyTOTP($secret, $verification_code)) {
            throw new Exception('Invalid verification code');
        }
        
        // Update user record
        $sql = "UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?";
        $this->db->query($sql, [$secret, $user_id]);
        
        return true;
    }
    
    /**
     * Disable 2FA for a user
     */
    public function disable2FA($user_id, $verification_code) {
        // Get current secret
        $user = $this->db->fetchOne("SELECT two_factor_secret FROM users WHERE id = ?", [$user_id]);
        
        if (!$user || !$user['two_factor_secret']) {
            throw new Exception('Two-factor authentication is not enabled');
        }
        
        // Verify current code
        if (!$this->verifyTOTP($user['two_factor_secret'], $verification_code)) {
            throw new Exception('Invalid verification code');
        }
        
        // Disable 2FA
        $sql = "UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?";
        $this->db->query($sql, [$user_id]);
        
        return true;
    }
    
    /**
     * Verify TOTP code
     */
    public function verifyTOTP($secret, $code, $window = 1) {
        $currentTime = time();
        
        // Check current time window and adjacent windows
        for ($i = -$window; $i <= $window; $i++) {
            $timeSlice = intval(($currentTime + ($i * self::TOTP_PERIOD)) / self::TOTP_PERIOD);
            
            if ($this->generateTOTP($secret, $timeSlice) === $code) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verify 2FA code for user login
     */
    public function verifyUser2FA($user_id, $code) {
        $user = $this->db->fetchOne(
            "SELECT two_factor_enabled, two_factor_secret FROM users WHERE id = ?", 
            [$user_id]
        );
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        if (!$user['two_factor_enabled']) {
            throw new Exception('Two-factor authentication is not enabled for this user');
        }
        
        return $this->verifyTOTP($user['two_factor_secret'], $code);
    }
    
    /**
     * Generate TOTP code
     */
    private function generateTOTP($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = intval(time() / self::TOTP_PERIOD);
        }
        
        // Convert secret from Base32
        $secretBinary = $this->base32Decode($secret);
        
        // Pack time slice as binary string
        $timeData = pack('N*', 0) . pack('N*', $timeSlice);
        
        // Generate HMAC hash
        $hash = hash_hmac('sha1', $timeData, $secretBinary, true);
        
        // Get dynamic binary code
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, self::TOTP_DIGITS);
        
        // Pad with zeros if necessary
        return str_pad($code, self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }
    
    /**
     * Decode Base32 string
     */
    private function base32Decode($input) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0; $i < strlen($input); $i++) {
            $value = strpos($alphabet, $input[$i]);
            if ($value === false) continue;
            
            $v <<= 5;
            $v += $value;
            $vbits += 5;
            
            if ($vbits >= 8) {
                $output .= chr(($v >> ($vbits - 8)) & 255);
                $vbits -= 8;
            }
        }
        
        return $output;
    }
    
    /**
     * Generate backup codes for 2FA
     */
    public function generateBackupCodes($count = 10) {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            // Generate 8-character backup code
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                $code .= random_int(0, 9);
            }
            $codes[] = $code;
        }
        
        return $codes;
    }
    
    /**
     * Save backup codes for user (hashed)
     */
    public function saveBackupCodes($user_id, $codes) {
        // Hash the codes before storing
        $hashedCodes = array_map(function($code) {
            return password_hash($code, PASSWORD_DEFAULT);
        }, $codes);
        
        // Store in user table as JSON (or create separate table)
        $sql = "UPDATE users SET backup_codes = ? WHERE id = ?";
        $this->db->query($sql, [json_encode($hashedCodes), $user_id]);
        
        return true;
    }
    
    /**
     * Verify backup code
     */
    public function verifyBackupCode($user_id, $code) {
        $user = $this->db->fetchOne("SELECT backup_codes FROM users WHERE id = ?", [$user_id]);
        
        if (!$user || !$user['backup_codes']) {
            return false;
        }
        
        $storedCodes = json_decode($user['backup_codes'], true);
        
        foreach ($storedCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Remove used code
                unset($storedCodes[$index]);
                $sql = "UPDATE users SET backup_codes = ? WHERE id = ?";
                $this->db->query($sql, [json_encode(array_values($storedCodes)), $user_id]);
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get 2FA status for user
     */
    public function get2FAStatus($user_id) {
        $user = $this->db->fetchOne(
            "SELECT two_factor_enabled, backup_codes FROM users WHERE id = ?", 
            [$user_id]
        );
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        $backupCodes = $user['backup_codes'] ? json_decode($user['backup_codes'], true) : [];
        
        return [
            'enabled' => (bool)$user['two_factor_enabled'],
            'backup_codes_count' => count($backupCodes)
        ];
    }
    
    /**
     * Test TOTP generation (for development/testing)
     */
    public function testTOTP($secret) {
        return [
            'current_code' => $this->generateTOTP($secret),
            'timestamp' => time(),
            'time_remaining' => self::TOTP_PERIOD - (time() % self::TOTP_PERIOD)
        ];
    }
}
?>
