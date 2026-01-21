<?php

declare(strict_types=1);

namespace Tests\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PDO;
use Psr\Log\NullLogger;
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Repositories\MessageRepository;
use WhatsApp\Adapter\Services\MessageService;
use WhatsApp\Adapter\Services\RetryHandler;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Models\Requests\MediaRequest;

/**
 * Meta Platform Switching Integration Tests
 * 
 * Tests platform detection and switching between Instagram and Messenger:
 * - Automatic platform detection from recipient ID
 * - Platform-specific limit enforcement
 * - Correct metadata persistence
 * - Multiple platforms in same session
 * 
 * Requirements: 13.1-13.13
 */
class MetaPlatformSwitchingTest extends \PHPUnit\Framework\TestCase
{
    private PDO $db;
    private string $testDbName = 'whatsapp_adapter_test';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestDatabase();
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
     * Test: Platform detection from recipient ID
     * 
     * Verifies:
     * - Instagram ID detection (15+ digits)
     * - Messenger ID detection (shorter)
     * - Correct platform metadata
     */
    public function testPlatformDetectionFromRecipientId(): void
    {
        // Arrange: Mock responses for both platforms
        $igsid = '123456789012345'; // 15-digit Instagram ID
        $psid = '1234567890';       // 10-digit Messenger ID

        $mockHandler = new MockHandler([
            // Response for Instagram
            new Response(200, [], json_encode([
                'message_id' => 'ig_msg_123',
                'recipient_id' => $igsid
            ])),
            // Response for Messenger
            new Response(200, [], json_encode([
                'message_id' => 'msg_messenger_456',
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

        // Act: Send to Instagram
        $instagramRequest = new TextRequest(
            to: $igsid,
            text: 'Message to Instagram'
        );
        $instagramResult = $messageService->sendText($instagramRequest, 'meta');

        // Act: Send to Messenger
        $messengerRequest = new TextRequest(
            to: $psid,
            text: 'Message to Messenger'
        );
        $messengerResult = $messageService->sendText($messengerRequest, 'meta');

        // Assert: Both messages sent successfully
        $this->assertTrue($instagramResult->success);
        $this->assertTrue($messengerResult->success);

        // Assert: Verify Instagram message in database
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$instagramResult->messageId]);
        $igMessage = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($igMessage);
        $this->assertEquals($igsid, $igMessage['to_number']);

        // Assert: Verify Messenger message in database
        $stmt = $this->db->prepare('SELECT * FROM messages WHERE id = ?');
        $stmt->execute([$messengerResult->messageId]);
        $messengerMessage = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($messengerMessage);
        $this->assertEquals($psid, $messengerMessage['to_number']);

        // Assert: Both messages exist in database
        $stmt = $this->db->query('SELECT COUNT(*) FROM messages');
        $count = $stmt->fetchColumn();
        $this->assertEquals(2, $count);
    }

    /**
     * Test: Multiple images limit enforcement (Instagram vs Messenger)
     * 
     * Verifies:
     * - Instagram allows up to 10 images
     * - Messenger allows 1 image (standard)
     * - Platform-specific validation
     */
    public function testMultipleImagesLimitEnforcement(): void
    {
        // Arrange: Mock responses
        $igsid = '123456789012345'; // Instagram ID
        $psid = '1234567890';       // Messenger ID

        $mockHandler = new MockHandler([
            // Response for Instagram (10 images - should succeed)
            new Response(200, [], json_encode([
                'message_id' => 'ig_multi_123',
                'recipient_id' => $igsid
            ])),
            // Response for Messenger (1 image - should succeed)
            new Response(200, [], json_encode([
                'message_id' => 'msg_single_456',
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

        $provider = $providerFactory->getProvider('meta');

        // Act: Send 10 images to Instagram (should succeed)
        $instagramImages = array_fill(0, 10, 'https://example.com/image.jpg');
        $instagramResult = $provider->sendMultipleImages($igsid, $instagramImages);

        // Assert: Instagram accepts 10 images
        $this->assertTrue($instagramResult->success);
        $this->assertEquals('ig_multi_123', $instagramResult->messageId);

        // Act: Send 1 image to Messenger (should succeed)
        $messengerImages = ['https://example.com/image.jpg'];
        $messengerResult = $provider->sendMultipleImages($psid, $messengerImages);

        // Assert: Messenger accepts 1 image
        $this->assertTrue($messengerResult->success);
        $this->assertEquals('msg_single_456', $messengerResult->messageId);

        // Act: Try to send 2 images to Messenger (should fail validation)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Too many images');
        $this->expectExceptionMessage('Messenger');
        
        $tooManyImages = ['https://example.com/image1.jpg', 'https://example.com/image2.jpg'];
        $provider->sendMultipleImages($psid, $tooManyImages);
    }

    /**
     * Test: Platform switching in same session
     * 
     * Verifies:
     * - Multiple messages to different platforms
     * - Correct platform detection for each
     * - Independent message handling
     */
    public function testPlatformSwitchingInSameSession(): void
    {
        // Arrange: Mock responses for alternating platforms
        $igsid = '123456789012345';
        $psid = '1234567890';

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode(['message_id' => 'ig_1', 'recipient_id' => $igsid])),
            new Response(200, [], json_encode(['message_id' => 'msg_1', 'recipient_id' => $psid])),
            new Response(200, [], json_encode(['message_id' => 'ig_2', 'recipient_id' => $igsid])),
            new Response(200, [], json_encode(['message_id' => 'msg_2', 'recipient_id' => $psid])),
            new Response(200, [], json_encode(['message_id' => 'ig_3', 'recipient_id' => $igsid])),
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

        // Act: Send messages alternating between platforms
        $results = [];
        
        // Instagram message 1
        $results[] = $messageService->sendText(
            new TextRequest(to: $igsid, text: 'Instagram 1'),
            'meta'
        );
        
        // Messenger message 1
        $results[] = $messageService->sendText(
            new TextRequest(to: $psid, text: 'Messenger 1'),
            'meta'
        );
        
        // Instagram message 2
        $results[] = $messageService->sendText(
            new TextRequest(to: $igsid, text: 'Instagram 2'),
            'meta'
        );
        
        // Messenger message 2
        $results[] = $messageService->sendText(
            new TextRequest(to: $psid, text: 'Messenger 2'),
            'meta'
        );
        
        // Instagram message 3
        $results[] = $messageService->sendText(
            new TextRequest(to: $igsid, text: 'Instagram 3'),
            'meta'
        );

        // Assert: All messages sent successfully
        foreach ($results as $result) {
            $this->assertTrue($result->success);
        }

        // Assert: Verify all messages in database
        $stmt = $this->db->query('SELECT COUNT(*) FROM messages');
        $count = $stmt->fetchColumn();
        $this->assertEquals(5, $count);

        // Assert: Verify Instagram messages
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM messages WHERE to_number = ?');
        $stmt->execute([$igsid]);
        $igCount = $stmt->fetchColumn();
        $this->assertEquals(3, $igCount);

        // Assert: Verify Messenger messages
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM messages WHERE to_number = ?');
        $stmt->execute([$psid]);
        $messengerCount = $stmt->fetchColumn();
        $this->assertEquals(2, $messengerCount);
    }

    /**
     * Test: Webhook platform detection (Instagram)
     * 
     * Verifies:
     * - Platform detection from webhook payload
     * - Correct metadata in incoming message
     */
    public function testWebhookPlatformDetectionInstagram(): void
    {
        // Arrange: Instagram webhook payload
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
                        'text' => 'Hello from Instagram'
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

        // Act: Process webhook
        $provider = $providerFactory->getProvider('meta');
        $incomingMessage = $provider->processIncomingMessage($webhookPayload);

        // Assert: Verify platform detection
        $this->assertEquals('instagram', $incomingMessage->content['metadata']['platform']);
        $this->assertEquals('Instagram', $incomingMessage->content['metadata']['platform_name']);
        $this->assertEquals($igsid, $incomingMessage->from);
    }

    /**
     * Test: Webhook platform detection (Messenger)
     * 
     * Verifies:
     * - Platform detection from webhook payload
     * - Correct metadata in incoming message
     */
    public function testWebhookPlatformDetectionMessenger(): void
    {
        // Arrange: Messenger webhook payload (no 'instagram' object field)
        $psid = '1234567890';
        $webhookPayload = [
            'object' => 'page',
            'entry' => [[
                'id' => '987654321',
                'time' => 1642345678000,
                'messaging' => [[
                    'sender' => ['id' => $psid],
                    'recipient' => ['id' => '987654321'],
                    'timestamp' => 1642345678000,
                    'message' => [
                        'mid' => 'msg_incoming_456',
                        'text' => 'Hello from Messenger'
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

        // Act: Process webhook
        $provider = $providerFactory->getProvider('meta');
        $incomingMessage = $provider->processIncomingMessage($webhookPayload);

        // Assert: Verify platform detection
        $this->assertEquals('messenger', $incomingMessage->content['metadata']['platform']);
        $this->assertEquals('Facebook Messenger', $incomingMessage->content['metadata']['platform_name']);
        $this->assertEquals($psid, $incomingMessage->from);
    }

    /**
     * Test: Delivery report platform detection
     * 
     * Verifies:
     * - Platform detection in delivery reports
     * - Correct metadata persistence
     */
    public function testDeliveryReportPlatformDetection(): void
    {
        // Arrange: Insert messages for both platforms
        $igMessageId = 'ig_msg_123';
        $messengerMessageId = 'msg_messenger_456';
        $igsid = '123456789012345';
        $psid = '1234567890';

        $stmt = $this->db->prepare(
            'INSERT INTO messages (id, type, to_number, from_number, status, content, sent_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $igMessageId,
            'text',
            $igsid,
            '987654321',
            'SENT',
            json_encode(['text' => 'Test']),
            date('Y-m-d H:i:s')
        ]);
        $stmt->execute([
            $messengerMessageId,
            'text',
            $psid,
            '987654321',
            'SENT',
            json_encode(['text' => 'Test']),
            date('Y-m-d H:i:s')
        ]);

        // Create delivery report webhooks for both platforms
        $igWebhook = [
            'object' => 'instagram',
            'entry' => [[
                'id' => '987654321',
                'time' => 1642345678000,
                'messaging' => [[
                    'sender' => ['id' => $igsid],
                    'recipient' => ['id' => '987654321'],
                    'timestamp' => 1642345678000,
                    'delivery' => [
                        'mids' => [$igMessageId],
                        'watermark' => 1642345678000
                    ]
                ]]
            ]]
        ];

        $messengerWebhook = [
            'object' => 'page',
            'entry' => [[
                'id' => '987654321',
                'time' => 1642345678000,
                'messaging' => [[
                    'sender' => ['id' => $psid],
                    'recipient' => ['id' => '987654321'],
                    'timestamp' => 1642345678000,
                    'delivery' => [
                        'mids' => [$messengerMessageId],
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

        $provider = $providerFactory->getProvider('meta');

        // Act: Process both delivery reports
        $igDeliveryReport = $provider->processDeliveryReport($igWebhook);
        $messengerDeliveryReport = $provider->processDeliveryReport($messengerWebhook);

        // Assert: Verify Instagram platform detection
        $this->assertEquals('instagram', $igDeliveryReport->metadata['platform']);
        $this->assertEquals('Instagram', $igDeliveryReport->metadata['platform_name']);

        // Assert: Verify Messenger platform detection
        $this->assertEquals('messenger', $messengerDeliveryReport->metadata['platform']);
        $this->assertEquals('Facebook Messenger', $messengerDeliveryReport->metadata['platform_name']);
    }
}
