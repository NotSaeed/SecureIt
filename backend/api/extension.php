<?php
/**
 * Extension API for password analysis and vault checking
 */

header('Content-Type: application/json');

// Allow requests from extension
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'chrome-extension://',
    'moz-extension://',
    'http://localhost',
    'https://localhost'
];

$isAllowed = false;
foreach ($allowedOrigins as $allowed) {
    if (strpos($origin, $allowed) === 0) {
        $isAllowed = true;
        break;
    }
}

if ($isAllowed || empty($origin)) {
    header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../classes/Vault.php';
require_once '../classes/EncryptionManager.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'POST':
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'check_password_in_vault':
                    if (!isset($_SESSION['user_id'])) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'User not authenticated',
                            'in_vault' => false
                        ]);
                        exit;
                    }

                    $password = $input['password'] ?? '';
                    $currentUrl = $input['url'] ?? '';
                    
                    if (empty($password)) {
                        echo json_encode([
                            'success' => true,
                            'in_vault' => false,
                            'message' => 'Empty password'
                        ]);
                        exit;
                    }

                    $vault = new Vault();
                    $vaultItems = $vault->getUserItems($_SESSION['user_id']);
                    
                    $foundInVault = false;
                    $matchedItems = [];
                    
                    foreach ($vaultItems as $item) {
                        if (isset($item['decrypted_data']['password']) && 
                            $item['decrypted_data']['password'] === $password) {
                            $foundInVault = true;
                            $matchedItems[] = [
                                'id' => $item['id'],
                                'name' => $item['item_name'],
                                'url' => $item['website_url'],
                                'username' => $item['decrypted_data']['username'] ?? ''
                            ];
                        }
                    }

                    echo json_encode([
                        'success' => true,
                        'in_vault' => $foundInVault,
                        'matched_items' => $matchedItems,
                        'current_url' => $currentUrl
                    ]);
                    break;

                case 'get_vault_passwords':
                    if (!isset($_SESSION['user_id'])) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'User not authenticated',
                            'passwords' => []
                        ]);
                        exit;
                    }

                    $vault = new Vault();
                    $vaultItems = $vault->getUserItems($_SESSION['user_id']);
                    
                    $passwords = [];
                    foreach ($vaultItems as $item) {
                        if (isset($item['decrypted_data']['password']) && 
                            !empty($item['decrypted_data']['password'])) {
                            $passwords[] = [
                                'password' => $item['decrypted_data']['password'],
                                'url' => $item['website_url'],
                                'name' => $item['item_name']
                            ];
                        }
                    }

                    echo json_encode([
                        'success' => true,
                        'passwords' => $passwords
                    ]);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
