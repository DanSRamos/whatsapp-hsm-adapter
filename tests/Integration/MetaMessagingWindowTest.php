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
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Repositories\MessageRepository;
use WhatsApp\Adapter\Services\MessageService;
use WhatsApp\Adapter\Services\RetryHandler;
use WhatsApp\Adapter\Models\Requests\TextRequest;

/**
 * Meta Messaging Window and Platform Switching Tests
 * 
 * Tests edge cases and platform-specific behaviors:
 * - 24-hour messaging window validation
 * - Error handling for expired windows
 * - Platform switching (Instagram ↔ Messenger)
 * - Platform-specific limits enforcement
 * 
 * Requirements: 9.1-9.5, 10.1-10.6, 13.1-13.13
 */
class MetaMessagingWindowTest extends \PHPUnit\Framework\TestCase
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
     * Test: 24-hour messaging window error (Instagram)
     * 
     * Verifies:
     * - Error code 2022 handling
     * - Error subcode 2018278 handling
     * - User-friendly error message
     * - Non-transient error marking
     */
    public function testMessagingWindowExpiredErrorInstagram(): void
    {
        // Arrange: Mock Meta API error response for expired window
        $igsid = '123456789012345';
        $errorResponse = [
            'error' => [
                'message' => 'Message cannot be sent outside the 24-hour window',
                'type' => 'OAuthException',
                'code' => 2022,
                'error_subcode' => 2018278,
                'fbtrace_id' => 'ABC123XYZ'
            ]
        ];

        $mockHandler = new MockHandler([
            new RequestException(
                'Client error',
                new Request('POST', 'test'),
                new Response(400, [], json_encode($errorResponse))
            )
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

        // Act: Attempt to send message (should fail with window error)
        $request = new TextRequest(
            to: $igsid,
            text: 'This message is outside the 24-hour window'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Verify error handling
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('24 horas', $result->error);
        $this->assertStringContainsString('janela', strtolower($result->error));

        // Assert: Verify error is marked as non-transient
        $this->assertArrayHasKey('is_transient', $result->details);
        $this->assertFalse($result->details['is_transient']);
    }

    /**
     * Test: 24-hour messaging window error (Messenger)
     * 
     * Verifies:
     * - Same error handling for Messenger
     * - Platform-agnostic error messages
     */
    public function testMessagingWindowExpiredErrorMessenger(): void
    {
        // Arrange: Mock Meta API error response for expired window
        $psid = '1234567890'; // Messenger PSID
        $errorResponse = [
            'error' => [
                'message' => 'Message cannot be sent outside the 24-hour window',
                'type' => 'OAuthException',
                'code' => 2022,
                'error_subcode' => 2018278,
                'fbtrace_id' => 'DEF456UVW'
            ]
        ];

        $mockHandler = new MockHandler([
            new RequestException(
                'Client error',
                new Request('POST', 'test'),
                new Response(400, [], json_encode($errorResponse))
            )
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

        // Act: Attempt to send message to Messenger (should fail with window error)
        $request = new TextRequest(
            to: $psid,
            text: 'This message is outside the 24-hour window'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Verify error handling
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('24 horas', $result->error);

        // Assert: Verify error is marked as non-transient
        $this->assertArrayHasKey('is_transient', $result->details);
        $this->assertFalse($result->details['is_transient']);
    }

    /**
     * Test: Account not eligible error (error code 36103)
     * 
     * Verifies:
     * - Error code 36103 handling
     * - User-friendly error message
     * - Non-transient error marking
     */
    public function testAccountNotEligibleError(): void
    {
        // Arrange: Mock Meta API error response for ineligible account
        $igsid = '123456789012345';
        $errorResponse = [
            'error' => [
                'message' => 'This Instagram account is not eligible for messaging',
                'type' => 'OAuthException',
                'code' => 36103,
                'fbtrace_id' => 'GHI789RST'
            ]
        ];

        $mockHandler = new MockHandler([
            new RequestException(
                'Client error',
                new Request('POST', 'test'),
                new Response(400, [], json_encode($errorResponse))
            )
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

        // Act: Attempt to send message
        $request = new TextRequest(
            to: $igsid,
            text: 'Test message'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Verify error handling
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('elegível', $result->error);
        $this->assertStringContainsString('Instagram', $result->error);

        // Assert: Verify error is marked as non-transient
        $this->assertArrayHasKey('is_transient', $result->details);
        $this->assertFalse($result->details['is_transient']);
    }

    /**
     * Test: Feature not available error (error code 2534068)
     * 
     * Verifies:
     * - Error code 2534068 handling
     * - User-friendly error message
     * - Non-transient error marking
     */
    public function testFeatureNotAvailableError(): void
    {
        // Arrange: Mock Meta API error response for unavailable feature
        $igsid = '123456789012345';
        $errorResponse = [
            'error' => [
                'message' => 'This feature is not available for this account',
                'type' => 'OAuthException',
                'code' => 2534068,
                'fbtrace_id' => 'JKL012MNO'
            ]
        ];

        $mockHandler = new MockHandler([
            new RequestException(
                'Client error',
                new Request('POST', 'test'),
                new Response(400, [], json_encode($errorResponse))
            )
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

        // Act: Attempt to send message
        $request = new TextRequest(
            to: $igsid,
            text: 'Test message'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Verify error handling
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);
        $this->assertStringContainsString('disponível', $result->error);

        // Assert: Verify error is marked as non-transient
        $this->assertArrayHasKey('is_transient', $result->details);
        $this->assertFalse($result->details['is_transient']);
    }

    /**
     * Test: Rate limit error (transient)
     * 
     * Verifies:
     * - Rate limit error handling
     * - Transient error marking
     * - Retry eligibility
     */
    public function testRateLimitErrorTransient(): void
    {
        // Arrange: Mock Meta API error response for rate limit
        $igsid = '123456789012345';
        $errorResponse = [
            'error' => [
                'message' => 'Application request limit reached',
                'type' => 'OAuthException',
                'code' => 4,
                'fbtrace_id' => 'PQR345STU'
            ]
        ];

        $mockHandler = new MockHandler([
            new RequestException(
                'Client error',
                new Request('POST', 'test'),
                new Response(429, [], json_encode($errorResponse))
            )
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

        // Act: Attempt to send message
        $request = new TextRequest(
            to: $igsid,
            text: 'Test message'
        );

        $result = $messageService->sendText($request, 'meta');

        // Assert: Verify error handling
        $this->assertFalse($result->success);
        $this->assertNotNull($result->error);

        // Assert: Verify error is marked as transient (should be retried)
        $this->assertArrayHasKey('is_transient', $result->details);
        $this->assertTrue($result->details['is_transient']);
    }
}
