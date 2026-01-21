<?php

declare(strict_types=1);

namespace App\Providers\Meta\Models;

/**
 * Represents a Quick Reply button for Meta messaging (Instagram and Facebook Messenger).
 * Quick Replies are buttons that appear below a message for quick user responses.
 */
class MetaQuickReply
{
    private const CONTENT_TYPE_TEXT = 'text';
    private const CONTENT_TYPE_USER_PHONE = 'user_phone_number';
    private const CONTENT_TYPE_USER_EMAIL = 'user_email';

    private const MAX_TITLE_LENGTH = 20;
    private const MAX_PAYLOAD_LENGTH = 1000;

    /**
     * @param string $title The text displayed on the button (max 20 characters)
     * @param string $payload The data sent back when button is clicked (max 1000 characters)
     * @param string $contentType The type of quick reply (default: 'text')
     * @param string|null $imageUrl Optional image URL for the quick reply
     */
    public function __construct(
        public readonly string $title,
        public readonly string $payload,
        public readonly string $contentType = self::CONTENT_TYPE_TEXT,
        public readonly ?string $imageUrl = null
    ) {
        $this->validateTitle($title);
        $this->validatePayload($payload);
        $this->validateContentType($contentType);
        
        if ($imageUrl !== null) {
            $this->validateImageUrl($imageUrl);
        }
    }

    /**
     * Validate quick reply title.
     */
    private function validateTitle(string $title): void
    {
        if (empty($title)) {
            throw new \InvalidArgumentException('Quick reply title cannot be empty');
        }

        if (strlen($title) > self::MAX_TITLE_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Quick reply title must be %d characters or less (got %d)',
                    self::MAX_TITLE_LENGTH,
                    strlen($title)
                )
            );
        }
    }

    /**
     * Validate quick reply payload.
     */
    private function validatePayload(string $payload): void
    {
        if (empty($payload)) {
            throw new \InvalidArgumentException('Quick reply payload cannot be empty');
        }

        if (strlen($payload) > self::MAX_PAYLOAD_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Quick reply payload must be %d characters or less (got %d)',
                    self::MAX_PAYLOAD_LENGTH,
                    strlen($payload)
                )
            );
        }
    }

    /**
     * Validate content type.
     */
    private function validateContentType(string $contentType): void
    {
        $validTypes = [
            self::CONTENT_TYPE_TEXT,
            self::CONTENT_TYPE_USER_PHONE,
            self::CONTENT_TYPE_USER_EMAIL,
        ];

        if (!in_array($contentType, $validTypes, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid content type: %s. Must be one of: %s',
                    $contentType,
                    implode(', ', $validTypes)
                )
            );
        }
    }

    /**
     * Validate image URL.
     */
    private function validateImageUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid image URL format');
        }

        if (!str_starts_with($url, 'https://')) {
            throw new \InvalidArgumentException('Image URL must use HTTPS protocol');
        }
    }

    /**
     * Convert to array format for API payload.
     */
    public function toArray(): array
    {
        $data = [
            'content_type' => $this->contentType,
            'title' => $this->title,
            'payload' => $this->payload,
        ];

        if ($this->imageUrl !== null) {
            $data['image_url'] = $this->imageUrl;
        }

        return $data;
    }

    /**
     * Create a text quick reply.
     */
    public static function text(string $title, string $payload, ?string $imageUrl = null): self
    {
        return new self($title, $payload, self::CONTENT_TYPE_TEXT, $imageUrl);
    }

    /**
     * Create a phone number quick reply.
     * This prompts the user to share their phone number.
     */
    public static function phoneNumber(string $title = 'Share Phone'): self
    {
        return new self($title, 'PHONE_NUMBER', self::CONTENT_TYPE_USER_PHONE);
    }

    /**
     * Create an email quick reply.
     * This prompts the user to share their email address.
     */
    public static function email(string $title = 'Share Email'): self
    {
        return new self($title, 'EMAIL', self::CONTENT_TYPE_USER_EMAIL);
    }

    /**
     * Check if this is a text quick reply.
     */
    public function isText(): bool
    {
        return $this->contentType === self::CONTENT_TYPE_TEXT;
    }

    /**
     * Check if this is a phone number quick reply.
     */
    public function isPhoneNumber(): bool
    {
        return $this->contentType === self::CONTENT_TYPE_USER_PHONE;
    }

    /**
     * Check if this is an email quick reply.
     */
    public function isEmail(): bool
    {
        return $this->contentType === self::CONTENT_TYPE_USER_EMAIL;
    }

    /**
     * Validate an array of quick replies against platform limits.
     *
     * @param array<MetaQuickReply> $quickReplies
     * @param int $maxQuickReplies Maximum allowed quick replies (default: 13)
     * @throws \InvalidArgumentException
     */
    public static function validateQuickReplies(array $quickReplies, int $maxQuickReplies = 13): void
    {
        if (empty($quickReplies)) {
            throw new \InvalidArgumentException('Quick replies array cannot be empty');
        }

        if (count($quickReplies) > $maxQuickReplies) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Too many quick replies: %d (maximum: %d)',
                    count($quickReplies),
                    $maxQuickReplies
                )
            );
        }

        foreach ($quickReplies as $index => $quickReply) {
            if (!$quickReply instanceof self) {
                throw new \InvalidArgumentException(
                    sprintf('Quick reply at index %d is not a MetaQuickReply instance', $index)
                );
            }
        }
    }
}
