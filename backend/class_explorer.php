<?php
// filepath: c:\xampp\htdocs\SecureIt\backend\class_explorer.php
session_start();

// Include all classes
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Vault.php';
require_once 'classes/PasswordGenerator.php';
require_once 'classes/EncryptionHelper.php';
require_once 'classes/SendManager.php';
require_once 'classes/SecurityManager.php';
require_once 'classes/ReportManager.php';
require_once 'classes/Authenticator.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureIt Class Explorer</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            color: #f7fafc;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #fbb6ce;
        }

        .class-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
        }

        .class-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .class-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .class-icon {
            font-size: 2rem;
            margin-right: 15px;
            color: #fbb6ce;
        }

        .class-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .class-description {
            color: #cbd5e0;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .method-list {
            margin-bottom: 20px;
        }

        .method-list h4 {
            color: #a78bfa;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .method-item {
            background: rgba(0, 0, 0, 0.2);
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: #68d391;
        }

        .test-section {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 20px;
        }

        .test-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-right: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .test-result {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }

        .success { border-left: 4px solid #48bb78; }
        .error { border-left: 4px solid #f56565; }
        .info { border-left: 4px solid #4299e1; }

        .back-button {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-bottom: 20px;
            text-decoration: none;
            display: inline-block;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="header">
            <h1><i class="fas fa-code"></i> SecureIt Class Explorer</h1>
            <p>Explore and test all backend PHP classes</p>
        </div>

        <div class="class-grid">
            <!-- Database Class -->
            <div class="class-card">
                <div class="class-header">
                    <div class="class-icon"><i class="fas fa-database"></i></div>
                    <div class="class-title">Database</div>
                </div>
                <div class="class-description">
                    Core database connection and query execution class with PDO support and prepared statements.
                </div>
                <div class="method-list">
                    <h4>Key Methods:</h4>
                    <div class="method-item">query($sql, $params)</div>
                    <div class="method-item">fetchOne($sql, $params)</div>
                    <div class="method-item">fetchAll($sql, $params)</div>
                    <div class="method-item">lastInsertId()</div>
                </div>
                <div class="test-section">
                    <button class="test-button" onclick="testDatabase()">Test Connection</button>
                    <div class="test-result" id="database-result"></div>
                </div>
            </div>

            <!-- User Class -->
            <div class="class-card">
                <div class="class-header">
                    <div class="class-icon"><i class="fas fa-user"></i></div>
                    <div class="class-title">User</div>
                </div>
                <div class="class-description">
                    User management system with registration, authentication, and profile management capabilities.
                </div>
                <div class="method-list">
                    <h4>Key Methods:</h4>
                    <div class="method-item">create($email, $password, $name)</div>
                    <div class="method-item">authenticate($email, $password)</div>
                    <div class="method-item">getById($id)</div>
                    <div class="method-item">updateProfile($id, $data)</div>
                </div>
                <div class="test-section">
                    <button class="test-button" onclick="testUser()">Test User Creation</button>
                    <div class="test-result" id="user-result"></div>
                </div>
            </div>

            <!-- Vault Class -->
            <div class="class-card">
                <div class="class-header">
                    <div class="class-icon"><i class="fas fa-vault"></i></div>
                    <div class="class-title">Vault</div>
                </div>
                <div class="class-description">
                    Secure vault management for storing encrypted passwords, cards, identities, and other sensitive data.
                </div>
                <div class="method-list">
                    <h4>Key Methods:</h4>
                    <div class="method-item">addItem($user_id, $name, $type, $data)</div>
                    <div class="method-item">getUserItems($user_id)</div>
                    <div class="method-item">updateItem($id, $user_id, $data)</div>
                    <div class="method-item">deleteItem($id, $user_id)</div>
                </div>
                <div class="test-section">
                    <button class="test-button" onclick="testVault()">Test Vault Operations</button>
                    <div class="test-result" id="vault-result"></div>
                </div>
            </div>

            <!-- PasswordGenerator Class -->
            <div class="class-card">
                <div class="class-header">
                    <div class="class-icon"><i class="fas fa-key"></i></div>
                    <div class="class-title">PasswordGenerator</div>
                </div>
                <div class="class-description">
                    Advanced password and passphrase generation with customizable complexity and security options.
                </div>
                <div class="method-list">
                    <h4>Key Methods:</h4>
                    <div class="method-item">generatePassword($length, $options)</div>
                    <div class="method-item">generatePassphrase($words, $separator)</div>
                    <div class="method-item">generateUsername($type, $numbers)</div>
                    <div class="method-item">checkStrength($password)</div>
                </div>
                <div class="test-section">
                    <button class="test-button" onclick="testPasswordGenerator()">Generate Passwords</button>
                    <div class="test-result" id="generator-result"></div>
                </div>
            </div>

            <!-- EncryptionHelper Class -->
            <div class="class-card">
                <div class="class-header">
                    <div class="class-icon"><i class="fas fa-lock"></i></div>
                    <div class="class-title">EncryptionHelper</div>
                </div>
                <div class="class-description">
                    AES-256-GCM encryption and decryption for securing sensitive data with authenticated encryption.
                </div>
                <div class="method-list">
                    <h4>Key Methods:</h4>
                    <div class="method-item">encrypt($data)</div>
                    <div class="method-item">decrypt($encrypted_data)</div>
                    <div class="method-item">generateKey()</div>
                    <div class="method-item">hashPassword($password)</div>
                </div>
                <div class="test-section">
                    <button class="test-button" onclick="testEncryption()">Test Encryption</button>
                    <div class="test-result" id="encryption-result"></div>
                </div>
            </div>

            <!-- SendManager Class -->
            <div class="class-card">
                <div class="class-header">
                    <div class="class-icon"><i class="fas fa-share"></i></div>
                    <div class="class-title">SendManager</div>
                </div>
                <div class="class-description">
                    Secure temporary sharing system for text and files with expiration and access control.
                </div>
                <div class="method-list">
                    <h4>Key Methods:</h4>
                    <div class="method-item">createSend($user_id, $data)</div>
                    <div class="method-item">getSend($access_id)</div>
                    <div class="method-item">deleteSend($id, $user_id)</div>
                    <div class="method-item">getUserSends($user_id)</div>
                </div>
                <div class="test-section">
                    <button class="test-button" onclick="testSendManager()">Test Send Creation</button>
                    <div class="test-result" id="send-result"></div>
                </div>
            </div>

            <!-- SecurityManager Class -->
            <div class="class-card">
                <div class="class-header">
                    <div class="class-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="class-title">SecurityManager</div>
                </div>
                <div class="class-description">
                    Security analysis and reporting system for password strength and vulnerability detection.
                </div>
                <div class="method-list">
                    <h4>Key Methods:</h4>
                    <div class="method-item">analyzeUserSecurity($user_id)</div>
                    <div class="method-item">checkWeakPasswords($user_id)</div>
                    <div class="method-item">findDuplicatePasswords($user_id)</div>
                    <div class="method-item">generateSecurityReport($user_id)</div>
                </div>
                <div class="test-section">
                    <button class="test-button" onclick="testSecurityManager()">Run Security Analysis</button>
                    <div class="test-result" id="security-result"></div>
                </div>
            </div>

            <!-- Authenticator Class -->
            <div class="class-card">
                <div class="class-header">
                    <div class="class-icon"><i class="fas fa-mobile-alt"></i></div>
                    <div class="class-title">Authenticator</div>
                </div>
                <div class="class-description">
                    Two-factor authentication with TOTP (Time-based One-Time Password) support and QR code generation.
                </div>
                <div class="method-list">
                    <h4>Key Methods:</h4>
                    <div class="method-item">generateSecret()</div>
                    <div class="method-item">generateQRCode($secret, $email)</div>
                    <div class="method-item">verifyCode($secret, $code)</div>
                    <div class="method-item">generateBackupCodes()</div>
                </div>
                <div class="test-section">
                    <button class="test-button" onclick="testAuthenticator()">Test 2FA Setup</button>
                    <div class="test-result" id="authenticator-result"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Test Database Class
        async function testDatabase() {
            const result = document.getElementById('database-result');
            result.style.display = 'block';
            result.innerHTML = 'Testing database connection...';
            
            try {
                const response = await fetch('test_class.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ class: 'Database', method: 'testConnection' })
                });
                const data = await response.json();
                
                result.className = data.success ? 'test-result success' : 'test-result error';
                result.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            } catch (error) {
                result.className = 'test-result error';
                result.innerHTML = `<pre>Error: ${error.message}</pre>`;
            }
        }

        // Test User Class
        async function testUser() {
            const result = document.getElementById('user-result');
            result.style.display = 'block';
            result.innerHTML = 'Testing user operations...';
            
            try {
                const response = await fetch('test_class.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ class: 'User', method: 'testOperations' })
                });
                const data = await response.json();
                
                result.className = data.success ? 'test-result success' : 'test-result error';
                result.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            } catch (error) {
                result.className = 'test-result error';
                result.innerHTML = `<pre>Error: ${error.message}</pre>`;
            }
        }

        // Test Vault Class
        async function testVault() {
            const result = document.getElementById('vault-result');
            result.style.display = 'block';
            result.innerHTML = 'Testing vault operations...';
            
            try {
                const response = await fetch('test_class.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ class: 'Vault', method: 'testOperations' })
                });
                const data = await response.json();
                
                result.className = data.success ? 'test-result success' : 'test-result error';
                result.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            } catch (error) {
                result.className = 'test-result error';
                result.innerHTML = `<pre>Error: ${error.message}</pre>`;
            }
        }

        // Test PasswordGenerator Class
        async function testPasswordGenerator() {
            const result = document.getElementById('generator-result');
            result.style.display = 'block';
            result.innerHTML = 'Generating passwords...';
            
            try {
                const response = await fetch('test_class.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ class: 'PasswordGenerator', method: 'testGeneration' })
                });
                const data = await response.json();
                
                result.className = data.success ? 'test-result success' : 'test-result error';
                result.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            } catch (error) {
                result.className = 'test-result error';
                result.innerHTML = `<pre>Error: ${error.message}</pre>`;
            }
        }

        // Test EncryptionHelper Class
        async function testEncryption() {
            const result = document.getElementById('encryption-result');
            result.style.display = 'block';
            result.innerHTML = 'Testing encryption...';
            
            try {
                const response = await fetch('test_class.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ class: 'EncryptionHelper', method: 'testEncryption' })
                });
                const data = await response.json();
                
                result.className = data.success ? 'test-result success' : 'test-result error';
                result.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            } catch (error) {
                result.className = 'test-result error';
                result.innerHTML = `<pre>Error: ${error.message}</pre>`;
            }
        }

        // Test SendManager Class
        async function testSendManager() {
            const result = document.getElementById('send-result');
            result.style.display = 'block';
            result.innerHTML = 'Testing send operations...';
            
            try {
                const response = await fetch('test_class.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ class: 'SendManager', method: 'testOperations' })
                });
                const data = await response.json();
                
                result.className = data.success ? 'test-result success' : 'test-result error';
                result.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            } catch (error) {
                result.className = 'test-result error';
                result.innerHTML = `<pre>Error: ${error.message}</pre>`;
            }
        }

        // Test SecurityManager Class
        async function testSecurityManager() {
            const result = document.getElementById('security-result');
            result.style.display = 'block';
            result.innerHTML = 'Running security analysis...';
            
            try {
                const response = await fetch('test_class.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ class: 'SecurityManager', method: 'testAnalysis' })
                });
                const data = await response.json();
                
                result.className = data.success ? 'test-result success' : 'test-result error';
                result.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            } catch (error) {
                result.className = 'test-result error';
                result.innerHTML = `<pre>Error: ${error.message}</pre>`;
            }
        }

        // Test Authenticator Class
        async function testAuthenticator() {
            const result = document.getElementById('authenticator-result');
            result.style.display = 'block';
            result.innerHTML = 'Testing 2FA setup...';
            
            try {
                const response = await fetch('test_class.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ class: 'Authenticator', method: 'testSetup' })
                });
                const data = await response.json();
                
                result.className = data.success ? 'test-result success' : 'test-result error';
                result.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            } catch (error) {
                result.className = 'test-result error';
                result.innerHTML = `<pre>Error: ${error.message}</pre>`;
            }
        }
    </script>
</body>
</html>
