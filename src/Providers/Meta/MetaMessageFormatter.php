<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers\Meta;

/**
 * Formats messages for Meta Messaging Platform API
 * 
 * Handles message formatting for both Instagram and Facebook Messenger
 */
class MetaMessageFormatter
{
    /**
     * Format a text message payload
     *
     * @param string $recipientId IGSID (Instagram) or PSID (Messenger)
     * @param string $text Message text
     * @return array<string, mixed> Formatted payload
     */
    public function formatTextMessage(string $recipientId, string $text): array
    {
        return [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'text' => $text
            ]
        ];
    }

    /**
     * Format a media message payload
     *
     * @param string $recipientId IGSID (Instagram) or PSID (Messenger)
     * @param string $type Media type (image, video, audio, file)
     * @param string $url Media URL
     * @return array<string, mixed> Formatted payload
     */
    public function formatMediaMessage(string $recipientId, string $type, string $url): array
    {
        return [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachment' => [
                    'type' => $type,
                    'payload' => [
                        'url' => $url,
                        'is_reusable' => true
                    ]
                ]
            ]
        ];
    }

    /**
     * Format a message with multiple images
     *
     * @param string $recipientId IGSID (Instagram) or PSID (Messenger)
     * @param array<string> $urls Array of image URLs
     * @return array<string, mixed> Formatted payload
     */
    public function formatMultipleImages(string $recipientId, array $urls): array
    {
        $attachments = array_map(function ($url) {
            return [
                'type' => 'image',
                'payload' => [
                    'url' => $url,
                    'is_reusable' => true
                ]
            ];
        }, $urls);

        return [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachments' => $attachments
            ]
        ];
    }

    /**
     * Format a message with quick replies
     *
     * @param string $recipientId IGSID (Instagram) or PSID (Messenger)
     * @param string $text Message text
     * @param array<array<string, string>> $buttons Array of buttons with 'title' and 'id'
     * @return array<string, mixed> Formatted payload
     */
    public function formatQuickReplies(string $recipientId, string $text, array $buttons): array
    {
        $quickReplies = array_map(function ($button) {
            return [
                'content_type' => 'text',
                'title' => $button['title'],
                'payload' => $button['id']
            ];
        }, $buttons);

        return [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'text' => $text,
                'quick_replies' => $quickReplies
            ]
        ];
    }

    /**
     * Format a generic template message (carousel)
     *
     * @param string $recipientId IGSID (Instagram) or PSID (Messenger)
     * @param array<array<string, mixed>> $elements Array of card elements
     * @return array<string, mixed> Formatted payload
     */
    public function formatGenericTemplate(string $recipientId, array $elements): array
    {
        return [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'generic',
                        'elements' => $elements
                    ]
                ]
            ]
        ];
    }

    /**
     * Format a button template message (Messenger only)
     *
     * @param string $recipientId PSID (Messenger)
     * @param string $text Message text
     * @param array<array<string, mixed>> $buttons Array of button objects
     * @return array<string, mixed> Formatted payload
     */
    public function formatButtonTemplate(string $recipientId, string $text, array $buttons): array
    {
        return [
            'recipient' => [
                'id' => $recipientId
            ],
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => $text,
                        'buttons' => $buttons
                    ]
                ]
            ]
        ];
    }

    /**
     * Convert HSM template to plain text by substituting placeholders
     *
     * @param string $templateText Template text with {{1}}, {{2}}, etc.
     * @param array<string> $parameters Array of parameter values
     * @return string Text with placeholders replaced
     */
    public function convertTemplateToText(string $templateText, array $parameters): string
    {
        $text = $templateText;
        
        foreach ($parameters as $index => $value) {
            $placeholder = '{{' . ($index + 1) . '}}';
            $text = str_replace($placeholder, $value, $text);
        }

        return $text;
    }

    /**
     * Validate recipient ID format
     *
     * @param string $recipientId IGSID or PSID
     * @return bool True if valid
     */
    public function validateRecipientId(string $recipientId): bool
    {
        // Both IGSID and PSID are numeric strings
        return !empty($recipientId) && is_numeric($recipientId);
    }

    /**
     * Validate media type
     *
     * @param string $type Media type
     * @return bool True if valid
     */
    public function validateMediaType(string $type): bool
    {
        $validTypes = ['image', 'video', 'audio', 'file'];
        return in_array($type, $validTypes, true);
    }
}
