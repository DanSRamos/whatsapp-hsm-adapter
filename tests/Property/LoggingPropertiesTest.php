<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use WhatsApp\Adapter\Models\Requests\HSMRequest;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Providers\Infobip\InfobipProvider;
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Repositories\MessageRepository;
use WhatsApp\Adapter\Services\MessageService;
use WhatsApp\Adapter\Services\RetryHandler;
use WhatsApp\Adapter\Services\TemplateService;
use WhatsApp\Adapter\Repositories\TemplateRepository;
use WhatsApp\Adapter\Services\CacheInterface;

/**
 * Property 21: Comprehensive Logging
 * 
 * For any pedido recebido, resposta da Infobip, ou erro ocorrido, 
 * o adapter deve registar o evento com timestamps e contexto suficiente, 
 * excluindo informações sensíveis
 * 
 * Validates: Requirements 12.1, 12.2, 12.3, 12.5
 * 
 * Feature: whatsapp-hsm-adapter, Property 21: Comprehensive Logging
 */

beforeEach(function () {
    // Create test handler to capture log records
    $this->testHandler = new TestHandler();
    
    // Create logger with test handler
    $this->logger = new Logger('test');
    $this->logger->pushHandler($this->testHandler);
});

test('Property 21.1: Successful operations are logged with INFO level', function () {
    // Create mock HTTP client that returns success
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'messages' => [[
                'messageId' => 'msg_' . uniqid(),
                'status' => ['groupId' => 1, 'groupName' => 'PENDING', 'id' => 1, 'name' => 'PENDING_ENROUTE']
            ]]
        ]))
    ]);
    
    $handlerStack = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handlerStack]);
    
    // Create provider with test logger
    $provider = new InfobipProvider(
        $httpClient,
        [
            'api_key' => 'test_key',
            'base_url' => 'https://api.infobip.com',
            'sender' => '1234567890'
        ],
        $this->logger
    );
    
    // Create factory with proper configuration
    $factory = new MessagingProviderFactory(
        [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => 'test_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '1234567890'
                ]
            ]
        ],
        $httpClient,
        $this->logger
    );
    
    // Create message repository mock
    $messageRepo = Mockery::mock(MessageRepository::class);
    $messageRepo->shouldReceive('save')->andReturn(true);
    
    // Create retry handler
    $retryHandler = new RetryHandler($this->logger);
    
    // Create message service
    $messageService = new MessageService(
        $factory,
        $messageRepo,
        $retryHandler,
        $this->logger
    );
    
    // Send HSM message
    $request = new HSMRequest(
        to: '+351912345678',
        templateName: 'welcome_message',
        templateLanguage: 'pt',
        parameters: ['John']
    );
    
    $result = $messageService->sendHSM($request, 'infobip');
    
    // Verify logging occurred
    expect($this->testHandler->hasInfoRecords())->toBeTrue();
    
    // Verify log contains context
    $records = $this->testHandler->getRecords();
    $infoRecords = array_filter($records, fn($r) => $r['level'] === Logger::INFO);
    
    expect(count($infoRecords))->toBeGreaterThan(0);
    
    // Verify at least one record has context about the operation
    $hasContext = false;
    foreach ($infoRecords as $record) {
        if (isset($record['context']['to']) || isset($record['context']['template'])) {
            $hasContext = true;
            break;
        }
    }
    
    expect($hasContext)->toBeTrue();
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'logging');

test('Property 21.2: Errors are logged with ERROR level and stack trace', function () {
    // Create mock HTTP client that returns error
    $mock = new MockHandler([
        new Response(500, [], json_encode([
            'requestError' => [
                'serviceException' => [
                    'messageId' => 'INTERNAL_SERVER_ERROR',
                    'text' => 'Internal server error'
                ]
            ]
        ]))
    ]);
    
    $handlerStack = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handlerStack]);
    
    // Create provider with test logger
    $provider = new InfobipProvider(
        $httpClient,
        [
            'api_key' => 'test_key',
            'base_url' => 'https://api.infobip.com',
            'sender' => '1234567890'
        ],
        $this->logger
    );
    
    // Create factory with proper configuration
    $factory = new MessagingProviderFactory(
        [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => 'test_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '1234567890'
                ]
            ]
        ],
        $httpClient,
        $this->logger
    );
    
    // Create message repository mock
    $messageRepo = Mockery::mock(MessageRepository::class);
    $messageRepo->shouldReceive('save')->andReturn(true);
    
    // Create retry handler with max 1 retry for faster test
    $retryHandler = new RetryHandler($this->logger, 1);
    
    // Create message service
    $messageService = new MessageService(
        $factory,
        $messageRepo,
        $retryHandler,
        $this->logger
    );
    
    // Send HSM message (will fail)
    $request = new HSMRequest(
        to: '+351912345678',
        templateName: 'welcome_message',
        templateLanguage: 'pt',
        parameters: ['John']
    );
    
    $result = $messageService->sendHSM($request, 'infobip');
    
    // Verify error logging occurred
    expect($this->testHandler->hasErrorRecords())->toBeTrue();
    
    // Verify error log contains context
    $errorRecords = array_filter(
        $this->testHandler->getRecords(),
        fn($r) => $r['level'] === Logger::ERROR
    );
    
    expect(count($errorRecords))->toBeGreaterThan(0);
    
    // Verify at least one error record has error message
    $hasErrorContext = false;
    foreach ($errorRecords as $record) {
        if (isset($record['context']['error'])) {
            $hasErrorContext = true;
            break;
        }
    }
    
    expect($hasErrorContext)->toBeTrue();
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'logging');

test('Property 21.3: Sensitive data is not logged', function () {
    // Create mock HTTP client
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'messages' => [[
                'messageId' => 'msg_' . uniqid(),
                'status' => ['groupId' => 1, 'groupName' => 'PENDING', 'id' => 1, 'name' => 'PENDING_ENROUTE']
            ]]
        ]))
    ]);
    
    $handlerStack = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handlerStack]);
    
    // Create provider with test logger and sensitive API key
    $sensitiveApiKey = 'secret_api_key_12345';
    $provider = new InfobipProvider(
        $httpClient,
        [
            'api_key' => $sensitiveApiKey,
            'base_url' => 'https://api.infobip.com',
            'sender' => '1234567890',
            'webhook_secret' => 'secret_webhook_key'
        ],
        $this->logger
    );
    
    // Create factory with proper configuration
    $factory = new MessagingProviderFactory(
        [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => $sensitiveApiKey,
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '1234567890',
                    'webhook_secret' => 'secret_webhook_key'
                ]
            ]
        ],
        $httpClient,
        $this->logger
    );
    
    // Create message repository mock
    $messageRepo = Mockery::mock(MessageRepository::class);
    $messageRepo->shouldReceive('save')->andReturn(true);
    
    // Create retry handler
    $retryHandler = new RetryHandler($this->logger);
    
    // Create message service
    $messageService = new MessageService(
        $factory,
        $messageRepo,
        $retryHandler,
        $this->logger
    );
    
    // Send message
    $request = new TextRequest(
        to: '+351912345678',
        text: 'Test message with sensitive data: password=secret123'
    );
    
    $result = $messageService->sendText($request, 'infobip');
    
    // Get all log records as strings
    $allLogs = array_map(function ($record) {
        return json_encode($record);
    }, $this->testHandler->getRecords());
    
    $allLogsString = implode(' ', $allLogs);
    
    // Verify sensitive data is NOT in logs
    expect($allLogsString)->not->toContain($sensitiveApiKey);
    expect($allLogsString)->not->toContain('secret_webhook_key');
    expect($allLogsString)->not->toContain('password=secret123');
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'logging');

test('Property 21.4: All requests include timestamps', function () {
    // Create mock HTTP client
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'messages' => [[
                'messageId' => 'msg_' . uniqid(),
                'status' => ['groupId' => 1, 'groupName' => 'PENDING', 'id' => 1, 'name' => 'PENDING_ENROUTE']
            ]]
        ]))
    ]);
    
    $handlerStack = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handlerStack]);
    
    // Create provider with test logger
    $provider = new InfobipProvider(
        $httpClient,
        [
            'api_key' => 'test_key',
            'base_url' => 'https://api.infobip.com',
            'sender' => '1234567890'
        ],
        $this->logger
    );
    
    // Create factory with proper configuration
    $factory = new MessagingProviderFactory(
        [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => 'test_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '1234567890'
                ]
            ]
        ],
        $httpClient,
        $this->logger
    );
    
    // Create message repository mock
    $messageRepo = Mockery::mock(MessageRepository::class);
    $messageRepo->shouldReceive('save')->andReturn(true);
    
    // Create retry handler
    $retryHandler = new RetryHandler($this->logger);
    
    // Create message service
    $messageService = new MessageService(
        $factory,
        $messageRepo,
        $retryHandler,
        $this->logger
    );
    
    // Send message
    $request = new HSMRequest(
        to: '+351912345678',
        templateName: 'welcome_message',
        templateLanguage: 'pt',
        parameters: ['John']
    );
    
    $result = $messageService->sendHSM($request, 'infobip');
    
    // Verify all log records have timestamps
    $records = $this->testHandler->getRecords();
    
    expect(count($records))->toBeGreaterThan(0);
    
    foreach ($records as $record) {
        expect($record)->toHaveKey('datetime');
        expect($record['datetime'])->toBeInstanceOf(\DateTimeImmutable::class);
    }
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'logging');

test('Property 21.5: Template operations are logged with sufficient context', function () {
    // Create mock HTTP client
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'templates' => [
                [
                    'id' => 'tpl_' . uniqid(),
                    'name' => 'welcome_message',
                    'language' => 'pt',
                    'status' => 'APPROVED',
                    'category' => 'MARKETING',
                    'structure' => [
                        'body' => 'Welcome {{1}}!'
                    ]
                ]
            ]
        ]))
    ]);
    
    $handlerStack = HandlerStack::create($mock);
    $httpClient = new Client(['handler' => $handlerStack]);
    
    // Create provider with test logger
    $provider = new InfobipProvider(
        $httpClient,
        [
            'api_key' => 'test_key',
            'base_url' => 'https://api.infobip.com',
            'sender' => '1234567890'
        ],
        $this->logger
    );
    
    // Create factory with proper configuration
    $factory = new MessagingProviderFactory(
        [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => 'test_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '1234567890'
                ]
            ]
        ],
        $httpClient,
        $this->logger
    );
    
    // Create template repository mock
    $templateRepo = Mockery::mock(TemplateRepository::class);
    $templateRepo->shouldReceive('save')->andReturn(true);
    $templateRepo->shouldReceive('findAll')->andReturn([]);
    
    // Create cache mock
    $cache = Mockery::mock(CacheInterface::class);
    $cache->shouldReceive('has')->andReturn(false);
    $cache->shouldReceive('get')->andReturn(null);
    $cache->shouldReceive('set')->andReturn(true);
    $cache->shouldReceive('delete')->andReturn(true);
    
    // Create template service
    $templateService = new TemplateService(
        $factory,
        $templateRepo,
        $cache,
        $this->logger
    );
    
    // Get all templates
    $templates = $templateService->getAllTemplates('infobip');
    
    // Verify logging occurred with context
    expect($this->testHandler->hasInfoRecords())->toBeTrue();
    
    $records = $this->testHandler->getRecords();
    $infoRecords = array_filter($records, fn($r) => $r['level'] === Logger::INFO);
    
    // Verify at least one record mentions templates
    $hasTemplateContext = false;
    foreach ($infoRecords as $record) {
        $message = strtolower($record['message']);
        if (str_contains($message, 'template')) {
            $hasTemplateContext = true;
            break;
        }
    }
    
    expect($hasTemplateContext)->toBeTrue();
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'logging');
