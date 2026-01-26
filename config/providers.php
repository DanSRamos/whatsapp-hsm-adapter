<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Messaging Providers Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for all messaging providers
    | supported by the WhatsApp HSM Adapter. Each provider has its own
    | configuration and capabilities.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | The default messaging provider to use when none is specified.
    | Options: 'infobip', 'twilio', 'meta'
    |
    */

    'default' => env('WHATSAPP_PROVIDER', 'infobip'),

    /*
    |--------------------------------------------------------------------------
    | Available Providers
    |--------------------------------------------------------------------------
    |
    | List of all available messaging providers and their configurations.
    |
    */

    'providers' => [
        /*
        |--------------------------------------------------------------------------
        | Infobip Provider (WhatsApp)
        |--------------------------------------------------------------------------
        */
        'infobip' => [
            'enabled' => true,
            'type' => 'whatsapp',
            'class' => \WhatsApp\Adapter\Providers\Infobip\InfobipProvider::class,
            'config' => [
                'api_key' => env('INFOBIP_API_KEY'),
                'base_url' => env('INFOBIP_BASE_URL', 'https://api.infobip.com'),
                'sender' => env('INFOBIP_SENDER'),
                'webhook_secret' => env('INFOBIP_WEBHOOK_SECRET'),
            ],
            'features' => [
                'text_messages' => true,
                'media_messages' => true,
                'interactive_buttons' => true,
                'interactive_lists' => true,
                'hsm_templates' => true,
                'delivery_reports' => true,
                'read_receipts' => true,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Twilio Provider (WhatsApp)
        |--------------------------------------------------------------------------
        */
        'twilio' => [
            'enabled' => true,
            'type' => 'whatsapp',
            'class' => \WhatsApp\Adapter\Providers\Twilio\TwilioProvider::class,
            'config' => [
                'account_sid' => env('TWILIO_ACCOUNT_SID'),
                'auth_token' => env('TWILIO_AUTH_TOKEN'),
                'sender' => env('TWILIO_SENDER'),
                'webhook_secret' => env('TWILIO_WEBHOOK_SECRET'),
            ],
            'features' => [
                'text_messages' => true,
                'media_messages' => true,
                'interactive_buttons' => true,
                'interactive_lists' => true,
                'hsm_templates' => true,
                'delivery_reports' => true,
                'read_receipts' => true,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Meta Provider (Instagram + Facebook Messenger)
        |--------------------------------------------------------------------------
        |
        | This provider supports both Instagram Messaging API and Facebook
        | Messenger API using Meta's unified Messenger Platform.
        |
        */
        'meta' => [
            'enabled' => true,
            'type' => 'meta', // Supports both Instagram and Messenger
            'class' => \WhatsApp\Adapter\Providers\Meta\MetaProvider::class,
            'config' => [
                'page_access_token' => env('META_PAGE_ACCESS_TOKEN'),
                'app_id' => env('META_APP_ID'),
                'app_secret' => env('META_APP_SECRET'),
                'page_id' => env('META_PAGE_ID'),
                'verify_token' => env('META_VERIFY_TOKEN'),
                'api_version' => env('META_API_VERSION', 'v21.0'),
                'base_url' => 'https://graph.facebook.com',
            ],
            'features' => [
                'text_messages' => true,
                'media_messages' => true,
                'interactive_buttons' => true,  // Quick Replies
                'interactive_lists' => true,    // Generic Template
                'button_template' => true,      // Messenger only
                'hsm_templates' => false,       // Not supported, converted to text
                'delivery_reports' => true,
                'read_receipts' => true,
                'multiple_images' => true,      // Instagram: 10, Messenger: 1
            ],
            'platforms' => [
                'instagram' => true,
                'messenger' => true,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Instagram Provider (Alias for Meta)
        |--------------------------------------------------------------------------
        |
        | Alias for the Meta provider for backward compatibility and clarity.
        | Uses the same Meta provider but can be referenced as 'instagram'.
        |
        */
        'instagram' => [
            'enabled' => true,
            'type' => 'meta',
            'alias_for' => 'meta',
            'class' => \WhatsApp\Adapter\Providers\Meta\MetaProvider::class,
            'config' => [
                'page_access_token' => env('META_PAGE_ACCESS_TOKEN'),
                'app_id' => env('META_APP_ID'),
                'app_secret' => env('META_APP_SECRET'),
                'page_id' => env('META_PAGE_ID'),
                'verify_token' => env('META_VERIFY_TOKEN'),
                'api_version' => env('META_API_VERSION', 'v21.0'),
                'base_url' => 'https://graph.facebook.com',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Messenger Provider (Alias for Meta)
        |--------------------------------------------------------------------------
        |
        | Alias for the Meta provider for backward compatibility and clarity.
        | Uses the same Meta provider but can be referenced as 'messenger'.
        |
        */
        'messenger' => [
            'enabled' => true,
            'type' => 'meta',
            'alias_for' => 'meta',
            'class' => \WhatsApp\Adapter\Providers\Meta\MetaProvider::class,
            'config' => [
                'page_access_token' => env('META_PAGE_ACCESS_TOKEN'),
                'app_id' => env('META_APP_ID'),
                'app_secret' => env('META_APP_SECRET'),
                'page_id' => env('META_PAGE_ID'),
                'verify_token' => env('META_VERIFY_TOKEN'),
                'api_version' => env('META_API_VERSION', 'v21.0'),
                'base_url' => 'https://graph.facebook.com',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Infobip RCS Provider
        |--------------------------------------------------------------------------
        |
        | Rich Communication Services (RCS) provider through Infobip.
        | Supports rich cards, carousels, suggested actions, and file sharing.
        |
        */
        'infobip-rcs' => [
            'enabled' => true,
            'type' => 'rcs',
            'class' => \WhatsApp\Adapter\Providers\Infobip\InfobipRcsProvider::class,
            'config' => [
                'api_key' => env('INFOBIP_API_KEY'),
                'base_url' => env('INFOBIP_BASE_URL', 'https://api.infobip.com'),
                'sender' => env('INFOBIP_RCS_SENDER', env('INFOBIP_SENDER')),
                'webhook_secret' => env('INFOBIP_WEBHOOK_SECRET'),
            ],
            'features' => [
                'text_messages' => true,
                'file_messages' => true,
                'rich_cards' => true,
                'carousels' => true,
                'suggested_actions' => true,
                'suggested_replies' => true,
                'delivery_reports' => true,
                'read_receipts' => true,
                'hsm_templates' => false, // RCS doesn't use templates
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Capabilities
    |--------------------------------------------------------------------------
    |
    | Define which capabilities are required for different use cases.
    |
    */

    'capabilities' => [
        'required' => [
            'text_messages',
            'delivery_reports',
        ],
        'optional' => [
            'media_messages',
            'interactive_buttons',
            'interactive_lists',
            'hsm_templates',
            'read_receipts',
        ],
    ],
];
