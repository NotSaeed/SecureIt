<?php
/**
 * Get User Passwords for Analysis
 * This script retrieves user's saved passwords for brute force analysis
 */

// Start session
session_start();

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once 'config/database.php';
require_once 'classes/EncryptionHelper.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $userId = $_SESSION['user_id'];
    $encryption = new EncryptionHelper();
    
    // Get user's saved passwords from vaults table
    $stmt = $pdo->prepare("
        SELECT 
            id,
            item_name,
            item_type,
            encrypted_data,
            website_url
        FROM vaults 
        WHERE user_id = ? AND item_type = 'login'
        ORDER BY item_name ASC
    ");
    
    $stmt->execute([$userId]);
    $vaultItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $passwords = [];
    
    foreach ($vaultItems as $item) {
        try {
            // Decrypt the password data
            $decryptedData = $encryption->decrypt($item['encrypted_data']);
            $dataArray = json_decode($decryptedData, true);
            
            if ($dataArray && isset($dataArray['password'])) {
                $passwords[] = [
                    'id' => $item['id'],
                    'name' => $item['item_name'],
                    'website' => $item['website_url'],
                    'password' => $dataArray['password']
                ];
            }
        } catch (Exception $e) {
            // Skip items that can't be decrypted
            error_log("Failed to decrypt password for item {$item['id']}: " . $e->getMessage());
            continue;
        }
    }
    
    echo json_encode([
        'success' => true,
        'passwords' => $passwords,
        'count' => count($passwords)
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching user passwords: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve passwords'
    ]);
}
?>
