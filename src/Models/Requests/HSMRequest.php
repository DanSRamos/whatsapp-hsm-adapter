<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models\Requests;

class HSMRequest
{
    public function __construct(
        public readonly string $to,
        public readonly string $templateName,
        public readonly string $templateLanguage,
        public readonly array $parameters = [],
        public readonly ?string $notifyUrl = null
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->to)) {
            throw new \InvalidArgumentException('Field "to" is required');
        }

        if (empty($this->templateName)) {
            throw new \InvalidArgumentException('Field "templateName" is required');
        }

        if (empty($this->templateLanguage)) {
            throw new \InvalidArgumentException('Field "templateLanguage" is required');
        }
    }
}
