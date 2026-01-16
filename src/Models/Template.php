<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models;

class Template
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
        return $this->status === 'approved';
    }

    public function getParameters(): array
    {
        $parameters = [];
        
        foreach ($this->components as $component) {
            if (isset($component['parameters'])) {
                $parameters = array_merge($parameters, $component['parameters']);
            }
        }
        
        return $parameters;
    }
}
