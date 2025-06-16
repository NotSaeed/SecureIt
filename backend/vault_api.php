<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\vault_api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'classes/Database.php';
require_once 'classes/Vault.php';
require_once 'classes/EncryptionHelper.php';

try {
    $vault = new Vault();
    $encryption = new EncryptionHelper();
    $action = $_REQUEST['action'] ?? '';

    switch($action) {
        case 'add':
            $type = $_POST['type'] ?? '';
            $name = $_POST['name'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $url = $_POST['url'] ?? '';
            $notes = $_POST['notes'] ?? '';

            if (empty($type) || empty($name)) {
                throw new Exception('Type and name are required');
            }

            // Create sample data structure for demonstration
            $itemData = [
                'type' => $type,
                'name' => $name,
                'username' => $username,
                'password' => $password,
                'url' => $url,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s'),
                'id' => rand(1000, 9999) // Demo ID
            ];

            // In a real implementation, this would be saved to database
            // $result = $vault->addItem($userId, $itemData);

            echo json_encode([
                'success' => true,
                'message' => 'Vault item added successfully',
                'item_id' => $itemData['id']
            ]);
            break;

        case 'getAll':
            // Demo data - in real implementation, fetch from database
            $demoItems = [
                [
                    'id' => 1001,
                    'type' => 'password',
                    'name' => 'Gmail Account',
                    'username' => 'user@gmail.com',
                    'url' => 'https://gmail.com',
                    'notes' => 'Primary email account',
                    'created_at' => '2024-01-15 10:30:00'
                ],
                [
                    'id' => 1002,
                    'type' => 'password',
                    'name' => 'Facebook',
                    'username' => 'johndoe',
                    'url' => 'https://facebook.com',
                    'notes' => 'Social media account',
                    'created_at' => '2024-01-16 14:20:00'
                ],
                [
                    'id' => 1003,
                    'type' => 'card',
                    'name' => 'Main Credit Card',
                    'username' => 'John Doe',
                    'url' => '',
                    'notes' => 'Visa ending in 1234',
                    'created_at' => '2024-01-17 09:15:00'
                ],
                [
                    'id' => 1004,
                    'type' => 'note',
                    'name' => 'WiFi Password',
                    'username' => '',
                    'url' => '',
                    'notes' => 'Home network: MyNetwork123!',
                    'created_at' => '2024-01-18 16:45:00'
                ],
                [
                    'id' => 1005,
                    'type' => 'identity',
                    'name' => 'Personal Identity',
                    'username' => 'John Doe',
                    'url' => '',
                    'notes' => 'Driver license, SSN, passport info',
                    'created_at' => '2024-01-19 11:30:00'
                ]
            ];

            echo json_encode([
                'success' => true,
                'items' => $demoItems,
                'count' => count($demoItems)
            ]);
            break;

        case 'search':
            $query = $_GET['query'] ?? '';
            $type = $_GET['type'] ?? '';

            // Demo search functionality
            $allItems = [
                [
                    'id' => 1001,
                    'type' => 'password',
                    'name' => 'Gmail Account',
                    'username' => 'user@gmail.com',
                    'url' => 'https://gmail.com',
                    'notes' => 'Primary email account',
                    'created_at' => '2024-01-15 10:30:00'
                ],
                [
                    'id' => 1002,
                    'type' => 'password',
                    'name' => 'Facebook',
                    'username' => 'johndoe',
                    'url' => 'https://facebook.com',
                    'notes' => 'Social media account',
                    'created_at' => '2024-01-16 14:20:00'
                ],
                [
                    'id' => 1003,
                    'type' => 'card',
                    'name' => 'Main Credit Card',
                    'username' => 'John Doe',
                    'url' => '',
                    'notes' => 'Visa ending in 1234',
                    'created_at' => '2024-01-17 09:15:00'
                ]
            ];

            $filteredItems = array_filter($allItems, function($item) use ($query, $type) {
                $matchesQuery = empty($query) || 
                    stripos($item['name'], $query) !== false ||
                    stripos($item['username'], $query) !== false ||
                    stripos($item['url'], $query) !== false;
                
                $matchesType = empty($type) || $item['type'] === $type;
                
                return $matchesQuery && $matchesType;
            });

            echo json_encode([
                'success' => true,
                'items' => array_values($filteredItems),
                'count' => count($filteredItems)
            ]);
            break;

        case 'getPassword':
            $id = $_GET['id'] ?? '';
            
            if (empty($id)) {
                throw new Exception('Item ID is required');
            }

            // Demo password retrieval - in real implementation, decrypt from database
            $demoPasswords = [
                '1001' => 'gmail_secure_pass_123!',
                '1002' => 'facebook_password_456#',
                '1003' => '****-****-****-1234',
                '1004' => 'MyNetwork123!',
                '1005' => 'identity_data_encrypted'
            ];

            $password = $demoPasswords[$id] ?? null;
            
            if ($password) {
                echo json_encode([
                    'success' => true,
                    'password' => $password
                ]);
            } else {
                throw new Exception('Password not found');
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                throw new Exception('Item ID is required');
            }

            // In real implementation: $result = $vault->deleteItem($userId, $id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Item deleted successfully',
                'deleted_id' => $id
            ]);
            break;

        case 'export':
            // Demo export functionality
            $exportData = [
                'exported_at' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'items' => [
                    [
                        'type' => 'password',
                        'name' => 'Gmail Account',
                        'username' => 'user@gmail.com',
                        'password' => 'gmail_secure_pass_123!',
                        'url' => 'https://gmail.com',
                        'notes' => 'Primary email account'
                    ],
                    [
                        'type' => 'password',
                        'name' => 'Facebook',
                        'username' => 'johndoe',
                        'password' => 'facebook_password_456#',
                        'url' => 'https://facebook.com',
                        'notes' => 'Social media account'
                    ]
                ]
            ];

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="secureit_vault_export.json"');
            echo json_encode($exportData, JSON_PRETTY_PRINT);
            exit;

        case 'import':
            if (!isset($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }

            $file = $_FILES['file'];
            $fileContent = file_get_contents($file['tmp_name']);
            $importData = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON file');
            }

            $importedCount = 0;
            if (isset($importData['items']) && is_array($importData['items'])) {
                $importedCount = count($importData['items']);
                // In real implementation: process and save each item
            }

            echo json_encode([
                'success' => true,
                'message' => 'Import completed successfully',
                'imported_count' => $importedCount
            ]);
            break;

        case 'stats':
            // Demo statistics
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_items' => 25,
                    'passwords' => 18,
                    'notes' => 4,
                    'cards' => 2,
                    'identities' => 1,
                    'last_updated' => date('Y-m-d H:i:s')
                ]
            ]);
            break;

        default:
            throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
