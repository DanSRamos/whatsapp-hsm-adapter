<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models;

class MessageStatus
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $status,
        public readonly string $to,
        public readonly \DateTimeImmutable $sentAt,
        public readonly ?\DateTimeImmutable $deliveredAt = null,
        public readonly ?\DateTimeImmutable $readAt = null,
        public readonly ?string $error = null
    ) {}
}
