<?php
/**
 * Have I Been Pwned API Endpoint
 * 
 * This script handles AJAX requests for HIBP data checks
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

require_once 'classes/HIBPHelper.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    $hibp = new HIBPHelper();
    
    switch ($action) {
        case 'check_account':
            $account = $input['account'] ?? '';
            if (empty($account)) {
                throw new Exception('Account parameter is required');
            }
            
            // Validate email format
            if (!filter_var($account, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address');
            }
            
            $breaches = $hibp->checkBreachedAccount($account, false); // Get full breach data
            $pastes = null;
            
            // Also check for pastes
            try {
                $pastes = $hibp->checkPasteAccount($account);
            } catch (Exception $e) {
                // Pastes might not be available, continue without them
            }
            
            $response = [
                'success' => true,
                'account' => $account,
                'breaches' => $breaches,
                'pastes' => $pastes,
                'breach_count' => $breaches ? count($breaches) : 0,
                'paste_count' => $pastes ? count($pastes) : 0,
                'demo_mode' => $hibp->isDemoMode()
            ];
            
            // Add breach details if found
            if ($breaches && !empty($breaches)) {
                $response['breach_details'] = [];
                foreach ($breaches as $breach) {
                    $response['breach_details'][] = [
                        'name' => $breach['Name'] ?? $breach['name'] ?? 'Unknown',
                        'title' => $breach['Title'] ?? $breach['title'] ?? $breach['Name'] ?? 'Unknown',
                        'domain' => $breach['Domain'] ?? $breach['domain'] ?? '',
                        'breach_date' => $breach['BreachDate'] ?? $breach['breach_date'] ?? '',
                        'pwn_count' => $breach['PwnCount'] ?? $breach['pwn_count'] ?? 0,
                        'description' => strip_tags($breach['Description'] ?? $breach['description'] ?? ''),
                        'data_classes' => $breach['DataClasses'] ?? $breach['data_classes'] ?? [],
                        'is_verified' => $breach['IsVerified'] ?? $breach['is_verified'] ?? true
                    ];
                }
            }
            
            echo json_encode($response);
            break;
            
        case 'get_config':
            $config = $hibp->getConfigStatus();
            echo json_encode([
                'success' => true,
                'config' => $config
            ]);
            break;
            
        case 'get_all_breaches':
            $breaches = $hibp->getAllBreaches();
            echo json_encode([
                'success' => true,
                'breaches' => $breaches,
                'count' => count($breaches),
                'demo_mode' => $hibp->isDemoMode()
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
