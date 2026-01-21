<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers\Meta;

/**
 * Detects whether a message is from Instagram or Facebook Messenger
 * and provides platform-specific limits and configurations.
 */
class MetaPlatformDetector
{
    private const PLATFORM_INSTAGRAM = 'instagram';
    private const PLATFORM_MESSENGER = 'messenger';

    /**
     * Detect platform from webhook payload structure.
     *
     * Instagram webhooks have 'instagram' field in entry[].messaging[]
     * Messenger webhooks have standard 'messaging' field without 'instagram'
     */
    public function detectFromWebhook(array $payload): string
    {
        if (!isset($payload['entry']) || !is_array($payload['entry'])) {
            throw new \InvalidArgumentException('Invalid webhook payload: missing entry field');
        }

        // Check top-level object type first
        if (isset($payload['object']) && $payload['object'] === 'instagram') {
            return self::PLATFORM_INSTAGRAM;
        }

        foreach ($payload['entry'] as $entry) {
            // Check for Instagram-specific structure
            if (isset($entry['messaging']) && is_array($entry['messaging'])) {
                foreach ($entry['messaging'] as $message) {
                    // Instagram messages have specific indicators
                    if (isset($message['message']['is_echo']) && isset($message['sender']['id'])) {
                        // Check if it's an Instagram-scoped ID (typically longer)
                        $senderId = (string)$message['sender']['id'];
                        if ($this->isInstagramId($senderId)) {
                            return self::PLATFORM_INSTAGRAM;
                        }
                    }
                }
            }

            // Check for explicit Instagram field (some webhook versions)
            if (isset($entry['instagram'])) {
                return self::PLATFORM_INSTAGRAM;
            }
        }

        // Default to Messenger if no Instagram indicators found
        return self::PLATFORM_MESSENGER;
    }

    /**
     * Detect platform from recipient ID format.
     *
     * Instagram-Scoped IDs (IGSID) are typically longer numeric strings
     * Page-Scoped IDs (PSID) for Messenger are shorter
     */
    public function detectFromId(string $id): string
    {
        if ($this->isInstagramId($id)) {
            return self::PLATFORM_INSTAGRAM;
        }

        return self::PLATFORM_MESSENGER;
    }

    /**
     * Check if an ID is an Instagram-Scoped ID.
     *
     * IGSIDs are typically 15+ digit numeric strings
     * PSIDs are typically shorter (10-14 digits)
     */
    private function isInstagramId(string $id): bool
    {
        // Must be numeric
        if (!is_numeric($id)) {
            return false;
        }

        // Instagram IDs are typically 15+ digits
        // This is a heuristic and may need adjustment based on real data
        return strlen($id) >= 15;
    }

    /**
     * Get platform-specific limits for media and messages.
     */
    public function getPlatformLimits(string $platform): array
    {
        if ($platform === self::PLATFORM_INSTAGRAM) {
            return [
                'max_images_per_message' => 10,
                'max_image_size' => 8 * 1024 * 1024,      // 8MB
                'max_video_size' => 25 * 1024 * 1024,     // 25MB
                'max_audio_size' => 25 * 1024 * 1024,     // 25MB
                'max_file_size' => 25 * 1024 * 1024,      // 25MB
                'max_quick_replies' => 13,
                'messaging_window_hours' => 24,
                'supports_button_template' => false,
                'supports_generic_template' => true,
            ];
        }

        // Messenger limits
        return [
            'max_images_per_message' => 1,                // Standard, or use carousel
            'max_image_size' => 25 * 1024 * 1024,         // 25MB
            'max_video_size' => 25 * 1024 * 1024,         // 25MB
            'max_audio_size' => 25 * 1024 * 1024,         // 25MB
            'max_file_size' => 25 * 1024 * 1024,          // 25MB
            'max_quick_replies' => 13,
            'messaging_window_hours' => 24,
            'supports_button_template' => true,
            'supports_generic_template' => true,
        ];
    }

    /**
     * Get the platform name constant.
     */
    public function getPlatformName(string $platform): string
    {
        return match ($platform) {
            self::PLATFORM_INSTAGRAM => 'Instagram',
            self::PLATFORM_MESSENGER => 'Facebook Messenger',
            default => 'Unknown Platform',
        };
    }

    /**
     * Validate if a platform identifier is supported.
     */
    public function isValidPlatform(string $platform): bool
    {
        return in_array($platform, [self::PLATFORM_INSTAGRAM, self::PLATFORM_MESSENGER], true);
    }
}
