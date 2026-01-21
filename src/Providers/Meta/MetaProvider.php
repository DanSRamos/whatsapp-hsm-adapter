<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers\Meta;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Models\IncomingMessage;
use WhatsApp\Adapter\Models\Requests\HSMRequest;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Models\Requests\MediaRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;
use WhatsApp\Adapter\Providers\Models\DeliveryReport;
use WhatsApp\Adapter\Providers\Models\ProviderMessageStatus;
use WhatsApp\Adapter\Providers\Models\ProviderSendResult;
use WhatsApp\Adapter\Providers\Models\ProviderTemplate;
use WhatsApp\Adapter\Providers\Models\TemplateUpdate;
use WhatsApp\Adapter\Providers\MessagingProviderInterface;

/**
 * Meta Provider for Instagram Messaging API and Facebook Messenger API
 * 
 * This provider implements the MessagingProviderInterface for Meta's Messenger Platform,
 * which powers both Instagram and Facebook Messenger messaging capabilities.
 * The platform is automatically detected based on the recipient ID format.
 */
class MetaProvider implements MessagingProviderInterface
{
    private const API_VERSION = 'v21.0';
    private const BASE_URL = 'https://graph.facebook.com';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly array $config,
        private readonly LoggerInterface $logger,
        private readonly ?\WhatsApp\Adapter\Repositories\MessageRepositoryInterface $messageRepository = null,
        private readonly ?\WhatsApp\Adapter\Services\CacheInterface $cache = null
    ) {
        $this->validateConfig();
    }

    /**
     * Get the provider name
     * 
     * @return string Returns 'meta' as the provider identifier
     */
    public function getName(): string
    {
        return 'meta';
    }

    /**
     * Send a template/HSM message
     * 
     * Note: Instagram and Messenger don't support HSM templates like WhatsApp.
     * This method will convert the template to plain text by substituting placeholders.
     * 
     * @param HSMRequest $request The HSM message request
     * @return ProviderSendResult The result of the send operation
     */
    public function sendTemplate(HSMRequest $request): ProviderSendResult
    {
        $this->logger->warning('Templates not natively supported for Meta provider (Instagram/Messenger)', [
            'template_name' => $request->templateName,
            'template_language' => $request->templateLanguage,
            'to' => $request->to,
            'action' => 'converting_to_text'
        ]);

        // Since Meta doesn't support HSM templates, we need to convert to plain text
        // The template text should be provided in the parameters or we need to fetch it
        // For now, we'll create a simple text representation
        
        // Check if we have a template body in the parameters
        // Common pattern: first parameter is the template body with placeholders
        if (empty($request->parameters)) {
            $errorMessage = sprintf(
                'Cannot send template "%s" via Meta provider: No parameters provided for text conversion. ' .
                'Meta (Instagram/Messenger) does not support HSM templates. ' .
                'Please provide template text with placeholders in parameters or use sendText() instead.',
                $request->templateName
            );
            
            $this->logger->error('Template conversion failed - no parameters', [
                'template_name' => $request->templateName,
                'to' => $request->to
            ]);

            return new ProviderSendResult(
                success: false,
                error: $errorMessage,
                details: [
                    'template_name' => $request->templateName,
                    'reason' => 'templates_not_supported',
                    'suggestion' => 'Use sendText() method instead or provide template text in parameters'
                ]
            );
        }

        // Try to build text from parameters
        // If first parameter looks like a template (contains {{}}), use it as template text
        $templateText = $request->parameters[0] ?? '';
        $substitutionParams = array_slice($request->parameters, 1);

        // Check if the first parameter contains placeholders
        if (strpos($templateText, '{{') !== false && strpos($templateText, '}}') !== false) {
            // First parameter is the template text, rest are substitution values
            $formatter = new MetaMessageFormatter();
            $convertedText = $formatter->convertTemplateToText($templateText, $substitutionParams);
            
            $this->logger->info('Template converted to plain text', [
                'template_name' => $request->templateName,
                'original_text' => $templateText,
                'converted_text' => $convertedText,
                'parameters_used' => count($substitutionParams)
            ]);
        } else {
            // No placeholders found, treat all parameters as text parts to concatenate
            // or just use the first parameter as-is
            $convertedText = implode(' ', $request->parameters);
            
            $this->logger->info('Template parameters concatenated to text', [
                'template_name' => $request->templateName,
                'converted_text' => $convertedText,
                'parameters_used' => count($request->parameters)
            ]);
        }

        // Validate that we have non-empty text
        if (empty(trim($convertedText))) {
            $errorMessage = sprintf(
                'Cannot send template "%s" via Meta provider: Converted text is empty. ' .
                'Meta (Instagram/Messenger) does not support HSM templates.',
                $request->templateName
            );
            
            $this->logger->error('Template conversion resulted in empty text', [
                'template_name' => $request->templateName,
                'to' => $request->to,
                'parameters' => $request->parameters
            ]);

            return new ProviderSendResult(
                success: false,
                error: $errorMessage,
                details: [
                    'template_name' => $request->templateName,
                    'reason' => 'empty_converted_text'
                ]
            );
        }

        // Send as plain text message
        $textRequest = new TextRequest(
            to: $request->to,
            text: $convertedText,
            notifyUrl: $request->notifyUrl
        );

        $result = $this->sendText($textRequest);

        // Add template metadata to the result
        if ($result->success) {
            $this->logger->info('Template successfully sent as text via Meta', [
                'template_name' => $request->templateName,
                'message_id' => $result->messageId,
                'to' => $request->to
            ]);

            // Enhance result details with template info
            $enhancedDetails = array_merge($result->details ?? [], [
                'template_name' => $request->templateName,
                'template_language' => $request->templateLanguage,
                'converted_from_template' => true,
                'original_parameters' => $request->parameters
            ]);

            return new ProviderSendResult(
                success: true,
                messageId: $result->messageId,
                status: $result->status,
                details: $enhancedDetails
            );
        }

        return $result;
    }

    /**
     * Send a free-text message
     * 
     * @param TextRequest $request The text message request
     * @return ProviderSendResult The result of the send operation
     */
    public function sendText(TextRequest $request): ProviderSendResult
    {
        $this->logger->info('Sending text message via Meta', [
            'to' => $request->to,
            'preview_url' => $request->previewUrl
        ]);

        // Build the message payload
        $payload = [
            'recipient' => [
                'id' => $request->to
            ],
            'message' => [
                'text' => $request->text
            ]
        ];

        // Add URL preview support if enabled
        if ($request->previewUrl) {
            $payload['message']['preview_url'] = true;
        }

        return $this->sendRequest('POST', '/messages', $payload);
    }

    /**
     * Send media (image, document, audio, video)
     * 
     * @param MediaRequest $request The media message request
     * @return ProviderSendResult The result of the send operation
     */
    public function sendMedia(MediaRequest $request): ProviderSendResult
    {
        $this->logger->info('Sending media message via Meta', [
            'to' => $request->to,
            'media_type' => $request->mediaType,
            'media_url' => $request->mediaUrl
        ]);

        // Validate media URL format
        $this->validateMediaUrl($request->mediaUrl);

        // Detect platform for validation
        $platform = $this->detectPlatform($request->to);
        
        // Validate media format
        $this->validateMediaFormat($request->mediaType, $request->mediaUrl);

        // Map media types to Meta API types
        $metaType = $this->mapMediaType($request->mediaType);

        // Build the message payload
        $payload = [
            'recipient' => [
                'id' => $request->to
            ],
            'message' => [
                'attachment' => [
                    'type' => $metaType,
                    'payload' => [
                        'url' => $request->mediaUrl,
                        'is_reusable' => true
                    ]
                ]
            ]
        ];

        return $this->sendRequest('POST', '/messages', $payload);
    }

    /**
     * Send multiple images in a single message
     * 
     * Note: Instagram supports up to 10 images per message.
     * Messenger typically supports 1 image (or use carousel for multiple).
     * 
     * @param string $to Recipient ID (IGSID or PSID)
     * @param array<string> $imageUrls Array of image URLs
     * @return ProviderSendResult The result of the send operation
     */
    public function sendMultipleImages(string $to, array $imageUrls): ProviderSendResult
    {
        // Detect platform to apply appropriate limits
        $platform = $this->detectPlatform($to);
        $limits = $this->getPlatformLimits($platform);
        $maxImages = $limits['max_images_per_message'];

        // Validate number of images
        if (empty($imageUrls)) {
            throw new \InvalidArgumentException('At least one image URL is required');
        }

        if (count($imageUrls) > $maxImages) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Too many images. Maximum %d images allowed for %s',
                    $maxImages,
                    $platform === 'instagram' ? 'Instagram' : 'Messenger'
                )
            );
        }

        // Validate each image URL
        foreach ($imageUrls as $index => $url) {
            try {
                $this->validateMediaUrl($url);
                $this->validateMediaFormat('image', $url);
            } catch (\InvalidArgumentException $e) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid image URL at index %d: %s', $index, $e->getMessage())
                );
            }
        }

        $this->logger->info('Sending multiple images via Meta', [
            'to' => $to,
            'platform' => $platform,
            'image_count' => count($imageUrls)
        ]);

        // Build attachments array
        $attachments = array_map(function($url) {
            return [
                'type' => 'image',
                'payload' => [
                    'url' => $url,
                    'is_reusable' => true
                ]
            ];
        }, $imageUrls);

        // Build the message payload
        $payload = [
            'recipient' => [
                'id' => $to
            ],
            'message' => [
                'attachment' => count($attachments) === 1 ? $attachments[0] : null,
                'attachments' => count($attachments) > 1 ? $attachments : null
            ]
        ];

        // Remove null fields
        $payload['message'] = array_filter($payload['message'], fn($value) => $value !== null);

        return $this->sendRequest('POST', '/messages', $payload);
    }

    /**
     * Detect platform from recipient ID
     * 
     * @param string $recipientId The recipient ID (IGSID or PSID)
     * @return string Platform identifier ('instagram' or 'messenger')
     */
    private function detectPlatform(string $recipientId): string
    {
        // Instagram-Scoped IDs (IGSID) are typically 15+ digits
        // Page-Scoped IDs (PSID) for Messenger are typically shorter
        if (is_numeric($recipientId) && strlen($recipientId) >= 15) {
            return 'instagram';
        }
        
        return 'messenger';
    }

    /**
     * Get platform-specific limits
     * 
     * @param string $platform Platform identifier ('instagram' or 'messenger')
     * @return array Platform limits
     */
    private function getPlatformLimits(string $platform): array
    {
        if ($platform === 'instagram') {
            return [
                'max_images_per_message' => 10,
                'max_image_size' => 8 * 1024 * 1024,      // 8MB
                'max_video_size' => 25 * 1024 * 1024,     // 25MB
                'max_audio_size' => 25 * 1024 * 1024,     // 25MB
                'max_file_size' => 25 * 1024 * 1024,      // 25MB
            ];
        }

        // Messenger limits
        return [
            'max_images_per_message' => 1,
            'max_image_size' => 25 * 1024 * 1024,         // 25MB
            'max_video_size' => 25 * 1024 * 1024,         // 25MB
            'max_audio_size' => 25 * 1024 * 1024,         // 25MB
            'max_file_size' => 25 * 1024 * 1024,          // 25MB
        ];
    }

    /**
     * Validate media URL format
     * 
     * @param string $url The media URL to validate
     * @throws \InvalidArgumentException If URL is invalid
     */
    private function validateMediaUrl(string $url): void
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Media URL cannot be empty');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException(
                'Invalid media URL format. Must be a valid URL.'
            );
        }

        // Meta API requires HTTPS URLs
        if (!str_starts_with($url, 'https://')) {
            throw new \InvalidArgumentException(
                'Media URL must use HTTPS protocol. HTTP URLs are not supported by Meta API.'
            );
        }
    }

    /**
     * Validate media format based on type
     * 
     * @param string $mediaType The media type (image, video, audio, document)
     * @param string $url The media URL
     * @throws \InvalidArgumentException If format is not supported
     */
    private function validateMediaFormat(string $mediaType, string $url): void
    {
        // Extract file extension from URL
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (empty($extension)) {
            // If no extension in URL, log warning but don't fail
            // Meta API will validate the actual content
            $this->logger->warning('Media URL has no file extension', [
                'url' => $url,
                'media_type' => $mediaType
            ]);
            return;
        }

        // Define supported formats per media type
        $supportedFormats = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'mov', 'avi', 'webm', 'ogg'],
            'audio' => ['mp3', 'aac', 'm4a', 'wav', 'ogg'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']
        ];

        $validFormats = $supportedFormats[$mediaType] ?? [];

        if (!in_array($extension, $validFormats, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported %s format: .%s. Supported formats: %s',
                    $mediaType,
                    $extension,
                    implode(', ', array_map(fn($f) => ".$f", $validFormats))
                )
            );
        }
    }

    /**
     * Validate media size
     * 
     * Note: This is a helper method for external validation.
     * The actual file size validation happens on Meta's servers.
     * 
     * @param string $mediaType The media type
     * @param int $sizeInBytes The file size in bytes
     * @param string $platform The platform (instagram or messenger)
     * @throws \InvalidArgumentException If size exceeds limits
     */
    public function validateMediaSize(string $mediaType, int $sizeInBytes, string $platform = 'instagram'): void
    {
        $limits = $this->getPlatformLimits($platform);
        
        $maxSize = match($mediaType) {
            'image' => $limits['max_image_size'],
            'video' => $limits['max_video_size'],
            'audio' => $limits['max_audio_size'],
            'document' => $limits['max_file_size'],
            default => throw new \InvalidArgumentException("Unknown media type: {$mediaType}")
        };

        if ($sizeInBytes > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);
            $actualSizeMB = round($sizeInBytes / (1024 * 1024), 2);
            
            throw new \InvalidArgumentException(
                sprintf(
                    'Media file size (%s MB) exceeds the maximum allowed size for %s on %s (%s MB)',
                    $actualSizeMB,
                    $mediaType,
                    $platform === 'instagram' ? 'Instagram' : 'Messenger',
                    $maxSizeMB
                )
            );
        }
    }

    /**
     * Map MediaRequest media type to Meta API attachment type
     * 
     * @param string $mediaType The media type from MediaRequest
     * @return string The Meta API attachment type
     */
    private function mapMediaType(string $mediaType): string
    {
        return match($mediaType) {
            'image' => 'image',
            'video' => 'video',
            'audio' => 'audio',
            'document' => 'file',
            default => throw new \InvalidArgumentException("Unsupported media type: {$mediaType}")
        };
    }

    /**
     * Send interactive message with buttons (Quick Replies)
     * 
     * @param InteractiveButtonsRequest $request The interactive buttons request
     * @return ProviderSendResult The result of the send operation
     */
    public function sendInteractiveButtons(InteractiveButtonsRequest $request): ProviderSendResult
    {
        $this->logger->info('Sending interactive buttons (quick replies) via Meta', [
            'to' => $request->to,
            'button_count' => count($request->buttons)
        ]);

        // Validate maximum quick replies (Meta supports up to 13)
        $maxQuickReplies = 13;
        if (count($request->buttons) > $maxQuickReplies) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Too many quick replies. Maximum %d quick replies allowed, %d provided',
                    $maxQuickReplies,
                    count($request->buttons)
                )
            );
        }

        // Map buttons to quick_reply format
        $quickReplies = array_map(function($button) {
            // Validate title length (Meta limit is 20 characters)
            $title = $button['text'] ?? $button['title'] ?? '';
            if (strlen($title) > 20) {
                $this->logger->warning('Quick reply title exceeds 20 characters, truncating', [
                    'original_title' => $title,
                    'truncated_title' => substr($title, 0, 20)
                ]);
                $title = substr($title, 0, 20);
            }

            return [
                'content_type' => 'text',
                'title' => $title,
                'payload' => $button['id']
            ];
        }, $request->buttons);

        // Build the message payload
        $payload = [
            'recipient' => [
                'id' => $request->to
            ],
            'message' => [
                'text' => $request->bodyText,
                'quick_replies' => $quickReplies
            ]
        ];

        return $this->sendRequest('POST', '/messages', $payload);
    }

    /**
     * Send interactive message with list (Generic Template)
     * 
     * @param InteractiveListRequest $request The interactive list request
     * @return ProviderSendResult The result of the send operation
     */
    public function sendInteractiveList(InteractiveListRequest $request): ProviderSendResult
    {
        $this->logger->info('Sending interactive list (generic template) via Meta', [
            'to' => $request->to,
            'section_count' => count($request->sections)
        ]);

        // Build elements array from sections
        $elements = [];
        
        foreach ($request->sections as $section) {
            foreach ($section['items'] as $item) {
                $element = [
                    'title' => $item['title']
                ];

                // Add subtitle/description if provided
                if (!empty($item['description'])) {
                    $element['subtitle'] = $item['description'];
                }

                // Add image URL if provided
                if (!empty($item['image_url'])) {
                    $element['image_url'] = $item['image_url'];
                }

                // Add buttons if provided
                if (!empty($item['buttons'])) {
                    $buttons = [];
                    
                    // Validate maximum 3 buttons per card
                    $maxButtonsPerCard = 3;
                    $buttonCount = count($item['buttons']);
                    
                    if ($buttonCount > $maxButtonsPerCard) {
                        $this->logger->warning('Too many buttons per card, truncating', [
                            'item_title' => $item['title'],
                            'button_count' => $buttonCount,
                            'max_buttons' => $maxButtonsPerCard
                        ]);
                        $item['buttons'] = array_slice($item['buttons'], 0, $maxButtonsPerCard);
                    }

                    foreach ($item['buttons'] as $button) {
                        $buttonData = [
                            'type' => $button['type'] ?? 'postback',
                            'title' => $button['text'] ?? $button['title'] ?? ''
                        ];

                        // Add button-specific fields based on type
                        if ($buttonData['type'] === 'web_url') {
                            $buttonData['url'] = $button['url'] ?? '';
                        } else {
                            // postback type
                            $buttonData['payload'] = $button['id'] ?? $button['payload'] ?? '';
                        }

                        $buttons[] = $buttonData;
                    }

                    $element['buttons'] = $buttons;
                }

                // Add default action if provided (makes entire card clickable)
                if (!empty($item['url'])) {
                    $element['default_action'] = [
                        'type' => 'web_url',
                        'url' => $item['url']
                    ];
                }

                $elements[] = $element;
            }
        }

        // Validate maximum 10 elements (Meta limit for generic template)
        $maxElements = 10;
        if (count($elements) > $maxElements) {
            $this->logger->warning('Too many elements in generic template, truncating', [
                'element_count' => count($elements),
                'max_elements' => $maxElements
            ]);
            $elements = array_slice($elements, 0, $maxElements);
        }

        // Validate title length (80 characters max)
        foreach ($elements as &$element) {
            if (strlen($element['title']) > 80) {
                $this->logger->warning('Element title exceeds 80 characters, truncating', [
                    'original_title' => $element['title']
                ]);
                $element['title'] = substr($element['title'], 0, 80);
            }

            // Validate subtitle length (80 characters max)
            if (isset($element['subtitle']) && strlen($element['subtitle']) > 80) {
                $this->logger->warning('Element subtitle exceeds 80 characters, truncating', [
                    'original_subtitle' => $element['subtitle']
                ]);
                $element['subtitle'] = substr($element['subtitle'], 0, 80);
            }
        }

        // Build the message payload
        $payload = [
            'recipient' => [
                'id' => $request->to
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

        return $this->sendRequest('POST', '/messages', $payload);
    }

    /**
     * Send button template message (Messenger-specific)
     * 
     * Button template is primarily used for Messenger and allows up to 3 buttons
     * with a text message. This is different from quick replies which appear below
     * the message bubble.
     * 
     * @param string $to Recipient ID (PSID for Messenger)
     * @param string $text Message text (up to 640 characters)
     * @param array<array<string, mixed>> $buttons Array of button objects (max 3)
     * @return ProviderSendResult The result of the send operation
     */
    public function sendButtonTemplate(string $to, string $text, array $buttons): ProviderSendResult
    {
        // Detect platform - Button Template is Messenger-specific
        $platform = $this->detectPlatform($to);
        
        if ($platform === 'instagram') {
            $this->logger->warning('Button Template is Messenger-specific, using Quick Replies for Instagram', [
                'to' => $to,
                'platform' => $platform
            ]);
            
            // For Instagram, fall back to quick replies
            // Convert buttons to quick reply format
            $quickReplyButtons = array_map(function($button) {
                return [
                    'id' => $button['payload'] ?? $button['id'] ?? uniqid('btn_'),
                    'text' => $button['title'] ?? $button['text'] ?? ''
                ];
            }, $buttons);
            
            // Create an InteractiveButtonsRequest and use sendInteractiveButtons
            $request = new InteractiveButtonsRequest(
                to: $to,
                bodyText: $text,
                buttons: $quickReplyButtons
            );
            
            return $this->sendInteractiveButtons($request);
        }

        $this->logger->info('Sending button template via Meta (Messenger)', [
            'to' => $to,
            'button_count' => count($buttons)
        ]);

        // Validate maximum 3 buttons for button template
        $maxButtons = 3;
        if (count($buttons) > $maxButtons) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Too many buttons for button template. Maximum %d buttons allowed, %d provided',
                    $maxButtons,
                    count($buttons)
                )
            );
        }

        // Validate text length (640 characters max)
        if (strlen($text) > 640) {
            $this->logger->warning('Button template text exceeds 640 characters, truncating', [
                'original_length' => strlen($text)
            ]);
            $text = substr($text, 0, 640);
        }

        // Format buttons for button template
        $formattedButtons = [];
        foreach ($buttons as $button) {
            $buttonType = $button['type'] ?? 'postback';
            
            $buttonData = [
                'type' => $buttonType,
                'title' => $button['title'] ?? $button['text'] ?? ''
            ];

            // Validate title length (20 characters max)
            if (strlen($buttonData['title']) > 20) {
                $this->logger->warning('Button title exceeds 20 characters, truncating', [
                    'original_title' => $buttonData['title']
                ]);
                $buttonData['title'] = substr($buttonData['title'], 0, 20);
            }

            // Add type-specific fields
            switch ($buttonType) {
                case 'web_url':
                    $buttonData['url'] = $button['url'] ?? '';
                    // Optional: add webview_height_ratio
                    if (!empty($button['webview_height_ratio'])) {
                        $buttonData['webview_height_ratio'] = $button['webview_height_ratio'];
                    }
                    break;
                    
                case 'phone_number':
                    $buttonData['payload'] = $button['payload'] ?? $button['phone_number'] ?? '';
                    break;
                    
                case 'postback':
                default:
                    $buttonData['payload'] = $button['payload'] ?? $button['id'] ?? '';
                    break;
            }

            $formattedButtons[] = $buttonData;
        }

        // Build the message payload
        $payload = [
            'recipient' => [
                'id' => $to
            ],
            'message' => [
                'attachment' => [
                    'type' => 'template',
                    'payload' => [
                        'template_type' => 'button',
                        'text' => $text,
                        'buttons' => $formattedButtons
                    ]
                ]
            ]
        ];

        return $this->sendRequest('POST', '/messages', $payload);
    }

    /**
     * Query the status of a message
     * 
     * Note: Meta doesn't provide a direct endpoint to query message status.
     * Status is tracked via webhooks and stored in the local repository.
     * This method queries the local repository for the last known status.
     * 
     * Caching Strategy:
     * - First checks cache for recent status (TTL: 5 minutes)
     * - Falls back to repository if cache miss
     * - Caches the result for subsequent queries
     * 
     * Timeout Handling:
     * - If message is older than timeout threshold and still in SENT status, returns UNKNOWN
     * - Default timeout: 24 hours (configurable via config)
     * - Logs unknown status for monitoring
     * 
     * @param string $messageId The message ID to query
     * @return ProviderMessageStatus The message status
     * @throws \RuntimeException If message repository is not configured
     * @throws \InvalidArgumentException If message is not found
     */
    public function getMessageStatus(string $messageId): ProviderMessageStatus
    {
        $this->logger->info('Querying message status', [
            'message_id' => $messageId,
            'provider' => 'meta',
            'cache_enabled' => $this->cache !== null,
            'note' => 'Meta API does not provide direct status query endpoint'
        ]);

        // Try to get from cache first
        if ($this->cache !== null) {
            $cacheKey = $this->getStatusCacheKey($messageId);
            
            if ($this->cache->has($cacheKey)) {
                $cachedStatus = $this->cache->get($cacheKey);
                
                if ($cachedStatus instanceof ProviderMessageStatus) {
                    $this->logger->debug('Message status retrieved from cache', [
                        'message_id' => $messageId,
                        'status' => $cachedStatus->status
                    ]);
                    return $cachedStatus;
                }
            }
        }

        // Check if repository is configured
        if ($this->messageRepository === null) {
            $errorMessage = 'Message repository not configured. Cannot query message status.';
            $this->logger->error($errorMessage, [
                'message_id' => $messageId
            ]);
            throw new \RuntimeException($errorMessage);
        }

        // Query message from repository
        $message = $this->messageRepository->findById($messageId);

        if ($message === null) {
            $errorMessage = sprintf('Message not found: %s', $messageId);
            $this->logger->warning($errorMessage, [
                'message_id' => $messageId,
                'provider' => 'meta'
            ]);
            throw new \InvalidArgumentException($errorMessage);
        }

        // Check for timeout - if message is old and still in SENT status, mark as UNKNOWN
        $status = $message->status;
        $now = new \DateTimeImmutable();
        $messageAge = $now->getTimestamp() - $message->sentAt->getTimestamp();
        $timeoutThreshold = $this->getStatusTimeoutThreshold();

        if ($status === 'SENT' && $messageAge > $timeoutThreshold) {
            $this->logger->warning('Message status timeout - no delivery confirmation received', [
                'message_id' => $messageId,
                'status' => $status,
                'sent_at' => $message->sentAt->format('Y-m-d H:i:s'),
                'age_seconds' => $messageAge,
                'timeout_threshold' => $timeoutThreshold,
                'age_hours' => round($messageAge / 3600, 2)
            ]);

            // Override status to UNKNOWN
            $status = 'UNKNOWN';
        }

        $this->logger->info('Message status retrieved from repository', [
            'message_id' => $messageId,
            'status' => $status,
            'original_status' => $message->status,
            'to' => $message->toNumber,
            'sent_at' => $message->sentAt->format('Y-m-d H:i:s'),
            'delivered_at' => $message->deliveredAt?->format('Y-m-d H:i:s'),
            'read_at' => $message->readAt?->format('Y-m-d H:i:s'),
            'age_seconds' => $messageAge
        ]);

        // Map Message to ProviderMessageStatus
        $providerStatus = new ProviderMessageStatus(
            messageId: $message->id,
            status: $status,
            to: $message->toNumber,
            sentAt: $message->sentAt,
            deliveredAt: $message->deliveredAt,
            readAt: $message->readAt,
            error: $status === 'UNKNOWN' 
                ? 'No delivery confirmation received within timeout period' 
                : $message->errorMessage
        );

        // Cache the status for future queries
        if ($this->cache !== null) {
            $cacheKey = $this->getStatusCacheKey($messageId);
            $cacheTtl = $this->getStatusCacheTtl($status);
            
            $this->cache->set($cacheKey, $providerStatus, $cacheTtl);
            
            $this->logger->debug('Message status cached', [
                'message_id' => $messageId,
                'cache_key' => $cacheKey,
                'ttl_seconds' => $cacheTtl
            ]);
        }

        return $providerStatus;
    }

    /**
     * Get cache key for message status
     * 
     * @param string $messageId The message ID
     * @return string The cache key
     */
    private function getStatusCacheKey(string $messageId): string
    {
        return "meta:message_status:{$messageId}";
    }

    /**
     * Get cache TTL based on message status
     * 
     * Different statuses have different cache durations:
     * - SENT: 2 minutes (status likely to change soon)
     * - DELIVERED: 3 minutes (might be read soon)
     * - READ: 10 minutes (final status, longer cache)
     * - FAILED/ERROR: 10 minutes (final status)
     * - UNKNOWN: 1 minute (short cache, might update soon)
     * 
     * @param string $status The message status
     * @return int TTL in seconds
     */
    private function getStatusCacheTtl(string $status): int
    {
        return match(strtoupper($status)) {
            'SENT' => 120,           // 2 minutes
            'DELIVERED' => 180,      // 3 minutes
            'READ' => 600,           // 10 minutes
            'FAILED', 'ERROR' => 600, // 10 minutes
            'UNKNOWN' => 60,         // 1 minute
            default => 300           // 5 minutes (default)
        };
    }

    /**
     * Get timeout threshold for message status
     * 
     * Messages that remain in SENT status longer than this threshold
     * will be marked as UNKNOWN status.
     * 
     * Default: 24 hours (86400 seconds)
     * Configurable via config['status_timeout_seconds']
     * 
     * @return int Timeout threshold in seconds
     */
    private function getStatusTimeoutThreshold(): int
    {
        return $this->config['status_timeout_seconds'] ?? 86400; // 24 hours default
    }

    /**
     * Retrieve all available templates
     * 
     * Note: Instagram and Messenger don't support templates like WhatsApp.
     * This method returns an empty array for interface compatibility.
     * 
     * @return array<ProviderTemplate> Empty array
     */
    public function getTemplates(): array
    {
        $this->logger->info('Templates not supported for Meta provider (Instagram/Messenger)');
        return [];
    }

    /**
     * Retrieve a specific template by ID
     * 
     * Note: Instagram and Messenger don't support templates like WhatsApp.
     * This method returns null for interface compatibility.
     * 
     * @param string $templateId The template ID
     * @return ProviderTemplate|null Always returns null
     */
    public function getTemplate(string $templateId): ?ProviderTemplate
    {
        $this->logger->info('Templates not supported for Meta provider (Instagram/Messenger)', [
            'template_id' => $templateId
        ]);
        return null;
    }

    /**
     * Validate webhook received from Meta
     * 
     * Validates webhook signature using HMAC SHA-256 with App Secret.
     * Also handles GET verification requests for initial webhook setup.
     * 
     * @param ServerRequestInterface $request The HTTP request
     * @return bool True if webhook is valid, false otherwise
     */
    public function validateWebhook(ServerRequestInterface $request): bool
    {
        $method = $request->getMethod();

        // Handle GET request for webhook verification (initial setup)
        if ($method === 'GET') {
            return $this->handleWebhookVerification($request);
        }

        // Handle POST request - validate signature
        if ($method === 'POST') {
            $handler = new MetaWebhookHandler($this->logger);
            $appSecret = $this->config['app_secret'];
            
            return $handler->validateSignature($request, $appSecret);
        }

        // Other methods not supported
        $this->logger->warning('Unsupported HTTP method for webhook', [
            'method' => $method
        ]);
        
        return false;
    }

    /**
     * Handle webhook verification (GET request)
     * 
     * Meta sends a GET request with hub.mode, hub.verify_token, and hub.challenge
     * to verify the webhook endpoint during setup.
     * 
     * @param ServerRequestInterface $request The HTTP request
     * @return bool True if verification is successful
     */
    private function handleWebhookVerification(ServerRequestInterface $request): bool
    {
        $params = $request->getQueryParams();
        $verifyToken = $this->config['verify_token'] ?? '';

        if (empty($verifyToken)) {
            $this->logger->error('Verify token not configured for Meta webhook verification');
            return false;
        }

        $handler = new MetaWebhookHandler($this->logger);
        $challenge = $handler->handleVerification($params, $verifyToken);

        // If verification successful, the challenge will be returned
        // The controller should send this back to Meta
        if ($challenge !== null) {
            // Store challenge in request attribute so controller can access it
            // This is a workaround since we can't modify the response here
            $request = $request->withAttribute('webhook_challenge', $challenge);
            return true;
        }

        return false;
    }

    /**
     * Process delivery report webhook
     * 
     * Processes delivery and read receipts from Meta webhooks.
     * Supports both Instagram and Facebook Messenger platforms.
     * 
     * Meta sends delivery reports in two types:
     * - delivery: Message was delivered to the recipient
     * - read: Message was read by the recipient
     * 
     * @param array $payload The webhook payload
     * @return DeliveryReport The parsed delivery report
     * @throws \InvalidArgumentException If payload is invalid or missing required fields
     */
    public function processDeliveryReport(array $payload): DeliveryReport
    {
        $this->logger->debug('Processing delivery report from Meta webhook', [
            'payload_keys' => array_keys($payload)
        ]);

        // Extract delivery reports using webhook handler
        $handler = new MetaWebhookHandler($this->logger);
        $reports = $handler->extractDeliveryReports($payload);

        if (empty($reports)) {
            throw new \InvalidArgumentException('No delivery reports found in webhook payload');
        }

        // Process the first report (webhooks typically contain one report per event)
        $messagingEvent = $reports[0];

        // Extract sender ID (IGSID for Instagram, PSID for Messenger)
        $senderId = $messagingEvent['sender']['id'] ?? null;
        if ($senderId === null) {
            throw new \InvalidArgumentException('Missing sender ID in delivery report');
        }

        // Extract recipient ID (Page ID)
        $recipientId = $messagingEvent['recipient']['id'] ?? null;
        if ($recipientId === null) {
            throw new \InvalidArgumentException('Missing recipient ID in delivery report');
        }

        // Detect platform automatically
        $platform = $this->detectPlatform($senderId);

        // Determine if this is a delivery or read event
        $isReadEvent = isset($messagingEvent['read']);
        $isDeliveryEvent = isset($messagingEvent['delivery']);

        if (!$isReadEvent && !$isDeliveryEvent) {
            throw new \InvalidArgumentException('Webhook payload is neither a delivery nor read event');
        }

        // Extract the appropriate event data
        $eventData = $isReadEvent ? $messagingEvent['read'] : $messagingEvent['delivery'];

        // Extract timestamp
        $timestamp = $messagingEvent['timestamp'] ?? null;
        if ($timestamp === null) {
            throw new \InvalidArgumentException('Missing timestamp in delivery report');
        }

        // Convert timestamp (milliseconds) to DateTimeImmutable
        $timestampSeconds = (int)($timestamp / 1000);
        $reportTimestamp = \DateTimeImmutable::createFromFormat('U', (string)$timestampSeconds);
        if ($reportTimestamp === false) {
            $reportTimestamp = new \DateTimeImmutable();
        }

        // Extract message IDs
        // For delivery events, Meta provides an array of message IDs (mids)
        // For read events, Meta provides watermark (timestamp) and may include mids
        $messageIds = $eventData['mids'] ?? [];

        // If no message IDs provided, we can't process this report
        if (empty($messageIds)) {
            $this->logger->warning('Delivery report contains no message IDs', [
                'event_type' => $isReadEvent ? 'read' : 'delivery',
                'sender_id' => $senderId,
                'watermark' => $eventData['watermark'] ?? null
            ]);

            throw new \InvalidArgumentException('No message IDs found in delivery report');
        }

        // Process the first message ID (most common case)
        // If multiple IDs exist, we'll log them but only return one DeliveryReport
        $messageId = $messageIds[0];

        if (count($messageIds) > 1) {
            $this->logger->info('Delivery report contains multiple message IDs', [
                'message_ids' => $messageIds,
                'processing_first' => $messageId,
                'event_type' => $isReadEvent ? 'read' : 'delivery'
            ]);
        }

        // Determine status based on event type
        $status = $isReadEvent ? 'read' : 'delivered';

        // Extract watermark (timestamp up to which all messages have been delivered/read)
        $watermark = $eventData['watermark'] ?? null;

        // Build metadata
        $metadata = [
            'provider' => 'meta',
            'platform' => $platform,
            'platform_name' => $platform === 'instagram' ? 'Instagram' : 'Facebook Messenger',
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'raw_timestamp' => $timestamp,
            'event_type' => $isReadEvent ? 'read' : 'delivery',
            'watermark' => $watermark,
            'all_message_ids' => $messageIds
        ];

        $this->logger->info('Delivery report processed successfully', [
            'message_id' => $messageId,
            'status' => $status,
            'platform' => $platform,
            'sender_id' => $senderId,
            'message_count' => count($messageIds)
        ]);

        return new DeliveryReport(
            messageId: $messageId,
            status: $status,
            timestamp: $reportTimestamp,
            error: null,
            metadata: $metadata
        );
    }

    /**
     * Process incoming message webhook
     * 
     * Extracts message data from Meta webhook payload and converts it to IncomingMessage model.
     * Supports both Instagram and Facebook Messenger platforms with automatic detection.
     * 
     * @param array $payload The webhook payload
     * @return IncomingMessage The parsed incoming message
     * @throws \InvalidArgumentException If payload is invalid or missing required fields
     */
    public function processIncomingMessage(array $payload): IncomingMessage
    {
        $this->logger->debug('Processing incoming message from Meta webhook', [
            'payload_keys' => array_keys($payload)
        ]);

        // Extract messaging events using webhook handler
        $handler = new MetaWebhookHandler($this->logger);
        $messages = $handler->extractMessages($payload);

        // Check if this is a postback event instead of a message
        $postbacks = $this->extractPostbacks($payload);
        
        if (empty($messages) && empty($postbacks)) {
            throw new \InvalidArgumentException('No messages or postbacks found in webhook payload');
        }

        // Process postback if present, otherwise process message
        if (!empty($postbacks)) {
            return $this->processPostbackEvent($postbacks[0]);
        }

        // Process the first message (webhooks typically contain one message per event)
        $messagingEvent = $messages[0];

        // Extract sender ID (IGSID for Instagram, PSID for Messenger)
        $senderId = $messagingEvent['sender']['id'] ?? null;
        if ($senderId === null) {
            throw new \InvalidArgumentException('Missing sender ID in webhook payload');
        }

        // Extract recipient ID (Page ID)
        $recipientId = $messagingEvent['recipient']['id'] ?? null;
        if ($recipientId === null) {
            throw new \InvalidArgumentException('Missing recipient ID in webhook payload');
        }

        // Detect platform automatically
        $platform = $this->detectPlatform($senderId);
        
        $this->logger->info('Detected platform from incoming message', [
            'platform' => $platform,
            'sender_id' => $senderId,
            'sender_id_length' => strlen($senderId)
        ]);

        // Extract message data
        $message = $messagingEvent['message'] ?? null;
        if ($message === null) {
            throw new \InvalidArgumentException('Missing message data in webhook payload');
        }

        // Extract message ID
        $messageId = $message['mid'] ?? null;
        if ($messageId === null) {
            throw new \InvalidArgumentException('Missing message ID in webhook payload');
        }

        // Extract timestamp
        $timestamp = $messagingEvent['timestamp'] ?? null;
        if ($timestamp === null) {
            throw new \InvalidArgumentException('Missing timestamp in webhook payload');
        }

        // Convert timestamp (milliseconds) to DateTimeImmutable
        $receivedAt = \DateTimeImmutable::createFromFormat('U', (string)($timestamp / 1000));
        if ($receivedAt === false) {
            $receivedAt = new \DateTimeImmutable();
        }

        // Determine message type and extract content
        $type = $this->determineMessageType($message);
        $content = $this->extractMessageContent($message, $type);

        // Extract context message ID (reply_to)
        $contextMessageId = $this->extractContextMessageId($message);

        // Include platform metadata
        $metadata = [
            'provider' => 'meta',
            'platform' => $platform,
            'platform_name' => $platform === 'instagram' ? 'Instagram' : 'Facebook Messenger',
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'raw_timestamp' => $timestamp
        ];

        // Add metadata to content if it's an array
        if (is_array($content)) {
            $content['metadata'] = $metadata;
        } else {
            // For non-array content, wrap it with metadata
            $content = [
                'value' => $content,
                'metadata' => $metadata
            ];
        }

        $this->logger->info('Incoming message processed successfully', [
            'message_id' => $messageId,
            'from' => $senderId,
            'to' => $recipientId,
            'type' => $type,
            'platform' => $platform,
            'has_context' => $contextMessageId !== null
        ]);

        return new IncomingMessage(
            messageId: $messageId,
            from: $senderId,
            to: $recipientId,
            type: $type,
            content: $content,
            receivedAt: $receivedAt,
            contextMessageId: $contextMessageId
        );
    }

    /**
     * Extract postback events from webhook payload
     *
     * @param array<string, mixed> $payload The webhook payload
     * @return array<array<string, mixed>> Array of postback data
     */
    private function extractPostbacks(array $payload): array
    {
        $postbacks = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['messaging'] ?? [] as $messagingEvent) {
                if (isset($messagingEvent['postback'])) {
                    $postbacks[] = $messagingEvent;
                }
            }
        }

        return $postbacks;
    }

    /**
     * Process postback event (button click)
     * 
     * @param array $messagingEvent The messaging event with postback
     * @return IncomingMessage The parsed postback as incoming message
     * @throws \InvalidArgumentException If postback data is invalid
     */
    private function processPostbackEvent(array $messagingEvent): IncomingMessage
    {
        // Extract sender ID (IGSID for Instagram, PSID for Messenger)
        $senderId = $messagingEvent['sender']['id'] ?? null;
        if ($senderId === null) {
            throw new \InvalidArgumentException('Missing sender ID in postback event');
        }

        // Extract recipient ID (Page ID)
        $recipientId = $messagingEvent['recipient']['id'] ?? null;
        if ($recipientId === null) {
            throw new \InvalidArgumentException('Missing recipient ID in postback event');
        }

        // Detect platform automatically
        $platform = $this->detectPlatform($senderId);

        // Extract postback data
        $postback = $messagingEvent['postback'] ?? null;
        if ($postback === null) {
            throw new \InvalidArgumentException('Missing postback data in event');
        }

        // Extract timestamp
        $timestamp = $messagingEvent['timestamp'] ?? null;
        if ($timestamp === null) {
            throw new \InvalidArgumentException('Missing timestamp in postback event');
        }

        // Convert timestamp (milliseconds) to DateTimeImmutable
        $receivedAt = \DateTimeImmutable::createFromFormat('U', (string)($timestamp / 1000));
        if ($receivedAt === false) {
            $receivedAt = new \DateTimeImmutable();
        }

        // Generate a message ID for the postback (Meta doesn't provide one for postbacks)
        $messageId = 'postback_' . $timestamp . '_' . substr(md5($senderId . $timestamp), 0, 8);

        // Extract postback payload and title
        $payload = $postback['payload'] ?? '';
        $title = $postback['title'] ?? '';

        // Extract referral data if present (for Get Started button or referral links)
        $referral = $postback['referral'] ?? null;

        // Build content
        $content = [
            'payload' => $payload,
            'title' => $title,
            'metadata' => [
                'provider' => 'meta',
                'platform' => $platform,
                'platform_name' => $platform === 'instagram' ? 'Instagram' : 'Facebook Messenger',
                'sender_id' => $senderId,
                'recipient_id' => $recipientId,
                'raw_timestamp' => $timestamp
            ]
        ];

        // Add referral data if present
        if ($referral !== null) {
            $content['referral'] = [
                'ref' => $referral['ref'] ?? null,
                'source' => $referral['source'] ?? null,
                'type' => $referral['type'] ?? null
            ];
        }

        $this->logger->info('Postback event processed successfully', [
            'message_id' => $messageId,
            'from' => $senderId,
            'to' => $recipientId,
            'payload' => $payload,
            'title' => $title,
            'platform' => $platform
        ]);

        return new IncomingMessage(
            messageId: $messageId,
            from: $senderId,
            to: $recipientId,
            type: 'postback',
            content: $content,
            receivedAt: $receivedAt,
            contextMessageId: null
        );
    }

    /**
     * Determine the type of incoming message
     * 
     * @param array $message The message data from webhook
     * @return string Message type (text, image, video, audio, file, quick_reply, postback)
     */
    private function determineMessageType(array $message): string
    {
        // Check for quick reply response
        if (isset($message['quick_reply'])) {
            return 'quick_reply';
        }

        // Check for attachments (media)
        if (isset($message['attachments']) && !empty($message['attachments'])) {
            $firstAttachment = $message['attachments'][0];
            $attachmentType = $firstAttachment['type'] ?? 'unknown';
            
            // Map Meta attachment types to our types
            return match($attachmentType) {
                'image' => 'image',
                'video' => 'video',
                'audio' => 'audio',
                'file' => 'file',
                default => 'attachment'
            };
        }

        // Check for text message
        if (isset($message['text'])) {
            return 'text';
        }

        // Unknown type
        $this->logger->warning('Unknown message type in webhook', [
            'message_keys' => array_keys($message)
        ]);
        
        return 'unknown';
    }

    /**
     * Extract message content based on type
     * 
     * @param array $message The message data from webhook
     * @param string $type The message type
     * @return mixed The extracted content
     */
    private function extractMessageContent(array $message, string $type): mixed
    {
        switch ($type) {
            case 'text':
                return [
                    'text' => $message['text'] ?? '',
                    'has_text' => isset($message['text'])
                ];

            case 'quick_reply':
                return [
                    'text' => $message['text'] ?? '',
                    'quick_reply' => [
                        'payload' => $message['quick_reply']['payload'] ?? ''
                    ]
                ];

            case 'image':
            case 'video':
            case 'audio':
            case 'file':
            case 'attachment':
                return $this->extractAttachmentContent($message);

            default:
                // Return raw message for unknown types
                return $message;
        }
    }

    /**
     * Extract attachment content from message
     * 
     * @param array $message The message data from webhook
     * @return array Attachment data
     */
    private function extractAttachmentContent(array $message): array
    {
        $attachments = $message['attachments'] ?? [];
        
        if (empty($attachments)) {
            return [
                'attachments' => [],
                'text' => $message['text'] ?? null
            ];
        }

        $processedAttachments = [];
        
        foreach ($attachments as $attachment) {
            $type = $attachment['type'] ?? 'unknown';
            $payload = $attachment['payload'] ?? [];
            
            $processedAttachment = [
                'type' => $type,
                'url' => $payload['url'] ?? null
            ];

            // Add sticker ID if present
            if ($type === 'image' && isset($payload['sticker_id'])) {
                $processedAttachment['sticker_id'] = $payload['sticker_id'];
            }

            $processedAttachments[] = $processedAttachment;
        }

        return [
            'attachments' => $processedAttachments,
            'text' => $message['text'] ?? null,
            'attachment_count' => count($processedAttachments)
        ];
    }

    /**
     * Extract context message ID (reply_to) from message
     * 
     * Meta includes reply_to information in the message payload when a user
     * replies to a specific message.
     * 
     * @param array $message The message data from webhook
     * @return string|null The context message ID if present
     */
    private function extractContextMessageId(array $message): ?string
    {
        // Check for reply_to field
        if (isset($message['reply_to']['mid'])) {
            return $message['reply_to']['mid'];
        }

        // Some webhook versions use different structure
        if (isset($message['reply_to_message_id'])) {
            return $message['reply_to_message_id'];
        }

        return null;
    }

    /**
     * Process template update webhook
     * 
     * Note: Instagram and Messenger don't support templates like WhatsApp.
     * This method is a no-op for interface compatibility.
     * 
     * @param array $payload The webhook payload
     * @return TemplateUpdate The parsed template update
     */
    public function processTemplateUpdate(array $payload): TemplateUpdate
    {
        $this->logger->info('Template updates not supported for Meta provider (Instagram/Messenger)');
        
        return new TemplateUpdate(
            templateId: '',
            action: 'not_supported',
            timestamp: new \DateTimeImmutable(),
            template: null,
            reason: 'Templates not supported for Instagram/Messenger'
        );
    }

    /**
     * Send HTTP request to Meta API
     * 
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint API endpoint (e.g., '/messages')
     * @param array|null $payload Request payload
     * @return ProviderSendResult The result of the send operation
     */
    private function sendRequest(string $method, string $endpoint, ?array $payload = null): ProviderSendResult
    {
        $url = $this->getApiUrl($endpoint);

        $options = [
            'headers' => $this->getAuthHeaders()
        ];

        if ($payload !== null) {
            $options['json'] = $payload;
        }

        try {
            $this->logger->debug('Sending request to Meta API', [
                'method' => $method,
                'endpoint' => $endpoint,
                'url' => $url
            ]);

            $response = $this->httpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $messageId = $body['message_id'] ?? null;

                $this->logger->info('Meta API request successful', [
                    'message_id' => $messageId,
                    'status_code' => $statusCode
                ]);

                return new ProviderSendResult(
                    success: true,
                    messageId: $messageId,
                    status: 'SENT',
                    details: $body
                );
            }

            // Handle error response
            $errorMessage = $body['error']['message'] ?? 'Unknown error';
            $errorCode = $body['error']['code'] ?? 0;
            $errorType = $body['error']['type'] ?? 'UnknownError';

            $this->logger->error('Meta API request failed', [
                'status_code' => $statusCode,
                'error_code' => $errorCode,
                'error_type' => $errorType,
                'error_message' => $errorMessage
            ]);

            return new ProviderSendResult(
                success: false,
                error: $this->formatErrorMessage($errorCode, $errorMessage),
                details: $body
            );

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Handle HTTP errors (4xx, 5xx)
            $response = $e->getResponse();
            
            if ($response) {
                $statusCode = $response->getStatusCode();
                $body = json_decode((string) $response->getBody(), true);
                $errorCode = $body['error']['code'] ?? 0;
                $errorMessage = $body['error']['message'] ?? $e->getMessage();
                $errorType = $body['error']['type'] ?? 'RequestException';
                $errorSubcode = $body['error']['error_subcode'] ?? null;
                $fbTraceId = $body['error']['fbtrace_id'] ?? null;

                $this->logger->error('Meta API request exception', [
                    'status_code' => $statusCode,
                    'error_code' => $errorCode,
                    'error_subcode' => $errorSubcode,
                    'error_type' => $errorType,
                    'error_message' => $errorMessage,
                    'fbtrace_id' => $fbTraceId,
                    'endpoint' => $endpoint
                ]);

                return new ProviderSendResult(
                    success: false,
                    error: $this->formatErrorMessage($errorCode, $errorMessage, $errorSubcode),
                    details: array_merge($body ?? [], [
                        'is_transient' => $this->isTransientError($errorCode, $errorSubcode)
                    ])
                );
            }

            // Network error or other exception
            $this->logger->error('Meta API network error', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint
            ]);

            return new ProviderSendResult(
                success: false,
                error: 'Network error: ' . $e->getMessage(),
                details: ['is_transient' => true]
            );

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during Meta API request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'endpoint' => $endpoint
            ]);

            return new ProviderSendResult(
                success: false,
                error: 'Unexpected error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Format error message based on error code
     * 
     * @param int $errorCode Meta API error code
     * @param string $defaultMessage Default error message
     * @param int|null $errorSubcode Error subcode for more specific errors
     * @return string Formatted error message
     */
    private function formatErrorMessage(int $errorCode, string $defaultMessage, ?int $errorSubcode = null): string
    {
        // Handle specific Meta error codes
        return match($errorCode) {
            36103 => 'Conta não elegível para mensagens. Verifique se a conta Instagram está conectada corretamente.',
            2534068 => 'Feature não disponível para esta conta. Verifique as permissões e configurações da página.',
            10 => 'Permissão negada. Verifique as permissões do Page Access Token.',
            100 => 'Parâmetro inválido: ' . $defaultMessage,
            190 => 'Token de acesso inválido ou expirado. Atualize o Page Access Token.',
            200 => 'Erro de permissão. Verifique as permissões do aplicativo.',
            551 => 'Usuário não disponível para receber mensagens.',
            2022 => 'Janela de mensagens de 24 horas expirada. O usuário precisa enviar uma mensagem primeiro.',
            default => $this->formatSubcodeError($errorSubcode) ?? $defaultMessage
        };
    }

    /**
     * Format error message based on error subcode
     * 
     * @param int|null $errorSubcode Error subcode
     * @return string|null Formatted error message or null if no specific message
     */
    private function formatSubcodeError(?int $errorSubcode): ?string
    {
        if ($errorSubcode === null) {
            return null;
        }

        return match($errorSubcode) {
            2018278 => 'Janela de mensagens de 24 horas expirada. O usuário precisa iniciar a conversa.',
            default => null
        };
    }

    /**
     * Check if error is transient and should be retried
     * 
     * @param int $errorCode Meta API error code
     * @param int|null $errorSubcode Error subcode
     * @return bool True if error is transient
     */
    private function isTransientError(int $errorCode, ?int $errorSubcode = null): bool
    {
        // Permanent errors that should not be retried
        $permanentErrors = [36103, 2534068, 10, 100, 190, 200, 551, 2022];

        if (in_array($errorCode, $permanentErrors)) {
            return false;
        }

        // Check subcode for specific transient errors
        if ($errorSubcode === 2018278) {
            return false; // 24h window expired - permanent
        }

        // Rate limit errors (should be retried with backoff)
        if ($errorCode === 4 || $errorCode === 32 || $errorCode === 613) {
            return true;
        }

        // Server errors (5xx equivalent) are transient
        if ($errorCode >= 1 && $errorCode <= 2) {
            return true;
        }

        // Default: assume transient for unknown errors
        return true;
    }

    /**
     * Get authentication headers for Meta API requests
     * 
     * @return array<string, string> Headers with Authorization token
     */
    private function getAuthHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config['page_access_token'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get the full API URL for an endpoint
     * 
     * @param string $endpoint The API endpoint (e.g., '/messages')
     * @return string The full URL
     */
    private function getApiUrl(string $endpoint): string
    {
        $baseUrl = $this->config['base_url'] ?? self::BASE_URL;
        $version = $this->config['api_version'] ?? self::API_VERSION;
        $pageId = $this->config['page_id'];

        // Build URL: https://graph.facebook.com/v21.0/{page-id}{endpoint}
        return "{$baseUrl}/{$version}/{$pageId}{$endpoint}";
    }

    /**
     * Validate configuration
     * 
     * @throws \InvalidArgumentException If required configuration is missing
     */
    private function validateConfig(): void
    {
        $required = ['page_access_token', 'app_secret', 'page_id'];
        
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new \InvalidArgumentException(
                    "Missing required Meta configuration: {$key}"
                );
            }
        }

        // Validate Page Access Token format (should be a non-empty string)
        if (!is_string($this->config['page_access_token']) || 
            strlen($this->config['page_access_token']) < 10) {
            throw new \InvalidArgumentException(
                'Invalid Page Access Token format. Token must be at least 10 characters.'
            );
        }
    }
}
