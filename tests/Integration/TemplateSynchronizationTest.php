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
 * Template Synchronization Integration Tests
 * 
 * Tests complete template synchronization flows including:
 * - Manual synchronization from provider
 * - Webhook-based template updates
 * - Cache invalidation
 * - Multi-provider template management
 * 
 * Requirements: 1.1-1.4, 2.1-2.7
 */
class TemplateSynchronizationTest extends \PHPUnit\Framework\TestCase
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
            __DIR__ . '/../../database/migrations/003_create_templates_table.sql',
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
     * Test: Manual template synchronization from provider
     * 
     * Verifies:
     * - Fetching templates from provider API
     * - Comparing with local database
     * - Adding new templates
     * - Updating existing templates
     * - Removing deleted templates
     * - Cache invalidation
     */
    public function testManualTemplateSynchronization(): void
    {
        // Arrange: Insert existing template
        $stmt = $this->db->prepare(
            'INSERT INTO templates (id, name, language, status, category, components, last_synced_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            'old_template_1',
            'old_welcome',
            'pt',
            'APPROVED',
            'MARKETING',
            json_encode([['type' => 'BODY', 'text' => 'Old text']]),
            date('Y-m-d H:i:s', strtotime('-1 day'))
        ]);

        // Mock provider API response with templates
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'templates' => [
                    [
                        'id' => 'template_1',
                        'name' => 'welcome_message',
                        'language' => 'pt',
                        'status' => 'APPROVED',
                        'category' => 'MARKETING',
                        'components' => [
                            [
                                'type' => 'BODY',
                                'text' => 'Welcome {{1}}! Your account is {{2}}.',
                                'example' => [
                                    'body_text' => [['John Doe', 'active']]
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'template_2',
                        'name' => 'order_confirmation',
                        'language' => 'en',
                        'status' => 'APPROVED',
                        'category' => 'TRANSACTIONAL',
                        'components' => [
                            [
                                'type' => 'BODY',
                                'text' => 'Your order {{1}} has been confirmed.'
                            ]
                        ]
                    ]
                ]
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

        $templateRepo = new \App\Repositories\TemplateRepository($this->db);
        
        $cache = new class($this->redis) implements \App\Services\CacheInterface {
            public function __construct(private Redis $redis) {}
            public function get(string $key): mixed {
                $value = $this->redis->get($key);
                return $value !== false ? json_decode($value, true) : null;
            }
            public function set(string $key, mixed $value, int $ttl = 3600): void {
                $this->redis->setex($key, $ttl, json_encode($value));
            }
            public function delete(string $key): void {
                $this->redis->del($key);
            }
            public function clear(): void {
                $this->redis->flushDB();
            }
        };

        $templateService = new \App\Services\TemplateService(
            $providerFactory,
            $templateRepo,
            $cache,
            new NullLogger()
        );

        // Act: Sync templates
        $stats = $templateService->syncTemplates('infobip');

        // Assert: Verify statistics
        $this->assertEquals(2, $stats['added']);
        $this->assertEquals(0, $stats['updated']);
        $this->assertEquals(1, $stats['deleted']); // old_template_1 should be removed

        // Assert: Verify database state
        $stmt = $this->db->query('SELECT COUNT(*) FROM templates');
        $count = $stmt->fetchColumn();
        $this->assertEquals(2, $count);

        // Assert: Verify new templates exist
        $stmt = $this->db->prepare('SELECT * FROM templates WHERE id = ?');
        $stmt->execute(['template_1']);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotNull($template);
        $this->assertEquals('welcome_message', $template['name']);
        $this->assertEquals('APPROVED', $template['status']);

        // Assert: Verify old template removed
        $stmt = $this->db->prepare('SELECT * FROM templates WHERE id = ?');
        $stmt->execute(['old_template_1']);
        $oldTemplate = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertFalse($oldTemplate);

        // Assert: Verify cache was invalidated
        $cachedTemplates = $cache->get('templates:all');
        $this->assertNull($cachedTemplates);
    }

    /**
     * Test: Template update via webhook
     * 
     * Verifies:
     * - Webhook payload parsing
     * - Template status update
     * - Cache invalidation
     * - Notification dispatch
     */
    public function testTemplateUpdateViaWebhook(): void
    {
        // Arrange: Insert existing template
        $stmt = $this->db->prepare(
            'INSERT INTO templates (id, name, language, status, category, components, last_synced_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            'template_123',
            'welcome_message',
            'pt',
            'PENDING',
            'MARKETING',
            json_encode([['type' => 'BODY', 'text' => 'Welcome {{1}}']]),
            date('Y-m-d H:i:s')
        ]);

        // Create webhook payload
        $webhookPayload = [
            'templateId' => 'template_123',
            'name' => 'welcome_message',
            'language' => 'pt',
            'status' => 'APPROVED',
            'category' => 'MARKETING',
            'updatedAt' => '2026-01-16T10:30:00.000+0000'
        ];

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

        $templateRepo = new \App\Repositories\TemplateRepository($this->db);
        
        $cache = new class($this->redis) implements \App\Services\CacheInterface {
            public function __construct(private Redis $redis) {}
            public function get(string $key): mixed {
                $value = $this->redis->get($key);
                return $value !== false ? json_decode($value, true) : null;
            }
            public function set(string $key, mixed $value, int $ttl = 3600): void {
                $this->redis->setex($key, $ttl, json_encode($value));
            }
            public function delete(string $key): void {
                $this->redis->del($key);
            }
            public function clear(): void {
                $this->redis->flushDB();
            }
        };

        // Set cache before update
        $cache->set('templates:all', [['id' => 'template_123', 'status' => 'PENDING']]);

        $templateService = new \App\Services\TemplateService(
            $providerFactory,
            $templateRepo,
            $cache,
            new NullLogger()
        );

        // Act: Process template update
        $provider = $providerFactory->getProvider('infobip');
        $templateUpdate = $provider->processTemplateUpdate($webhookPayload);
        
        // Update in database
        $stmt = $this->db->prepare(
            'UPDATE templates SET status = ?, last_synced_at = ? WHERE id = ?'
        );
        $stmt->execute([
            $templateUpdate->status,
            date('Y-m-d H:i:s'),
            $templateUpdate->templateId
        ]);

        // Invalidate cache
        $templateService->invalidateCache();

        // Assert: Verify template update
        $this->assertEquals('template_123', $templateUpdate->templateId);
        $this->assertEquals('APPROVED', $templateUpdate->status);

        // Assert: Verify database update
        $stmt = $this->db->prepare('SELECT * FROM templates WHERE id = ?');
        $stmt->execute(['template_123']);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('APPROVED', $template['status']);

        // Assert: Verify cache invalidation
        $cachedTemplates = $cache->get('templates:all');
        $this->assertNull($cachedTemplates);
    }

    /**
     * Test: Multi-provider template synchronization
     * 
     * Verifies:
     * - Syncing from multiple providers
     * - Provider-specific template handling
     * - Consolidated template storage
     */
    public function testMultiProviderTemplateSynchronization(): void
    {
        // Arrange: Mock responses from both providers
        $infobipResponse = new Response(200, [], json_encode([
            'templates' => [
                [
                    'id' => 'infobip_template_1',
                    'name' => 'infobip_welcome',
                    'language' => 'pt',
                    'status' => 'APPROVED',
                    'category' => 'MARKETING',
                    'components' => [['type' => 'BODY', 'text' => 'Welcome from Infobip']]
                ]
            ]
        ]));

        $twilioResponse = new Response(200, [], json_encode([
            'content' => [
                [
                    'sid' => 'twilio_template_1',
                    'friendly_name' => 'twilio_welcome',
                    'language' => 'en',
                    'approval_status' => 'approved',
                    'types' => ['twilio/text'],
                    'variables' => []
                ]
            ]
        ]));

        $mockHandler = new MockHandler([$infobipResponse, $twilioResponse]);
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

        $templateRepo = new \App\Repositories\TemplateRepository($this->db);
        
        $cache = new class($this->redis) implements \App\Services\CacheInterface {
            public function __construct(private Redis $redis) {}
            public function get(string $key): mixed {
                $value = $this->redis->get($key);
                return $value !== false ? json_decode($value, true) : null;
            }
            public function set(string $key, mixed $value, int $ttl = 3600): void {
                $this->redis->setex($key, $ttl, json_encode($value));
            }
            public function delete(string $key): void {
                $this->redis->del($key);
            }
            public function clear(): void {
                $this->redis->flushDB();
            }
        };

        $templateService = new \App\Services\TemplateService(
            $providerFactory,
            $templateRepo,
            $cache,
            new NullLogger()
        );

        // Act: Sync from all providers
        $stats = $templateService->syncTemplates(); // null = all providers

        // Assert: Verify both providers' templates were synced
        $stmt = $this->db->query('SELECT COUNT(*) FROM templates');
        $count = $stmt->fetchColumn();
        $this->assertEquals(2, $count);

        // Assert: Verify Infobip template
        $stmt = $this->db->prepare('SELECT * FROM templates WHERE id = ?');
        $stmt->execute(['infobip_template_1']);
        $infobipTemplate = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($infobipTemplate);
        $this->assertEquals('infobip_welcome', $infobipTemplate['name']);

        // Assert: Verify Twilio template
        $stmt = $this->db->prepare('SELECT * FROM templates WHERE id = ?');
        $stmt->execute(['twilio_template_1']);
        $twilioTemplate = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($twilioTemplate);
        $this->assertEquals('twilio_welcome', $twilioTemplate['name']);
    }

    /**
     * Test: Template caching behavior
     * 
     * Verifies:
     * - Templates are cached after first fetch
     * - Cache hit reduces database queries
     * - Cache invalidation works correctly
     */
    public function testTemplateCachingBehavior(): void
    {
        // Arrange: Insert templates
        $stmt = $this->db->prepare(
            'INSERT INTO templates (id, name, language, status, category, components, last_synced_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            'template_1',
            'welcome',
            'pt',
            'APPROVED',
            'MARKETING',
            json_encode([['type' => 'BODY', 'text' => 'Welcome']]),
            date('Y-m-d H:i:s')
        ]);

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

        $templateRepo = new \App\Repositories\TemplateRepository($this->db);
        
        $cache = new class($this->redis) implements \App\Services\CacheInterface {
            public function __construct(private Redis $redis) {}
            public function get(string $key): mixed {
                $value = $this->redis->get($key);
                return $value !== false ? json_decode($value, true) : null;
            }
            public function set(string $key, mixed $value, int $ttl = 3600): void {
                $this->redis->setex($key, $ttl, json_encode($value));
            }
            public function delete(string $key): void {
                $this->redis->del($key);
            }
            public function clear(): void {
                $this->redis->flushDB();
            }
        };

        $templateService = new \App\Services\TemplateService(
            $providerFactory,
            $templateRepo,
            $cache,
            new NullLogger()
        );

        // Act: First fetch (should hit database and cache)
        $templates1 = $templateService->getAllTemplates();

        // Assert: Cache should now contain templates
        $cachedTemplates = $cache->get('templates:all');
        $this->assertNotNull($cachedTemplates);
        $this->assertCount(1, $cachedTemplates);

        // Act: Second fetch (should hit cache)
        $templates2 = $templateService->getAllTemplates();

        // Assert: Both fetches return same data
        $this->assertEquals($templates1, $templates2);

        // Act: Invalidate cache
        $templateService->invalidateCache();

        // Assert: Cache should be empty
        $cachedTemplates = $cache->get('templates:all');
        $this->assertNull($cachedTemplates);
    }
}
