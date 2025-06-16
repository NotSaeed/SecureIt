<?php
/**
 * Email Configuration
 * 
 * Set up your Gmail SMTP credentials here for anonymous email sending.
 * 
 * To use Gmail SMTP:
 * 1. Enable 2-Factor Authentication on your Gmail account
 * 2. Generate an App Password: https://myaccount.google.com/apppasswords
 * 3. Use your Gmail address and the generated App Password below
 * 
 * SECURITY NOTE: 
 * - Never commit real credentials to version control
 * - Consider using environment variables in production
 * - Restrict App Password permissions to only what's needed
 */

return [
    // Gmail SMTP Configuration
    'SMTP_USERNAME' => 'alshybany235@gmail.com',        // ⚠️ REPLACE WITH YOUR ACTUAL GMAIL ADDRESS
    'SMTP_PASSWORD' => 'cokl ohjp vxhp naor',         // Your Gmail App Password (seems to be set)
    
    // Optional: Custom sender info
    'SMTP_FROM_NAME' => 'SecureIt Anonymous',        // Display name for anonymous emails
    
    // SMTP Settings (usually don't need to change these for Gmail)
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => 587,
    'SMTP_SECURE' => 'tls',
    
    // Feature flags - SET TO FALSE TO ENABLE REAL EMAIL SENDING
    'DEMO_MODE' => false,                              // Set to false when SMTP is configured
];
