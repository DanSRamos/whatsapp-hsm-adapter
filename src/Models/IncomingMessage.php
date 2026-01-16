<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models;

class IncomingMessage
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $from,
        public readonly string $to,
        public readonly string $type,
        public readonly mixed $content,
        public readonly \DateTimeImmutable $receivedAt,
        public readonly ?string $contextMessageId = null
    ) {}
}
