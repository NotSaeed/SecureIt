<?php
/**
 * Authentication API Endpoints
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

require_once '../classes/User.php';
require_once '../classes/Authenticator.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'POST':
            if (!isset($input['action'])) {
                throw new Exception('Action not specified');
            }

            switch ($input['action']) {
                case 'register':
                    if (!isset($input['email']) || !isset($input['password'])) {
                        throw new Exception('Email and password are required');
                    }

                    $user = new User();
                    $newUser = $user->create($input['email'], $input['password'], $input['name'] ?? null);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'User registered successfully',
                        'user' => [
                            'id' => $newUser->id,
                            'email' => $newUser->email,
                            'name' => $newUser->name
                        ]
                    ]);
                    break;

                case 'login':
                    if (!isset($input['email']) || !isset($input['password'])) {
                        throw new Exception('Email and password are required');
                    }

                    $user = new User();
                    $authenticatedUser = $user->authenticate($input['email'], $input['password']);
                    
                    if (!$authenticatedUser) {
                        throw new Exception('Invalid credentials');
                    }

                    // Check if 2FA is enabled
                    if ($authenticatedUser->two_factor_enabled) {
                        if (!isset($input['totp_code'])) {
                            echo json_encode([
                                'success' => false,
                                'requires_2fa' => true,
                                'message' => 'Two-factor authentication code required'
                            ]);
                            break;
                        }

                        $auth = new Authenticator();
                        if (!$auth->verifyUser2FA($authenticatedUser->id, $input['totp_code'])) {
                            throw new Exception('Invalid two-factor authentication code');
                        }
                    }
                    
                    $_SESSION['user_id'] = $authenticatedUser->id;
                    $_SESSION['user_email'] = $authenticatedUser->email;
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful',
                        'user' => [
                            'id' => $authenticatedUser->id,
                            'email' => $authenticatedUser->email,
                            'name' => $authenticatedUser->name,
                            'two_factor_enabled' => $authenticatedUser->two_factor_enabled
                        ]
                    ]);
                    break;

                case 'logout':
                    session_destroy();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Logged out successfully'
                    ]);
                    break;

                case 'setup_2fa':
                    if (!isset($_SESSION['user_id'])) {
                        throw new Exception('User not authenticated');
                    }

                    $auth = new Authenticator();
                    $secret = $auth->generateSecret();
                    $qrCodeData = $auth->generateQRCodeData($_SESSION['user_email'], $secret);

                    echo json_encode([
                        'success' => true,
                        'secret' => $secret,
                        'qr_code_url' => $qrCodeData
                    ]);
                    break;

                case 'enable_2fa':
                    if (!isset($_SESSION['user_id'])) {
                        throw new Exception('User not authenticated');
                    }

                    if (!isset($input['secret']) || !isset($input['verification_code'])) {
                        throw new Exception('Secret and verification code are required');
                    }

                    $auth = new Authenticator();
                    $auth->enable2FA($_SESSION['user_id'], $input['secret'], $input['verification_code']);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Two-factor authentication enabled successfully'
                    ]);
                    break;

                case 'disable_2fa':
                    if (!isset($_SESSION['user_id'])) {
                        throw new Exception('User not authenticated');
                    }

                    if (!isset($input['verification_code'])) {
                        throw new Exception('Verification code is required');
                    }

                    $auth = new Authenticator();
                    $auth->disable2FA($_SESSION['user_id'], $input['verification_code']);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Two-factor authentication disabled successfully'
                    ]);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
            break;

        case 'GET':
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('User not authenticated');
            }

            $action = $_GET['action'] ?? '';

            switch ($action) {
                case 'profile':
                    $user = new User();
                    $userProfile = $user->findById($_SESSION['user_id']);

                    echo json_encode([
                        'success' => true,
                        'user' => [
                            'id' => $userProfile->id,
                            'email' => $userProfile->email,
                            'name' => $userProfile->name,
                            'two_factor_enabled' => $userProfile->two_factor_enabled,
                            'security_score' => $userProfile->security_score,
                            'created_at' => $userProfile->created_at,
                            'last_login' => $userProfile->last_login
                        ]
                    ]);
                    break;

                case '2fa_status':
                    $auth = new Authenticator();
                    $status = $auth->get2FAStatus($_SESSION['user_id']);

                    echo json_encode([
                        'success' => true,
                        '2fa_status' => $status
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
