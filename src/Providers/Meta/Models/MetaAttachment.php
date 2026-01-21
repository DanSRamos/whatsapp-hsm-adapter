<?php

declare(strict_types=1);

namespace App\Providers\Meta\Models;

/**
 * Represents a media attachment for Meta messaging (Instagram or Facebook Messenger).
 * Supports images, videos, audio, and files with platform-specific size limits.
 */
class MetaAttachment
{
    private const TYPE_IMAGE = 'image';
    private const TYPE_VIDEO = 'video';
    private const TYPE_AUDIO = 'audio';
    private const TYPE_FILE = 'file';

    private const VALID_TYPES = [
        self::TYPE_IMAGE,
        self::TYPE_VIDEO,
        self::TYPE_AUDIO,
        self::TYPE_FILE,
    ];

    // Default limits (can be overridden by platform-specific limits)
    private const DEFAULT_LIMITS = [
        self::TYPE_IMAGE => 8 * 1024 * 1024,   // 8MB (Instagram default)
        self::TYPE_VIDEO => 25 * 1024 * 1024,  // 25MB
        self::TYPE_AUDIO => 25 * 1024 * 1024,  // 25MB
        self::TYPE_FILE => 25 * 1024 * 1024,   // 25MB
    ];

    /**
     * @param string $type Attachment type (image, video, audio, file)
     * @param string $url URL of the attachment
     * @param int|null $size Size in bytes (optional, for validation)
     * @param array $platformLimits Platform-specific size limits (optional)
     */
    public function __construct(
        public readonly string $type,
        public readonly string $url,
        public readonly ?int $size = null,
        private readonly array $platformLimits = []
    ) {
        $this->validateType($type);
        $this->validateUrl($url);
        
        if ($size !== null) {
            $this->validateSize($type, $size);
        }
    }

    /**
     * Validate attachment type.
     */
    private function validateType(string $type): void
    {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid attachment type: %s. Must be one of: %s',
                    $type,
                    implode(', ', self::VALID_TYPES)
                )
            );
        }
    }

    /**
     * Validate attachment URL.
     */
    private function validateUrl(string $url): void
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Attachment URL cannot be empty');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(
                'Invalid attachment URL format'
            );
        }

        // Must be HTTPS for Meta APIs
        if (!str_starts_with($url, 'https://')) {
            throw new \InvalidArgumentException(
                'Attachment URL must use HTTPS protocol'
            );
        }
    }

    /**
     * Validate attachment size against platform limits.
     */
    private function validateSize(string $type, int $size): void
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('Attachment size must be positive');
        }

        $limits = !empty($this->platformLimits) ? $this->platformLimits : self::DEFAULT_LIMITS;
        
        $maxSize = $limits['max_' . $type . '_size'] ?? $limits[$type] ?? self::DEFAULT_LIMITS[$type];

        if ($size > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);
            $actualSizeMB = round($size / (1024 * 1024), 2);
            
            throw new \InvalidArgumentException(
                sprintf(
                    'Attachment size (%s MB) exceeds limit for type %s (%s MB)',
                    $actualSizeMB,
                    $type,
                    $maxSizeMB
                )
            );
        }
    }

    /**
     * Convert to array format for API payload.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'payload' => [
                'url' => $this->url,
                'is_reusable' => true, // Allow Meta to cache the attachment
            ],
        ];
    }

    /**
     * Create an image attachment.
     */
    public static function image(string $url, ?int $size = null, array $platformLimits = []): self
    {
        return new self(self::TYPE_IMAGE, $url, $size, $platformLimits);
    }

    /**
     * Create a video attachment.
     */
    public static function video(string $url, ?int $size = null, array $platformLimits = []): self
    {
        return new self(self::TYPE_VIDEO, $url, $size, $platformLimits);
    }

    /**
     * Create an audio attachment.
     */
    public static function audio(string $url, ?int $size = null, array $platformLimits = []): self
    {
        return new self(self::TYPE_AUDIO, $url, $size, $platformLimits);
    }

    /**
     * Create a file attachment.
     */
    public static function file(string $url, ?int $size = null, array $platformLimits = []): self
    {
        return new self(self::TYPE_FILE, $url, $size, $platformLimits);
    }

    /**
     * Check if this is an image attachment.
     */
    public function isImage(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    /**
     * Check if this is a video attachment.
     */
    public function isVideo(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    /**
     * Check if this is an audio attachment.
     */
    public function isAudio(): bool
    {
        return $this->type === self::TYPE_AUDIO;
    }

    /**
     * Check if this is a file attachment.
     */
    public function isFile(): bool
    {
        return $this->type === self::TYPE_FILE;
    }
}
