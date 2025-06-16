<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\user_api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/SecurityManager.php';

try {
    $user = new User();
    $security = new SecurityManager();
    $action = $_REQUEST['action'] ?? '';

    switch($action) {
        case 'create':
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'user';

            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception('Username, email, and password are required');
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }

            // Validate password strength
            if (strlen($password) < 8) {
                throw new Exception('Password must be at least 8 characters long');
            }

            // In real implementation: $result = $user->register($username, $email, $password, $role);
            
            echo json_encode([
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => rand(1000, 9999), // Demo ID
                'username' => $username,
                'email' => $email,
                'role' => $role
            ]);
            break;

        case 'getAll':
            // Demo data - in real implementation, fetch from database
            $demoUsers = [
                [
                    'id' => 1001,
                    'username' => 'admin',
                    'email' => 'admin@secureit.com',
                    'role' => 'admin',
                    'status' => 'active',
                    'created_at' => '2024-01-10 09:00:00',
                    'last_login' => '2024-01-20 14:30:00'
                ],
                [
                    'id' => 1002,
                    'username' => 'johndoe',
                    'email' => 'john@example.com',
                    'role' => 'premium',
                    'status' => 'active',
                    'created_at' => '2024-01-12 10:15:00',
                    'last_login' => '2024-01-19 16:45:00'
                ],
                [
                    'id' => 1003,
                    'username' => 'janedoe',
                    'email' => 'jane@example.com',
                    'role' => 'user',
                    'status' => 'active',
                    'created_at' => '2024-01-15 14:20:00',
                    'last_login' => '2024-01-18 11:30:00'
                ],
                [
                    'id' => 1004,
                    'username' => 'testuser',
                    'email' => 'test@example.com',
                    'role' => 'user',
                    'status' => 'inactive',
                    'created_at' => '2024-01-08 16:45:00',
                    'last_login' => null
                ],
                [
                    'id' => 1005,
                    'username' => 'newuser',
                    'email' => 'new@example.com',
                    'role' => 'user',
                    'status' => 'pending',
                    'created_at' => '2024-01-20 12:00:00',
                    'last_login' => null
                ]
            ];

            echo json_encode([
                'success' => true,
                'users' => $demoUsers,
                'count' => count($demoUsers)
            ]);
            break;

        case 'search':
            $query = $_GET['query'] ?? '';
            $role = $_GET['role'] ?? '';
            $status = $_GET['status'] ?? '';

            // Demo search functionality
            $allUsers = [
                [
                    'id' => 1001,
                    'username' => 'admin',
                    'email' => 'admin@secureit.com',
                    'role' => 'admin',
                    'status' => 'active',
                    'created_at' => '2024-01-10 09:00:00',
                    'last_login' => '2024-01-20 14:30:00'
                ],
                [
                    'id' => 1002,
                    'username' => 'johndoe',
                    'email' => 'john@example.com',
                    'role' => 'premium',
                    'status' => 'active',
                    'created_at' => '2024-01-12 10:15:00',
                    'last_login' => '2024-01-19 16:45:00'
                ],
                [
                    'id' => 1003,
                    'username' => 'janedoe',
                    'email' => 'jane@example.com',
                    'role' => 'user',
                    'status' => 'active',
                    'created_at' => '2024-01-15 14:20:00',
                    'last_login' => '2024-01-18 11:30:00'
                ]
            ];

            $filteredUsers = array_filter($allUsers, function($user) use ($query, $role, $status) {
                $matchesQuery = empty($query) || 
                    stripos($user['username'], $query) !== false ||
                    stripos($user['email'], $query) !== false ||
                    strpos($user['id'], $query) !== false;
                
                $matchesRole = empty($role) || $user['role'] === $role;
                $matchesStatus = empty($status) || $user['status'] === $status;
                
                return $matchesQuery && $matchesRole && $matchesStatus;
            });

            echo json_encode([
                'success' => true,
                'users' => array_values($filteredUsers),
                'count' => count($filteredUsers)
            ]);
            break;

        case 'resetPassword':
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                throw new Exception('User ID is required');
            }

            // In real implementation: generate new password and send email
            $newPassword = bin2hex(random_bytes(8));
            
            echo json_encode([
                'success' => true,
                'message' => 'Password reset successfully',
                'new_password' => $newPassword, // In production, this would be sent via email
                'user_id' => $id
            ]);
            break;

        case 'toggleStatus':
            $id = $_POST['id'] ?? '';
            $status = $_POST['status'] ?? '';
            
            if (empty($id) || empty($status)) {
                throw new Exception('User ID and status are required');
            }

            if (!in_array($status, ['active', 'inactive', 'pending'])) {
                throw new Exception('Invalid status value');
            }

            // In real implementation: update user status in database
            
            echo json_encode([
                'success' => true,
                'message' => 'User status updated successfully',
                'user_id' => $id,
                'new_status' => $status
            ]);
            break;

        case 'delete':
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                throw new Exception('User ID is required');
            }

            // In real implementation: soft delete or hard delete user
            
            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully',
                'deleted_id' => $id
            ]);
            break;

        case 'stats':
            // Demo statistics
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total' => 156,
                    'active' => 142,
                    'inactive' => 12,
                    'pending' => 2,
                    'new_this_month' => 23,
                    'premium' => 45,
                    'admin' => 3,
                    'last_updated' => date('Y-m-d H:i:s')
                ]
            ]);
            break;

        case 'export':
            $exportData = [
                'exported_at' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'total_users' => 156,
                'users' => [
                    [
                        'id' => 1001,
                        'username' => 'admin',
                        'email' => 'admin@secureit.com',
                        'role' => 'admin',
                        'status' => 'active',
                        'created_at' => '2024-01-10 09:00:00',
                        'last_login' => '2024-01-20 14:30:00'
                    ],
                    [
                        'id' => 1002,
                        'username' => 'johndoe',
                        'email' => 'john@example.com',
                        'role' => 'premium',
                        'status' => 'active',
                        'created_at' => '2024-01-12 10:15:00',
                        'last_login' => '2024-01-19 16:45:00'
                    ]
                ]
            ];

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="secureit_users_export.json"');
            echo json_encode($exportData, JSON_PRETTY_PRINT);
            exit;

        case 'getUserById':
            $id = $_GET['id'] ?? '';
            
            if (empty($id)) {
                throw new Exception('User ID is required');
            }

            // Demo user retrieval
            $demoUser = [
                'id' => $id,
                'username' => 'johndoe',
                'email' => 'john@example.com',
                'role' => 'premium',
                'status' => 'active',
                'created_at' => '2024-01-12 10:15:00',
                'last_login' => '2024-01-19 16:45:00',
                'profile' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'phone' => '+1234567890',
                    'country' => 'United States'
                ],
                'settings' => [
                    'two_factor_enabled' => true,
                    'email_notifications' => true,
                    'login_notifications' => false
                ]
            ];

            echo json_encode([
                'success' => true,
                'user' => $demoUser
            ]);
            break;

        case 'updateProfile':
            $id = $_POST['id'] ?? '';
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $country = $_POST['country'] ?? '';
            
            if (empty($id)) {
                throw new Exception('User ID is required');
            }

            // In real implementation: update user profile in database
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user_id' => $id
            ]);
            break;

        case 'getLoginHistory':
            $id = $_GET['id'] ?? '';
            
            if (empty($id)) {
                throw new Exception('User ID is required');
            }

            // Demo login history
            $loginHistory = [
                [
                    'timestamp' => '2024-01-20 14:30:00',
                    'ip_address' => '192.168.1.100',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'location' => 'New York, USA',
                    'status' => 'success'
                ],
                [
                    'timestamp' => '2024-01-19 16:45:00',
                    'ip_address' => '192.168.1.100',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'location' => 'New York, USA',
                    'status' => 'success'
                ],
                [
                    'timestamp' => '2024-01-18 11:30:00',
                    'ip_address' => '10.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
                    'location' => 'New York, USA',
                    'status' => 'failed'
                ]
            ];

            echo json_encode([
                'success' => true,
                'login_history' => $loginHistory,
                'count' => count($loginHistory)
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
