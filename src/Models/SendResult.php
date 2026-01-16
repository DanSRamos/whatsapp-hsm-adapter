<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models;

class SendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $messageId = null,
        public readonly ?string $status = null,
        public readonly ?string $error = null,
        public readonly ?array $details = null
    ) {}
}
