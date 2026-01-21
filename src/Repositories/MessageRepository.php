<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Repositories;

use WhatsApp\Adapter\Models\Message;
use PDO;

class MessageRepository implements MessageRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function save(Message $message): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $sql = <<<SQL
                INSERT OR REPLACE INTO messages (
                    id, type, to_number, from_number, status, content,
                    sent_at, delivered_at, read_at, error_message, metadata,
                    created_at, updated_at
                ) VALUES (
                    :id, :type, :to_number, :from_number, :status, :content,
                    :sent_at, :delivered_at, :read_at, :error_message, :metadata,
                    COALESCE((SELECT created_at FROM messages WHERE id = :id), CURRENT_TIMESTAMP),
                    CURRENT_TIMESTAMP
                )
            SQL;
        } else {
            // MySQL/PostgreSQL
            $sql = <<<SQL
                INSERT INTO messages (
                    id, type, to_number, from_number, status, content,
                    sent_at, delivered_at, read_at, error_message, metadata
                ) VALUES (
                    :id, :type, :to_number, :from_number, :status, :content,
                    :sent_at, :delivered_at, :read_at, :error_message, :metadata
                )
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    delivered_at = VALUES(delivered_at),
                    read_at = VALUES(read_at),
                    error_message = VALUES(error_message),
                    metadata = VALUES(metadata),
                    updated_at = CURRENT_TIMESTAMP
            SQL;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $message->id,
            'type' => $message->type,
            'to_number' => $message->toNumber,
            'from_number' => $message->fromNumber,
            'status' => $message->status,
            'content' => json_encode($message->content),
            'sent_at' => $message->sentAt->format('Y-m-d H:i:s'),
            'delivered_at' => $message->deliveredAt?->format('Y-m-d H:i:s'),
            'read_at' => $message->readAt?->format('Y-m-d H:i:s'),
            'error_message' => $message->errorMessage,
            'metadata' => $message->metadata ? json_encode($message->metadata) : null,
        ]);
    }

    public function findById(string $messageId): ?Message
    {
        $sql = <<<SQL
            SELECT id, type, to_number, from_number, status, content,
                   sent_at, delivered_at, read_at, error_message, metadata
            FROM messages
            WHERE id = :id
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $messageId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        return new Message(
            id: $row['id'],
            type: $row['type'],
            toNumber: $row['to_number'],
            fromNumber: $row['from_number'],
            status: $row['status'],
            content: json_decode($row['content'], true),
            sentAt: new \DateTimeImmutable($row['sent_at']),
            deliveredAt: $row['delivered_at'] ? new \DateTimeImmutable($row['delivered_at']) : null,
            readAt: $row['read_at'] ? new \DateTimeImmutable($row['read_at']) : null,
            errorMessage: $row['error_message'],
            metadata: $row['metadata'] ? json_decode($row['metadata'], true) : null
        );
    }

    public function updateStatus(string $messageId, string $status, array $metadata): void
    {
        $sql = <<<SQL
            UPDATE messages
            SET status = :status,
                metadata = :metadata,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $messageId,
            'status' => $status,
            'metadata' => json_encode($metadata),
        ]);
    }

    /**
     * Update message status with delivery timestamps
     * 
     * Updates the message status and sets appropriate timestamp fields
     * based on the delivery report status (delivered or read).
     * 
     * @param string $messageId The message ID to update
     * @param string $status The new status (delivered, read, etc.)
     * @param \DateTimeImmutable $timestamp The timestamp from the delivery report
     * @param array $metadata Additional metadata to store
     * @return void
     */
    public function updateDeliveryStatus(
        string $messageId,
        string $status,
        \DateTimeImmutable $timestamp,
        array $metadata
    ): void {
        // Determine which timestamp field to update based on status
        $updateFields = ['status = :status', 'metadata = :metadata', 'updated_at = CURRENT_TIMESTAMP'];
        $params = [
            'id' => $messageId,
            'status' => $status,
            'metadata' => json_encode($metadata)
        ];

        // Add appropriate timestamp field based on status
        if ($status === 'delivered') {
            $updateFields[] = 'delivered_at = :delivered_at';
            $params['delivered_at'] = $timestamp->format('Y-m-d H:i:s');
        } elseif ($status === 'read') {
            // When message is read, it's also delivered (if not already)
            $updateFields[] = 'read_at = :read_at';
            $params['read_at'] = $timestamp->format('Y-m-d H:i:s');
            
            // Also set delivered_at if it's not already set
            $updateFields[] = 'delivered_at = COALESCE(delivered_at, :delivered_at)';
            $params['delivered_at'] = $timestamp->format('Y-m-d H:i:s');
        }

        $sql = sprintf(
            'UPDATE messages SET %s WHERE id = :id',
            implode(', ', $updateFields)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function saveIncomingMessage(\WhatsApp\Adapter\Models\IncomingMessage $message): void
    {
        $sql = <<<SQL
            INSERT INTO incoming_messages (
                id, from_number, to_number, type, content,
                context_message_id, received_at, processed
            ) VALUES (
                :id, :from_number, :to_number, :type, :content,
                :context_message_id, :received_at, :processed
            )
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $message->messageId,
            'from_number' => $message->from,
            'to_number' => $message->to,
            'type' => $message->type,
            'content' => json_encode($message->content),
            'context_message_id' => $message->contextMessageId,
            'received_at' => $message->receivedAt->format('Y-m-d H:i:s'),
            'processed' => false,
        ]);
    }

    /**
     * Find the last incoming message from a specific sender
     * 
     * This is used to validate the 24-hour messaging window for Meta provider.
     * 
     * @param string $fromNumber The sender's number/ID (phone number, IGSID, or PSID)
     * @return \WhatsApp\Adapter\Models\IncomingMessage|null The last incoming message or null if none found
     */
    public function findLastIncomingMessage(string $fromNumber): ?\WhatsApp\Adapter\Models\IncomingMessage
    {
        $sql = <<<SQL
            SELECT id, from_number, to_number, type, content,
                   context_message_id, received_at
            FROM incoming_messages
            WHERE from_number = :from_number
            ORDER BY received_at DESC
            LIMIT 1
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['from_number' => $fromNumber]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        return new \WhatsApp\Adapter\Models\IncomingMessage(
            messageId: $row['id'],
            from: $row['from_number'],
            to: $row['to_number'],
            type: $row['type'],
            content: json_decode($row['content'], true),
            receivedAt: new \DateTimeImmutable($row['received_at']),
            contextMessageId: $row['context_message_id']
        );
    }
}
