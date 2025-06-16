<?php
/**
 * VirusTotal API Helper
 * 
 * This class handles communication with the VirusTotal API
 * Provides methods for scanning files, URLs, and getting reports
 */

class VirusTotalHelper {
    private $config;
    private $apiKey;
    private $baseUrl;
    private $userAgent;
    private $demoMode;
    
    public function __construct() {
        $this->config = require_once __DIR__ . '/../config/virustotal_config.php';
        $this->apiKey = $this->config['API_KEY'];
        $this->baseUrl = $this->config['API_BASE_URL'];
        $this->userAgent = $this->config['USER_AGENT'];
        $this->demoMode = $this->config['DEMO_MODE'] || $this->apiKey === 'YOUR_VIRUSTOTAL_API_KEY_HERE';
    }
    
    /**
     * Scan a file for malware
     */    public function scanFile($filePath, $fileName) {
        if ($this->demoMode) {
            return $this->getDemoFileScanResult($fileName);
        }
        
        // Check file size
        $fileSize = filesize($filePath);
        if ($fileSize > $this->config['MAX_FILE_SIZE']) {
            throw new Exception('File size exceeds maximum limit of ' . $this->formatFileSize($this->config['MAX_FILE_SIZE']));
        }
        
        try {
            // Upload file for scanning
            $uploadResult = $this->uploadFile($filePath, $fileName);
            if (!$uploadResult || !isset($uploadResult['data']['id'])) {
                throw new Exception('Failed to upload file for scanning - invalid response from VirusTotal');
            }
            
            // Return immediate response
            return [
                'scan_id' => $uploadResult['data']['id'],
                'scan_date' => date('Y-m-d H:i:s'),
                'resource' => $fileName,
                'md5' => md5_file($filePath),
                'sha256' => hash_file('sha256', $filePath),
                'permalink' => 'https://www.virustotal.com/gui/file/' . hash_file('sha256', $filePath),
                'positives' => 0, // Will be updated when results are available
                'total' => 0,
                'message' => 'File uploaded for scanning. Results will be available shortly.'
            ];
            
        } catch (Exception $e) {
            // If there's a connection error, return a user-friendly error
            if (strpos($e->getMessage(), 'Timed out') !== false || 
                strpos($e->getMessage(), 'CURL Error') !== false) {
                throw new Exception('Unable to connect to VirusTotal API. Please check your internet connection and try again.');
            }
            throw $e;
        }
    }
      /**
     * Scan a URL for malware
     */    public function scanUrl($url) {
        if ($this->demoMode) {
            return $this->getDemoUrlScanResult($url);
        }
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL format');
        }
        
        if (strlen($url) > $this->config['MAX_URL_LENGTH']) {
            throw new Exception('URL exceeds maximum length');
        }
          try {
            // Submit URL for scanning
            $submitResult = $this->submitUrl($url);
            
            if (!$submitResult) {
                throw new Exception('No response from VirusTotal API');
            }
            
            // Debug the response structure
            error_log("VirusTotal URL scan response structure: " . json_encode($submitResult, JSON_PRETTY_PRINT));
            
            // Handle different response formats - the API returns the scan ID in data.id
            $scanId = null;
            if (isset($submitResult['data']['id'])) {
                $scanId = $submitResult['data']['id'];
            } elseif (isset($submitResult['id'])) {
                $scanId = $submitResult['id'];
            } else {
                error_log("VirusTotal URL submission response: " . json_encode($submitResult));
                throw new Exception('Unexpected response format from VirusTotal API');
            }
            
            // Get the URL ID for the permalink
            $urlId = $this->base64url_encode($url);
            
            // For immediate response, return the submission result
            return [
                'scan_id' => $scanId,
                'scan_date' => date('Y-m-d H:i:s'),
                'resource' => $url,
                'permalink' => 'https://www.virustotal.com/gui/url/' . $urlId,
                'positives' => 0, // Will be updated when results are available
                'total' => 0,
                'message' => 'URL submitted for scanning. Results will be available shortly.',
                'response_debug' => $submitResult // For debugging
            ];
            
        } catch (Exception $e) {
            error_log("VirusTotal URL scan error: " . $e->getMessage());
            
            // If there's a connection error, return a user-friendly error
            if (strpos($e->getMessage(), 'Timed out') !== false || 
                strpos($e->getMessage(), 'CURL Error') !== false) {
                throw new Exception('Unable to connect to VirusTotal API. Please check your internet connection and try again.');
            }
            throw $e;
        }
    }
    
    /**
     * Get file report by hash
     */
    public function getFileReport($hash) {
        if ($this->demoMode) {
            return $this->getDemoFileReport($hash);
        }
        
        $endpoint = str_replace('{id}', $hash, $this->config['ENDPOINTS']['FILE_REPORT']);
        $url = $this->baseUrl . $endpoint;
        
        return $this->makeRequest($url, 'GET');
    }
    
    /**
     * Get URL report
     */
    public function getUrlReport($urlId) {
        if ($this->demoMode) {
            return $this->getDemoUrlReport($urlId);
        }
        
        $endpoint = str_replace('{id}', $urlId, $this->config['ENDPOINTS']['URL_REPORT']);
        $url = $this->baseUrl . $endpoint;
        
        return $this->makeRequest($url, 'GET');
    }
    
    /**
     * Upload file for scanning
     */
    private function uploadFile($filePath, $fileName) {
        $url = $this->baseUrl . $this->config['ENDPOINTS']['FILE_SCAN'];
        
        $postData = [
            'file' => new CURLFile($filePath, mime_content_type($filePath), $fileName)
        ];
        
        return $this->makeRequest($url, 'POST', $postData, true);
    }
      /**
     * Submit URL for scanning
     */    private function submitUrl($url) {
        $endpoint = $this->baseUrl . '/urls';
        
        // VirusTotal v3 API expects form data for URL submission
        $postData = 'url=' . urlencode($url);
        
        $headers = [
            'x-apikey: ' . $this->apiKey,
            'User-Agent: ' . $this->userAgent,
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['SCAN_OPTIONS']['timeout']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output for debugging
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        // Enhanced debug logging
        error_log("VirusTotal URL submission Debug:");
        error_log("- URL: $url");
        error_log("- Endpoint: $endpoint");
        error_log("- HTTP Code: $httpCode");
        error_log("- CURL Error: " . ($error ?: 'None'));
        error_log("- Response Length: " . strlen($response));
        error_log("- Response: " . substr($response, 0, 500));
        
        if ($error) {
            throw new Exception('CURL Error: ' . $error);
        }
        
        if (empty($response)) {
            throw new Exception('Empty response from VirusTotal API - check API key and internet connection');
        }
        
        return $this->handleResponse($response, $httpCode);
    }
    
    /**
     * Wait for scan results
     */
    private function waitForScanResults($scanId, $type) {
        $maxWaitTime = $this->config['SCAN_OPTIONS']['max_wait_time'];
        $startTime = time();
        
        while ((time() - $startTime) < $maxWaitTime) {
            if ($type === 'file') {
                $result = $this->getFileReport($scanId);
            } else {
                $result = $this->getUrlReport($scanId);
            }
            
            if ($result && isset($result['data']['attributes']['stats'])) {
                return $result;
            }
            
            // Wait before next check
            sleep(5);
        }
        
        throw new Exception('Scan timeout - results not available within time limit');
    }
    
    /**
     * Make HTTP request to VirusTotal API
     */
    private function makeRequest($url, $method = 'GET', $data = null, $isFileUpload = false) {
        $headers = [
            'x-apikey: ' . $this->apiKey,
            'User-Agent: ' . $this->userAgent
        ];
        
        if (!$isFileUpload && $data && $method === 'POST') {
            $headers[] = 'Content-Type: application/json';
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['SCAN_OPTIONS']['timeout']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL Error: ' . $error);
        }
        
        return $this->handleResponse($response, $httpCode);
    }
    
    /**
     * Handle API response
     */    private function handleResponse($response, $httpCode) {
        // Check for empty response
        if (empty($response)) {
            throw new Exception('Empty response from VirusTotal API');
        }
        
        $decoded = json_decode($response, true);
        
        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("VirusTotal JSON decode error: " . json_last_error_msg() . " - Response: " . substr($response, 0, 500));
            throw new Exception('Invalid JSON response from VirusTotal API');
        }
        
        switch ($httpCode) {
            case 200:
            case 201:
                return $decoded;
            
            case 400:
                $errorMsg = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'Bad request - check your parameters';
                throw new Exception($errorMsg);
            
            case 401:
                throw new Exception('Unauthorized - invalid API key');
            
            case 403:
                throw new Exception('Forbidden - insufficient privileges or quota exceeded');
            
            case 404:
                return null; // Resource not found
            
            case 429:
                throw new Exception('Rate limit exceeded - please wait before making more requests');
            
            case 500:
                throw new Exception('Server error - please try again later');
            
            default:
                $errorMsg = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'Unknown error';
                throw new Exception('API Error (' . $httpCode . '): ' . $errorMsg);
        }
    }
    
    /**
     * Demo data for testing when no API key is configured
     */
    private function getDemoFileScanResult($fileName) {
        $isThreat = rand(0, 10) < 2; // 20% chance of threat
        $engines = rand(65, 75);
        $detections = $isThreat ? rand(1, 5) : 0;
        
        return [
            'data' => [
                'id' => md5($fileName . time()),
                'type' => 'analysis',
                'attributes' => [
                    'stats' => [
                        'harmless' => $engines - $detections,
                        'malicious' => $detections,
                        'suspicious' => 0,
                        'undetected' => 0,
                        'timeout' => 0
                    ],
                    'results' => $this->getDemoEngineResults($detections, $isThreat),
                    'date' => time(),
                    'status' => 'completed'
                ]
            ],
            'meta' => [
                'file_info' => [
                    'name' => $fileName,
                    'size' => rand(1024, 10485760),
                    'sha256' => hash('sha256', $fileName . time())
                ]
            ]
        ];
    }
    
    private function getDemoUrlScanResult($url) {
        $isThreat = rand(0, 10) < 3; // 30% chance of threat for URLs
        $engines = rand(65, 75);
        $detections = $isThreat ? rand(1, 8) : 0;
        
        return [
            'data' => [
                'id' => base64_encode($url),
                'type' => 'analysis',
                'attributes' => [
                    'stats' => [
                        'harmless' => $engines - $detections,
                        'malicious' => $detections,
                        'suspicious' => 0,
                        'undetected' => 0,
                        'timeout' => 0
                    ],
                    'results' => $this->getDemoEngineResults($detections, $isThreat, true),
                    'date' => time(),
                    'status' => 'completed',
                    'url' => $url
                ]
            ]
        ];
    }
    
    private function getDemoEngineResults($detections, $isThreat, $isUrl = false) {
        $engines = ['Kaspersky', 'Avast', 'Bitdefender', 'ESET-NOD32', 'McAfee', 'Symantec', 'Sophos', 'Trend Micro'];
        $results = [];
        
        $threatTypes = $isUrl ? ['phishing', 'malware', 'suspicious'] : ['trojan', 'virus', 'malware', 'pup'];
        
        foreach ($engines as $engine) {
            if ($detections > 0 && $isThreat) {
                $results[$engine] = [
                    'category' => 'malicious',
                    'result' => $threatTypes[array_rand($threatTypes)],
                    'method' => 'blacklist',
                    'engine_name' => $engine
                ];
                $detections--;
            } else {
                $results[$engine] = [
                    'category' => 'harmless',
                    'result' => null,
                    'method' => 'blacklist',
                    'engine_name' => $engine
                ];
            }
        }
        
        return $results;
    }
    
    private function getDemoFileReport($hash) {
        return $this->getDemoFileScanResult('file_' . substr($hash, 0, 8));
    }
    
    private function getDemoUrlReport($urlId) {
        $url = base64_decode($urlId);
        return $this->getDemoUrlScanResult($url ?: 'https://example.com');
    }
    
    /**
     * Check if demo mode is enabled
     */
    public function isDemoMode() {
        return $this->demoMode;
    }
    
    /**
     * Get configuration status
     */
    public function getConfigStatus() {
        return [
            'demo_mode' => $this->demoMode,
            'api_key_configured' => $this->apiKey !== 'YOUR_VIRUSTOTAL_API_KEY_HERE',
            'rate_limit' => $this->config['RATE_LIMIT_RPM'],
            'max_file_size' => $this->config['MAX_FILE_SIZE']
        ];
    }
    
    /**
     * Format file size
     */
    public function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Base64 URL encode
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
