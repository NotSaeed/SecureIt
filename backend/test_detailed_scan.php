<?php
// Test detailed VirusTotal scan results
echo "<h1>Detailed VirusTotal Scan Test</h1>\n";

try {
    require_once 'classes/VirusTotalAPI.php';
    
    $vtApi = new VirusTotalAPI();
    
    echo "<h2>Testing URL Scan with Detailed Results</h2>\n";
    
    // Test with a known URL
    $testUrl = 'https://www.google.com';
    echo "🔍 Scanning: $testUrl\n<br><br>";
    
    $result = $vtApi->scanUrl($testUrl);
    
    if ($result['success']) {
        echo "✅ Scan successful!\n<br>";
        echo "📊 Positives: " . $result['positives'] . "/" . $result['total'] . "\n<br>";
        echo "🛡️ Threat Level: " . $result['threat_level'] . "\n<br>";
        echo "📅 Scan Date: " . $result['scan_date'] . "\n<br>";
        
        if (isset($result['scan_details'])) {
            echo "<h3>Detailed Scan Results:</h3>\n";
            
            $details = $result['scan_details'];
            
            if (!empty($details['malicious'])) {
                echo "<h4 style='color: red;'>🚨 Malicious Detections (" . count($details['malicious']) . "):</h4>\n";
                foreach ($details['malicious'] as $scan) {
                    echo "❌ " . $scan['engine'] . ": " . $scan['result'] . "\n<br>";
                }
            }
            
            if (!empty($details['suspicious'])) {
                echo "<h4 style='color: orange;'>⚠️ Suspicious Detections (" . count($details['suspicious']) . "):</h4>\n";
                foreach ($details['suspicious'] as $scan) {
                    echo "⚠️ " . $scan['engine'] . ": " . $scan['result'] . "\n<br>";
                }
            }
            
            if (!empty($details['clean'])) {
                echo "<h4 style='color: green;'>✅ Clean Results (" . count($details['clean']) . "):</h4>\n";
                $cleanCount = count($details['clean']);
                echo "✅ $cleanCount security engines found no threats\n<br>";
                
                // Show first 5 clean results
                for ($i = 0; $i < min(5, $cleanCount); $i++) {
                    $scan = $details['clean'][$i];
                    echo "✅ " . $scan['engine'] . ": Clean\n<br>";
                }
                
                if ($cleanCount > 5) {
                    echo "... and " . ($cleanCount - 5) . " more clean results\n<br>";
                }
            }
        }
        
        echo "<br><h3>JSON Response Sample:</h3>\n";
        echo "<pre style='background: #f5f5f5; padding: 1rem; border-radius: 4px; overflow: auto; max-height: 300px;'>";
        echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT));
        echo "</pre>\n";
        
    } else {
        echo "❌ Scan failed: " . $result['error'] . "\n<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n<br>";
}
?>
