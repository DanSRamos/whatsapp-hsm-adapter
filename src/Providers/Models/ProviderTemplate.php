<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers\Models;

/**
 * Template information from a provider
 */
class ProviderTemplate
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $language,
        public readonly string $status,
        public readonly string $category,
        public readonly array $components,
        public readonly ?string $rejectionReason = null
    ) {}

    public function isApproved(): bool
    {
        return $this->status === 'approved' || $this->status === 'APPROVED';
    }

    public function getParameters(): array
    {
        $parameters = [];
        
        foreach ($this->components as $component) {
            if (isset($component['type']) && $component['type'] === 'BODY') {
                if (isset($component['text'])) {
                    // Extract parameters from template text (e.g., {{1}}, {{2}})
                    preg_match_all('/\{\{(\d+)\}\}/', $component['text'], $matches);
                    if (!empty($matches[1])) {
                        $parameters = array_map('intval', $matches[1]);
                    }
                }
            }
        }
        
        return $parameters;
    }
}
