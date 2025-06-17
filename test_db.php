<?php
$host = 'localhost';
$dbname = 'secureit';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "Database connection successful!\n";
    
    $stmt = $pdo->query('DESCRIBE sends');
    echo "Sends table structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    // Test if we can fetch some sends
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM sends');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal sends in database: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
