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

// Bootstrap the application
use WhatsApp\Adapter\Http\Router;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;

try {
    // Create PSR-7 request from globals
    $request = ServerRequest::fromGlobals();
    
    // Load configuration
    $dbConfig = require __DIR__ . '/../config/database.php';
    $providersConfig = require __DIR__ . '/../config/providers.php';
    $metaConfig = require __DIR__ . '/../config/meta.php';
    
    // Get connection config
    $connectionName = $dbConfig['default'];
    $config = $dbConfig['connections'][$connectionName];
    
    // Create database connection
    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['driver'],
        $config['host'],
        $config['port'],
        $config['database']
    );
    
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Create logger
    $loggerFactory = new WhatsApp\Adapter\Services\LoggerFactory();
    $logger = $loggerFactory->createLogger('app');
    
    // Create HTTP client
    $httpClient = new GuzzleHttp\Client([
        'timeout' => 30,
        'connect_timeout' => 10,
    ]);
    
    // Create repositories
    $messageRepository = new WhatsApp\Adapter\Repositories\MessageRepository($pdo, $logger);
    $templateRepository = new WhatsApp\Adapter\Repositories\TemplateRepository($pdo, $logger);
    
    // Create provider factory
    $providerFactory = new WhatsApp\Adapter\Providers\MessagingProviderFactory(
        $providersConfig,
        $httpClient,
        $logger
    );
    
    // Create retry handler
    $retryHandler = new WhatsApp\Adapter\Services\RetryHandler($logger);
    
    // Create cache (simple array cache for now)
    $cache = new WhatsApp\Adapter\Services\ArrayCache();
    
    // Create services
    $messageService = new WhatsApp\Adapter\Services\MessageService(
        $providerFactory,
        $messageRepository,
        $retryHandler,
        $logger
    );
    
    $templateService = new WhatsApp\Adapter\Services\TemplateService(
        $providerFactory,
        $templateRepository,
        $cache,
        $logger
    );
    
    // Create controllers
    $healthController = new WhatsApp\Adapter\Http\Controllers\HealthController(
        $pdo,
        $cache,
        $httpClient,
        $providersConfig,
        $logger
    );
    $messageController = new WhatsApp\Adapter\Http\Controllers\MessageController($messageService, $logger);
    $templateController = new WhatsApp\Adapter\Http\Controllers\TemplateController($templateService, $logger);
    $webhookController = new WhatsApp\Adapter\Http\Controllers\WebhookController(
        $providerFactory,
        $messageService,
        $templateService,
        $logger
    );
    
    // Create dependency container
    $container = [
        WhatsApp\Adapter\Http\Controllers\HealthController::class => $healthController,
        WhatsApp\Adapter\Http\Controllers\MessageController::class => $messageController,
        WhatsApp\Adapter\Http\Controllers\TemplateController::class => $templateController,
        WhatsApp\Adapter\Http\Controllers\WebhookController::class => $webhookController,
    ];
    
    // Create router
    $router = new Router();
    
    // Load routes
    $routeLoader = require __DIR__ . '/../config/routes.php';
    $routeLoader($router, $container);
    
    // Handle request
    $response = $router->dispatch($request);
    
    // Send response
    http_response_code($response->getStatusCode());
    
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header(sprintf('%s: %s', $name, $value), false);
        }
    }
    
    echo $response->getBody();
    
} catch (\Throwable $e) {
    // Log error
    if (isset($logger)) {
        $logger->error('Application error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    // Send error response
    http_response_code(500);
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'INTERNAL_SERVER_ERROR',
            'message' => $_ENV['APP_DEBUG'] === 'true' 
                ? $e->getMessage() 
                : 'An internal server error occurred',
            'trace' => $_ENV['APP_DEBUG'] === 'true' 
                ? $e->getTraceAsString() 
                : null
        ],
        'timestamp' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM)
    ], JSON_PRETTY_PRINT);
}
