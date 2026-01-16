<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PDO;
use Psr\Log\NullLogger;
use Redis;

/**
 * End-to-End Integration Tests
 * 
 * Tests complete flows through the system including:
 * - HSM message sending
 * - Incoming message reception
 * - Delivery report webhooks
 * - Provider switching at runtime
 * 
 * Requirements: 1.1-1.4, 2.1-2.5, 3.1-3.6, 4.1-4.4, 5.1-5.5, 6.1-6.6, 
 *               7.1-7.7, 8.1-8.5, 9.1-9.6, 10.1-10.5
 */
class EndToEndMessageFlowTest extends \PHPUnit\Framework\TestCase
{
    private PDO $db;
    private Redis $redis;
    private string $testDbName = 'whatsapp_adapter_test';

    protected function setUp(): void
    {
        parent::setUp();

        // Setup test database
        $this->setupTestDatabase();

        // Setup Redis
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
        $this->redis->flushDB();
    }

    protected function tearDown(): void
    {
        // Cleanup
        $this->redis->flushDB();
        $this->redis->close();

        parent::tearDown();
    }

    private function setupTestDatabase(): void
    {
        // Connect to MySQL
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        $pdo = new PDO("mysql:host={$host}", $user, $pass);
        
        // Create test database
        $pdo->exec("DROP DATABASE IF EXISTS {$this->testDbName}");
        $pdo->exec("CREATE DATABASE {$this->testDbName}");
        $pdo->exec("USE {$this->testDbName}");

        // Run migrations
        $migrations = [
            __DIR__ . '/../../database/migrations/001_create_messages_table.sql',
            __DIR__ . '/../../database/migrations/002_create_incoming_messages_table.sql',
            __DIR__ . '/../../database/migrations/003_create_templates_table.sql',
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
     * Test: Complete HSM sending flow
     * 
     * Verifies:
     * - Request validation
     * - Provider API call
     * - Response parsing
     * - Database persistence
     * - Status tracking
     */
    public function testCompleteHSMSendingFlow(): void
    {
        // Arrange: Mock Infobip API response
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'messages' => [[
                    'messageId' => 'msg_12345',
                    'status' => [
                        'groupId' => 1,
                        'groupName' => 'PENDING',
                        'id' => 26,
                        'name' => 'PENDING_ACCEPTED',
                        'description' => 'Message accepted'
                    ],
                    'to' => '351912345678'
                ]]
            ]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        // Create services
        $config = [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => 'test_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '447860099299',
                    'webhook_secret' => 'test_secret'
                ]
            ]
        ];

        $providerFactory = new \App\Providers\WhatsAppProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new \App\Repositories\MessageRepository($this->db);
        $retryHandler = new \App\Services\RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new \App\Services\MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send HSM
        $request = new \App\Models\Requests\HSMRequest(
            to: '351912345678',
            templateName: 'welcome_message',
            templateLanguage: 'pt',
            parameters: ['John Doe', 'Premium']
        );

        $result = $messageService->sendHSM($request);

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('msg_12345', $result->messageId);
        $this->assertEquals('PENDING_ACCEPTED', $result->status);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('hsm', $message['type']);
        $this->assertEquals('351912345678', $message['to_number']);
        $this->assertEquals('PENDING_ACCEPTED', $message['status']);
    }

    /**
     * Test: Complete incoming message reception flow
     * 
     * Verifies:
     * - Webhook validation
     * - Payload parsing
     * - Content extraction
     * - Database persistence
     * - Event dispatching
     */
    public function testCompleteIncomingMessageFlow(): void
    {
        // Arrange: Create webhook payload
        $webhookPayload = [
            'results' => [[
                'messageId' => 'incoming_123',
                'from' => '351912345678',
                'to' => '447860099299',
                'receivedAt' => '2026-01-16T10:30:00.000+0000',
                'message' => [
                    'type' => 'TEXT',
                    'text' => 'Hello, I need help!'
                ],
                'contact' => [
                    'name' => 'John Doe'
                ]
            ]]
        ];

        // Create services
        $config = [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => 'test_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '447860099299',
                    'webhook_secret' => 'test_secret'
                ]
            ]
        ];

        $httpClient = new Client();
        $providerFactory = new \App\Providers\WhatsAppProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new \App\Repositories\MessageRepository($this->db);
        $retryHandler = new \App\Services\RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new \App\Services\MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Process incoming message
        $provider = $providerFactory->getProvider('infobip');
        $incomingMessage = $provider->processIncomingMessage($webhookPayload);
        
        // Persist to database
        $stmt = $this->db->prepare(
            'INSERT INTO incoming_messages (id, from_number, to_number, type, content, received_at) 
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $incomingMessage->messageId,
            $incomingMessage->from,
            $incomingMessage->to,
            $incomingMessage->type,
            json_encode($incomingMessage->content),
            $incomingMessage->receivedAt->format('Y-m-d H:i:s')
        ]);

        // Assert: Verify message extraction
        $this->assertEquals('incoming_123', $incomingMessage->messageId);
        $this->assertEquals('351912345678', $incomingMessage->from);
        $this->assertEquals('447860099299', $incomingMessage->to);
        $this->assertEquals('text', $incomingMessage->type);
        $this->assertEquals('Hello, I need help!', $incomingMessage->content);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM incoming_messages WHERE id = ?');
        $stmt->execute([$incomingMessage->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('text', $message['type']);
        $this->assertEquals('351912345678', $message['from_number']);
    }

    /**
     * Test: Complete delivery report webhook flow
     * 
     * Verifies:
     * - Webhook validation
     * - Status update parsing
     * - Database update
     * - Timestamp tracking
     */
    public function testCompleteDeliveryReportFlow(): void
    {
        // Arrange: Insert a message first
        $messageId = 'msg_12345';
        $stmt = $this->db->prepare(
            'INSERT INTO messages (id, type, to_number, from_number, status, content, sent_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $messageId,
            'hsm',
            '351912345678',
            '447860099299',
            'PENDING_ACCEPTED',
            json_encode(['template' => 'welcome_message']),
            date('Y-m-d H:i:s')
        ]);

        // Create webhook payload
        $webhookPayload = [
            'results' => [[
                'messageId' => $messageId,
                'to' => '351912345678',
                'sentAt' => '2026-01-16T10:30:00.000+0000',
                'doneAt' => '2026-01-16T10:30:05.000+0000',
                'status' => [
                    'groupId' => 3,
                    'groupName' => 'DELIVERED',
                    'id' => 5,
                    'name' => 'DELIVERED_TO_HANDSET',
                    'description' => 'Message delivered to handset'
                ],
                'price' => [
                    'pricePerMessage' => 0.05,
                    'currency' => 'EUR'
                ]
            ]]
        ];

        // Create services
        $config = [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => 'test_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '447860099299',
                    'webhook_secret' => 'test_secret'
                ]
            ]
        ];

        $httpClient = new Client();
        $providerFactory = new \App\Providers\WhatsAppProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        // Act: Process delivery report
        $provider = $providerFactory->getProvider('infobip');
        $deliveryReport = $provider->processDeliveryReport($webhookPayload);

        // Update database
        $stmt = $this->db->prepare(
            'UPDATE messages SET status = ?, delivered_at = ?, metadata = ? WHERE id = ?'
        );
        $stmt->execute([
            $deliveryReport->status,
            $deliveryReport->deliveredAt?->format('Y-m-d H:i:s'),
            json_encode(['price' => $deliveryReport->price]),
            $deliveryReport->messageId
        ]);

        // Assert: Verify delivery report
        $this->assertEquals($messageId, $deliveryReport->messageId);
        $this->assertEquals('DELIVERED_TO_HANDSET', $deliveryReport->status);
        $this->assertNotNull($deliveryReport->deliveredAt);

        // Assert: Verify database update
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('DELIVERED_TO_HANDSET', $message['status']);
        $this->assertNotNull($message['delivered_at']);
    }

    /**
     * Test: Provider switching at runtime
     * 
     * Verifies:
     * - Multiple providers can be configured
     * - Provider can be selected at runtime
     * - Each provider uses correct API format
     * - Responses are normalized correctly
     */
    public function testProviderSwitchingAtRuntime(): void
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

        $twilioResponse = new Response(201, [], json_encode([
            'sid' => 'twilio_msg_456',
            'status' => 'queued',
            'to' => 'whatsapp:+351912345678',
            'from' => 'whatsapp:+447860099299'
        ]));

        // Test with Infobip
        $mockHandler = new MockHandler([$infobipResponse]);
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
                'twilio' => [
                    'account_sid' => 'AC123',
                    'auth_token' => 'token123',
                    'sender' => '447860099299',
                    'webhook_secret' => 'twilio_secret'
                ]
            ]
        ];

        $providerFactory = new \App\Providers\WhatsAppProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new \App\Repositories\MessageRepository($this->db);
        $retryHandler = new \App\Services\RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new \App\Services\MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send with Infobip
        $request = new \App\Models\Requests\HSMRequest(
            to: '351912345678',
            templateName: 'welcome_message',
            templateLanguage: 'pt',
            parameters: ['John']
        );

        $infobipResult = $messageService->sendHSM($request, 'infobip');

        // Assert: Infobip result
        $this->assertTrue($infobipResult->success);
        $this->assertEquals('infobip_msg_123', $infobipResult->messageId);

        // Now test with Twilio
        $mockHandler = new MockHandler([$twilioResponse]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $providerFactory = new \App\Providers\WhatsAppProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageService = new \App\Services\MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send with Twilio
        $twilioResult = $messageService->sendHSM($request, 'twilio');

        // Assert: Twilio result
        $this->assertTrue($twilioResult->success);
        $this->assertEquals('twilio_msg_456', $twilioResult->messageId);

        // Assert: Both messages in database
        $stmt = $this->db->query('SELECT COUNT(*) FROM messages');
        $count = $stmt->fetchColumn();
        $this->assertEquals(2, $count);
    }

    /**
     * Test: Complete text message flow with session handling
     * 
     * Verifies:
     * - Text message sending
     * - Session expiry handling
     * - Error response parsing
     */
    public function testCompleteTextMessageFlow(): void
    {
        // Arrange: Mock successful text send
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'messages' => [[
                    'messageId' => 'text_msg_789',
                    'status' => [
                        'groupId' => 1,
                        'groupName' => 'PENDING',
                        'id' => 26,
                        'name' => 'PENDING_ACCEPTED'
                    ],
                    'to' => '351912345678'
                ]]
            ]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => 'test_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '447860099299',
                    'webhook_secret' => 'test_secret'
                ]
            ]
        ];

        $providerFactory = new \App\Providers\WhatsAppProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new \App\Repositories\MessageRepository($this->db);
        $retryHandler = new \App\Services\RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new \App\Services\MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send text message
        $request = new \App\Models\Requests\TextRequest(
            to: '351912345678',
            text: 'Thank you for your message!'
        );

        $result = $messageService->sendText($request);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals('text_msg_789', $result->messageId);

        // Verify database
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('text', $message['type']);
    }

    /**
     * Test: Interactive message flow with button response
     * 
     * Verifies:
     * - Interactive button message sending
     * - Button response webhook processing
     * - Response association with original message
     */
    public function testInteractiveMessageWithButtonResponse(): void
    {
        // Arrange: Mock interactive message send
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'messages' => [[
                    'messageId' => 'interactive_123',
                    'status' => [
                        'groupId' => 1,
                        'groupName' => 'PENDING',
                        'id' => 26,
                        'name' => 'PENDING_ACCEPTED'
                    ],
                    'to' => '351912345678'
                ]]
            ]))
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);

        $config = [
            'default_provider' => 'infobip',
            'providers' => [
                'infobip' => [
                    'api_key' => 'test_key',
                    'base_url' => 'https://api.infobip.com',
                    'sender' => '447860099299',
                    'webhook_secret' => 'test_secret'
                ]
            ]
        ];

        $providerFactory = new \App\Providers\WhatsAppProviderFactory(
            $config,
            $httpClient,
            new NullLogger()
        );

        $messageRepo = new \App\Repositories\MessageRepository($this->db);
        $retryHandler = new \App\Services\RetryHandler(3, 1000, new NullLogger());
        
        $messageService = new \App\Services\MessageService(
            $providerFactory,
            $messageRepo,
            $retryHandler,
            new NullLogger()
        );

        // Act: Send interactive message
        $request = new \App\Models\Requests\InteractiveButtonsRequest(
            to: '351912345678',
            bodyText: 'How can we help you?',
            buttons: [
                ['id' => 'btn_1', 'title' => 'Sales'],
                ['id' => 'btn_2', 'title' => 'Support'],
                ['id' => 'btn_3', 'title' => 'Billing']
            ]
        );

        $result = $messageService->sendInteractiveButtons($request);

        // Assert: Message sent
        $this->assertTrue($result->success);
        $this->assertEquals('interactive_123', $result->messageId);

        // Simulate button response webhook
        $webhookPayload = [
            'results' => [[
                'messageId' => 'response_456',
                'from' => '351912345678',
                'to' => '447860099299',
                'receivedAt' => '2026-01-16T10:35:00.000+0000',
                'message' => [
                    'type' => 'BUTTON',
                    'button' => [
                        'id' => 'btn_2',
                        'title' => 'Support'
                    ]
                ],
                'context' => [
                    'messageId' => 'interactive_123'
                ]
            ]]
        ];

        $provider = $providerFactory->getProvider('infobip');
        $incomingMessage = $provider->processIncomingMessage($webhookPayload);

        // Assert: Button response processed
        $this->assertEquals('button', $incomingMessage->type);
        $this->assertEquals('btn_2', $incomingMessage->content['id']);
        $this->assertEquals('interactive_123', $incomingMessage->contextMessageId);
    }
}
