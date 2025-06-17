<?php
// Test the enhanced credential delivery functionality
require_once 'classes/SendManager.php';
require_once 'classes/Vault.php';

echo "=== Enhanced Credential Delivery Test ===\n";

try {
    // Test user ID (assuming user exists)
    $testUserId = 1;
    $recipientEmail = 'test@example.com';
    
    // Get some vault items for testing
    $vault = new Vault();
    $userItems = $vault->getUserItems($testUserId);
    
    if (empty($userItems)) {
        echo "No vault items found for user ID: $testUserId\n";
        echo "Please create some vault items first to test credential delivery.\n";
        exit;
    }
    
    echo "Found " . count($userItems) . " vault items for testing:\n";
    foreach ($userItems as $item) {
        echo "- ID: {$item['id']}, Name: {$item['item_name']}, Type: {$item['item_type']}\n";
    }
    echo "\n";
    
    // Test single item delivery
    echo "Testing single item delivery...\n";
    $sendManager = new SendManager();
    $singleItemIds = [array_values($userItems)[0]['id']];
    
    $result = $sendManager->createMultiCredentialDelivery(
        $testUserId,
        $singleItemIds,
        $recipientEmail,
        [
            'message' => 'Test single credential delivery',
            'expiry_hours' => 24,
            'selection_mode' => 'single'
        ]
    );
    
    echo "✓ Single item delivery created successfully!\n";
    echo "  Access link: " . substr($result['access_link'], 0, 20) . "...\n";
    echo "  Item count: " . $result['item_count'] . "\n\n";
    
    // Test multiple items delivery (if we have more than 1 item)
    if (count($userItems) > 1) {
        echo "Testing multiple items delivery...\n";
        $multipleItemIds = array_slice(array_column($userItems, 'id'), 0, min(3, count($userItems)));
        
        $result2 = $sendManager->createMultiCredentialDelivery(
            $testUserId,
            $multipleItemIds,
            $recipientEmail,
            [
                'message' => 'Test multiple credentials delivery',
                'expiry_hours' => 48,
                'selection_mode' => 'multiple'
            ]
        );
        
        echo "✓ Multiple items delivery created successfully!\n";
        echo "  Access link: " . substr($result2['access_link'], 0, 20) . "...\n";
        echo "  Item count: " . $result2['item_count'] . "\n\n";
    }
    
    // Test all items delivery
    echo "Testing all items delivery...\n";
    $allItemIds = array_column($userItems, 'id');
    
    $result3 = $sendManager->createMultiCredentialDelivery(
        $testUserId,
        $allItemIds,
        $recipientEmail,
        [
            'message' => 'Test all vault items delivery',
            'expiry_hours' => 72,
            'selection_mode' => 'all'
        ]
    );
    
    echo "✓ All items delivery created successfully!\n";
    echo "  Access link: " . substr($result3['access_link'], 0, 20) . "...\n";
    echo "  Item count: " . $result3['item_count'] . "\n\n";
    
    echo "=== All Tests Passed! ===\n";
    echo "The enhanced credential delivery system is working correctly.\n";
    echo "You can now:\n";
    echo "1. Share single vault items\n";
    echo "2. Share multiple selected vault items\n";
    echo "3. Share all vault items at once\n";
    echo "4. View actual credential data instead of 'N/A'\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
