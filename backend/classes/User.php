<?php
/**
 * User Class
 * Manages user authentication, account creation, and basic user management
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/EncryptionHelper.php';

class User {
    private $db;
    private $encryption;
    
    public $id;
    public $email;
    public $name;
    public $created_at;
    public $last_login;
    public $security_score;
    public $two_factor_enabled;

    public function __construct() {
        $this->db = new Database();
        $this->encryption = new EncryptionHelper();
    }    /**
     * Create a new user
     */
    public function create($email, $password, $name = null) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Validate password strength
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        // Create email hash for duplicate checking (deterministic)
        $emailHash = hash('sha256', strtolower(trim($email)));
        
        // Check if user already exists using email hash
        $existing = $this->db->fetchOne("SELECT id FROM users WHERE email_hash = ?", [$emailHash]);
        if ($existing) {
            throw new Exception("User already exists with this email");
        }        // Encrypt sensitive data
        $encryptedEmail = $this->encryption->encrypt($email);
        $encryptedName = $name ? $this->encryption->encrypt($name) : null;
        $password_hash = $this->encryption->hashPassword($password);
        
        $sql = "INSERT INTO users (email_hash, email, password_hash, name) VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [$emailHash, $encryptedEmail, $password_hash, $encryptedName]);
        $this->id = $this->db->lastInsertId();
        $this->email = $email; // Store decrypted for session use
        $this->name = $name;   // Store decrypted for session use
        $this->security_score = 0;
        $this->two_factor_enabled = false;
        
        return $this;
    }    /**
     * Authenticate user with email and password
     */
    public function authenticate($email, $password) {
        // Create email hash for lookup
        $emailHash = hash('sha256', strtolower(trim($email)));
        
        $user = $this->db->fetchOne("SELECT * FROM users WHERE email_hash = ?", [$emailHash]);
        
        if (!$user || !$this->encryption->verifyPassword($password, $user['password_hash'])) {
            return false;
        }

        // Update last login
        $this->db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
        
        $this->loadFromArray($user);
        return $this;
    }

    /**
     * Find user by ID
     */
    public function findById($id) {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        if ($user) {
            $this->loadFromArray($user);
            return $this;
        }
        return null;
    }    /**
     * Update user profile
     */
    public function updateProfile($name, $email = null) {
        $updates = [];
        $params = [];        if ($name !== null) {
            $updates[] = "name = ?";
            $params[] = $this->encryption->encrypt($name);
        }

        if ($email !== null) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            
            $emailHash = hash('sha256', strtolower(trim($email)));
            
            // Check if email is already taken by another user
            $existing = $this->db->fetchOne("SELECT id FROM users WHERE email_hash = ? AND id != ?", [$emailHash, $this->id]);
            if ($existing) {
                throw new Exception("Email is already taken by another user");
            }
            
            $updates[] = "email_hash = ?, email = ?";
            $params[] = $emailHash;
            $params[] = $this->encryption->encrypt($email);
        }

        if (!empty($updates)) {
            $params[] = $this->id;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $this->db->query($sql, $params);
            
            // Refresh user data
            $this->findById($this->id);
        }

        return $this;
    }

    /**
     * Change user password
     */
    public function changePassword($currentPassword, $newPassword) {
        // Verify current password
        $user = $this->db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$this->id]);
        
        if (!$this->encryption->verifyPassword($currentPassword, $user['password_hash'])) {
            throw new Exception("Current password is incorrect");
        }

        // Validate new password
        if (strlen($newPassword) < 8) {
            throw new Exception("New password must be at least 8 characters long");
        }

        $newPasswordHash = $this->encryption->hashPassword($newPassword);
        $this->db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$newPasswordHash, $this->id]);

        return true;
    }

    /**
     * Update security score
     */
    public function updateSecurityScore($score) {
        $this->db->query("UPDATE users SET security_score = ? WHERE id = ?", [$score, $this->id]);
        $this->security_score = $score;
    }

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactor($secret) {
        $this->db->query(
            "UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?", 
            [$secret, $this->id]
        );
        $this->two_factor_enabled = true;
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor() {
        $this->db->query(
            "UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?", 
            [$this->id]
        );
        $this->two_factor_enabled = false;
    }

    /**
     * Delete user account
     */
    public function deleteAccount() {
        $this->db->query("DELETE FROM users WHERE id = ?", [$this->id]);
    }    /**
     * Load user data from array and decrypt sensitive fields
     */
    private function loadFromArray($data) {
        $this->id = $data['id'];
        
        // Decrypt sensitive data
        try {
            $this->email = $data['email'] ? $this->encryption->decrypt($data['email']) : null;
            $this->name = $data['name'] ? $this->encryption->decrypt($data['name']) : null;
        } catch (Exception $e) {
            // Handle decryption failure gracefully
            $this->email = 'encrypted_email_' . $this->id;
            $this->name = 'encrypted_name_' . $this->id;
        }
        
        $this->created_at = $data['created_at'];
        $this->last_login = $data['last_login'];
        $this->security_score = $data['security_score'];
        $this->two_factor_enabled = (bool)$data['two_factor_enabled'];
    }

    /**
     * Register a new user (alternative interface to create)
     * Returns response format expected by registration forms
     */
    public function register($email, $password, $name = null) {
        try {
            $user = $this->create($email, $password, $name);
            return [
                'success' => true,
                'message' => 'User registered successfully',
                'user_id' => $user->id
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Login user (alternative interface to authenticate)
     * Returns response format expected by login forms
     */
    public function login($email, $password) {
        try {
            $user = $this->authenticate($email, $password);
            if ($user) {
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                        'security_score' => $user->security_score
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
?>