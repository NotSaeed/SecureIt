<?php
/**
 * VirusTotal API Endpoint
 * Handles AJAX requests for VirusTotal scanning
 */

session_start();
require_once __DIR__ . '/../classes/VirusTotalAPI.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$vtApi = new VirusTotalAPI();

try {
    switch ($action) {
        case 'scan_file':
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded or upload error occurred');
            }
            
            $uploadedFile = $_FILES['file'];
            $tempPath = $uploadedFile['tmp_name'];
            
            // Validate file size (32MB limit)
            if ($uploadedFile['size'] > 33554432) {
                throw new Exception('File too large. Maximum size is 32MB.');
            }
            
            $result = $vtApi->scanFile($tempPath);
            echo json_encode($result);
            break;
            
        case 'scan_url':
            $url = $_POST['url'] ?? '';
            if (empty($url)) {
                throw new Exception('URL is required');
            }
            
            $result = $vtApi->scanUrl($url);
            echo json_encode($result);
            break;
            
        case 'get_file_report':
            $resource = $_POST['resource'] ?? '';
            if (empty($resource)) {
                throw new Exception('Resource hash is required');
            }
            
            $result = $vtApi->getFileReport($resource);
            echo json_encode($result);
            break;
            
        case 'get_url_report':
            $url = $_POST['url'] ?? '';
            if (empty($url)) {
                throw new Exception('URL is required');
            }
            
            $result = $vtApi->getUrlReport($url);
            echo json_encode($result);
            break;
            
        case 'validate_api':
            $isValid = $vtApi->validateApiKey();
            echo json_encode([
                'success' => true,
                'valid' => $isValid,
                'message' => $isValid ? 'API key is valid' : 'API key is invalid'
            ]);
            break;
            
        case 'get_stats':
            $stats = $vtApi->getApiStats();
            echo json_encode($stats);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
