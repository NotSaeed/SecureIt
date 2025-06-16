<?php
// Clean output buffer and prevent any unwanted output
ob_start();

// Suppress all output except fatal errors
error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR);
ini_set('display_errors', 0);

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any buffered output that might have been generated
ob_clean();

// Include required files
require_once 'config/database.php';
require_once 'classes/VirusTotalHelper.php';

// Clear buffer again after includes
ob_clean();

// Set JSON response header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $virustotal = new VirusTotalHelper();
    $action = $_POST['action'] ?? '';
    
    // Debug logging
    error_log("VirusTotal API called with action: " . $action);
    
    switch ($action) {case 'check_config':
            // Check if VirusTotal is configured
            $status = $virustotal->getConfigStatus();
            ob_clean();
            echo json_encode([
                'configured' => $status['api_key_configured'],
                'demo_mode' => $status['demo_mode'],
                'rate_limit' => $status['rate_limit']
            ]);
            break;
              case 'scan_url':
            $url = $_POST['url'] ?? '';
            if (empty($url)) {
                throw new Exception('URL is required');
            }
            
            // Validate URL format
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception('Invalid URL format');
            }
            
            // Debug log
            error_log("VirusTotal API: Scanning URL: $url");
            
            $result = $virustotal->scanUrl($url);
            
            // Debug log the result
            error_log("VirusTotal API: URL scan result: " . json_encode($result));
            
            ob_clean();
            echo json_encode($result);
            break;
            
        case 'scan_file':
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded or upload error');
            }
            
            $file = $_FILES['file'];
            
            // Check file size (max 32MB for VirusTotal free API)
            if ($file['size'] > 32 * 1024 * 1024) {
                throw new Exception('File too large. Maximum size is 32MB');
            }
            
            $result = $virustotal->scanFile($file['tmp_name'], $file['name']);
            ob_clean();
            echo json_encode($result);
            break;
            
        case 'get_report':
            $resource_id = $_POST['resource_id'] ?? '';
            if (empty($resource_id)) {
                throw new Exception('Resource ID is required');
            }
            
            $result = $virustotal->getReport($resource_id);
            ob_clean();
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    ob_clean();
    error_log("VirusTotal API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

// Ensure clean exit
ob_end_flush();
exit;
?>
