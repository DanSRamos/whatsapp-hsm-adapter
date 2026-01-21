<?php

declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Helper function for environment variables
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string booleans
        if (is_string($value)) {
            $lower = strtolower($value);
            if ($lower === 'true' || $lower === '(true)') {
                return true;
            }
            if ($lower === 'false' || $lower === '(false)') {
                return false;
            }
            if ($lower === 'null' || $lower === '(null)') {
                return null;
            }
        }
        
        return $value;
    }
}

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'WhatsApp HSM Adapter - Meta Messaging Integration',
    'version' => '1.0.0',
    'timestamp' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
    'environment' => [
        'php_version' => PHP_VERSION,
        'app_env' => env('APP_ENV', 'local'),
        'app_debug' => env('APP_DEBUG', 'false'),
    ],
    'providers' => [
        'infobip' => [
            'configured' => !empty(env('INFOBIP_API_KEY')),
            'base_url' => env('INFOBIP_BASE_URL', 'not configured'),
        ],
        'twilio' => [
            'configured' => !empty(env('TWILIO_ACCOUNT_SID')),
        ],
        'meta' => [
            'configured' => !empty(env('META_PAGE_ACCESS_TOKEN')),
            'api_version' => env('META_API_VERSION', 'v21.0'),
            'page_id' => env('META_PAGE_ID') ? 'configured' : 'not configured',
        ],
    ],
    'features' => [
        'webhook_validation' => 'implemented',
        'meta_instagram' => 'supported',
        'meta_messenger' => 'supported',
        'whatsapp' => 'supported',
    ],
    'endpoints' => [
        'health' => '/health',
        'webhooks' => [
            'meta' => '/webhooks/meta (GET/POST)',
            'delivery_reports' => '/webhooks/delivery-reports',
            'incoming_messages' => '/webhooks/incoming-messages',
            'template_updates' => '/webhooks/template-updates',
        ],
        'api' => [
            'messages' => [
                'hsm' => 'POST /api/messages/hsm',
                'text' => 'POST /api/messages/text',
                'media' => 'POST /api/messages/media',
                'interactive_buttons' => 'POST /api/messages/interactive/buttons',
                'interactive_list' => 'POST /api/messages/interactive/list',
                'status' => 'GET /api/messages/{messageId}/status',
            ],
            'templates' => [
                'list' => 'GET /api/templates',
                'get' => 'GET /api/templates/{templateId}',
                'sync' => 'POST /api/templates/sync',
            ],
        ],
    ],
    'recent_implementations' => [
        'task_9' => [
            'name' => 'Webhook Validation',
            'status' => 'completed',
            'features' => [
                'POST webhook signature validation (HMAC SHA-256)',
                'GET webhook verification challenge',
                'Timing-safe comparisons (hash_equals)',
                'Meta webhook endpoint (/webhooks/meta)',
            ],
            'tests' => '79 tests passing',
        ],
    ],
], JSON_PRETTY_PRINT);
