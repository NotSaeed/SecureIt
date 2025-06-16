<?php
/**
 * Have I Been Pwned API Helper
 * 
 * This class handles communication with the Have I Been Pwned API
 * Provides methods for checking breached accounts, pastes, and breaches
 */

class HIBPHelper {
    private $config;
    private $apiKey;
    private $baseUrl;
    private $userAgent;
    private $demoMode;
    
    public function __construct() {
        $this->config = require_once __DIR__ . '/../config/hibp_config.php';
        $this->apiKey = $this->config['API_KEY'];
        $this->baseUrl = $this->config['API_BASE_URL'];
        $this->userAgent = $this->config['USER_AGENT'];
        $this->demoMode = $this->config['DEMO_MODE'] || $this->apiKey === 'YOUR_HIBP_API_KEY_HERE';
    }
    
    /**
     * Check if an account has been in any data breaches
     */
    public function checkBreachedAccount($account, $truncateResponse = true, $includeUnverified = true) {
        if ($this->demoMode) {
            return $this->getDemoBreachData($account);
        }
        
        $endpoint = str_replace('{account}', urlencode($account), $this->config['ENDPOINTS']['BREACHED_ACCOUNT']);
        $url = $this->baseUrl . $endpoint;
        
        $params = [];
        if (!$truncateResponse) {
            $params['truncateResponse'] = 'false';
        }
        if (!$includeUnverified) {
            $params['includeUnverified'] = 'false';
        }
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->makeRequest($url);
    }
    
    /**
     * Check for pastes containing the account
     */
    public function checkPasteAccount($account) {
        if ($this->demoMode) {
            return $this->getDemoPasteData($account);
        }
        
        $endpoint = str_replace('{account}', urlencode($account), $this->config['ENDPOINTS']['PASTE_ACCOUNT']);
        $url = $this->baseUrl . $endpoint;
        
        return $this->makeRequest($url);
    }
    
    /**
     * Get all breaches in the system
     */
    public function getAllBreaches($domain = null, $isSpamList = null) {
        if ($this->demoMode) {
            return $this->getDemoAllBreaches();
        }
        
        $url = $this->baseUrl . $this->config['ENDPOINTS']['ALL_BREACHES'];
        
        $params = [];
        if ($domain) {
            $params['domain'] = $domain;
        }
        if ($isSpamList !== null) {
            $params['isSpamList'] = $isSpamList ? 'true' : 'false';
        }
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->makeRequest($url, false); // No auth required for this endpoint
    }
    
    /**
     * Get a single breach by name
     */
    public function getSingleBreach($name) {
        if ($this->demoMode) {
            return $this->getDemoSingleBreach($name);
        }
        
        $endpoint = str_replace('{name}', urlencode($name), $this->config['ENDPOINTS']['SINGLE_BREACH']);
        $url = $this->baseUrl . $endpoint;
        
        return $this->makeRequest($url, false); // No auth required for this endpoint
    }
    
    /**
     * Make HTTP request to HIBP API
     */
    private function makeRequest($url, $requireAuth = true) {
        $headers = [
            'User-Agent: ' . $this->userAgent,
            'Accept: application/json'
        ];
        
        if ($requireAuth) {
            $headers[] = 'hibp-api-key: ' . $this->apiKey;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
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
     */
    private function handleResponse($response, $httpCode) {
        switch ($httpCode) {
            case 200:
                return json_decode($response, true);
            
            case 400:
                throw new Exception('Bad request - the account does not comply with an acceptable format');
            
            case 401:
                throw new Exception('Unauthorised - API key was not provided or is invalid');
            
            case 403:
                throw new Exception('Forbidden - no user agent has been specified in the request');
            
            case 404:
                return null; // Account not found in any breaches
            
            case 429:
                $retryAfter = $this->getRetryAfterHeader($response);
                throw new Exception('Rate limit exceeded. Try again in ' . $retryAfter . ' seconds');
            
            case 503:
                throw new Exception('Service unavailable - please try again later');
            
            default:
                throw new Exception('Unexpected response code: ' . $httpCode);
        }
    }
    
    /**
     * Extract retry-after header value
     */
    private function getRetryAfterHeader($response) {
        // In a real implementation, you'd parse the response headers
        // For now, return a default value
        return 60;
    }
    
    /**
     * Demo data for testing when no API key is configured
     */
    private function getDemoBreachData($account) {
        // Simulate realistic breach data
        $demoBreaches = [
            'test@example.com' => [
                ['Name' => 'Adobe'],
                ['Name' => 'LinkedIn'],
                ['Name' => 'MySpace']
            ],
            'user@gmail.com' => [
                ['Name' => 'Dropbox'],
                ['Name' => 'LastFM']
            ],
            'admin@company.com' => [
                ['Name' => 'Adobe'],
                ['Name' => 'Gawker'],
                ['Name' => 'LinkedIn'],
                ['Name' => 'Twitter']
            ]
        ];
        
        $account = strtolower(trim($account));
        
        // Check for exact matches first
        if (isset($demoBreaches[$account])) {
            return $demoBreaches[$account];
        }
        
        // Simulate random results for other emails
        $allBreaches = ['Adobe', 'LinkedIn', 'MySpace', 'Dropbox', 'LastFM', 'Gawker', 'Twitter', 'Facebook', 'Yahoo'];
        $numBreaches = rand(0, 4);
        
        if ($numBreaches === 0) {
            return null;
        }
        
        $selectedBreaches = array_rand(array_flip($allBreaches), $numBreaches);
        if (!is_array($selectedBreaches)) {
            $selectedBreaches = [$selectedBreaches];
        }
        
        return array_map(function($breach) {
            return ['Name' => $breach];
        }, $selectedBreaches);
    }
    
    private function getDemoPasteData($account) {
        // Simulate paste data - most accounts won't have pastes
        if (rand(0, 10) < 2) { // 20% chance of having pastes
            return [
                [
                    'Source' => 'Pastebin',
                    'Id' => substr(md5($account . time()), 0, 8),
                    'Title' => 'user data',
                    'Date' => date('c', strtotime('-' . rand(30, 365) . ' days')),
                    'EmailCount' => rand(10, 500)
                ]
            ];
        }
        return null;
    }
    
    private function getDemoAllBreaches() {
        return [
            [
                'Name' => 'Adobe',
                'Title' => 'Adobe',
                'Domain' => 'adobe.com',
                'BreachDate' => '2013-10-04',
                'AddedDate' => '2013-12-04T00:00:00Z',
                'PwnCount' => 152445165,
                'Description' => 'In October 2013, 153 million Adobe accounts were breached...',
                'DataClasses' => ['Email addresses', 'Password hints', 'Passwords', 'Usernames'],
                'IsVerified' => true
            ],
            [
                'Name' => 'LinkedIn',
                'Title' => 'LinkedIn',
                'Domain' => 'linkedin.com',
                'BreachDate' => '2012-05-05',
                'AddedDate' => '2016-05-21T21:35:40Z',
                'PwnCount' => 164611595,
                'Description' => 'In May 2012, LinkedIn was breached...',
                'DataClasses' => ['Email addresses', 'Passwords'],
                'IsVerified' => true
            ]
        ];
    }
    
    private function getDemoSingleBreach($name) {
        $breaches = $this->getDemoAllBreaches();
        foreach ($breaches as $breach) {
            if (strtolower($breach['Name']) === strtolower($name)) {
                return $breach;
            }
        }
        return null;
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
            'api_key_configured' => $this->apiKey !== 'YOUR_HIBP_API_KEY_HERE',
            'rate_limit' => $this->config['RATE_LIMIT_RPM']
        ];
    }
}
