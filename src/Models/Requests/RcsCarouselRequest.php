<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models\Requests;

/**
 * Request model for sending RCS carousel messages
 */
class RcsCarouselRequest
{
    /**
     * @param string $to Recipient phone number in E.164 format
     * @param array<array{title: string, description?: string, mediaUrl?: string, suggestions?: array}> $cards Array of card objects
     * @param string $cardWidth Card width (SMALL, MEDIUM)
     * @param string|null $notifyUrl Webhook URL for delivery reports
     */
    public function __construct(
        public readonly string $to,
        public readonly array $cards,
        public readonly string $cardWidth = 'MEDIUM',
        public readonly ?string $notifyUrl = null
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->to)) {
            throw new \InvalidArgumentException('Recipient phone number is required');
        }

        if (empty($this->cards)) {
            throw new \InvalidArgumentException('At least one card is required');
        }

        if (count($this->cards) > 10) {
            throw new \InvalidArgumentException('Maximum 10 cards allowed in carousel');
        }

        if (!in_array($this->cardWidth, ['SMALL', 'MEDIUM'])) {
            throw new \InvalidArgumentException('Card width must be SMALL or MEDIUM');
        }

        foreach ($this->cards as $index => $card) {
            if (empty($card['title'])) {
                throw new \InvalidArgumentException("Card at index {$index} must have a title");
            }

            if (strlen($card['title']) > 200) {
                throw new \InvalidArgumentException("Card title at index {$index} must not exceed 200 characters");
            }

            if (isset($card['description']) && strlen($card['description']) > 2000) {
                throw new \InvalidArgumentException("Card description at index {$index} must not exceed 2000 characters");
            }

            if (isset($card['suggestions']) && count($card['suggestions']) > 4) {
                throw new \InvalidArgumentException("Card at index {$index} can have maximum 4 suggestions");
            }
        }
    }
}

