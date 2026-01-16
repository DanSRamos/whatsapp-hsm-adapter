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
    
    public function saveIncomingMessage(IncomingMessage $message): void;
}
