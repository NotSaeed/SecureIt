<?php
/**
 * Have I Been Pwned API Configuration
 * 
 * To use the real HIBP API:
 * 1. Get an API key from https://haveibeenpwned.com/API/Key
 * 2. Replace 'YOUR_HIBP_API_KEY_HERE' with your actual API key
 * 3. Set DEMO_MODE to false to enable real API calls
 * 
 * SECURITY NOTE: 
 * - Never commit real API keys to version control
 * - Consider using environment variables in production
 */

return [
    // HIBP API Configuration
    'API_KEY' => 'YOUR_HIBP_API_KEY_HERE',           // Replace with your actual HIBP API key
    'API_BASE_URL' => 'https://haveibeenpwned.com/api/v3',
    'USER_AGENT' => 'SecureIt-Password-Manager/1.0', // Your app name
    
    // Feature flags
    'DEMO_MODE' => true,                              // Set to false when API key is configured
    
    // Rate limiting (based on your subscription level)
    'RATE_LIMIT_RPM' => 60,                          // Requests per minute (adjust based on your plan)
    
    // API endpoints
    'ENDPOINTS' => [
        'BREACHED_ACCOUNT' => '/breachedaccount/{account}',
        'BREACHED_DOMAIN' => '/breacheddomain/{domain}',
        'PASTE_ACCOUNT' => '/pasteaccount/{account}',
        'ALL_BREACHES' => '/breaches',
        'SINGLE_BREACH' => '/breach/{name}',
        'LATEST_BREACH' => '/latestbreach',
    ]
];
