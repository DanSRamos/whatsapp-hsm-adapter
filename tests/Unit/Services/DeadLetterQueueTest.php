<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use WhatsApp\Adapter\Services\DeadLetterQueue;

class DeadLetterQueueTest extends TestCase
{
    private \PDO $db;
    private DeadLetterQueue $queue;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Create webhook_logs table
        $this->db->exec("
            CREATE TABLE webhook_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type VARCHAR(50) NOT NULL,
                payload TEXT NOT NULL,
                processed BOOLEAN DEFAULT 0,
                error_message TEXT NULL,
                received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                processed_at TIMESTAMP NULL
            )
        ");

        $this->queue = new DeadLetterQueue($this->db, new NullLogger());
    }

    public function testAddWebhookToQueue(): void
    {
        $payload = ['object' => 'page', 'entry' => []];
        $error = 'Test error';
        $errorCode = 36103;

        $this->queue->add($payload, $error, $errorCode, 1);

        // Verify it was added
        $stmt = $this->db->query("SELECT * FROM webhook_logs");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result);
        $this->assertEquals('meta_page', $result['type']);
        $this->assertEquals($error, $result['error_message']);
        $this->assertEquals(0, $result['processed']);
    }

    public function testGetFailedWebhooks(): void
    {
        // Add some webhooks with slight delay to ensure different timestamps
        $this->queue->add(['object' => 'page'], 'Error 1', 36103, 1);
        usleep(10000); // 10ms delay
        $this->queue->add(['object' => 'page'], 'Error 2', 2022, 1);

        $failed = $this->queue->getFailedWebhooks();

        $this->assertCount(2, $failed);
        // Just verify both are present, order may vary with same timestamp
        $errors = array_column($failed, 'error_message');
        $this->assertContains('Error 1', $errors);
        $this->assertContains('Error 2', $errors);
    }

    public function testGetFailedWebhooksWithLimit(): void
    {
        // Add 5 webhooks
        for ($i = 1; $i <= 5; $i++) {
            $this->queue->add(['object' => 'page'], "Error $i", 500, 1);
        }

        $failed = $this->queue->getFailedWebhooks(3);

        $this->assertCount(3, $failed);
    }

    public function testGetFailedWebhooksWithOffset(): void
    {
        // Add 5 webhooks
        for ($i = 1; $i <= 5; $i++) {
            $this->queue->add(['object' => 'page'], "Error $i", 500, 1);
        }

        $failed = $this->queue->getFailedWebhooks(2, 2);

        $this->assertCount(2, $failed);
        $this->assertEquals('Error 3', $failed[0]['error_message']);
    }

    public function testGetById(): void
    {
        $this->queue->add(['object' => 'page'], 'Test error', 36103, 1);

        $webhook = $this->queue->getById(1);

        $this->assertNotNull($webhook);
        $this->assertEquals('Test error', $webhook['error_message']);
        $this->assertEquals('meta_page', $webhook['type']);
        $this->assertIsArray($webhook['payload']);
    }

    public function testGetByIdNotFound(): void
    {
        $webhook = $this->queue->getById(999);

        $this->assertNull($webhook);
    }

    public function testMarkAsProcessed(): void
    {
        $this->queue->add(['object' => 'page'], 'Test error', 36103, 1);

        $this->queue->markAsProcessed(1);

        $webhook = $this->queue->getById(1);
        $this->assertEquals(1, $webhook['processed']);
        $this->assertNotNull($webhook['processed_at']);
    }

    public function testRetrySuccessful(): void
    {
        $this->queue->add(['object' => 'page'], 'Test error', 36103, 1);

        $processed = false;
        $processor = function ($payload) use (&$processed) {
            $processed = true;
        };

        $result = $this->queue->retry(1, $processor);

        $this->assertTrue($result);
        $this->assertTrue($processed);

        // Verify marked as processed
        $webhook = $this->queue->getById(1);
        $this->assertEquals(1, $webhook['processed']);
    }

    public function testRetryFailed(): void
    {
        $this->queue->add(['object' => 'page'], 'Test error', 36103, 1);

        $processor = function ($payload) {
            throw new \Exception('Processing failed');
        };

        $result = $this->queue->retry(1, $processor);

        $this->assertFalse($result);

        // Verify NOT marked as processed
        $webhook = $this->queue->getById(1);
        $this->assertEquals(0, $webhook['processed']);
    }

    public function testRetryNotFound(): void
    {
        $processor = function ($payload) {};

        $result = $this->queue->retry(999, $processor);

        $this->assertFalse($result);
    }

    public function testRetryAlreadyProcessed(): void
    {
        $this->queue->add(['object' => 'page'], 'Test error', 36103, 1);
        $this->queue->markAsProcessed(1);

        $processor = function ($payload) {};

        $result = $this->queue->retry(1, $processor);

        $this->assertFalse($result);
    }

    public function testGetFailedCount(): void
    {
        $this->queue->add(['object' => 'page'], 'Error 1', 36103, 1);
        $this->queue->add(['object' => 'page'], 'Error 2', 2022, 1);
        $this->queue->add(['object' => 'page'], 'Error 3', 500, 1);

        $count = $this->queue->getFailedCount();

        $this->assertEquals(3, $count);
    }

    public function testGetFailedCountExcludesProcessed(): void
    {
        $this->queue->add(['object' => 'page'], 'Error 1', 36103, 1);
        $this->queue->add(['object' => 'page'], 'Error 2', 2022, 1);
        $this->queue->markAsProcessed(1);

        $count = $this->queue->getFailedCount();

        $this->assertEquals(1, $count);
    }

    public function testGetFailedCountByType(): void
    {
        $this->queue->add(['object' => 'page'], 'Error 1', 36103, 1);
        $this->queue->add(['object' => 'page'], 'Error 2', 2022, 1);
        $this->queue->add(['object' => 'instagram'], 'Error 3', 500, 1);

        $counts = $this->queue->getFailedCountByType();

        $this->assertEquals(2, $counts['meta_page']);
        $this->assertEquals(1, $counts['meta_instagram']);
    }

    public function testCleanupOldWebhooks(): void
    {
        // Add and process some webhooks
        $this->queue->add(['object' => 'page'], 'Error 1', 36103, 1);
        $this->queue->add(['object' => 'page'], 'Error 2', 2022, 1);
        $this->queue->markAsProcessed(1);
        $this->queue->markAsProcessed(2);

        // Set processed_at to old date
        $oldDate = (new \DateTimeImmutable())->modify('-31 days')->format('Y-m-d H:i:s');
        $this->db->exec("UPDATE webhook_logs SET processed_at = '$oldDate' WHERE processed = 1");

        $deletedCount = $this->queue->cleanupOldWebhooks(30);

        $this->assertEquals(2, $deletedCount);

        // Verify they were deleted
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM webhook_logs");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(0, $result['count']);
    }

    public function testCleanupDoesNotDeleteUnprocessed(): void
    {
        // Add unprocessed webhook
        $this->queue->add(['object' => 'page'], 'Error 1', 36103, 1);

        $deletedCount = $this->queue->cleanupOldWebhooks(30);

        $this->assertEquals(0, $deletedCount);

        // Verify it's still there
        $count = $this->queue->getFailedCount();
        $this->assertEquals(1, $count);
    }

    public function testCleanupDoesNotDeleteRecentProcessed(): void
    {
        // Add and process webhook
        $this->queue->add(['object' => 'page'], 'Error 1', 36103, 1);
        $this->queue->markAsProcessed(1);

        $deletedCount = $this->queue->cleanupOldWebhooks(30);

        $this->assertEquals(0, $deletedCount);
    }
}

