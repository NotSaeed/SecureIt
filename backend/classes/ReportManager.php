<?php
/**
 * ReportManager Class
 * Generates and manages various security reports for users
 */

require_once 'Database.php';
require_once 'SecurityManager.php';
require_once 'EncryptionHelper.php';

class ReportManager {
    private $db;
    private $securityManager;
    private $encryption;

    public function __construct() {
        $this->db = new Database();
        $this->securityManager = new SecurityManager();
        $this->encryption = new EncryptionHelper();
    }

    /**
     * Generate comprehensive security report
     */
    public function generateSecurityReport($user_id) {
        $report = [
            'user_id' => $user_id,
            'generated_at' => date('Y-m-d H:i:s'),
            'overall_score' => $this->securityManager->assessSecurityScore($user_id),
            'vault_stats' => $this->getVaultStatistics($user_id),
            'password_analysis' => $this->getPasswordAnalysis($user_id),
            'security_issues' => $this->getSecurityIssues($user_id),
            'recommendations' => $this->getSecurityRecommendations($user_id)
        ];

        // Save report to database
        $this->saveReport($user_id, 'security_report', $report);

        return $report;
    }

    /**
     * Generate data breach report
     */
    public function generateBreachReport($user_id) {
        $sql = "SELECT id, item_name, encrypted_data FROM vaults WHERE user_id = ? AND item_type = 'login'";
        $items = $this->db->fetchAll($sql, [$user_id]);

        $breachedPasswords = [];
        $checkedCount = 0;

        foreach ($items as $item) {
            try {
                $data = json_decode($this->encryption->decrypt($item['encrypted_data']), true);
                $password = $data['password'] ?? '';

                if (!empty($password)) {
                    $breachResult = $this->securityManager->checkPasswordBreach($password);
                    $checkedCount++;

                    if ($breachResult['breached']) {
                        $breachedPasswords[] = [
                            'item_id' => $item['id'],
                            'item_name' => $item['item_name'],
                            'breach_count' => $breachResult['count']
                        ];
                    }
                }
            } catch (Exception $e) {
                // Skip items that can't be decrypted
            }
        }

        $report = [
            'user_id' => $user_id,
            'generated_at' => date('Y-m-d H:i:s'),
            'total_passwords_checked' => $checkedCount,
            'breached_passwords_count' => count($breachedPasswords),
            'breached_passwords' => $breachedPasswords,
            'breach_percentage' => $checkedCount > 0 ? round((count($breachedPasswords) / $checkedCount) * 100, 2) : 0
        ];

        $this->saveReport($user_id, 'breach_report', $report);
        return $report;
    }

    /**
     * Generate password strength report
     */
    public function generatePasswordStrengthReport($user_id) {
        $sql = "SELECT id, item_name, encrypted_data FROM vaults WHERE user_id = ? AND item_type = 'login'";
        $items = $this->db->fetchAll($sql, [$user_id]);

        $strengthDistribution = [
            'very_weak' => 0,
            'weak' => 0,
            'fair' => 0,
            'strong' => 0,
            'very_strong' => 0
        ];

        $passwordDetails = [];

        foreach ($items as $item) {
            try {
                $data = json_decode($this->encryption->decrypt($item['encrypted_data']), true);
                $password = $data['password'] ?? '';

                if (!empty($password)) {
                    $score = $this->securityManager->calculatePasswordScore($password);
                    
                    $strength = $this->getStrengthCategory($score);
                    $strengthDistribution[$strength]++;

                    $passwordDetails[] = [
                        'item_id' => $item['id'],
                        'item_name' => $item['item_name'],
                        'score' => $score,
                        'strength' => $strength,
                        'length' => strlen($password)
                    ];
                }
            } catch (Exception $e) {
                // Skip items that can't be decrypted
            }
        }

        $totalPasswords = array_sum($strengthDistribution);
        $averageScore = $totalPasswords > 0 ? 
            array_sum(array_column($passwordDetails, 'score')) / $totalPasswords : 0;

        $report = [
            'user_id' => $user_id,
            'generated_at' => date('Y-m-d H:i:s'),
            'total_passwords' => $totalPasswords,
            'average_score' => round($averageScore, 2),
            'strength_distribution' => $strengthDistribution,
            'password_details' => $passwordDetails
        ];

        $this->saveReport($user_id, 'password_strength_report', $report);
        return $report;
    }

    /**
     * Export vault data
     */
    public function exportVault($user_id, $format = 'json', $include_passwords = false) {
        $sql = "SELECT * FROM vaults WHERE user_id = ? ORDER BY item_type, item_name";
        $items = $this->db->fetchAll($sql, [$user_id]);

        $exportData = [];

        foreach ($items as $item) {
            try {
                $decryptedData = json_decode($this->encryption->decrypt($item['encrypted_data']), true);
                
                $exportItem = [
                    'name' => $item['item_name'],
                    'type' => $item['item_type'],
                    'website' => $item['website_url'],
                    'username' => $decryptedData['username'] ?? '',
                    'notes' => $decryptedData['notes'] ?? '',
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at']
                ];

                // Include password if requested
                if ($include_passwords) {
                    $exportItem['password'] = $decryptedData['password'] ?? '';
                } else {
                    $exportItem['password'] = '[HIDDEN]';
                }

                // Add type-specific fields
                switch ($item['item_type']) {
                    case 'card':
                        $exportItem['card_number'] = $include_passwords ? 
                            ($decryptedData['card_number'] ?? '') : '[HIDDEN]';
                        $exportItem['expiry_date'] = $decryptedData['expiry_date'] ?? '';
                        $exportItem['cvv'] = $include_passwords ? 
                            ($decryptedData['cvv'] ?? '') : '[HIDDEN]';
                        break;
                    
                    case 'identity':
                        $exportItem['full_name'] = $decryptedData['full_name'] ?? '';
                        $exportItem['address'] = $decryptedData['address'] ?? '';
                        $exportItem['phone'] = $decryptedData['phone'] ?? '';
                        break;
                }

                $exportData[] = $exportItem;

            } catch (Exception $e) {
                // Skip items that can't be decrypted
            }
        }

        switch ($format) {
            case 'csv':
                return $this->convertToCSV($exportData);
            case 'xml':
                return $this->convertToXML($exportData);
            default:
                return json_encode($exportData, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Generate activity report
     */
    public function generateActivityReport($user_id, $days = 30) {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // Vault activity
        $vaultActivity = $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as items_added 
             FROM vaults WHERE user_id = ? AND created_at >= ? 
             GROUP BY DATE(created_at) ORDER BY date",
            [$user_id, $startDate]
        );

        $vaultUpdates = $this->db->fetchAll(
            "SELECT DATE(updated_at) as date, COUNT(*) as items_updated 
             FROM vaults WHERE user_id = ? AND updated_at >= ? AND updated_at != created_at
             GROUP BY DATE(updated_at) ORDER BY date",
            [$user_id, $startDate]
        );

        // Send activity
        $sendActivity = $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as sends_created 
             FROM sends WHERE user_id = ? AND created_at >= ? 
             GROUP BY DATE(created_at) ORDER BY date",
            [$user_id, $startDate]
        );

        // Generator activity
        $generatorActivity = $this->db->fetchAll(
            "SELECT DATE(created_at) as date, generated_type, COUNT(*) as count 
             FROM generator_history WHERE user_id = ? AND created_at >= ? 
             GROUP BY DATE(created_at), generated_type ORDER BY date",
            [$user_id, $startDate]
        );

        $report = [
            'user_id' => $user_id,
            'generated_at' => date('Y-m-d H:i:s'),
            'period_days' => $days,
            'start_date' => $startDate,
            'vault_activity' => $vaultActivity,
            'vault_updates' => $vaultUpdates,
            'send_activity' => $sendActivity,
            'generator_activity' => $generatorActivity
        ];

        $this->saveReport($user_id, 'activity_report', $report);
        return $report;
    }

    /**
     * Get vault statistics
     */
    private function getVaultStatistics($user_id) {
        $sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN item_type = 'login' THEN 1 ELSE 0 END) as login_items,
                    SUM(CASE WHEN item_type = 'card' THEN 1 ELSE 0 END) as card_items,
                    SUM(CASE WHEN item_type = 'identity' THEN 1 ELSE 0 END) as identity_items,
                    SUM(CASE WHEN item_type = 'note' THEN 1 ELSE 0 END) as note_items,
                    SUM(CASE WHEN is_favorite = 1 THEN 1 ELSE 0 END) as favorite_items
                FROM vaults WHERE user_id = ?";

        return $this->db->fetchOne($sql, [$user_id]);
    }

    /**
     * Get password analysis
     */
    private function getPasswordAnalysis($user_id) {
        $duplicates = $this->securityManager->findDuplicatePasswords($user_id);
        $weakPasswords = $this->securityManager->findWeakPasswords($user_id);
        $oldPasswords = $this->securityManager->findOldPasswords($user_id);

        return [
            'duplicate_passwords' => count($duplicates),
            'weak_passwords' => count($weakPasswords),
            'old_passwords' => count($oldPasswords),
            'duplicate_details' => $duplicates,
            'weak_password_details' => $weakPasswords,
            'old_password_details' => $oldPasswords
        ];
    }

    /**
     * Get security issues
     */
    private function getSecurityIssues($user_id) {
        $issues = [];

        $duplicates = $this->securityManager->findDuplicatePasswords($user_id);
        if (count($duplicates) > 0) {
            $issues[] = [
                'type' => 'duplicate_passwords',
                'severity' => 'high',
                'count' => count($duplicates),
                'message' => 'You have duplicate passwords in your vault'
            ];
        }

        $weakPasswords = $this->securityManager->findWeakPasswords($user_id);
        if (count($weakPasswords) > 0) {
            $issues[] = [
                'type' => 'weak_passwords',
                'severity' => 'medium',
                'count' => count($weakPasswords),
                'message' => 'You have weak passwords that should be strengthened'
            ];
        }

        $oldPasswords = $this->securityManager->findOldPasswords($user_id);
        if (count($oldPasswords) > 0) {
            $issues[] = [
                'type' => 'old_passwords',
                'severity' => 'low',
                'count' => count($oldPasswords),
                'message' => 'You have passwords that haven\'t been updated in 6+ months'
            ];
        }

        return $issues;
    }

    /**
     * Get security recommendations
     */
    private function getSecurityRecommendations($user_id) {
        $recommendations = [];

        $analysis = $this->getPasswordAnalysis($user_id);

        if ($analysis['duplicate_passwords'] > 0) {
            $recommendations[] = "Update duplicate passwords to unique, strong passwords";
        }

        if ($analysis['weak_passwords'] > 0) {
            $recommendations[] = "Strengthen weak passwords using the password generator";
        }

        if ($analysis['old_passwords'] > 0) {
            $recommendations[] = "Update old passwords regularly for better security";
        }

        // Get user's 2FA status
        $user = $this->db->fetchOne("SELECT two_factor_enabled FROM users WHERE id = ?", [$user_id]);
        if (!$user['two_factor_enabled']) {
            $recommendations[] = "Enable two-factor authentication for enhanced security";
        }

        if (empty($recommendations)) {
            $recommendations[] = "Great job! Your vault security looks good";
        }

        return $recommendations;
    }

    /**
     * Save report to database
     */
    private function saveReport($user_id, $report_type, $report_data) {
        $sql = "INSERT INTO security_reports (user_id, report_type, report_data) VALUES (?, ?, ?)";
        $this->db->query($sql, [$user_id, $report_type, json_encode($report_data)]);
    }

    /**
     * Get saved reports
     */
    public function getSavedReports($user_id, $report_type = null, $limit = 10) {
        $sql = "SELECT * FROM security_reports WHERE user_id = ?";
        $params = [$user_id];

        if ($report_type) {
            $sql .= " AND report_type = ?";
            $params[] = $report_type;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;

        $reports = $this->db->fetchAll($sql, $params);

        foreach ($reports as &$report) {
            $report['report_data'] = json_decode($report['report_data'], true);
        }

        return $reports;
    }

    /**
     * Get strength category from score
     */
    private function getStrengthCategory($score) {
        if ($score < 30) return 'very_weak';
        if ($score < 50) return 'weak';
        if ($score < 70) return 'fair';
        if ($score < 90) return 'strong';
        return 'very_strong';
    }

    /**
     * Convert data to CSV format
     */
    private function convertToCSV($data) {
        if (empty($data)) return '';

        $csv = '';
        $headers = array_keys($data[0]);
        $csv .= implode(',', $headers) . "\n";

        foreach ($data as $row) {
            $csv .= implode(',', array_map(function($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row)) . "\n";
        }

        return $csv;
    }

    /**
     * Convert data to XML format
     */
    private function convertToXML($data) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<vault>\n";

        foreach ($data as $item) {
            $xml .= "  <item>\n";
            foreach ($item as $key => $value) {
                $xml .= "    <{$key}>" . htmlspecialchars($value) . "</{$key}>\n";
            }
            $xml .= "  </item>\n";
        }

        $xml .= "</vault>";
        return $xml;
    }
}
?>
