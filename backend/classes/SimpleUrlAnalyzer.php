<?php
/**
 * Simple URL Analyzer - Fallback when VirusTotal API is unavailable
 */

class SimpleUrlAnalyzer {
    
    private $suspiciousDomains = [
        'bit.ly', 'tinyurl.com', 'goo.gl', 't.co', 'ow.ly',
        'adf.ly', 'bc.vc', 'buzurl.com', 'cli.gs', 'is.gd'
    ];
    
    private $dangerousExtensions = [
        '.exe', '.scr', '.bat', '.cmd', '.com', '.pif', '.vbs', '.js', '.jar'
    ];
    
    /**
     * Analyze URL for basic security indicators
     */
    public function analyzeUrl($url) {
        $analysis = [
            'url' => $url,
            'risk_level' => 'low',
            'risk_score' => 0,
            'warnings' => [],
            'details' => []
        ];
        
        // Parse URL
        $parsed = parse_url($url);
        if (!$parsed) {
            return [
                'success' => false,
                'error' => 'Invalid URL format'
            ];
        }
        
        $domain = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        
        // Check protocol
        if (isset($parsed['scheme']) && $parsed['scheme'] === 'http') {
            $analysis['warnings'][] = 'URL uses unsecure HTTP protocol';
            $analysis['risk_score'] += 10;
        }
        
        // Check for suspicious domains
        foreach ($this->suspiciousDomains as $suspicious) {
            if (strpos($domain, $suspicious) !== false) {
                $analysis['warnings'][] = 'URL uses URL shortening service';
                $analysis['risk_score'] += 20;
                break;
            }
        }
        
        // Check for dangerous file extensions
        foreach ($this->dangerousExtensions as $ext) {
            if (substr($path, -strlen($ext)) === $ext) {
                $analysis['warnings'][] = 'URL points to potentially dangerous file type: ' . $ext;
                $analysis['risk_score'] += 30;
                break;
            }
        }
        
        // Check for IP address instead of domain
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            $analysis['warnings'][] = 'URL uses IP address instead of domain name';
            $analysis['risk_score'] += 15;
        }
        
        // Check for suspicious URL patterns
        if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $url)) {
            $analysis['warnings'][] = 'URL contains IP address pattern';
            $analysis['risk_score'] += 10;
        }
        
        if (preg_match('/[a-f0-9]{8,}/', $path)) {
            $analysis['warnings'][] = 'URL contains suspicious hexadecimal patterns';
            $analysis['risk_score'] += 10;
        }
        
        // Check domain length
        if (strlen($domain) > 50) {
            $analysis['warnings'][] = 'Unusually long domain name';
            $analysis['risk_score'] += 5;
        }
        
        // Check for homograph attacks (basic)
        if (preg_match('/[а-я]|[α-ω]/u', $domain)) {
            $analysis['warnings'][] = 'Domain contains non-Latin characters (possible spoofing)';
            $analysis['risk_score'] += 25;
        }
        
        // Determine risk level
        if ($analysis['risk_score'] >= 50) {
            $analysis['risk_level'] = 'high';
        } elseif ($analysis['risk_score'] >= 20) {
            $analysis['risk_level'] = 'medium';
        }
        
        // Add details
        $analysis['details'] = [
            'protocol' => $parsed['scheme'] ?? 'unknown',
            'domain' => $domain,
            'path' => $path,
            'query' => $parsed['query'] ?? '',
            'fragment' => $parsed['fragment'] ?? ''
        ];
        
        return [
            'success' => true,
            'analysis' => $analysis
        ];
    }
    
    /**
     * Convert analysis to VirusTotal-like format for compatibility
     */
    public function convertToVtFormat($analysis) {
        $riskScore = $analysis['risk_score'];
        $positives = min(10, intval($riskScore / 5)); // Convert to detection count
        
        return [
            'success' => true,
            'positives' => $positives,
            'total' => 10,
            'threat_level' => $analysis['risk_level'],
            'scan_date' => date('Y-m-d H:i:s'),
            'url' => $analysis['url'],
            'verbose_msg' => 'Basic URL analysis completed',
            'scan_details' => [
                'malicious' => $positives > 7 ? [
                    ['engine' => 'SecureIt Basic Scanner', 'result' => 'High Risk URL', 'version' => '1.0', 'update' => date('Ymd')]
                ] : [],
                'suspicious' => ($positives > 3 && $positives <= 7) ? [
                    ['engine' => 'SecureIt Basic Scanner', 'result' => 'Suspicious URL', 'version' => '1.0', 'update' => date('Ymd')]
                ] : [],
                'clean' => $positives <= 3 ? [
                    ['engine' => 'SecureIt Basic Scanner', 'result' => 'No obvious threats detected', 'version' => '1.0', 'update' => date('Ymd')]
                ] : []
            ],
            'warnings' => $analysis['warnings'],
            'fallback_scan' => true
        ];
    }
}
?>
