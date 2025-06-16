<?php
/**
 * Password Generator API Endpoints
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../classes/PasswordGenerator.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$generator = new PasswordGenerator();

try {
    switch ($method) {
        case 'POST':
            $action = $input['action'] ?? '';

            switch ($action) {
                case 'generate_password':
                    $length = $input['length'] ?? 14;
                    $options = $input['options'] ?? [];
                    
                    $password = $generator->generatePassword($length, $options);
                    
                    // Save to history if user is logged in
                    if (isset($_SESSION['user_id'])) {
                        $generator->saveToHistory($_SESSION['user_id'], 'password', $password, $options);
                    }

                    echo json_encode([
                        'success' => true,
                        'password' => $password
                    ]);
                    break;

                case 'generate_passphrase':
                    $wordCount = $input['word_count'] ?? 6;
                    $separator = $input['separator'] ?? '-';
                    $capitalize = $input['capitalize'] ?? false;
                    $includeNumber = $input['include_number'] ?? false;
                    
                    $passphrase = $generator->generatePassphrase($wordCount, $separator, $capitalize, $includeNumber);
                    
                    if (isset($_SESSION['user_id'])) {
                        $generator->saveToHistory($_SESSION['user_id'], 'passphrase', $passphrase, [
                            'word_count' => $wordCount,
                            'separator' => $separator,
                            'capitalize' => $capitalize,
                            'include_number' => $includeNumber
                        ]);
                    }

                    echo json_encode([
                        'success' => true,
                        'passphrase' => $passphrase
                    ]);
                    break;

                case 'generate_username':
                    $type = $input['type'] ?? 'random_word';
                    $capitalize = $input['capitalize'] ?? false;
                    $includeNumber = $input['include_number'] ?? false;
                    
                    $username = $generator->generateUsername($type, $capitalize, $includeNumber);
                    
                    if (isset($_SESSION['user_id'])) {
                        $generator->saveToHistory($_SESSION['user_id'], 'username', $username, [
                            'type' => $type,
                            'capitalize' => $capitalize,
                            'include_number' => $includeNumber
                        ]);
                    }

                    echo json_encode([
                        'success' => true,
                        'username' => $username
                    ]);
                    break;

                case 'check_strength':
                    if (!isset($input['password'])) {
                        throw new Exception('Password is required for strength check');
                    }

                    $strength = $generator->estimatePasswordStrength($input['password']);

                    echo json_encode([
                        'success' => true,
                        'strength' => $strength
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
                case 'history':
                    $type = $_GET['type'] ?? null;
                    $limit = (int)($_GET['limit'] ?? 10);
                    
                    $history = $generator->getHistory($_SESSION['user_id'], $type, $limit);

                    echo json_encode([
                        'success' => true,
                        'history' => $history
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
