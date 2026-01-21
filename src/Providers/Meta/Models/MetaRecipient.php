<?php

declare(strict_types=1);

namespace App\Providers\Meta\Models;

/**
 * Represents a Meta messaging recipient (Instagram or Facebook Messenger).
 * Handles both IGSID (Instagram-Scoped ID) and PSID (Page-Scoped ID).
 */
class MetaRecipient
{
    private const PLATFORM_INSTAGRAM = 'instagram';
    private const PLATFORM_MESSENGER = 'messenger';

    /**
     * @param string $id The recipient ID (IGSID or PSID)
     * @param string $platform The platform ('instagram' or 'messenger')
     */
    public function __construct(
        public readonly string $id,
        public readonly string $platform = self::PLATFORM_INSTAGRAM
    ) {
        $this->validateId($id);
        $this->validatePlatform($platform);
    }

    /**
     * Validate the recipient ID format.
     *
     * Both IGSID and PSID should be non-empty numeric strings.
     */
    private function validateId(string $id): void
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('Recipient ID cannot be empty');
        }

        if (!is_numeric($id)) {
            throw new \InvalidArgumentException(
                'Invalid recipient ID format: must be numeric'
            );
        }

        // Additional length validation
        if (strlen($id) < 10) {
            throw new \InvalidArgumentException(
                'Invalid recipient ID format: ID too short'
            );
        }
    }

    /**
     * Validate the platform identifier.
     */
    private function validatePlatform(string $platform): void
    {
        if (!in_array($platform, [self::PLATFORM_INSTAGRAM, self::PLATFORM_MESSENGER], true)) {
            throw new \InvalidArgumentException(
                "Invalid platform: must be 'instagram' or 'messenger'"
            );
        }
    }

    /**
     * Check if this is an Instagram recipient.
     */
    public function isInstagram(): bool
    {
        return $this->platform === self::PLATFORM_INSTAGRAM;
    }

    /**
     * Check if this is a Messenger recipient.
     */
    public function isMessenger(): bool
    {
        return $this->platform === self::PLATFORM_MESSENGER;
    }

    /**
     * Get the ID type name (IGSID or PSID).
     */
    public function getIdType(): string
    {
        return $this->isInstagram() ? 'IGSID' : 'PSID';
    }

    /**
     * Convert to array format for API payload.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
        ];
    }

    /**
     * Create from Instagram-Scoped ID.
     */
    public static function fromIGSID(string $igsid): self
    {
        return new self($igsid, self::PLATFORM_INSTAGRAM);
    }

    /**
     * Create from Page-Scoped ID (Messenger).
     */
    public static function fromPSID(string $psid): self
    {
        return new self($psid, self::PLATFORM_MESSENGER);
    }
}
