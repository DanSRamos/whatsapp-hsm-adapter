<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Repositories;

use WhatsApp\Adapter\Models\Message;
use WhatsApp\Adapter\Models\IncomingMessage;

interface MessageRepositoryInterface
{
    public function save(Message $message): void;
    
    public function findById(string $messageId): ?Message;
    
    public function updateStatus(string $messageId, string $status, array $metadata): void;
    
    /**
     * Update message status with delivery timestamps
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
    ): void;
    
    public function saveIncomingMessage(IncomingMessage $message): void;
    
    /**
     * Find the last incoming message from a specific sender
     * 
     * This is used to validate the 24-hour messaging window for Meta provider.
     * 
     * @param string $fromNumber The sender's number/ID (phone number, IGSID, or PSID)
     * @return IncomingMessage|null The last incoming message or null if none found
     */
    public function findLastIncomingMessage(string $fromNumber): ?IncomingMessage;
}
