<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models\Requests;

class InteractiveButtonsRequest
{
    private const MAX_BUTTONS = 3;

    public function __construct(
        public readonly string $to,
        public readonly string $bodyText,
        public readonly array $buttons,
        public readonly ?string $headerText = null,
        public readonly ?string $footerText = null,
        public readonly ?string $notifyUrl = null
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->to)) {
            throw new \InvalidArgumentException('Field "to" is required');
        }

        if (empty($this->bodyText)) {
            throw new \InvalidArgumentException('Field "bodyText" is required');
        }

        if (empty($this->buttons)) {
            throw new \InvalidArgumentException('Field "buttons" is required and must not be empty');
        }

        if (count($this->buttons) > self::MAX_BUTTONS) {
            throw new \InvalidArgumentException(
                sprintf('Maximum %d buttons allowed, %d provided', self::MAX_BUTTONS, count($this->buttons))
            );
        }

        $buttonIds = [];
        foreach ($this->buttons as $index => $button) {
            if (!isset($button['id']) || empty($button['id'])) {
                throw new \InvalidArgumentException(sprintf('Button at index %d must have an "id"', $index));
            }

            if (!isset($button['text']) || empty($button['text'])) {
                throw new \InvalidArgumentException(sprintf('Button at index %d must have "text"', $index));
            }

            if (in_array($button['id'], $buttonIds, true)) {
                throw new \InvalidArgumentException(sprintf('Duplicate button ID "%s"', $button['id']));
            }

            $buttonIds[] = $button['id'];
        }
    }
}
