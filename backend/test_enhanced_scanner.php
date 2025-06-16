<?php
// Test URL scanner with fallback functionality
echo "<h1>Enhanced URL Scanner Test</h1>\n";

try {
    require_once 'classes/VirusTotalAPI.php';
    
    $vtApi = new VirusTotalAPI();
    
    $testUrls = [
        'https://www.google.com',
        'http://malware-test.example.com/virus.exe',
        'https://bit.ly/suspicious-link',
        'http://192.168.1.1/download.exe'
    ];
    
    foreach ($testUrls as $url) {
        echo "<h2>Testing: $url</h2>\n";
        
        $result = $vtApi->scanUrl($url);
        
        if ($result['success']) {
            echo "✅ Scan completed successfully\n<br>";
            echo "📊 Detections: " . $result['positives'] . "/" . $result['total'] . "\n<br>";
            echo "🛡️ Threat Level: " . $result['threat_level'] . "\n<br>";
            
            if (isset($result['fallback_used'])) {
                echo "⚠️ <strong>Fallback Scanner Used:</strong> " . $result['fallback_reason'] . "\n<br>";
            }
            
            if (isset($result['warnings'])) {
                echo "<h4>Security Warnings:</h4>\n";
                foreach ($result['warnings'] as $warning) {
                    echo "⚠️ " . $warning . "\n<br>";
                }
            }
            
            if (isset($result['scan_details'])) {
                echo "<h4>Scan Details:</h4>\n";
                $details = $result['scan_details'];
                
                if (!empty($details['malicious'])) {
                    echo "<strong style='color: red;'>Malicious Detections:</strong>\n<br>";
                    foreach ($details['malicious'] as $scan) {
                        echo "❌ " . $scan['engine'] . ": " . $scan['result'] . "\n<br>";
                    }
                }
                
                if (!empty($details['suspicious'])) {
                    echo "<strong style='color: orange;'>Suspicious Detections:</strong>\n<br>";
                    foreach ($details['suspicious'] as $scan) {
                        echo "⚠️ " . $scan['engine'] . ": " . $scan['result'] . "\n<br>";
                    }
                }
                
                if (!empty($details['clean'])) {
                    echo "<strong style='color: green;'>Clean Results:</strong>\n<br>";
                    foreach ($details['clean'] as $scan) {
                        echo "✅ " . $scan['engine'] . ": " . $scan['result'] . "\n<br>";
                    }
                }
            }
        } else {
            echo "❌ Scan failed: " . $result['error'] . "\n<br>";
        }
        
        echo "<hr>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n<br>";
}
?>
