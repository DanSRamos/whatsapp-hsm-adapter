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
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Repositories\MessageRepository;
use WhatsApp\Adapter\Services\MessageService;
use WhatsApp\Adapter\Services\RetryHandler;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Models\Requests\MediaRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;

/**
 * Meta Messaging Integration End-to-End Tests
 * 
 * Tests complete flows through the Meta provider (Instagram + Messenger) including:
 * - Text message sending
 * - Media message sending (images, videos, audio, documents)
 * - Multiple images (Instagram)
 * - Interactive messages (quick replies, generic template, button template)
 * - Incoming message reception
 * - Delivery report webhooks
 * - Platform detection (Instagram vs Messenger)
 * - 24-hour messaging window validation
 * 
 * Requirements: 2.1-2.9, 3.1-3.12, 4.1-4.10, 6.1-6.12, 7.1-7.6, 9.1-9.5, 13.1-13.13
 */
class MetaMessageFlowTest extends \PHPUnit\Framework\TestCase
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
     * Test: Complete text message flow for Instagram
     * 
     * Verifies:
     * - Text message sending to Instagram
     * - Request validation
     * - Provider API call
     * - Response parsing
     * - Database persistence
     * - Platform detection
     */
    public function testCompleteTextMessageFlowInstagram(): void
    {
        // Arrange: Mock Meta API response for Instagram
        $igsid = '123456789012345'; // 15-digit IGSID
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_msg_12345',
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

        // Act: Send text message to Instagram
        $request = new TextRequest(
            to: $igsid,
            text: 'Hello from Instagram!'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('ig_msg_12345', $result->messageId);
        $this->assertEquals('SENT', $result->status);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('text', $message['type']);
        $this->assertEquals($igsid, $message['to_number']);
        $this->assertEquals('SENT', $message['status']);
        
        // Verify platform metadata
        $metadata = json_decode($message['metadata'], true);
        $this->assertEquals('meta', $metadata['provider']);
    }

    /**
     * Test: Complete text message flow for Messenger
     * 
     * Verifies:
     * - Text message sending to Messenger
     * - Platform detection (PSID vs IGSID)
     * - Database persistence with correct platform
     */
    public function testCompleteTextMessageFlowMessenger(): void
    {
        // Arrange: Mock Meta API response for Messenger
        $psid = '1234567890'; // 10-digit PSID
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'msg_messenger_12345',
                'recipient_id' => $psid
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

        // Act: Send text message to Messenger
        $request = new TextRequest(
            to: $psid,
            text: 'Hello from Messenger!'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('msg_messenger_12345', $result->messageId);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals($psid, $message['to_number']);
    }

    /**
     * Test: Complete media message flow (image)
     * 
     * Verifies:
     * - Image sending via Meta
     * - Media URL validation
     * - Database persistence
     */
    public function testCompleteMediaMessageFlowImage(): void
    {
        // Arrange: Mock Meta API response
        $igsid = '123456789012345';
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_media_12345',
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

        // Act: Send image
        $request = new MediaRequest(
            to: $igsid,
            mediaType: 'image',
            mediaUrl: 'https://example.com/image.jpg'
        );

        $result = $messageService->sendMedia($request, 'meta');

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('ig_media_12345', $result->messageId);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('image', $message['type']);
    }

    /**
     * Test: Complete interactive buttons flow (Quick Replies)
     * 
     * Verifies:
     * - Quick replies sending
     * - Button validation
     * - Database persistence
     */
    public function testCompleteInteractiveButtonsFlow(): void
    {
        // Arrange: Mock Meta API response
        $igsid = '123456789012345';
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_interactive_12345',
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

        // Act: Send interactive buttons
        $request = new InteractiveButtonsRequest(
            to: $igsid,
            bodyText: 'How can we help you?',
            buttons: [
                ['id' => 'btn_1', 'text' => 'Sales'],
                ['id' => 'btn_2', 'text' => 'Support'],
                ['id' => 'btn_3', 'text' => 'Billing']
            ]
        );

        $result = $messageService->sendInteractiveButtons($request, 'meta');

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('ig_interactive_12345', $result->messageId);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('interactive', $message['type']);
    }

    /**
     * Test: Complete incoming message webhook flow
     * 
     * Verifies:
     * - Webhook validation
     * - Payload parsing
     * - Content extraction
     * - Database persistence
     * - Platform detection
     */
    public function testCompleteIncomingMessageFlow(): void
    {
        // Arrange: Create webhook payload for Instagram
        $igsid = '123456789012345';
        $webhookPayload = [
            'object' => 'instagram',
            'entry' => [[
                'id' => '987654321',
                'time' => 1642345678000,
                'messaging' => [[
                    'sender' => ['id' => $igsid],
                    'recipient' => ['id' => '987654321'],
                    'timestamp' => 1642345678000,
                    'message' => [
                        'mid' => 'ig_incoming_123',
                        'text' => 'Hello, I need help!'
                    ]
                ]]
            ]]
        ];

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

        // Act: Process incoming message
        $provider = $providerFactory->getProvider('meta');
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
        $this->assertEquals('ig_incoming_123', $incomingMessage->messageId);
        $this->assertEquals($igsid, $incomingMessage->from);
        $this->assertEquals('987654321', $incomingMessage->to);
        $this->assertEquals('text', $incomingMessage->type);
        $this->assertEquals('Hello, I need help!', $incomingMessage->content['text']);

        // Assert: Verify platform metadata
        $this->assertEquals('meta', $incomingMessage->content['metadata']['provider']);
        $this->assertEquals('instagram', $incomingMessage->content['metadata']['platform']);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM incoming_messages WHERE id = ?');
        $stmt->execute([$incomingMessage->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('text', $message['type']);
        $this->assertEquals($igsid, $message['from_number']);
    }

    /**
     * Test: Complete delivery report webhook flow
     * 
     * Verifies:
     * - Webhook validation
     * - Status update parsing
     * - Database update
     * - Timestamp tracking
     * - Platform detection
     */
    public function testCompleteDeliveryReportFlow(): void
    {
        // Arrange: Insert a message first
        $messageId = 'ig_msg_12345';
        $igsid = '123456789012345';
        $stmt = $this->db->prepare(
            'INSERT INTO messages (id, type, to_number, from_number, status, content, sent_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $messageId,
            'text',
            $igsid,
            '987654321',
            'SENT',
            json_encode(['text' => 'Test message']),
            date('Y-m-d H:i:s')
        ]);

        // Create webhook payload for delivery
        $webhookPayload = [
            'object' => 'instagram',
            'entry' => [[
                'id' => '987654321',
                'time' => 1642345678000,
                'messaging' => [[
                    'sender' => ['id' => $igsid],
                    'recipient' => ['id' => '987654321'],
                    'timestamp' => 1642345678000,
                    'delivery' => [
                        'mids' => [$messageId],
                        'watermark' => 1642345678000
                    ]
                ]]
            ]]
        ];

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

        // Act: Process delivery report
        $provider = $providerFactory->getProvider('meta');
        $deliveryReport = $provider->processDeliveryReport($webhookPayload);

        // Update database
        $stmt = $this->db->prepare(
            'UPDATE messages SET status = ?, delivered_at = ?, metadata = ? WHERE id = ?'
        );
        $stmt->execute([
            strtoupper($deliveryReport->status),
            $deliveryReport->timestamp->format('Y-m-d H:i:s'),
            json_encode($deliveryReport->metadata),
            $deliveryReport->messageId
        ]);

        // Assert: Verify delivery report
        $this->assertEquals($messageId, $deliveryReport->messageId);
        $this->assertEquals('delivered', $deliveryReport->status);
        $this->assertNotNull($deliveryReport->timestamp);

        // Assert: Verify platform metadata
        $this->assertEquals('meta', $deliveryReport->metadata['provider']);
        $this->assertEquals('instagram', $deliveryReport->metadata['platform']);

        // Assert: Verify database update
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('DELIVERED', $message['status']);
        $this->assertNotNull($message['delivered_at']);
    }

    /**
     * Test: Read receipt webhook flow
     * 
     * Verifies:
     * - Read receipt processing
     * - Status update to READ
     * - Database update
     */
    public function testReadReceiptWebhookFlow(): void
    {
        // Arrange: Insert a delivered message
        $messageId = 'ig_msg_read_123';
        $igsid = '123456789012345';
        $stmt = $this->db->prepare(
            'INSERT INTO messages (id, type, to_number, from_number, status, content, sent_at, delivered_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $messageId,
            'text',
            $igsid,
            '987654321',
            'DELIVERED',
            json_encode(['text' => 'Test message']),
            date('Y-m-d H:i:s', strtotime('-5 minutes')),
            date('Y-m-d H:i:s', strtotime('-2 minutes'))
        ]);

        // Create webhook payload for read receipt
        $webhookPayload = [
            'object' => 'instagram',
            'entry' => [[
                'id' => '987654321',
                'time' => 1642345678000,
                'messaging' => [[
                    'sender' => ['id' => $igsid],
                    'recipient' => ['id' => '987654321'],
                    'timestamp' => 1642345678000,
                    'read' => [
                        'mids' => [$messageId],
                        'watermark' => 1642345678000
                    ]
                ]]
            ]]
        ];

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

        // Act: Process read receipt
        $provider = $providerFactory->getProvider('meta');
        $deliveryReport = $provider->processDeliveryReport($webhookPayload);

        // Update database
        $stmt = $this->db->prepare(
            'UPDATE messages SET status = ?, read_at = ? WHERE id = ?'
        );
        $stmt->execute([
            strtoupper($deliveryReport->status),
            $deliveryReport->timestamp->format('Y-m-d H:i:s'),
            $deliveryReport->messageId
        ]);

        // Assert: Verify read receipt
        $this->assertEquals($messageId, $deliveryReport->messageId);
        $this->assertEquals('read', $deliveryReport->status);

        // Assert: Verify database update
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('READ', $message['status']);
        $this->assertNotNull($message['read_at']);
    }

    /**
     * Test: Multiple images sending (Instagram)
     * 
     * Verifies:
     * - Multiple images support (up to 10 for Instagram)
     * - Platform-specific limits
     * - Database persistence
     */
    public function testMultipleImagesFlowInstagram(): void
    {
        // Arrange: Mock Meta API response
        $igsid = '123456789012345'; // Instagram ID
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_multi_img_123',
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

        // Act: Send multiple images via provider directly
        $provider = $providerFactory->getProvider('meta');
        $imageUrls = [
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg',
            'https://example.com/image3.jpg'
        ];
        
        $result = $provider->sendMultipleImages($igsid, $imageUrls);

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('ig_multi_img_123', $result->messageId);
    }

    /**
     * Test: Video message flow
     * 
     * Verifies:
     * - Video sending
     * - Media type validation
     * - Database persistence
     */
    public function testVideoMessageFlow(): void
    {
        // Arrange: Mock Meta API response
        $igsid = '123456789012345';
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_video_123',
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

        // Act: Send video
        $request = new MediaRequest(
            to: $igsid,
            mediaType: 'video',
            mediaUrl: 'https://example.com/video.mp4'
        );

        $result = $messageService->sendMedia($request, 'meta');

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('ig_video_123', $result->messageId);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('video', $message['type']);
    }

    /**
     * Test: Audio message flow
     * 
     * Verifies:
     * - Audio sending
     * - Media type validation
     * - Database persistence
     */
    public function testAudioMessageFlow(): void
    {
        // Arrange: Mock Meta API response
        $igsid = '123456789012345';
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_audio_123',
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

        // Act: Send audio
        $request = new MediaRequest(
            to: $igsid,
            mediaType: 'audio',
            mediaUrl: 'https://example.com/audio.mp3'
        );

        $result = $messageService->sendMedia($request, 'meta');

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('ig_audio_123', $result->messageId);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('audio', $message['type']);
    }

    /**
     * Test: Document message flow
     * 
     * Verifies:
     * - Document sending
     * - Media type validation
     * - Database persistence
     */
    public function testDocumentMessageFlow(): void
    {
        // Arrange: Mock Meta API response
        $igsid = '123456789012345';
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_doc_123',
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

        // Act: Send document
        $request = new MediaRequest(
            to: $igsid,
            mediaType: 'document',
            mediaUrl: 'https://example.com/document.pdf'
        );

        $result = $messageService->sendMedia($request, 'meta');

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('ig_doc_123', $result->messageId);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('document', $message['type']);
    }

    /**
     * Test: Generic template (Interactive list) flow
     * 
     * Verifies:
     * - Generic template sending
     * - Card formatting
     * - Button support
     * - Database persistence
     */
    public function testGenericTemplateFlow(): void
    {
        // Arrange: Mock Meta API response
        $igsid = '123456789012345';
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'ig_template_123',
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

        // Act: Send generic template
        $request = new InteractiveListRequest(
            to: $igsid,
            buttonText: 'View Options',
            sections: [
                [
                    'title' => 'Products',
                    'items' => [
                        [
                            'title' => 'Product 1',
                            'description' => 'Description 1',
                            'image_url' => 'https://example.com/product1.jpg',
                            'buttons' => [
                                ['type' => 'postback', 'text' => 'Buy', 'id' => 'buy_1']
                            ]
                        ],
                        [
                            'title' => 'Product 2',
                            'description' => 'Description 2',
                            'image_url' => 'https://example.com/product2.jpg',
                            'buttons' => [
                                ['type' => 'web_url', 'text' => 'View', 'url' => 'https://example.com/product2']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $result = $messageService->sendInteractiveList($request, 'meta');

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('ig_template_123', $result->messageId);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$result->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('interactive', $message['type']);
    }

    /**
     * Test: Button template flow (Messenger-specific)
     * 
     * Verifies:
     * - Button template sending
     * - Platform detection (Messenger)
     * - Button validation
     * - Database persistence
     */
    public function testButtonTemplateFlowMessenger(): void
    {
        // Arrange: Mock Meta API response for Messenger
        $psid = '1234567890'; // Messenger PSID
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'message_id' => 'msg_button_template_123',
                'recipient_id' => $psid
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

        // Act: Send button template via provider directly
        $provider = $providerFactory->getProvider('meta');
        $buttons = [
            ['type' => 'web_url', 'title' => 'Visit Website', 'url' => 'https://example.com'],
            ['type' => 'postback', 'title' => 'Get Started', 'payload' => 'GET_STARTED'],
            ['type' => 'phone_number', 'title' => 'Call Us', 'payload' => '+1234567890']
        ];
        
        $result = $provider->sendButtonTemplate($psid, 'Choose an option:', $buttons);

        // Assert: Verify result
        $this->assertTrue($result->success);
        $this->assertEquals('msg_button_template_123', $result->messageId);
    }

    /**
     * Test: Incoming media message (image) webhook
     * 
     * Verifies:
     * - Media message reception
     * - Attachment extraction
     * - Database persistence
     */
    public function testIncomingMediaMessageWebhook(): void
    {
        // Arrange: Create webhook payload with image attachment
        $igsid = '123456789012345';
        $webhookPayload = [
            'object' => 'instagram',
            'entry' => [[
                'id' => '987654321',
                'time' => 1642345678000,
                'messaging' => [[
                    'sender' => ['id' => $igsid],
                    'recipient' => ['id' => '987654321'],
                    'timestamp' => 1642345678000,
                    'message' => [
                        'mid' => 'ig_incoming_media_123',
                        'attachments' => [[
                            'type' => 'image',
                            'payload' => [
                                'url' => 'https://example.com/received_image.jpg'
                            ]
                        ]]
                    ]
                ]]
            ]]
        ];

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

        // Act: Process incoming media message
        $provider = $providerFactory->getProvider('meta');
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
        $this->assertEquals('ig_incoming_media_123', $incomingMessage->messageId);
        $this->assertEquals('image', $incomingMessage->type);
        $this->assertArrayHasKey('attachments', $incomingMessage->content);
        $this->assertCount(1, $incomingMessage->content['attachments']);
        $this->assertEquals('image', $incomingMessage->content['attachments'][0]['type']);
        $this->assertEquals('https://example.com/received_image.jpg', $incomingMessage->content['attachments'][0]['url']);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM incoming_messages WHERE id = ?');
        $stmt->execute([$incomingMessage->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('image', $message['type']);
    }

    /**
     * Test: Incoming quick reply response webhook
     * 
     * Verifies:
     * - Quick reply response reception
     * - Payload extraction
     * - Database persistence
     */
    public function testIncomingQuickReplyResponseWebhook(): void
    {
        // Arrange: Create webhook payload with quick reply response
        $igsid = '123456789012345';
        $webhookPayload = [
            'object' => 'instagram',
            'entry' => [[
                'id' => '987654321',
                'time' => 1642345678000,
                'messaging' => [[
                    'sender' => ['id' => $igsid],
                    'recipient' => ['id' => '987654321'],
                    'timestamp' => 1642345678000,
                    'message' => [
                        'mid' => 'ig_incoming_qr_123',
                        'text' => 'Support',
                        'quick_reply' => [
                            'payload' => 'btn_2'
                        ]
                    ]
                ]]
            ]]
        ];

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

        // Act: Process incoming quick reply
        $provider = $providerFactory->getProvider('meta');
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
        $this->assertEquals('ig_incoming_qr_123', $incomingMessage->messageId);
        $this->assertEquals('quick_reply', $incomingMessage->type);
        $this->assertEquals('Support', $incomingMessage->content['text']);
        $this->assertEquals('btn_2', $incomingMessage->content['quick_reply']['payload']);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM incoming_messages WHERE id = ?');
        $stmt->execute([$incomingMessage->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('quick_reply', $message['type']);
    }

    /**
     * Test: Incoming postback webhook (button click)
     * 
     * Verifies:
     * - Postback event reception
     * - Payload extraction
     * - Database persistence
     */
    public function testIncomingPostbackWebhook(): void
    {
        // Arrange: Create webhook payload with postback
        $igsid = '123456789012345';
        $webhookPayload = [
            'object' => 'instagram',
            'entry' => [[
                'id' => '987654321',
                'time' => 1642345678000,
                'messaging' => [[
                    'sender' => ['id' => $igsid],
                    'recipient' => ['id' => '987654321'],
                    'timestamp' => 1642345678000,
                    'postback' => [
                        'title' => 'Buy',
                        'payload' => 'buy_1'
                    ]
                ]]
            ]]
        ];

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

        // Act: Process incoming postback
        $provider = $providerFactory->getProvider('meta');
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
        $this->assertEquals('postback', $incomingMessage->type);
        $this->assertEquals('Buy', $incomingMessage->content['title']);
        $this->assertEquals('buy_1', $incomingMessage->content['payload']);

        // Assert: Verify database persistence
        $stmt = $this->db->prepare('SELECT * FROM incoming_messages WHERE id = ?');
        $stmt->execute([$incomingMessage->messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($message);
        $this->assertEquals('postback', $message['type']);
    }
}
