<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Models\Requests;

class MediaRequest
{
    private const VALID_MEDIA_TYPES = ['image', 'document', 'audio', 'video'];
    
    private const VALID_IMAGE_FORMATS = ['jpeg', 'jpg', 'png'];
    private const VALID_DOCUMENT_FORMATS = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
    private const VALID_AUDIO_FORMATS = ['mp3', 'ogg', 'amr'];
    private const VALID_VIDEO_FORMATS = ['mp4', '3gp'];

    public function __construct(
        public readonly string $to,
        public readonly string $mediaType,
        public readonly string $mediaUrl,
        public readonly ?string $caption = null,
        public readonly ?string $filename = null,
        public readonly ?string $notifyUrl = null
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->to)) {
            throw new \InvalidArgumentException('Field "to" is required');
        }

        if (empty($this->mediaType)) {
            throw new \InvalidArgumentException('Field "mediaType" is required');
        }

        if (!in_array($this->mediaType, self::VALID_MEDIA_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid media type "%s". Valid types: %s', 
                    $this->mediaType, 
                    implode(', ', self::VALID_MEDIA_TYPES)
                )
            );
        }

        if (empty($this->mediaUrl)) {
            throw new \InvalidArgumentException('Field "mediaUrl" is required');
        }
    }

    public function getValidFormats(): array
    {
        return match($this->mediaType) {
            'image' => self::VALID_IMAGE_FORMATS,
            'document' => self::VALID_DOCUMENT_FORMATS,
            'audio' => self::VALID_AUDIO_FORMATS,
            'video' => self::VALID_VIDEO_FORMATS,
            default => []
        };
    }
}
