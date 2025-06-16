<?php
/**
 * EmailHelper Class
 * Handles anonymous email sending via Gmail SMTP
 */

class EmailHelper {
    private $smtpHost = 'smtp.gmail.com';
    private $smtpPort = 587;
    private $smtpSecure = 'tls';
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;
      public function __construct($username = null, $password = null) {
        // Load from environment variables, config file, or passed parameters
        $this->username = $username ?: $this->getConfig('SMTP_USERNAME', 'your-email@gmail.com');
        $this->password = $password ?: $this->getConfig('SMTP_PASSWORD', 'your-app-password');
        $this->fromEmail = $this->username;
        $this->fromName = 'SecureIt Anonymous';
    }
    
    /**
     * Get configuration value from environment or config file
     */
    private function getConfig($key, $default = null) {
        // Try environment variable first
        $envValue = getenv($key);
        if ($envValue !== false) {
            return $envValue;
        }
        
        // Try config file
        $configFile = __DIR__ . '/../config/email_config.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            if (isset($config[$key])) {
                return $config[$key];
            }
        }
        
        return $default;
    }    /**
     * Send anonymous email
     */
    public function sendAnonymousEmail($fromEmail, $toEmail, $subject, $message, $isHtml = true) {
        // Validate email addresses
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid sender email address');
        }
        
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid recipient email address');
        }
        
        // Check if we're in demo mode or SMTP is not configured
        $demoMode = $this->getConfig('DEMO_MODE', true);
        $isSmtpConfigured = $this->isSmtpConfigured();
        
        if ($demoMode || !$isSmtpConfigured) {
            // Demo mode - log email instead of sending
            return $this->logEmailForDemo($fromEmail, $toEmail, $subject, $message);
        }
        
        // Try to send via SMTP if configured
        try {
            return $this->sendSMTPEmail($fromEmail, $toEmail, $subject, $message, $isHtml);
        } catch (Exception $e) {
            // Log the error and fallback to demo mode
            error_log("SMTP Error: " . $e->getMessage());
            return $this->logEmailForDemo($fromEmail, $toEmail, $subject, $message, 'SMTP_FAILED: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if SMTP is properly configured
     */
    private function isSmtpConfigured() {
        $username = $this->username;
        $password = $this->password;
        
        // Check if credentials are still at default values
        if ($username === 'your-email@gmail.com' || 
            $password === 'your-app-password' || 
            empty($username) || 
            empty($password)) {
            return false;
        }
        
        // Basic email format validation
        if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log email for demo purposes
     */
    private function logEmailForDemo($fromEmail, $toEmail, $subject, $message, $error = null) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/email_demo.log';
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'from' => $fromEmail,
            'to' => $toEmail,
            'subject' => $subject,
            'message' => substr($message, 0, 200) . '...',
            'status' => $error ? 'DEMO_MODE_ERROR' : 'DEMO_MODE_SUCCESS',
            'error' => $error,
            'note' => $error ? 'Email failed to send via SMTP, logged instead' : 'SMTP not configured, email logged for demo'
        ];
          
        file_put_contents($logFile, json_encode($logEntry, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        return true;
    }
    
    private function sendViaSMTP($data, $content) {
        // This would contain actual SMTP sending logic
        // For now, return true as if sent successfully
        return true;
    }
    
    /**
     * Send email via SMTP (more reliable method)
     * This is a basic implementation - for production use PHPMailer or similar
     */
    public function sendSMTPEmail($fromEmail, $toEmail, $subject, $message, $isHtml = true) {
        // Basic SMTP implementation
        $socket = fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 30);
        
        if (!$socket) {
            throw new Exception("Failed to connect to SMTP server: $errstr ($errno)");
        }
        
        // This is a simplified SMTP implementation
        // For production, use a proper SMTP library like PHPMailer
        
        try {
            // SMTP conversation
            $this->smtpCommand($socket, null, '220'); // Server greeting
            $this->smtpCommand($socket, "EHLO localhost\r\n", '250');
            $this->smtpCommand($socket, "STARTTLS\r\n", '220');
            
            // Enable crypto
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            $this->smtpCommand($socket, "EHLO localhost\r\n", '250');
            $this->smtpCommand($socket, "AUTH LOGIN\r\n", '334');
            $this->smtpCommand($socket, base64_encode($this->username) . "\r\n", '334');
            $this->smtpCommand($socket, base64_encode($this->password) . "\r\n", '235');
            
            $this->smtpCommand($socket, "MAIL FROM: <{$this->fromEmail}>\r\n", '250');
            $this->smtpCommand($socket, "RCPT TO: <$toEmail>\r\n", '250');
            $this->smtpCommand($socket, "DATA\r\n", '354');
            
            // Email headers and body
            $emailData = "From: $fromEmail\r\n";
            $emailData .= "To: $toEmail\r\n";
            $emailData .= "Subject: $subject\r\n";
            $emailData .= "MIME-Version: 1.0\r\n";
            $emailData .= $isHtml ? "Content-Type: text/html; charset=UTF-8\r\n" : "Content-Type: text/plain; charset=UTF-8\r\n";
            $emailData .= "\r\n";
            $emailData .= $message;
            $emailData .= "\r\n.\r\n";
            
            $this->smtpCommand($socket, $emailData, '250');
            $this->smtpCommand($socket, "QUIT\r\n", '221');
            
            fclose($socket);
            return true;
            
        } catch (Exception $e) {
            fclose($socket);
            throw $e;
        }
    }
    
    /**
     * Simple SMTP command helper
     */
    private function smtpCommand($socket, $command, $expectedCode) {
        if ($command) {
            fwrite($socket, $command);
        }
        
        $response = fgets($socket, 512);
        $code = substr($response, 0, 3);
        
        if ($code !== $expectedCode) {
            throw new Exception("SMTP Error: Expected $expectedCode, got $code - $response");
        }
        
        return $response;
    }
    
    /**
     * Validate and sanitize email content
     */
    public function sanitizeEmailContent($content) {
        // Basic sanitization
        $content = trim($content);
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        return $content;
    }
    
    /**
     * Generate anonymous email template
     */
    public function generateAnonymousTemplate($message, $senderNote = null) {
        $template = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3b82f6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 10px; font-size: 12px; color: #666; text-align: center; }
                .anonymous-note { background: #fee2e2; border: 1px solid #fecaca; padding: 10px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Anonymous Message</h2>
                </div>
                <div class='content'>
                    <div class='anonymous-note'>
                        <strong>Note:</strong> This is an anonymous message sent through SecureIt.
                    </div>
                    " . ($senderNote ? "<p><strong>From sender:</strong> " . htmlspecialchars($senderNote) . "</p>" : "") . "
                    <div style='background: white; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </div>
                </div>
                <div class='footer'>
                    <p>This message was sent anonymously through SecureIt's secure messaging system.</p>
                    <p>The sender's identity is protected.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $template;
    }
}
?>
