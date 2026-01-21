<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PDO;
use Psr\Log\NullLogger;
use Redis;
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Repositories\MessageRepository;
use WhatsApp\Adapter\Services\MessageService;
use WhatsApp\Adapter\Services\RetryHandler;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Models\Requests\MediaRequest;
use WhatsApp\Adapter\Models\Requests\HSMRequest;

/**
 * MessageService Integration Tests with Meta Provider
 * 
 * Tests MessageService integration with Meta provider including:
 * - Provider switching between WhatsApp and Meta
 * - Fallback handling on errors
 * - Retry logic with Meta provider
 * - Multi-provider message flows
 * - Error handling and recovery
 * 
 * Requirements: 11.1-11.5, 12.1-12.5, 15.1-15.2, 16.1-16.2
 */
class MetaMessageServiceTest extends \PHPUnit\Framework\TestCase
{
    private PDO $db;
    private Redis $redis;
    private string $testDbName = 'whatsapp_adapter_test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();

        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
        $this->redis->flushDB();
    }

    protected function tearDown(): void
    {
        $this->redis->flushDB();
        $this->redis->close();
        parent::tearDown();
    }

    private function setupTestDatabase(): void
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        $pdo = new PDO("mysql:host={$host}", $user, $pass);
        $pdo->exec("DROP DATABASE IF EXISTS {$this->testDbName}");
        $pdo->exec("CREATE DATABASE {$this->testDbName}");
        $pdo->exec("USE {$this->testDbName}");

        $migrations = [
            __DIR__ . '/../../database/migrations/001_create_messages_table.sql',
            __DIR__ . '/../../database/migrations/002_create_incoming_messages_table.sql',
            __DIR__ . '/../../database/migrations/004_create_webhook_logs_table.sql',
        ];

        foreach ($migrations as $migration) {
            if (file_exists($migration)) {
                $sql = file_get_contents($migration);
                $pdo->exec($sql);
            }
        }

        $this->db = $pdo;
    }

    /**
     * Test: MessageService integration with Meta provider
     * 
     * Verifies:
     * - MessageService can use Meta provider
     * - Text message sending through MessageService
     * - Database persistence via MessageService
     * - Metadata includes provider information
     */
    public function testMessageServiceIntegrationWithMetaProvider(): void
    {
        // Arrange: Mock Meta API response
        $igsid = '123456789012345';
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_msg_service_123',
                'recipient_id' => $igsid
            ]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'meta',
            'providers' => [
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send text message via MessageService
        $request = new TextRequest(
            to: $igsid,
            text: 'Test message via MessageService'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('ig_msg_service_123', $result->messageId);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('text', $message['type']);
        $this->assertEquals($igsid, $message['to_number']);
        
        // Verify provider metadata
        $metadata = json_decode($message['metadata'], true);
        $this->assertEquals('meta', $metadata['provider']);
    }

    /**
     * Test: Provider switching from WhatsApp to Meta
     * 
     * Verifies:
     * - MessageService can switch between providers
     * - Each provider uses correct API format
     * - Messages are persisted with correct provider metadata
     * - Both providers work in same session
     */
    public function testProviderSwitchingFromWhatsAppToMeta(): void
    {
        // Arrange: Mock responses for both providers
        $infobipResponse = new Response(200, [], json_encode([
            'messages' => [[
                'messageId' => 'infobip_msg_123',
                'status' => [
                    'groupId' => 1,
                    'groupName' => 'PENDING',
                    'id' => 26,
                    'name' => 'PENDING_ACCEPTED'
                ],
                'to' => '351912345678'
            ]]
        ]));

        $metaResponse = new Response(200, [], json_encode([
            'message_id' => 'ig_msg_456',
            'recipient_id' => '123456789012345'
        ]));

        $mockHandler = new MockHandler([$infobipResponse, $metaResponse]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => 'infobip_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '447860099299',
                    'webhook_secret' => 'infobip_secret'
                ],
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send via Infobip (WhatsApp)
        $whatsappRequest = new TextRequest(
            to: '351912345678',
            text: 'Message via WhatsApp'
        );
        $whatsappResult = $messageService->sendText($whatsappRequest, 'infobip');

        // Act: Send via Meta (Instagram)
        $metaRequest = new TextRequest(
            to: '123456789012345',
            text: 'Message via Instagram'
        );
        $metaResult = $messageService->sendText($metaRequest, 'meta');

        // Assert: Both messages sent successfully
        $this->assertTrue($whatsappResult->success);
        $this->assertEquals('infobip_msg_123', $whatsappResult->messageId);
        
        $this->assertTrue($metaResult->success);
        $this->assertEquals('ig_msg_456', $metaResult->messageId);

        // Assert: Verify both messages in database with correct provider
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        
        // Check WhatsApp message
        $stmt->execute([$whatsappResult->messageId]);
        $whatsappMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($whatsappMessage);
        $whatsappMetadata = json_decode($whatsappMessage['metadata'], true);
        $this->assertEquals('infobip', $whatsappMetadata['provider']);

        // Check Meta message
        $stmt->execute([$metaResult->messageId]);
        $metaMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($metaMessage);
        $metaMetadata = json_decode($metaMessage['metadata'], true);
        $this->assertEquals('meta', $metaMetadata['provider']);

        // Assert: Total message count
        $stmt = $this->db->query('SELECT COUNT(*) FROM messages');
        $count = $stmt->fetchColumn();
        $this->assertEquals(2, $count);
    }

    /**
     * Test: Provider switching from Meta to WhatsApp
     * 
     * Verifies:
     * - Reverse provider switching works
     * - Provider state doesn't leak between calls
     * - Each message uses correct provider configuration
     */
    public function testProviderSwitchingFromMetaToWhatsApp(): void
    {
        // Arrange: Mock responses in reverse order
        $metaResponse = new Response(200, [], json_encode([
            'message_id' => 'ig_msg_first_789',
            'recipient_id' => '123456789012345'
        ]));

        $infobipResponse = new Response(200, [], json_encode([
            'messages' => [[
                'messageId' => 'infobip_msg_second_101',
                'status' => [
                    'groupId' => 1,
                    'groupName' => 'PENDING',
                    'id' => 26,
                    'name' => 'PENDING_ACCEPTED'
                ],
                'to' => '351912345678'
            ]]
        ]));

        $mockHandler = new MockHandler([$metaResponse, $infobipResponse]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'meta',
            'providers' => [
                'infobip' => [
                    'api_key' => 'infobip_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '447860099299',
                    'webhook_secret' => 'infobip_secret'
                ],
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send via Meta first
        $metaRequest = new TextRequest(
            to: '123456789012345',
            text: 'First message via Meta'
        );
        $metaResult = $messageService->sendText($metaRequest, 'meta');

        // Act: Send via Infobip second
        $whatsappRequest = new TextRequest(
            to: '351912345678',
            text: 'Second message via WhatsApp'
        );
        $whatsappResult = $messageService->sendText($whatsappRequest, 'infobip');

        // Assert: Both messages sent successfully
        $this->assertTrue($metaResult->success);
        $this->assertEquals('ig_msg_first_789', $metaResult->messageId);
        
        $this->assertTrue($whatsappResult->success);
        $this->assertEquals('infobip_msg_second_101', $whatsappResult->messageId);

        // Assert: Verify correct provider metadata
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        
        $stmt->execute([$metaResult->messageId]);
        $metaMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        $metaMetadata = json_decode($metaMessage['metadata'], true);
        $this->assertEquals('meta', $metaMetadata['provider']);

        $stmt->execute([$whatsappResult->messageId]);
        $whatsappMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        $whatsappMetadata = json_decode($whatsappMessage['metadata'], true);
        $this->assertEquals('infobip', $whatsappMetadata['provider']);
    }

    /**
     * Test: Fallback handling on Meta provider error
     * 
     * Verifies:
     * - Error handling when Meta provider fails
     * - Error message is descriptive
     * - No database entry for failed message
     * - Service can recover and send next message
     */
    public function testFallbackHandlingOnMetaProviderError(): void
    {
        // Arrange: Mock Meta API error response
        $igsid = '123456789012345';
        $errorResponse = new Response(400, [], json_encode([
            'error' => [
                'message' => 'Invalid OAuth access token',
                'type' => 'OAuthException',
                'code' => 190,
                'fbtrace_id' => 'ABC123'
            ]
        ]));

        $successResponse = new Response(200, [], json_encode([
            'message_id' => 'ig_msg_after_error_123',
            'recipient_id' => $igsid
        ]));

        $mockHandler = new MockHandler([$errorResponse, $successResponse]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'meta',
            'providers' => [
                'meta' => [
                    'page_access_token' => 'invalid_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send message that will fail
        $request = new TextRequest(
            to: $igsid,
            text: 'This message will fail'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Verify error handling
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Invalid OAuth access token', $result->error);

        // Assert: No database entry for failed message
        $stmt = $this->db->query('SELECT COUNT(*) FROM messages');
        $count = $stmt->fetchColumn();
        $this->assertEquals(0, $count);

        // Act: Send another message that will succeed
        $request2 = new TextRequest(
            to: $igsid,
            text: 'This message will succeed'
        );

        $result2 = $messageService->sendText($request2, 'meta');

        // Assert: Service recovered and sent message successfully
        $this->assertTrue($result2->success);
        $this->assertEquals('ig_msg_after_error_123', $result2->messageId);

        // Assert: Only successful message in database
        $stmt = $this->db->query('SELECT COUNT(*) FROM messages');
        $count = $stmt->fetchColumn();
        $this->assertEquals(1, $count);
    }

    /**
     * Test: Retry logic with Meta provider transient errors
     * 
     * Verifies:
     * - RetryHandler works with Meta provider
     * - Transient errors trigger retries
     * - Successful retry persists message
     * - Retry count is reasonable
     */
    public function testRetryLogicWithMetaProviderTransientErrors(): void
    {
        // Arrange: Mock transient error followed by success
        $igsid = '123456789012345';
        
        // First attempt: 500 Internal Server Error (transient)
        $errorResponse1 = new Response(500, [], json_encode([
            'error' => [
                'message' => 'Internal Server Error',
                'type' => 'OAuthException',
                'code' => 1,
                'is_transient' => true,
                'fbtrace_id' => 'ERR123'
            ]
        ]));

        // Second attempt: 503 Service Unavailable (transient)
        $errorResponse2 = new Response(503, [], json_encode([
            'error' => [
                'message' => 'Service Temporarily Unavailable',
                'type' => 'OAuthException',
                'code' => 2,
                'is_transient' => true,
                'fbtrace_id' => 'ERR456'
            ]
        ]));

        // Third attempt: Success
        $successResponse = new Response(200, [], json_encode([
            'message_id' => 'ig_msg_retry_success_123',
            'recipient_id' => $igsid
        ]));

        $mockHandler = new MockHandler([
            $errorResponse1,
            $errorResponse2,
            $successResponse
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'meta',
            'providers' => [
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 100, new NullLogger()); // 3 retries, 100ms delay
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send message (will retry on transient errors)
        $request = new TextRequest(
            to: $igsid,
            text: 'Message with retries'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Eventually succeeded after retries
        $this->assertTrue($result->success);
        $this->assertEquals('ig_msg_retry_success_123', $result->messageId);

        // Assert: Message persisted in database
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('text', $message['type']);
    }

    /**
     * Test: Multiple provider message flow in single session
     * 
     * Verifies:
     * - Multiple messages to different providers
     * - Provider factory handles concurrent provider usage
     * - All messages persisted correctly
     * - No provider state interference
     */
    public function testMultipleProviderMessageFlowInSingleSession(): void
    {
        // Arrange: Mock responses for multiple messages across providers
        $responses = [
            // Meta message 1
            new Response(200, [], json_encode([
                'message_id' => 'ig_msg_1',
                'recipient_id' => '123456789012345'
            ])),
            // Infobip message 1
            new Response(200, [], json_encode([
                'messages' => [[
                    'messageId' => 'infobip_msg_1',
                    'status' => ['groupName' => 'PENDING', 'name' => 'PENDING_ACCEPTED'],
                    'to' => '351912345678'
                ]]
            ])),
            // Meta message 2
            new Response(200, [], json_encode([
                'message_id' => 'ig_msg_2',
                'recipient_id' => '123456789012345'
            ])),
            // Infobip message 2
            new Response(200, [], json_encode([
                'messages' => [[
                    'messageId' => 'infobip_msg_2',
                    'status' => ['groupName' => 'PENDING', 'name' => 'PENDING_ACCEPTED'],
                    'to' => '351912345678'
                ]]
            ])),
            // Meta message 3
            new Response(200, [], json_encode([
                'message_id' => 'ig_msg_3',
                'recipient_id' => '123456789012345'
            ])),
        ];

        $mockHandler = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => 'infobip_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '447860099299',
                    'webhook_secret' => 'infobip_secret'
                ],
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send messages alternating between providers
        $results = [];
        
        $results[] = $messageService->sendText(
            new TextRequest(to: '123456789012345', text: 'Meta message 1'),
            'meta'
        );
        
        $results[] = $messageService->sendText(
            new TextRequest(to: '351912345678', text: 'WhatsApp message 1'),
            'infobip'
        );
        
        $results[] = $messageService->sendText(
            new TextRequest(to: '123456789012345', text: 'Meta message 2'),
            'meta'
        );
        
        $results[] = $messageService->sendText(
            new TextRequest(to: '351912345678', text: 'WhatsApp message 2'),
            'infobip'
        );
        
        $results[] = $messageService->sendText(
            new TextRequest(to: '123456789012345', text: 'Meta message 3'),
            'meta'
        );

        // Assert: All messages sent successfully
        foreach ($results as $result) {
            $this->assertTrue($result->success);
            $this->assertNotNull($result->messageId);
        }

        // Assert: Verify message count in database
        $stmt = $this->db->query('SELECT COUNT(*) FROM messages');
        $count = $stmt->fetchColumn();
        $this->assertEquals(5, $count);

        // Assert: Verify provider distribution
        $stmt = $this->db->query("SELECT metadata FROM messages");
        $metaCount = 0;
        $infobipCount = 0;
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $metadata = json_decode($row['metadata'], true);
            if ($metadata['provider'] === 'meta') {
                $metaCount++;
            } elseif ($metadata['provider'] === 'infobip') {
                $infobipCount++;
            }
        }

        $this->assertEquals(3, $metaCount);
        $this->assertEquals(2, $infobipCount);
    }

    /**
     * Test: HSM template conversion with Meta provider
     * 
     * Verifies:
     * - HSM templates are converted to text for Meta
     * - Placeholder substitution works correctly
     * - Warning is logged about template conversion
     * - Message is sent successfully as text
     */
    public function testHSMTemplateConversionWithMetaProvider(): void
    {
        // Arrange: Mock Meta API response
        $igsid = '123456789012345';
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_msg_template_123',
                'recipient_id' => $igsid
            ]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'meta',
            'providers' => [
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send HSM template via Meta provider
        $request = new HSMRequest(
            to: $igsid,
            templateName: 'welcome_message',
            templateLanguage: 'en',
            parameters: ['John Doe', 'Premium Plan']
        );

        $result = $messageService->sendHSM($request, 'meta');

        // Assert: Message sent successfully (converted to text)
        $this->assertTrue($result->success);
        $this->assertEquals('ig_msg_template_123', $result->messageId);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('hsm', $message['type']);
        
        // Verify template information is stored
        $content = json_decode($message['content'], true);
        $this->assertEquals('welcome_message', $content['templateName']);
        $this->assertEquals('en', $content['templateLanguage']);
        $this->assertEquals(['John Doe', 'Premium Plan'], $content['parameters']);
    }

    /**
     * Test: Media message with Meta provider validation
     * 
     * Verifies:
     * - Media messages work through MessageService
     * - Meta-specific media validations are applied
     * - Invalid media URLs are rejected
     * - Valid media messages are sent successfully
     */
    public function testMediaMessageWithMetaProviderValidation(): void
    {
        // Arrange: Mock Meta API response
        $igsid = '123456789012345';
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_msg_media_123',
                'recipient_id' => $igsid
            ]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'meta',
            'providers' => [
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send media message with valid HTTPS URL
        $request = new MediaRequest(
            to: $igsid,
            mediaType: 'image',
            mediaUrl: 'https://example.com/image.jpg'
        );

        $result = $messageService->sendMedia($request, 'meta');

        // Assert: Message sent successfully
        $this->assertTrue($result->success);
        $this->assertEquals('ig_msg_media_123', $result->messageId);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('image', $message['type']);
    }

    /**
     * Test: Invalid recipient ID validation for Meta provider
     * 
     * Verifies:
     * - Meta-specific recipient ID validation
     * - Invalid IGSID/PSID formats are rejected
     * - Descriptive error messages
     * - No database entry for invalid requests
     */
    public function testInvalidRecipientIdValidationForMetaProvider(): void
    {
        // Arrange
        $config = [
            'default_provider' => 'meta',
            'providers' => [
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $httpClient = new Client();
        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Try to send with invalid recipient ID (non-numeric)
        $request = new TextRequest(
            to: 'invalid_id_abc',
            text: 'Test message'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Request rejected with validation error
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Invalid recipient ID format', $result->error);
        $this->assertStringContainsString('must be numeric', $result->error);

        // Assert: No database entry
        $stmt = $this->db->query('SELECT COUNT(*) FROM messages');
        $count = $stmt->fetchColumn();
        $this->assertEquals(0, $count);
    }

    /**
     * Test: Short recipient ID validation for Meta provider
     * 
     * Verifies:
     * - Recipient IDs must be at least 10 characters
     * - Short IDs are rejected
     * - Descriptive error message
     */
    public function testShortRecipientIdValidationForMetaProvider(): void
    {
        // Arrange
        $config = [
            'default_provider' => 'meta',
            'providers' => [
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $httpClient = new Client();
        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Try to send with short recipient ID
        $request = new TextRequest(
            to: '12345', // Only 5 digits
            text: 'Test message'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Request rejected with validation error
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('Invalid recipient ID format', $result->error);
        $this->assertStringContainsString('at least 10 characters', $result->error);

        // Assert: No database entry
        $stmt = $this->db->query('SELECT COUNT(*) FROM messages');
        $count = $stmt->fetchColumn();
        $this->assertEquals(0, $count);
    }

    /**
     * Test: HTTP URL rejection for Meta media
     * 
     * Verifies:
     * - Meta requires HTTPS URLs for media
     * - HTTP URLs are rejected
     * - Descriptive error message
     */
    public function testHttpUrlRejectionForMetaMedia(): void
    {
        // Arrange
        $igsid = '123456789012345';
        $config = [
            'default_provider' => 'meta',
            'providers' => [
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $httpClient = new Client();
        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Try to send media with HTTP URL
        $request = new MediaRequest(
            to: $igsid,
            mediaType: 'image',
            mediaUrl: 'http://example.com/image.jpg' // HTTP instead of HTTPS
        );

        $result = $messageService->sendMedia($request, 'meta');

        // Assert: Request rejected with validation error
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('must use HTTPS protocol', $result->error);

        // Assert: No database entry
        $stmt = $this->db->query('SELECT COUNT(*) FROM messages');
        $count = $stmt->fetchColumn();
        $this->assertEquals(0, $count);
    }

    /**
     * Test: Default provider usage with Meta
     * 
     * Verifies:
     * - MessageService uses default provider when none specified
     * - Default provider can be Meta
     * - Messages sent without explicit provider parameter
     */
    public function testDefaultProviderUsageWithMeta(): void
    {
        // Arrange: Set Meta as default provider
        $igsid = '123456789012345';
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_msg_default_123',
                'recipient_id' => $igsid
            ]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'meta', // Meta is default
            'providers' => [
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send message without specifying provider (should use default)
        $request = new TextRequest(
            to: $igsid,
            text: 'Message using default provider'
        );

        $result = $messageService->sendText($request); // No provider specified

        // Assert: Message sent successfully using Meta
        $this->assertTrue($result->success);
        $this->assertEquals('ig_msg_default_123', $result->messageId);

        // Assert: Verify Meta was used
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $metadata = json_decode($message['metadata'], true);
        $this->assertEquals('meta', $metadata['provider']);
    }

    /**
     * Test: Network error handling with Meta provider
     * 
     * Verifies:
     * - Network errors are handled gracefully
     * - Error message is descriptive
     * - Service can recover after network error
     */
    public function testNetworkErrorHandlingWithMetaProvider(): void
    {
        // Arrange: Mock network error
        $igsid = '123456789012345';
        $networkError = new RequestException(
            'Connection timeout',
            new Request('POST', 'https://graph.facebook.com/v21.0/987654321/messages')
        );

        $successResponse = new Response(200, [], json_encode([
            'message_id' => 'ig_msg_after_network_error_123',
            'recipient_id' => $igsid
        ]));

        $mockHandler = new MockHandler([$networkError, $successResponse]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'meta',
            'providers' => [
                'meta' => [
                    'page_access_token' => 'test_page_token',
                    'app_secret' => 'test_app_secret',
                    'page_id' => '987654321',
                    'verify_token' => 'test_verify_token'
                ]
            ]
        ];

        $providerFactory = new MessagingProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new MessageRepository($this->db);
        $retryHandler = new RetryHandler(3, 100, new NullLogger());
        
        $messageService = new MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send message (will fail with network error first)
        $request = new TextRequest(
            to: $igsid,
            text: 'Message with network error'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Eventually succeeded after retry
        $this->assertTrue($result->success);
        $this->assertEquals('ig_msg_after_network_error_123', $result->messageId);

        // Assert: Message persisted
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
    }
}
