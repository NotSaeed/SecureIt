<?php
/**
 * Test vault API directly
 */
session_start();

// Simulate login
$_SESSION['user_id'] = 2; // Test user ID

echo "<h2>Testing Vault API</h2>";

// Include the vault API logic
require_once 'backend/classes/Vault.php';

$vault = new Vault();
$user_id = $_SESSION['user_id'];

try {
    echo "<h3>Raw vault items:</h3>";
    $items = $vault->getUserItems($user_id);
    echo "<pre>";
    print_r($items);
    echo "</pre>";
    
    echo "<h3>Formatted for extension:</h3>";
    // Format items for extension compatibility (same as in vault.php)
    $formattedItems = array_map(function($item) {
        $formatted = [
            'id' => $item['id'],
            'name' => $item['item_name'] ?? 'Untitled',
            'type' => $item['item_type'],
            'url' => $item['website_url'],
            'created_at' => $item['created_at'],
            'updated_at' => $item['updated_at'],
            'folder_id' => $item['folder_id'],
            'is_favorite' => $item['is_favorite']
        ];
        
        // Extract username and password from decrypted_data
        if (isset($item['decrypted_data']) && is_array($item['decrypted_data'])) {
            $formatted['username'] = $item['decrypted_data']['username'] ?? '';
            $formatted['password'] = $item['decrypted_data']['password'] ?? '';
            $formatted['notes'] = $item['decrypted_data']['notes'] ?? '';
        }
        
        return $formatted;
    }, $items);
    
    echo "<pre>";
    print_r($formattedItems);
    echo "</pre>";
    
    echo "<h3>JSON response:</h3>";
    $response = [
        'success' => true,
        'items' => $formattedItems
    ];
    echo "<pre>";
    echo json_encode($response, JSON_PRETTY_PRINT);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
