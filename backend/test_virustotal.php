<?php
/**
 * VirusTotal API Test Script
 * Test the VirusTotal integration
 */

require_once 'classes/VirusTotalAPI.php';

echo "<h1>VirusTotal API Test</h1>\n";

$vtApi = new VirusTotalAPI();

// Test 1: Validate API Key
echo "<h2>Test 1: API Key Validation</h2>\n";
$isValid = $vtApi->validateApiKey();
echo $isValid ? "✅ API Key is valid\n" : "❌ API Key is invalid\n";
echo "<br><br>\n";

// Test 2: Get API Stats
echo "<h2>Test 2: API Usage Statistics</h2>\n";
$stats = $vtApi->getApiStats();
if ($stats['success']) {
    echo "📊 Requests today: " . $stats['requests_today'] . "\n<br>";
    echo "📊 Requests remaining: " . $stats['requests_remaining'] . "\n<br>";
    echo "📊 Daily limit: " . $stats['daily_limit'] . "\n<br>";
} else {
    echo "❌ Failed to get stats: " . $stats['error'] . "\n<br>";
}
echo "<br>\n";

// Test 3: Scan a known clean URL (Google)
echo "<h2>Test 3: URL Scan (Google.com)</h2>\n";
echo "🔍 Scanning https://www.google.com...\n<br>";
$urlResult = $vtApi->getUrlReport('https://www.google.com');

if ($urlResult['success']) {
    echo "✅ Scan successful\n<br>";
    echo "🛡️ Threat Level: " . $urlResult['threat_level'] . "\n<br>";
    echo "📊 Detections: " . $urlResult['positives'] . "/" . $urlResult['total'] . "\n<br>";
    if (isset($urlResult['permalink'])) {
        echo "🔗 <a href='" . $urlResult['permalink'] . "' target='_blank'>View full report</a>\n<br>";
    }
} else {
    echo "⚠️ " . $urlResult['error'] . "\n<br>";
}
echo "<br>\n";

// Test 4: Test with a suspicious URL (EICAR test)
echo "<h2>Test 4: URL Scan (EICAR Test File)</h2>\n";
echo "🔍 Scanning http://www.eicar.org/download/eicar.com...\n<br>";
$eicarResult = $vtApi->getUrlReport('http://www.eicar.org/download/eicar.com');

if ($eicarResult['success']) {
    echo "✅ Scan successful\n<br>";
    echo "🛡️ Threat Level: " . $eicarResult['threat_level'] . "\n<br>";
    echo "📊 Detections: " . $eicarResult['positives'] . "/" . $eicarResult['total'] . "\n<br>";
    if (isset($eicarResult['permalink'])) {
        echo "🔗 <a href='" . $eicarResult['permalink'] . "' target='_blank'>View full report</a>\n<br>";
    }
} else {
    echo "⚠️ " . $eicarResult['error'] . "\n<br>";
}
echo "<br>\n";

echo "<h2>Integration Status</h2>\n";
echo $isValid ? "🟢 VirusTotal API is ready to use!" : "🔴 VirusTotal API needs configuration";
?>
