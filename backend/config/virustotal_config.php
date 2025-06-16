<?php
/**
 * VirusTotal API Configuration
 * 
 * To use the real VirusTotal API:
 * 1. Get a free API key from https://www.virustotal.com/gui/my-apikey
 * 2. Replace 'YOUR_VIRUSTOTAL_API_KEY_HERE' with your actual API key
 * 3. Set DEMO_MODE to false to enable real API calls
 * 
 * SECURITY NOTE: 
 * - Never commit real API keys to version control
 * - Consider using environment variables in production
 */

return [    // VirusTotal API Configuration
    'API_KEY' => 'bae9cb80fc9024f29f95b6f372d14eb631371b1fa45a71ba4af31b9ae74fd699',    // Your actual VirusTotal API key
    'API_BASE_URL' => 'https://www.virustotal.com/api/v3',
    'USER_AGENT' => 'SecureIt-Security-Scanner/1.0', // Your app name
    
    // Feature flags
    'DEMO_MODE' => false,                            // Real API enabled with your key
    
    // Rate limiting (free tier: 4 requests per minute)
    'RATE_LIMIT_RPM' => 4,                          // Requests per minute (free: 4, premium: higher)
    
    // File size limits
    'MAX_FILE_SIZE' => 32 * 1024 * 1024,           // 32MB for free accounts, 650MB for premium
    'MAX_URL_LENGTH' => 2048,                       // Maximum URL length
    
    // API endpoints
    'ENDPOINTS' => [
        'FILE_SCAN' => '/files',
        'FILE_REPORT' => '/files/{id}',
        'URL_SCAN' => '/urls',
        'URL_REPORT' => '/urls/{id}',
        'IP_REPORT' => '/ip_addresses/{ip}',
        'DOMAIN_REPORT' => '/domains/{domain}',
    ],
    
    // Scan options
    'SCAN_OPTIONS' => [
        'timeout' => 30,                            // Request timeout in seconds
        'wait_for_results' => true,                 // Wait for scan completion
        'max_wait_time' => 300,                     // Maximum wait time for results (5 minutes)
    ]
];
