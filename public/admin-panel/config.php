<?php

/**
 * Configuration Example
 * 
 * Copy this file to config.php and update with your credentials
 */

return [
    // Infobip API Configuration
    'infobip_api_key' => 'YOUR_INFOBIP_API_KEY_HERE',
    'infobip_sender' => 'YOUR_SENDER_NUMBER_HERE', // Example: 351927587119
    
    // Storage Configuration
    'messages_file' => __DIR__ . '/messages.json',
    'webhook_log_file' => __DIR__ . '/webhook.log',
    
    // Security (for production)
    'enable_auth' => false, // Set to true to enable authentication
    'admin_username' => 'admin',
    'admin_password' => 'change_this_password',
    
    // Webhook Configuration
    'webhook_secret' => '', // Optional: Infobip webhook signature validation
    
    // Rate Limiting
    'rate_limit_enabled' => false,
    'rate_limit_max_requests' => 100,
    'rate_limit_window_seconds' => 60,
];
