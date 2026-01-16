<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application.
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'stderr'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => env('LOG_PATH', 'storage/logs') . '/whatsapp-adapter.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'format' => 'json',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => env('LOG_PATH', 'storage/logs') . '/whatsapp-adapter.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'format' => 'json',
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'WhatsApp Adapter',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => 'Monolog\Handler\StreamHandler',
            'formatter' => 'Monolog\Formatter\JsonFormatter',
            'level' => 'error',
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'ident' => 'whatsapp-adapter',
            'facility' => LOG_USER,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monolog Handlers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Monolog handlers used by LoggerFactory
    |
    */

    'handlers' => [
        [
            'type' => 'rotating',
            'path' => env('LOG_PATH', 'storage/logs') . '/whatsapp-adapter.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'format' => 'json',
            'max_files' => 14,
        ],
        [
            'type' => 'stream',
            'path' => 'php://stderr',
            'level' => 'error',
            'format' => 'json',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Data Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns to identify and redact sensitive information from logs
    |
    */

    'sensitive_patterns' => [
        'api_key' => '/api[_-]?key["\']?\s*[:=]\s*["\']?([^"\'}\s,]+)/i',
        'token' => '/token["\']?\s*[:=]\s*["\']?([^"\'}\s,]+)/i',
        'password' => '/password["\']?\s*[:=]\s*["\']?([^"\'}\s,]+)/i',
        'authorization' => '/authorization["\']?\s*[:=]\s*["\']?([^"\'}\s,]+)/i',
        'phone' => '/\+?\d{10,15}/',
    ],
];
