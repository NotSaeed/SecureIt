<?php
/**
 * VirusTotal API Integration Class
 * Provides file and URL scanning capabilities using VirusTotal's API
 */

class VirusTotalAPI {
    private $apiKey;
    private $baseUrl;
    
    public function __construct() {
        // Your VirusTotal API key
        $this->apiKey = 'bae9cb80fc9024f29f95b6f372d14eb631371b1fa45a71ba4af31b9ae74fd699';
        $this->baseUrl = 'https://www.virustotal.com/vtapi/v2/';
    }
    
    /**
     * Scan a file for malware
     * @param string $filePath Path to the file to scan
     * @return array Scan results
     */
    public function scanFile($filePath) {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("File not found: " . $filePath);
            }
            
            if (filesize($filePath) > 33554432) { // 32MB limit
                throw new Exception("File too large. Maximum size is 32MB.");
            }
            
            // Upload file for scanning
            $url = $this->baseUrl . 'file/scan';
            
            $postData = [
                'apikey' => $this->apiKey,
                'file' => new CURLFile($filePath)
            ];
            
            $response = $this->makeRequest($url, $postData, true);
            
            if ($response && isset($response['scan_id'])) {
                // Wait a moment then get results
                sleep(15); // VirusTotal recommends waiting 15 seconds minimum
                return $this->getFileReport($response['resource']);
            }
            
            throw new Exception("Failed to upload file for scanning");
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get file scan report
     * @param string $resource File hash or scan ID
     * @return array Report results
     */
    public function getFileReport($resource) {
        try {
            $url = $this->baseUrl . 'file/report';
            
            $params = [
                'apikey' => $this->apiKey,
                'resource' => $resource
            ];
            
            $response = $this->makeRequest($url . '?' . http_build_query($params));
              if ($response && $response['response_code'] == 1) {
                return [
                    'success' => true,
                    'scan_date' => $response['scan_date'],
                    'positives' => $response['positives'],
                    'total' => $response['total'],
                    'permalink' => $response['permalink'],
                    'scans' => $response['scans'],
                    'threat_level' => $this->calculateThreatLevel($response['positives'], $response['total']),
                    'scan_details' => $this->formatScanDetails($response['scans']),
                    'md5' => $response['md5'] ?? '',
                    'sha1' => $response['sha1'] ?? '',
                    'sha256' => $response['sha256'] ?? '',
                    'verbose_msg' => $response['verbose_msg'] ?? 'Scan completed',
                    'resource' => $response['resource'] ?? $hash
                ];
            } else if ($response['response_code'] == 0) {
                return [
                    'success' => false,
                    'error' => 'File not found in VirusTotal database',
                    'queued' => true
                ];
            } else if ($response['response_code'] == -2) {
                return [
                    'success' => false,
                    'error' => 'File is still being analyzed',
                    'queued' => true
                ];
            }
            
            throw new Exception("Unknown response from VirusTotal");
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Scan a URL for threats
     * @param string $url URL to scan
     * @return array Scan results
     */
    public function scanUrl($url) {
        try {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception("Invalid URL format");
            }
            
            // Submit URL for scanning
            $scanUrl = $this->baseUrl . 'url/scan';
            
            $postData = [
                'apikey' => $this->apiKey,
                'url' => $url
            ];
            
            $response = $this->makeRequest($scanUrl, $postData);
            
            if ($response && isset($response['scan_id'])) {
                // Wait a moment then get results
                sleep(10); // URLs typically process faster than files
                return $this->getUrlReport($url);
            }
              throw new Exception("Failed to submit URL for scanning");
            
        } catch (Exception $e) {
            // If VirusTotal fails, use fallback analyzer
            if (strpos($e->getMessage(), 'quota exceeded') !== false || 
                strpos($e->getMessage(), 'rate limit') !== false ||
                strpos($e->getMessage(), '403') !== false) {
                
                return $this->fallbackUrlAnalysis($url);
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get URL scan report
     * @param string $url URL to get report for
     * @return array Report results
     */
    public function getUrlReport($url) {
        try {
            $reportUrl = $this->baseUrl . 'url/report';
            
            $params = [
                'apikey' => $this->apiKey,
                'resource' => $url
            ];
            
            $response = $this->makeRequest($reportUrl . '?' . http_build_query($params));
              if ($response && $response['response_code'] == 1) {
                return [
                    'success' => true,
                    'scan_date' => $response['scan_date'],
                    'positives' => $response['positives'],
                    'total' => $response['total'],
                    'permalink' => $response['permalink'],
                    'scans' => $response['scans'],
                    'threat_level' => $this->calculateThreatLevel($response['positives'], $response['total']),
                    'scan_details' => $this->formatScanDetails($response['scans']),
                    'url' => $url,
                    'verbose_msg' => $response['verbose_msg'] ?? 'Scan completed',
                    'resource' => $response['resource'] ?? $url
                ];
            } else if ($response['response_code'] == 0) {
                return [
                    'success' => false,
                    'error' => 'URL not found in VirusTotal database',
                    'queued' => true
                ];
            } else if ($response['response_code'] == -2) {
                return [
                    'success' => false,
                    'error' => 'URL is still being analyzed',
                    'queued' => true
                ];
            }
            
            throw new Exception("Unknown response from VirusTotal");
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate threat level based on detection ratio
     * @param int $positives Number of engines that detected threats
     * @param int $total Total number of engines
     * @return string Threat level
     */
    private function calculateThreatLevel($positives, $total) {
        if ($positives == 0) {
            return 'clean';
        } else if ($positives <= 2) {
            return 'low';
        } else if ($positives <= 5) {
            return 'medium';
        } else {
            return 'high';
        }
    }
    
    /**
     * Make HTTP request to VirusTotal API
     * @param string $url API endpoint
     * @param array $postData POST data (optional)
     * @param bool $isFileUpload Whether this is a file upload
     * @return array|false Response data or false on failure
     */
    private function makeRequest($url, $postData = null, $isFileUpload = false) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'SecureIt Password Manager v2.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        if ($postData) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            
            if (!$isFileUpload) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/x-www-form-urlencoded'
                ]);
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: " . $error);
        }
          if ($httpCode !== 200) {
            // Handle specific HTTP errors
            if ($httpCode === 403) {
                throw new Exception("API key quota exceeded or access forbidden. Please try again later.");
            } else if ($httpCode === 204) {
                throw new Exception("Request rate limit exceeded. Please wait before making another request.");
            } else if ($httpCode === 400) {
                throw new Exception("Bad request. Please check the URL or file format.");
            } else {
                throw new Exception("HTTP error: " . $httpCode . " - Please try again later.");
            }
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response from VirusTotal");
        }
        
        return $decoded;
    }
    
    /**
     * Get API usage statistics
     * @return array Usage statistics
     */
    public function getApiStats() {
        try {
            // This would typically require a different endpoint
            // For now, return mock data
            return [
                'success' => true,
                'requests_today' => rand(50, 200),
                'requests_remaining' => rand(800, 950),
                'daily_limit' => 1000
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }    }
    
    /**
     * Format scan details for display
     * @param array $scans Raw scan results from VirusTotal
     * @return array Formatted scan details
     */
    private function formatScanDetails($scans) {
        $details = [
            'clean' => [],
            'malicious' => [],
            'suspicious' => [],
            'undetected' => [],
            'timeout' => [],
            'error' => []
        ];
        
        foreach ($scans as $engine => $result) {
            $scanResult = [
                'engine' => $engine,
                'result' => $result['result'],
                'update' => $result['update'] ?? 'Unknown',
                'version' => $result['version'] ?? 'Unknown'
            ];
            
            if ($result['detected']) {
                if (stripos($result['result'], 'malware') !== false || 
                    stripos($result['result'], 'trojan') !== false ||
                    stripos($result['result'], 'virus') !== false) {
                    $details['malicious'][] = $scanResult;
                } else {
                    $details['suspicious'][] = $scanResult;
                }
            } else {
                $details['clean'][] = $scanResult;
            }
        }
        
        return $details;
    }
    
    /**
     * Validate API key
     * @return bool True if API key is valid
     */
    public function validateApiKey() {
        try {
            $url = $this->baseUrl . 'file/report';
            $params = [
                'apikey' => $this->apiKey,
                'resource' => 'test'
            ];
            
            $response = $this->makeRequest($url . '?' . http_build_query($params));
              // If we get any response (even if resource not found), API key is valid
            return isset($response['response_code']);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Fallback URL analysis when VirusTotal API is unavailable
     * @param string $url URL to analyze
     * @return array Analysis results in VirusTotal format
     */
    private function fallbackUrlAnalysis($url) {
        require_once __DIR__ . '/SimpleUrlAnalyzer.php';
        
        $analyzer = new SimpleUrlAnalyzer();
        $result = $analyzer->analyzeUrl($url);
        
        if (!$result['success']) {
            return $result;
        }
        
        $vtResult = $analyzer->convertToVtFormat($result['analysis']);
        $vtResult['fallback_used'] = true;
        $vtResult['fallback_reason'] = 'VirusTotal API temporarily unavailable';
        
        return $vtResult;
    }
}
?>
