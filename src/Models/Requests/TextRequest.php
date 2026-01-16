<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models\Requests;

class TextRequest
{
    public function __construct(
        public readonly string $to,
        public readonly string $text,
        public readonly bool $previewUrl = false,
        public readonly ?string $notifyUrl = null
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->to)) {
            throw new \InvalidArgumentException('Field "to" is required');
        }

        if (empty($this->text)) {
            throw new \InvalidArgumentException('Field "text" is required');
        }
    }
}
