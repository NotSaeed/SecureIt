<?php
/**
 * Create test vault item
 */
require_once 'backend/classes/Database.php';
require_once 'backend/classes/Vault.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Creating Test Vault Item</h2>";

try {
    // Get test user ID
    $query = "SELECT id FROM users WHERE email = 'test@secureit.com'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<p style='color: red;'>Test user not found!</p>";
        exit;
    }
    
    $userId = $user['id'];
    echo "<p>Using user ID: {$userId}</p>";
    
    $vault = new Vault();
    
    // Create a test vault item
    $itemData = [
        'username' => 'testuser@example.com',
        'password' => 'mypassword123',
        'notes' => 'This is a test vault item'
    ];
    
    $itemId = $vault->addItem(
        $userId,
        'Test Website',
        'login',
        $itemData,
        'https://example.com'
    );
    
    echo "<p style='color: green;'>Test vault item created with ID: {$itemId}</p>";
    
    // Verify the item was created and can be retrieved
    $items = $vault->getUserItems($userId);
    echo "<h3>User's vault items:</h3>";
    echo "<pre>";
    print_r($items);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
