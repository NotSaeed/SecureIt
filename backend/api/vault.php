<?php
/**
 * Vault Management API Endpoints
 */

header('Content-Type: application/json');

// Allow requests from extension and localhost
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost',
    'https://localhost',
    'chrome-extension://',
    'moz-extension://'
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
    header('Access-Control-Allow-Origin: http://localhost:3000');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../classes/Vault.php';
require_once '../classes/SecurityManager.php';

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$vault = new Vault();
$user_id = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'list';

            switch ($action) {                case 'list':
                    $items = $vault->getUserItems($user_id);
                    
                    // Format items for extension compatibility
                    $formattedItems = array_map(function($item) {
                        $formatted = [
                            'id' => $item['id'],
                            'name' => $item['item_name'] ?? 'Untitled',
                            'type' => $item['item_type'],
                            'url' => $item['website_url'],
                            'created_at' => $item['created_at'],
                            'updated_at' => $item['updated_at'],
                            'folder_id' => $item['folder_id'],
                            'is_favorite' => $item['is_favorite']
                        ];
                        
                        // Extract username and password from decrypted_data
                        if (isset($item['decrypted_data']) && is_array($item['decrypted_data'])) {
                            $formatted['username'] = $item['decrypted_data']['username'] ?? '';
                            $formatted['password'] = $item['decrypted_data']['password'] ?? '';
                            $formatted['notes'] = $item['decrypted_data']['notes'] ?? '';
                        }
                        
                        return $formatted;
                    }, $items);
                    
                    echo json_encode([
                        'success' => true,
                        'items' => $formattedItems
                    ]);
                    break;

                case 'get':
                    if (!isset($_GET['id'])) {
                        throw new Exception('Item ID is required');
                    }

                    $item = $vault->getItem($_GET['id'], $user_id);
                    if (!$item) {
                        throw new Exception('Item not found');
                    }

                    echo json_encode([
                        'success' => true,
                        'item' => $item
                    ]);
                    break;

                case 'search':
                    if (!isset($_GET['query'])) {
                        throw new Exception('Search query is required');
                    }

                    $results = $vault->searchItems($user_id, $_GET['query']);
                    echo json_encode([
                        'success' => true,
                        'results' => $results
                    ]);
                    break;

                case 'security_check':
                    $security = new SecurityManager();
                    $score = $security->assessSecurityScore($user_id);
                    $duplicates = $security->findDuplicatePasswords($user_id);
                    $weakPasswords = $security->findWeakPasswords($user_id);

                    echo json_encode([
                        'success' => true,
                        'security_score' => $score,
                        'duplicate_passwords' => count($duplicates),
                        'weak_passwords' => count($weakPasswords)
                    ]);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
            break;

        case 'POST':
            if (!isset($input['item_name']) || !isset($input['item_type']) || !isset($input['data'])) {
                throw new Exception('Item name, type, and data are required');
            }

            $itemId = $vault->addItem(
                $user_id,
                $input['item_name'],
                $input['item_type'],
                $input['data'],
                $input['website_url'] ?? null,
                $input['folder_id'] ?? null
            );

            echo json_encode([
                'success' => true,
                'message' => 'Item added successfully',
                'item_id' => $itemId
            ]);
            break;

        case 'PUT':
            if (!isset($input['id'])) {
                throw new Exception('Item ID is required');
            }

            $result = $vault->updateItem(
                $input['id'],
                $user_id,
                $input['item_name'],
                $input['data'],
                $input['website_url'] ?? null
            );

            echo json_encode([
                'success' => true,
                'message' => 'Item updated successfully'
            ]);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('Item ID is required');
            }

            $result = $vault->deleteItem($_GET['id'], $user_id);

            echo json_encode([
                'success' => true,
                'message' => 'Item deleted successfully'
            ]);
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
