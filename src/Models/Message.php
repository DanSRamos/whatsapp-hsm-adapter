<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models;

class Message
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $toNumber,
        public readonly string $fromNumber,
        public readonly string $status,
        public readonly array $content,
        public readonly \DateTimeImmutable $sentAt,
        public readonly ?\DateTimeImmutable $deliveredAt = null,
        public readonly ?\DateTimeImmutable $readAt = null,
        public readonly ?string $errorMessage = null,
        public readonly ?array $metadata = null
    ) {}
}
