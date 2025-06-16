<?php
/**
 * PasswordGenerator Class
 * Generates secure passwords, passphrases, and random usernames
 */

require_once 'Database.php';

class PasswordGenerator {
    private $db;
    private $wordList = [
        'apple', 'banana', 'cherry', 'dragon', 'eagle', 'forest', 'garden', 'happy',
        'island', 'jungle', 'kitten', 'lemon', 'mountain', 'ocean', 'purple', 'quiet',
        'rainbow', 'sunset', 'tiger', 'umbrella', 'violet', 'water', 'yellow', 'zebra',
        'adventure', 'butterfly', 'crystal', 'diamond', 'elephant', 'firefly', 'galaxy',
        'harmony', 'infinite', 'journey', 'kingdom', 'liberty', 'miracle', 'nature',
        'optimism', 'paradise', 'quality', 'radiant', 'serenity', 'triumph', 'universe',
        'victory', 'wisdom', 'xenial', 'yearning', 'zenith'
    ];

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Generate a secure password
     */
    public function generatePassword($length = 14, $options = []) {
        $options = array_merge([
            'uppercase' => true,
            'lowercase' => true,
            'numbers' => true,
            'symbols' => false,
            'min_numbers' => 1,
            'min_symbols' => 0,
            'avoid_ambiguous' => false
        ], $options);

        $chars = '';
        $requiredChars = '';
        
        // Build character sets
        $upperCase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerCase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        if ($options['avoid_ambiguous']) {
            $upperCase = str_replace(['O', 'I'], '', $upperCase);
            $lowerCase = str_replace(['l', 'o'], '', $lowerCase);
            $numbers = str_replace(['0', '1'], '', $numbers);
            $symbols = str_replace(['|', '`'], '', $symbols);
        }
        
        if ($options['uppercase']) {
            $chars .= $upperCase;
            $requiredChars .= $upperCase[random_int(0, strlen($upperCase) - 1)];
        }
        
        if ($options['lowercase']) {
            $chars .= $lowerCase;
            $requiredChars .= $lowerCase[random_int(0, strlen($lowerCase) - 1)];
        }
        
        if ($options['numbers']) {
            $chars .= $numbers;
            for ($i = 0; $i < $options['min_numbers']; $i++) {
                $requiredChars .= $numbers[random_int(0, strlen($numbers) - 1)];
            }
        }
        
        if ($options['symbols']) {
            $chars .= $symbols;
            for ($i = 0; $i < $options['min_symbols']; $i++) {
                $requiredChars .= $symbols[random_int(0, strlen($symbols) - 1)];
            }
        }

        if (empty($chars)) {
            throw new Exception("At least one character type must be selected");
        }

        // Start with required characters
        $password = $requiredChars;
        
        // Fill the rest randomly
        $remaining = $length - strlen($password);
        for ($i = 0; $i < $remaining; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        // Shuffle the password to randomize position of required characters
        $password = str_shuffle($password);

        return substr($password, 0, $length);
    }

    /**
     * Generate a passphrase
     */
    public function generatePassphrase($word_count = 6, $separator = '-', $capitalize = false, $include_number = false) {
        if ($word_count < 3 || $word_count > 20) {
            throw new Exception("Word count must be between 3 and 20");
        }
        
        $words = [];
        $usedWords = [];
        
        for ($i = 0; $i < $word_count; $i++) {
            do {
                $word = $this->wordList[random_int(0, count($this->wordList) - 1)];
            } while (in_array($word, $usedWords) && count($usedWords) < count($this->wordList));
            
            $usedWords[] = $word;
            
            if ($capitalize) {
                $word = ucfirst($word);
            }
            $words[] = $word;
        }

        if ($include_number) {
            $words[] = random_int(100, 999);
        }

        return implode($separator, $words);
    }

    /**
     * Generate a username
     */
    public function generateUsername($type = 'random_word', $capitalize = false, $include_number = false) {
        switch ($type) {
            case 'random_word':
                $username = $this->wordList[random_int(0, count($this->wordList) - 1)];
                if ($capitalize) {
                    $username = ucfirst($username);
                }
                if ($include_number) {
                    $username .= random_int(100, 999);
                }
                return $username;
                
            case 'combination':
                $adjectives = ['swift', 'bright', 'clever', 'brave', 'calm', 'wise', 'bold', 'keen'];
                $nouns = ['wolf', 'eagle', 'tiger', 'lion', 'bear', 'shark', 'hawk', 'fox'];
                
                $username = $adjectives[random_int(0, count($adjectives) - 1)] . 
                           $nouns[random_int(0, count($nouns) - 1)];
                
                if ($capitalize) {
                    $username = ucfirst($username);
                }
                if ($include_number) {
                    $username .= random_int(10, 99);
                }
                return $username;
                
            case 'uuid':
                return $this->generateUUID();
                
            default:
                throw new Exception("Invalid username type");
        }
    }

    /**
     * Generate UUID v4
     */
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff), random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
        );
    }

    /**
     * Save generated item to history
     */
    public function saveToHistory($user_id, $type, $value, $options = []) {
        if (!in_array($type, ['password', 'passphrase', 'username'])) {
            throw new Exception("Invalid generation type");
        }
        
        $sql = "INSERT INTO generator_history (user_id, generated_type, generated_value, options) 
                VALUES (?, ?, ?, ?)";
        
        $this->db->query($sql, [$user_id, $type, $value, json_encode($options)]);
        return $this->db->lastInsertId();
    }

    /**
     * Get generation history for user
     */
    public function getHistory($user_id, $type = null, $limit = 10) {
        $sql = "SELECT * FROM generator_history WHERE user_id = ?";
        $params = [$user_id];
        
        if ($type) {
            $sql .= " AND generated_type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $history = $this->db->fetchAll($sql, $params);
        
        // Decode options JSON
        foreach ($history as &$item) {
            $item['options'] = json_decode($item['options'], true);
        }
        
        return $history;
    }

    /**
     * Clear history for user
     */
    public function clearHistory($user_id, $type = null) {
        if ($type) {
            $sql = "DELETE FROM generator_history WHERE user_id = ? AND generated_type = ?";
            return $this->db->query($sql, [$user_id, $type]);
        } else {
            $sql = "DELETE FROM generator_history WHERE user_id = ?";
            return $this->db->query($sql, [$user_id]);
        }
    }

    /**
     * Get password strength estimation
     */
    public function estimatePasswordStrength($password) {
        $length = strlen($password);
        $score = 0;
        $feedback = [];
        
        // Length scoring
        if ($length < 8) {
            $feedback[] = "Password is too short";
            $score += $length * 2;
        } else if ($length < 12) {
            $feedback[] = "Consider using a longer password";
            $score += 20 + ($length - 8) * 5;
        } else {
            $score += 40 + min(($length - 12) * 2, 20);
        }
        
        // Character variety
        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasDigit = preg_match('/[0-9]/', $password);
        $hasSymbol = preg_match('/[^a-zA-Z0-9]/', $password);
        
        $variety = $hasLower + $hasUpper + $hasDigit + $hasSymbol;
        $score += $variety * 10;
        
        if (!$hasLower) $feedback[] = "Add lowercase letters";
        if (!$hasUpper) $feedback[] = "Add uppercase letters";
        if (!$hasDigit) $feedback[] = "Add numbers";
        if (!$hasSymbol) $feedback[] = "Add symbols";
        
        // Pattern penalties
        if (preg_match('/(.)\1{2,}/', $password)) {
            $score -= 15;
            $feedback[] = "Avoid repeated characters";
        }
        
        if (preg_match('/123|abc|qwerty|password/i', $password)) {
            $score -= 25;
            $feedback[] = "Avoid common patterns";
        }
        
        $score = max(0, min(100, $score));
        
        if ($score < 30) {
            $strength = "Very Weak";
        } else if ($score < 50) {
            $strength = "Weak";
        } else if ($score < 70) {
            $strength = "Fair";
        } else if ($score < 90) {
            $strength = "Strong";
        } else {
            $strength = "Very Strong";
        }
        
        return [
            'score' => $score,
            'strength' => $strength,
            'feedback' => $feedback
        ];
    }
}
?>
