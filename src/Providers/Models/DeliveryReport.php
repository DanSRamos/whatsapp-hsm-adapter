<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers\Models;

/**
 * Delivery report from a provider webhook
 */
class DeliveryReport
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $status,
        public readonly \DateTimeImmutable $timestamp,
        public readonly ?string $error = null,
        public readonly ?array $metadata = null
    ) {}
}
