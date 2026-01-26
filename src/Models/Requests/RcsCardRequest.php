<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models\Requests;

/**
 * Request model for sending RCS rich card messages
 */
class RcsCardRequest
{
    /**
     * @param string $to Recipient phone number in E.164 format
     * @param string $title Card title
     * @param string|null $description Card description
     * @param string|null $mediaUrl Media URL (image or video)
     * @param string $mediaHeight Media height (SHORT, MEDIUM, TALL)
     * @param array<array{text: string, postbackData: string, type?: string}> $suggestions Suggestion buttons
     * @param string|null $notifyUrl Webhook URL for delivery reports
     */
    public function __construct(
        public readonly string $to,
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly ?string $mediaUrl = null,
        public readonly string $mediaHeight = 'MEDIUM',
        public readonly array $suggestions = [],
        public readonly ?string $notifyUrl = null
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->to)) {
            throw new \InvalidArgumentException('Recipient phone number is required');
        }

        if (empty($this->title)) {
            throw new \InvalidArgumentException('Card title is required');
        }

        if (strlen($this->title) > 200) {
            throw new \InvalidArgumentException('Card title must not exceed 200 characters');
        }

        if ($this->description && strlen($this->description) > 2000) {
            throw new \InvalidArgumentException('Card description must not exceed 2000 characters');
        }

        if (!in_array($this->mediaHeight, ['SHORT', 'MEDIUM', 'TALL'])) {
            throw new \InvalidArgumentException('Media height must be SHORT, MEDIUM, or TALL');
        }

        if (count($this->suggestions) > 4) {
            throw new \InvalidArgumentException('Maximum 4 suggestions allowed per card');
        }
    }
}

