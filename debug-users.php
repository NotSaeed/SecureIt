<?php
/**
 * User credential checker
 */
require_once 'backend/classes/Database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>User Credentials Check</h2>";

// Get all users and their emails (decrypted)
$query = "SELECT id, email, password_hash, created_at FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Users in database:</h3>";
foreach ($users as $user) {
    echo "<p>User ID: {$user['id']}<br>";
    echo "Email (encrypted): {$user['email']}<br>";
    echo "Created: {$user['created_at']}</p>";
}

// Also check if there are any unencrypted test users
try {
    $query = "SELECT id, email FROM users WHERE email NOT LIKE '%=%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $plainUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($plainUsers)) {
        echo "<h3>Plain text email users:</h3>";
        foreach ($plainUsers as $user) {
            echo "<p>User ID: {$user['id']} - Email: {$user['email']}</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>Error checking plain users: " . $e->getMessage() . "</p>";
}

// Let's create a test user for debugging
echo "<h3>Creating test user...</h3>";
try {
    $testEmail = 'test@secureit.com';
    $testPassword = 'password123';
    
    // Check if test user exists
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$testEmail]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingUser) {
        // Create test user
        $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (email, password_hash) VALUES (?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$testEmail, $hashedPassword]);
        
        echo "<p style='color: green;'>Test user created successfully!</p>";
        echo "<p>Email: {$testEmail}<br>Password: {$testPassword}</p>";
    } else {
        echo "<p style='color: blue;'>Test user already exists.</p>";
        echo "<p>Email: {$testEmail}<br>Password: {$testPassword}</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error creating test user: " . $e->getMessage() . "</p>";
}
?>
