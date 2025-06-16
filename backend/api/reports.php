<?php
/**
 * Reports and Security Analysis API Endpoints
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../classes/ReportManager.php';
require_once '../classes/SecurityManager.php';

session_start();

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
$reportManager = new ReportManager();
$securityManager = new SecurityManager();
$user_id = $_SESSION['user_id'];

try {
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? '';

            switch ($action) {
                case 'security_report':
                    $report = $reportManager->generateSecurityReport($user_id);

                    echo json_encode([
                        'success' => true,
                        'report' => $report
                    ]);
                    break;

                case 'breach_report':
                    $report = $reportManager->generateBreachReport($user_id);

                    echo json_encode([
                        'success' => true,
                        'report' => $report
                    ]);
                    break;

                case 'password_strength_report':
                    $report = $reportManager->generatePasswordStrengthReport($user_id);

                    echo json_encode([
                        'success' => true,
                        'report' => $report
                    ]);
                    break;

                case 'activity_report':
                    $days = (int)($_GET['days'] ?? 30);
                    $report = $reportManager->generateActivityReport($user_id, $days);

                    echo json_encode([
                        'success' => true,
                        'report' => $report
                    ]);
                    break;

                case 'export_vault':
                    $format = $_GET['format'] ?? 'json';
                    $includePasswords = ($_GET['include_passwords'] ?? 'false') === 'true';
                    
                    $exportData = $reportManager->exportVault($user_id, $format, $includePasswords);

                    if ($format === 'json') {
                        echo $exportData;
                    } else {
                        // Set appropriate headers for file download
                        $filename = 'secureit_vault_export_' . date('Y-m-d') . '.' . $format;
                        header('Content-Disposition: attachment; filename="' . $filename . '"');
                        
                        if ($format === 'csv') {
                            header('Content-Type: text/csv');
                        } elseif ($format === 'xml') {
                            header('Content-Type: application/xml');
                        }
                        
                        echo $exportData;
                    }
                    break;

                case 'saved_reports':
                    $reportType = $_GET['type'] ?? null;
                    $limit = (int)($_GET['limit'] ?? 10);
                    
                    $reports = $reportManager->getSavedReports($user_id, $reportType, $limit);

                    echo json_encode([
                        'success' => true,
                        'reports' => $reports
                    ]);
                    break;

                case 'duplicate_passwords':
                    $duplicates = $securityManager->findDuplicatePasswords($user_id);

                    echo json_encode([
                        'success' => true,
                        'duplicates' => $duplicates
                    ]);
                    break;

                case 'weak_passwords':
                    $threshold = (int)($_GET['threshold'] ?? 50);
                    $weakPasswords = $securityManager->findWeakPasswords($user_id, $threshold);

                    echo json_encode([
                        'success' => true,
                        'weak_passwords' => $weakPasswords
                    ]);
                    break;

                case 'old_passwords':
                    $months = (int)($_GET['months'] ?? 6);
                    $oldPasswords = $securityManager->findOldPasswords($user_id, $months);

                    echo json_encode([
                        'success' => true,
                        'old_passwords' => $oldPasswords
                    ]);
                    break;

                default:
                    throw new Exception('Invalid action');
            }
            break;

        case 'POST':
            $action = $input['action'] ?? '';

            switch ($action) {
                case 'check_password_breach':
                    if (!isset($input['password'])) {
                        throw new Exception('Password is required');
                    }

                    $result = $securityManager->checkPasswordBreach($input['password']);

                    echo json_encode([
                        'success' => true,
                        'breach_result' => $result
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
