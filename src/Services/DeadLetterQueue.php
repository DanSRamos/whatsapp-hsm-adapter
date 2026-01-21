<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Psr\Log\LoggerInterface;

/**
 * Dead Letter Queue for failed webhook processing
 * 
 * Stores webhooks that failed processing after all retry attempts.
 * Allows for manual inspection and reprocessing of failed webhooks.
 */
class DeadLetterQueue
{
    private const TABLE_NAME = 'webhook_logs';

    public function __construct(
        private readonly \PDO $db,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Add a failed webhook to the dead letter queue
     * 
     * @param array<string, mixed> $payload The webhook payload
     * @param string $error Error message
     * @param int $errorCode Error code (if available)
     * @param int $attemptNumber Number of attempts made
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function add(
        array $payload,
        string $error,
        int $errorCode = 0,
        int $attemptNumber = 0,
        array $metadata = []
    ): void {
        try {
            $type = $this->extractWebhookType($payload);
            
            $enrichedMetadata = array_merge($metadata, [
                'error_code' => $errorCode,
                'attempt_number' => $attemptNumber,
                'added_to_dlq_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ]);

            $stmt = $this->db->prepare(
                "INSERT INTO " . self::TABLE_NAME . " 
                (type, payload, processed, error_message, received_at) 
                VALUES (:type, :payload, :processed, :error_message, :received_at)"
            );

            $stmt->execute([
                'type' => $type,
                'payload' => json_encode([
                    'original_payload' => $payload,
                    'metadata' => $enrichedMetadata
                ]),
                'processed' => 0,
                'error_message' => $error,
                'received_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ]);

            $this->logger->info('Webhook added to dead letter queue', [
                'type' => $type,
                'error_code' => $errorCode,
                'attempt_number' => $attemptNumber,
                'dlq_id' => $this->db->lastInsertId()
            ]);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to add webhook to dead letter queue', [
                'error' => $e->getMessage(),
                'original_error' => $error
            ]);
            throw new \RuntimeException(
                'Failed to add webhook to dead letter queue: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get failed webhooks from the dead letter queue
     * 
     * @param int $limit Maximum number of records to retrieve
     * @param int $offset Offset for pagination
     * @return array<array<string, mixed>> Array of failed webhooks
     */
    public function getFailedWebhooks(int $limit = 100, int $offset = 0): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, type, payload, error_message, received_at, processed_at 
                FROM " . self::TABLE_NAME . " 
                WHERE processed = 0 
                ORDER BY received_at DESC 
                LIMIT :limit OFFSET :offset"
            );

            $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Decode JSON payloads
            return array_map(function ($row) {
                $row['payload'] = json_decode($row['payload'], true);
                return $row;
            }, $results);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to retrieve failed webhooks', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                'Failed to retrieve failed webhooks: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get a specific failed webhook by ID
     * 
     * @param int $id The webhook log ID
     * @return array<string, mixed>|null The webhook data or null if not found
     */
    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, type, payload, error_message, received_at, processed_at, processed 
                FROM " . self::TABLE_NAME . " 
                WHERE id = :id"
            );

            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$result) {
                return null;
            }

            $result['payload'] = json_decode($result['payload'], true);
            return $result;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to retrieve webhook by ID', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                'Failed to retrieve webhook: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Mark a webhook as processed
     * 
     * @param int $id The webhook log ID
     */
    public function markAsProcessed(int $id): void
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE " . self::TABLE_NAME . " 
                SET processed = 1, processed_at = :processed_at 
                WHERE id = :id"
            );

            $stmt->execute([
                'id' => $id,
                'processed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ]);

            $this->logger->info('Webhook marked as processed', [
                'dlq_id' => $id
            ]);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to mark webhook as processed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                'Failed to mark webhook as processed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Retry processing a failed webhook
     * 
     * @param int $id The webhook log ID
     * @param callable $processor Function to process the webhook
     * @return bool True if processing succeeded, false otherwise
     */
    public function retry(int $id, callable $processor): bool
    {
        $webhook = $this->getById($id);

        if (!$webhook) {
            $this->logger->warning('Webhook not found for retry', ['id' => $id]);
            return false;
        }

        if ($webhook['processed']) {
            $this->logger->warning('Webhook already processed', ['id' => $id]);
            return false;
        }

        try {
            $this->logger->info('Retrying webhook processing', [
                'dlq_id' => $id,
                'type' => $webhook['type']
            ]);

            // Extract original payload
            $originalPayload = $webhook['payload']['original_payload'] ?? $webhook['payload'];

            // Process the webhook
            $processor($originalPayload);

            // Mark as processed
            $this->markAsProcessed($id);

            $this->logger->info('Webhook retry successful', ['dlq_id' => $id]);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Webhook retry failed', [
                'dlq_id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get count of failed webhooks
     */
    public function getFailedCount(): int
    {
        try {
            $stmt = $this->db->query(
                "SELECT COUNT(*) as count FROM " . self::TABLE_NAME . " WHERE processed = 0"
            );
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int) ($result['count'] ?? 0);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to get failed webhook count', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get count of failed webhooks by type
     * 
     * @return array<string, int> Array mapping webhook type to count
     */
    public function getFailedCountByType(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT type, COUNT(*) as count 
                FROM " . self::TABLE_NAME . " 
                WHERE processed = 0 
                GROUP BY type"
            );
            
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $counts = [];
            foreach ($results as $row) {
                $counts[$row['type']] = (int) $row['count'];
            }
            
            return $counts;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to get failed webhook count by type', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Extract webhook type from payload
     */
    private function extractWebhookType(array $payload): string
    {
        // Try to determine type from payload structure
        if (isset($payload['object'])) {
            return 'meta_' . $payload['object'];
        }

        if (isset($payload['entry'][0]['messaging'])) {
            return 'meta_messaging';
        }

        return 'unknown';
    }

    /**
     * Clean up old processed webhooks
     * 
     * @param int $daysOld Number of days to keep processed webhooks
     * @return int Number of records deleted
     */
    public function cleanupOldWebhooks(int $daysOld = 30): int
    {
        try {
            $cutoffDate = (new \DateTimeImmutable())
                ->modify("-{$daysOld} days")
                ->format('Y-m-d H:i:s');

            $stmt = $this->db->prepare(
                "DELETE FROM " . self::TABLE_NAME . " 
                WHERE processed = 1 AND processed_at < :cutoff_date"
            );

            $stmt->execute(['cutoff_date' => $cutoffDate]);
            $deletedCount = $stmt->rowCount();

            $this->logger->info('Cleaned up old processed webhooks', [
                'deleted_count' => $deletedCount,
                'days_old' => $daysOld
            ]);

            return $deletedCount;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to cleanup old webhooks', [
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                'Failed to cleanup old webhooks: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}

