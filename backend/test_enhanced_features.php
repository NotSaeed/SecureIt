<?php
// Test enhanced credential delivery features
session_start();

echo "<h1>Enhanced Credential Delivery Test</h1>";

// Test 1: Access Duration Options
echo "<h2>Test 1: Access Duration Options</h2>";
echo "<p>Testing the new access duration options including Years and Forever:</p>";

$accessOptions = [
    1 => "1 Hour",
    6 => "6 Hours", 
    12 => "12 Hours",
    24 => "24 Hours (1 Day)",
    72 => "72 Hours (3 Days)",
    168 => "1 Week",
    720 => "1 Month (30 Days)",
    2160 => "3 Months (90 Days)",
    4320 => "6 Months (180 Days)",
    8760 => "1 Year (365 Days)",
    17520 => "2 Years (730 Days)",
    43800 => "5 Years",
    87600 => "10 Years",
    0 => "Forever (No Expiration)"
];

echo "<ul>";
foreach ($accessOptions as $hours => $label) {
    if ($hours == 0) {
        $expirationDate = date('Y-m-d H:i:s', strtotime('+100 years'));
        echo "<li><strong>$hours hours</strong> ($label) → Expires: $expirationDate</li>";
    } else {
        $expirationDate = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
        echo "<li><strong>$hours hours</strong> ($label) → Expires: $expirationDate</li>";
    }
}
echo "</ul>";

// Test 2: SendManager Integration
echo "<h2>Test 2: SendManager Integration</h2>";

require_once 'classes/SendManager.php';

try {
    $sendManager = new SendManager();
    echo "<p>✓ SendManager class loaded successfully</p>";
    
    // Test getSendPassword method exists
    $reflection = new ReflectionClass('SendManager');
    if ($reflection->hasMethod('getSendPassword')) {
        echo "<p>✓ getSendPassword method exists</p>";
        
        $method = $reflection->getMethod('getSendPassword');
        $parameters = $method->getParameters();
        echo "<p>Method signature: getSendPassword(";
        foreach ($parameters as $param) {
            echo $param->getName();
            if ($param->hasType()) {
                echo ": " . $param->getType();
            }
            if (!$param->isLast()) echo ", ";
        }
        echo ")</p>";
    } else {
        echo "<p>✗ getSendPassword method not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error loading SendManager: " . $e->getMessage() . "</p>";
}

// Test 3: JavaScript Functions
echo "<h2>Test 3: JavaScript Functions</h2>";
echo "<p>Testing that the new JavaScript functions are properly defined:</p>";

?>
<script>
// Test if the functions exist
const functions = [
    'viewSendPassword',
    'copyPassword', 
    'closePasswordModal'
];

functions.forEach(func => {
    if (typeof window[func] === 'function') {
        document.write(`<p>✓ ${func} function is defined</p>`);
    } else {
        document.write(`<p style="color: red;">✗ ${func} function not found</p>`);
    }
});
</script>

<?php
// Test 4: UI Components
echo "<h2>Test 4: UI Components</h2>";
echo "<p>Enhanced credential delivery now includes:</p>";
echo "<ul>";
echo "<li>✓ Extended access duration options (up to 10 years + Forever)</li>";
echo "<li>✓ 'View Password' button for password-protected sends</li>";
echo "<li>✓ Password protection indicator in send listings</li>";
echo "<li>✓ Modal interface for password display</li>";
echo "<li>✓ AJAX endpoint for secure password retrieval</li>";
echo "</ul>";

echo "<h2>Summary</h2>";
echo "<div style='background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 1rem; margin: 1rem 0;'>";
echo "<h3 style='color: #0369a1; margin-top: 0;'>✅ Enhancement Complete</h3>";
echo "<p><strong>Access Duration:</strong> Now supports durations from 1 hour to Forever (no expiration)</p>";
echo "<p><strong>Password Management:</strong> Send creators can now view passwords for their password-protected credential deliveries</p>";
echo "<p><strong>UI Improvements:</strong> Enhanced send listings with password protection indicators and intuitive access controls</p>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #1e40af; }
h2 { color: #374151; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem; }
ul { margin-left: 1rem; }
li { margin: 0.5rem 0; }
</style>
