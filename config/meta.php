<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta Platform Configuration (Instagram + Facebook Messenger)
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for Meta's Messenger Platform,
    | which powers both Instagram Messaging API and Facebook Messenger API.
    | Both platforms share the same API endpoints and authentication.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Page Access Token: Long-lived token for your Facebook Page
    | App ID: Your Meta App ID
    | App Secret: Your Meta App Secret (used for webhook validation)
    | Page ID: Your Facebook Page ID
    |
    */

    'page_access_token' => env('META_PAGE_ACCESS_TOKEN'),
    'app_id' => env('META_APP_ID'),
    'app_secret' => env('META_APP_SECRET'),
    'page_id' => env('META_PAGE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Verify Token: Custom token for webhook verification (you define this)
    |
    */

    'verify_token' => env('META_VERIFY_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | API Version: Meta Graph API version (e.g., v21.0)
    | Base URL: Meta Graph API base URL
    |
    */

    'api_version' => env('META_API_VERSION', 'v21.0'),
    'base_url' => 'https://graph.facebook.com',

    /*
    |--------------------------------------------------------------------------
    | Platform Limits
    |--------------------------------------------------------------------------
    |
    | Different limits for Instagram and Facebook Messenger
    |
    */

    'limits' => [
        'instagram' => [
            'quick_replies' => 13,
            'images_per_message' => 10,
            'image_size' => 8 * 1024 * 1024,      // 8MB
            'video_size' => 25 * 1024 * 1024,     // 25MB
            'audio_size' => 25 * 1024 * 1024,     // 25MB
            'file_size' => 25 * 1024 * 1024,      // 25MB
        ],
        'messenger' => [
            'quick_replies' => 13,
            'images_per_message' => 1,             // 1 image per message (or use carousel)
            'image_size' => 25 * 1024 * 1024,      // 25MB
            'video_size' => 25 * 1024 * 1024,      // 25MB
            'audio_size' => 25 * 1024 * 1024,      // 25MB
            'file_size' => 25 * 1024 * 1024,       // 25MB
            'buttons_per_template' => 3,           // Button Template limit
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Messaging Window
    |--------------------------------------------------------------------------
    |
    | 24-hour window to respond to user messages
    | After this window expires, you can only send messages with message tags
    |
    */

    'messaging_window' => 24 * 60 * 60, // 24 hours in seconds

    /*
    |--------------------------------------------------------------------------
    | Supported Media Types
    |--------------------------------------------------------------------------
    |
    | Supported attachment types for Instagram and Messenger
    |
    */

    'supported_media_types' => [
        'image' => ['image/jpeg', 'image/png'],
        'video' => ['video/mp4', 'video/ogg', 'video/avi', 'video/quicktime', 'video/webm'],
        'audio' => ['audio/aac', 'audio/m4a', 'audio/wav', 'audio/mp4'],
        'file' => ['application/pdf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Meta has rate limits per Page. Configure accordingly.
    |
    */

    'rate_limit' => [
        'enabled' => true,
        'requests_per_second' => 10,
        'requests_per_minute' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for transient errors
    |
    */

    'retry' => [
        'max_attempts' => env('RETRY_MAX_ATTEMPTS', 3),
        'initial_delay_ms' => env('RETRY_INITIAL_DELAY_MS', 1000),
        'permanent_error_codes' => [
            36103,  // Account not eligible
            2534068, // Feature not available
            10,     // Permission denied
            100,    // Invalid parameter
            190,    // Invalid token
            200,    // Permission error
            551,    // User not available
            2022,   // Messaging window expired
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Status Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout threshold for message status queries.
    | Messages that remain in SENT status longer than this threshold
    | will be marked as UNKNOWN status.
    |
    | Default: 24 hours (86400 seconds)
    |
    */

    'status_timeout_seconds' => env('META_STATUS_TIMEOUT_SECONDS', 86400), // 24 hours
];
