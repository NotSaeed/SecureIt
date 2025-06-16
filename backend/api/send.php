<?php
/**
 * Send Management API Endpoints
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../classes/SendManager.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$sendManager = new SendManager();

try {
    switch ($method) {
        case 'POST':
            $action = $input['action'] ?? 'create';

            switch ($action) {
                case 'create':
                    if (!isset($_SESSION['user_id'])) {
                        throw new Exception('User not authenticated');
                    }

                    if (!isset($input['type']) || !isset($input['name']) || !isset($input['content'])) {
                        throw new Exception('Type, name, and content are required');
                    }

                    $options = [
                        'deletion_date' => $input['deletion_date'] ?? date('Y-m-d H:i:s', strtotime('+7 days')),
                        'password' => $input['password'] ?? null,
                        'max_views' => $input['max_views'] ?? null,
                        'hide_email' => $input['hide_email'] ?? false
                    ];

                    $result = $sendManager->createSend(
                        $_SESSION['user_id'],
                        $input['type'],
                        $input['name'],
                        $input['content'],
                        $options
                    );

                    echo json_encode([
                        'success' => true,
                        'message' => 'Send created successfully',
                        'send' => $result
                    ]);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
            break;

        case 'GET':
            $action = $_GET['action'] ?? '';

            switch ($action) {
                case 'list':
                    if (!isset($_SESSION['user_id'])) {
                        throw new Exception('User not authenticated');
                    }

                    $sends = $sendManager->getUserSends($_SESSION['user_id']);

                    echo json_encode([
                        'success' => true,
                        'sends' => $sends
                    ]);
                    break;

                case 'stats':
                    if (!isset($_SESSION['user_id'])) {
                        throw new Exception('User not authenticated');
                    }

                    $stats = $sendManager->getSendStats($_SESSION['user_id']);

                    echo json_encode([
                        'success' => true,
                        'stats' => $stats
                    ]);
                    break;

                case 'retrieve':
                    if (!isset($_GET['link'])) {
                        throw new Exception('Access link is required');
                    }

                    $password = $_GET['password'] ?? null;
                    $send = $sendManager->getSend($_GET['link'], $password);

                    echo json_encode([
                        'success' => true,
                        'send' => $send
                    ]);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
            break;

        case 'DELETE':
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('User not authenticated');
            }

            if (!isset($_GET['id'])) {
                throw new Exception('Send ID is required');
            }

            $result = $sendManager->deleteSend($_GET['id'], $_SESSION['user_id']);

            echo json_encode([
                'success' => true,
                'message' => 'Send deleted successfully'
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
