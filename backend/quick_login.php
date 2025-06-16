<?php
session_start();
require_once 'classes/Database.php';
require_once 'classes/User.php';

echo "<h1>Quick Login for Testing</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
</style>";

try {
    // Check if user already exists
    $db = new Database();
    $conn = $db->getConnection();
    
    $result = $conn->query("SELECT * FROM users WHERE email = 'test@example.com'");
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p class='success'>Test user already exists</p>";
    } else {
        // Create a test user
        $hashedPassword = password_hash('testpass123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (email, password, name, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $email, $hashedPassword, $name);
        
        $email = 'test@example.com';
        $name = 'Test User';
        
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            echo "<p class='success'>Test user created with ID: $userId</p>";
            $user = ['id' => $userId, 'email' => $email, 'name' => $name];
        } else {
            throw new Exception("Failed to create test user");
        }
    }
    
    // Log in the test user
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    
    echo "<p class='success'>Logged in as: " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($user['email']) . ")</p>";
    echo "<p><strong><a href='main_vault.php'>Go to Main Vault</a></strong></p>";
    echo "<p>Login credentials for future use:</p>";
    echo "<ul>";
    echo "<li>Email: test@example.com</li>";
    echo "<li>Password: testpass123</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}
?>
