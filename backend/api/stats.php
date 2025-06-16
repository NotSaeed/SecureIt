<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\api\stats.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../classes/Database.php';

try {
    $db = new Database();
    
    // Get user count
    $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users");
    
    // Get vault items count
    $vaultCount = $db->fetchOne("SELECT COUNT(*) as count FROM vaults");
    
    // Get sends count
    $sendCount = $db->fetchOne("SELECT COUNT(*) as count FROM sends");
    
    // Get recent activity
    $recentActivity = $db->fetchAll("
        SELECT 'user' as type, created_at FROM users 
        UNION ALL 
        SELECT 'vault' as type, created_at FROM vaults 
        UNION ALL 
        SELECT 'send' as type, created_at FROM sends 
        ORDER BY created_at DESC LIMIT 10
    ");
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => (int)$userCount['count'],
            'total_vault_items' => (int)$vaultCount['count'],
            'total_sends' => (int)$sendCount['count'],
            'recent_activity' => $recentActivity
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching stats: ' . $e->getMessage()
    ]);
}
?>
