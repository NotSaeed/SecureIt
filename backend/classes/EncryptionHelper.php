<?php
/**
 * EncryptionHelper Class
 * Provides encryption and decryption functionalities using AES-256 encryption
 */

class EncryptionHelper {
    private $cipher = 'aes-256-gcm';
    private $key;

    public function __construct($key = null) {
        $this->key = $key ?: $this->getDefaultKey();
    }

    /**
     * Get default encryption key
     */
    private function getDefaultKey() {
        // In production, store this securely in environment variables
        $defaultKey = 'SecureIt_Default_Key_2025_' . hash('sha256', 'secureit_salt');
        return hash('sha256', $defaultKey, true);
    }

    /**
     * Encrypt data using AES-256-GCM
     */
    public function encrypt($data) {
        if (empty($data)) {
            throw new Exception('Data cannot be empty');
        }

        $iv = random_bytes(12); // 96-bit IV for GCM
        $tag = '';
        
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($encrypted === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }

        // Combine IV + tag + encrypted data and encode
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decrypt data encrypted with AES-256-GCM
     */
    public function decrypt($encryptedData) {
        if (empty($encryptedData)) {
            throw new Exception('Encrypted data cannot be empty');
        }

        $data = base64_decode($encryptedData);
        
        if ($data === false) {
            throw new Exception('Invalid encrypted data format');
        }

        if (strlen($data) < 28) { // 12 (IV) + 16 (tag) = 28 minimum
            throw new Exception('Encrypted data is too short');
        }

        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted = substr($data, 28);

        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($decrypted === false) {
            throw new Exception('Decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * Generate a random encryption key
     */
    public function generateKey() {
        return random_bytes(32);
    }

    /**
     * Hash password using Argon2ID
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }

    /**
     * Verify password against hash
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
?>