<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers\Models;

/**
 * Template update notification from a provider webhook
 */
class TemplateUpdate
{
    public function __construct(
        public readonly string $templateId,
        public readonly string $action, // 'updated', 'deleted', 'approved', 'rejected'
        public readonly \DateTimeImmutable $timestamp,
        public readonly ?ProviderTemplate $template = null,
        public readonly ?string $reason = null
    ) {}
}
