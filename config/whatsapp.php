<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default WhatsApp Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default WhatsApp provider that will be used
    | by the adapter. You may set this to any of the providers defined
    | in the "providers" array below.
    |
    */

    'default_provider' => env('WHATSAPP_PROVIDER', 'infobip'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Providers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the WhatsApp providers for your application.
    | Each provider has its own configuration options.
    |
    */

    'providers' => [
        'infobip' => [
            'api_key' => env('INFOBIP_API_KEY'),
            'base_url' => env('INFOBIP_BASE_URL', 'https://api.infobip.com'),
            'sender' => env('INFOBIP_SENDER'),
            'webhook_secret' => env('INFOBIP_WEBHOOK_SECRET'),
        ],

        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'sender' => env('TWILIO_SENDER'),
            'webhook_secret' => env('TWILIO_WEBHOOK_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure retry behavior for failed API requests.
    |
    */

    'retry' => [
        'max_attempts' => env('RETRY_MAX_ATTEMPTS', 3),
        'initial_delay_ms' => env('RETRY_INITIAL_DELAY_MS', 1000),
    ],
];
