<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers\Meta;

use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Models\Requests\HSMRequest;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Models\Requests\MediaRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;

/**
 * Adapter for converting standard requests to Meta-compatible formats
 * 
 * This adapter handles:
 * - Template to text conversion (Meta doesn't support HSM templates)
 * - Media format validation and conversion
 * - Request validation specific to Meta platform
 * - Logging of all conversions and adaptations
 */
class MetaRequestAdapter
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MetaMessageFormatter $formatter
    ) {
    }

    /**
     * Adapt HSM template request to text request
     * 
     * Meta (Instagram/Messenger) doesn't support HSM templates like WhatsApp.
     * This method converts template requests to plain text by substituting placeholders.
     * 
     * @param HSMRequest $request The HSM template request
     * @return TextRequest|null The adapted text request, or null if conversion fails
     */
    public function adaptTemplateRequest(HSMRequest $request): ?TextRequest
    {
        $this->logger->info('Adapting HSM template request for Meta', [
            'template_name' => $request->templateName,
            'template_language' => $request->templateLanguage,
            'to' => $request->to,
            'parameter_count' => count($request->parameters)
        ]);

        // Validate that we have parameters to work with
        if (empty($request->parameters)) {
            $this->logger->error('Cannot adapt template request: No parameters provided', [
                'template_name' => $request->templateName,
                'to' => $request->to
            ]);
            return null;
        }

        // Try to extract template text and substitution parameters
        $templateText = $request->parameters[0] ?? '';
        $substitutionParams = array_slice($request->parameters, 1);

        // Check if the first parameter contains placeholders
        if (strpos($templateText, '{{') !== false && strpos($templateText, '}}') !== false) {
            // First parameter is the template text, rest are substitution values
            $convertedText = $this->formatter->convertTemplateToText($templateText, $substitutionParams);
            
            $this->logger->info('Template converted to plain text', [
                'template_name' => $request->templateName,
                'original_text' => $templateText,
                'converted_text' => $convertedText,
                'parameters_used' => count($substitutionParams),
                'placeholders_found' => $this->countPlaceholders($templateText)
            ]);
        } else {
            // No placeholders found, treat all parameters as text parts to concatenate
            $convertedText = implode(' ', $request->parameters);
            
            $this->logger->info('Template parameters concatenated to text', [
                'template_name' => $request->templateName,
                'converted_text' => $convertedText,
                'parameters_used' => count($request->parameters)
            ]);
        }

        // Validate that we have non-empty text
        if (empty(trim($convertedText))) {
            $this->logger->error('Template conversion resulted in empty text', [
                'template_name' => $request->templateName,
                'to' => $request->to,
                'parameters' => $request->parameters
            ]);
            return null;
        }

        // Create adapted text request
        $textRequest = new TextRequest(
            to: $request->to,
            text: $convertedText,
            notifyUrl: $request->notifyUrl
        );

        $this->logger->info('Template request successfully adapted to text request', [
            'template_name' => $request->templateName,
            'to' => $request->to,
            'text_length' => strlen($convertedText)
        ]);

        return $textRequest;
    }

    /**
     * Validate and adapt media request for Meta platform
     * 
     * Validates:
     * - Media URL format (must be HTTPS)
     * - Media type support
     * - File format compatibility
     * - Platform-specific size limits
     * 
     * @param MediaRequest $request The media request
     * @param string $platform Platform identifier ('instagram' or 'messenger')
     * @return array{valid: bool, errors: array<string>, warnings: array<string>} Validation result
     */
    public function validateMediaRequest(MediaRequest $request, string $platform = 'instagram'): array
    {
        $errors = [];
        $warnings = [];

        $this->logger->debug('Validating media request for Meta', [
            'media_type' => $request->mediaType,
            'media_url' => $request->mediaUrl,
            'platform' => $platform,
            'to' => $request->to
        ]);

        // Validate URL format
        if (empty($request->mediaUrl)) {
            $errors[] = 'Media URL cannot be empty';
        } elseif (!filter_var($request->mediaUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid media URL format';
        } elseif (!str_starts_with($request->mediaUrl, 'https://')) {
            $errors[] = 'Media URL must use HTTPS protocol. HTTP URLs are not supported by Meta API.';
        }

        // Validate media type
        $supportedTypes = ['image', 'video', 'audio', 'document'];
        if (!in_array($request->mediaType, $supportedTypes, true)) {
            $errors[] = sprintf(
                'Unsupported media type: %s. Supported types: %s',
                $request->mediaType,
                implode(', ', $supportedTypes)
            );
        }

        // Validate file format
        $formatValidation = $this->validateMediaFormat($request->mediaType, $request->mediaUrl);
        if (!$formatValidation['valid']) {
            $errors = array_merge($errors, $formatValidation['errors']);
        }
        $warnings = array_merge($warnings, $formatValidation['warnings']);

        // Add platform-specific warnings
        if ($platform === 'messenger' && $request->mediaType === 'image') {
            $warnings[] = 'Messenger typically supports 1 image per message. For multiple images, use carousel/generic template.';
        }

        $isValid = empty($errors);

        $this->logger->info('Media request validation completed', [
            'valid' => $isValid,
            'error_count' => count($errors),
            'warning_count' => count($warnings),
            'media_type' => $request->mediaType,
            'platform' => $platform
        ]);

        if (!$isValid) {
            $this->logger->warning('Media request validation failed', [
                'errors' => $errors,
                'media_url' => $request->mediaUrl,
                'media_type' => $request->mediaType
            ]);
        }

        if (!empty($warnings)) {
            $this->logger->info('Media request validation warnings', [
                'warnings' => $warnings,
                'media_url' => $request->mediaUrl
            ]);
        }

        return [
            'valid' => $isValid,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Validate media format based on type
     * 
     * @param string $mediaType The media type (image, video, audio, document)
     * @param string $url The media URL
     * @return array{valid: bool, errors: array<string>, warnings: array<string>} Validation result
     */
    private function validateMediaFormat(string $mediaType, string $url): array
    {
        $errors = [];
        $warnings = [];

        // Extract file extension from URL
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (empty($extension)) {
            // If no extension in URL, add warning but don't fail
            // Meta API will validate the actual content
            $warnings[] = 'Media URL has no file extension. Meta API will validate the actual content type.';
            
            $this->logger->warning('Media URL has no file extension', [
                'url' => $url,
                'media_type' => $mediaType
            ]);
            
            return [
                'valid' => true,
                'errors' => $errors,
                'warnings' => $warnings
            ];
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
            $errors[] = sprintf(
                'Unsupported %s format: .%s. Supported formats: %s',
                $mediaType,
                $extension,
                implode(', ', array_map(fn($f) => ".$f", $validFormats))
            );
            
            $this->logger->error('Unsupported media format', [
                'media_type' => $mediaType,
                'extension' => $extension,
                'supported_formats' => $validFormats,
                'url' => $url
            ]);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Adapt interactive buttons request for Meta platform
     * 
     * Meta supports up to 13 quick replies (vs WhatsApp's 3 buttons).
     * This method validates and adapts the request accordingly.
     * 
     * @param InteractiveButtonsRequest $request The interactive buttons request
     * @param string $platform Platform identifier ('instagram' or 'messenger')
     * @return array{valid: bool, errors: array<string>, warnings: array<string>, adapted_request: InteractiveButtonsRequest|null}
     */
    public function adaptInteractiveButtonsRequest(
        InteractiveButtonsRequest $request,
        string $platform = 'instagram'
    ): array {
        $errors = [];
        $warnings = [];

        $this->logger->debug('Adapting interactive buttons request for Meta', [
            'button_count' => count($request->buttons),
            'platform' => $platform,
            'to' => $request->to
        ]);

        // Meta supports up to 13 quick replies
        $maxQuickReplies = 13;
        
        if (count($request->buttons) > $maxQuickReplies) {
            $errors[] = sprintf(
                'Too many quick replies. Maximum %d quick replies allowed for Meta, %d provided',
                $maxQuickReplies,
                count($request->buttons)
            );
        }

        // Validate button titles (Meta limit is 20 characters)
        $adaptedButtons = [];
        foreach ($request->buttons as $index => $button) {
            $title = $button['text'] ?? $button['title'] ?? '';
            
            if (strlen($title) > 20) {
                $truncatedTitle = substr($title, 0, 20);
                $warnings[] = sprintf(
                    'Button %d title exceeds 20 characters, will be truncated: "%s" -> "%s"',
                    $index + 1,
                    $title,
                    $truncatedTitle
                );
                
                $this->logger->warning('Quick reply title exceeds 20 characters, truncating', [
                    'button_index' => $index,
                    'original_title' => $title,
                    'truncated_title' => $truncatedTitle
                ]);
                
                // Create adapted button with truncated title
                $adaptedButtons[] = [
                    'id' => $button['id'],
                    'text' => $truncatedTitle
                ];
            } else {
                $adaptedButtons[] = $button;
            }
        }

        $isValid = empty($errors);
        $adaptedRequest = null;

        if ($isValid && !empty($adaptedButtons)) {
            // Create adapted request with truncated titles if needed
            $adaptedRequest = new InteractiveButtonsRequest(
                to: $request->to,
                bodyText: $request->bodyText,
                buttons: $adaptedButtons,
                headerText: $request->headerText,
                footerText: $request->footerText,
                notifyUrl: $request->notifyUrl
            );
            
            $this->logger->info('Interactive buttons request adapted successfully', [
                'button_count' => count($adaptedButtons),
                'truncated_count' => count($warnings),
                'platform' => $platform
            ]);
        }

        return [
            'valid' => $isValid,
            'errors' => $errors,
            'warnings' => $warnings,
            'adapted_request' => $adaptedRequest
        ];
    }

    /**
     * Adapt interactive list request for Meta platform
     * 
     * Meta uses Generic Template for lists/carousels.
     * This method validates and adapts the request accordingly.
     * 
     * @param InteractiveListRequest $request The interactive list request
     * @param string $platform Platform identifier ('instagram' or 'messenger')
     * @return array{valid: bool, errors: array<string>, warnings: array<string>, adapted_request: InteractiveListRequest|null}
     */
    public function adaptInteractiveListRequest(
        InteractiveListRequest $request,
        string $platform = 'instagram'
    ): array {
        $errors = [];
        $warnings = [];

        $this->logger->debug('Adapting interactive list request for Meta', [
            'section_count' => count($request->sections),
            'platform' => $platform,
            'to' => $request->to
        ]);

        // Count total items across all sections
        $totalItems = 0;
        foreach ($request->sections as $section) {
            $totalItems += count($section['items'] ?? []);
        }

        // Meta supports up to 10 elements in generic template
        $maxElements = 10;
        
        if ($totalItems > $maxElements) {
            $warnings[] = sprintf(
                'Too many elements in list. Maximum %d elements allowed for Meta, %d provided. Extra elements will be truncated.',
                $maxElements,
                $totalItems
            );
            
            $this->logger->warning('Too many elements in generic template', [
                'total_items' => $totalItems,
                'max_elements' => $maxElements,
                'will_truncate' => true
            ]);
        }

        // Validate title and subtitle lengths (80 characters max)
        foreach ($request->sections as $sectionIndex => $section) {
            foreach ($section['items'] ?? [] as $itemIndex => $item) {
                $title = $item['title'] ?? '';
                $description = $item['description'] ?? '';
                
                if (strlen($title) > 80) {
                    $warnings[] = sprintf(
                        'Item title in section %d exceeds 80 characters, will be truncated',
                        $sectionIndex + 1
                    );
                }
                
                if (strlen($description) > 80) {
                    $warnings[] = sprintf(
                        'Item description in section %d exceeds 80 characters, will be truncated',
                        $sectionIndex + 1
                    );
                }
                
                // Validate buttons per card (max 3)
                $buttonCount = count($item['buttons'] ?? []);
                if ($buttonCount > 3) {
                    $warnings[] = sprintf(
                        'Item in section %d has %d buttons. Maximum 3 buttons per card allowed, extras will be truncated.',
                        $sectionIndex + 1,
                        $buttonCount
                    );
                }
            }
        }

        $isValid = empty($errors);

        $this->logger->info('Interactive list request adaptation completed', [
            'valid' => $isValid,
            'total_items' => $totalItems,
            'warning_count' => count($warnings),
            'platform' => $platform
        ]);

        return [
            'valid' => $isValid,
            'errors' => $errors,
            'warnings' => $warnings,
            'adapted_request' => $request // List request doesn't need structural changes
        ];
    }

    /**
     * Get platform-specific media size limits
     * 
     * @param string $platform Platform identifier ('instagram' or 'messenger')
     * @return array<string, int> Size limits in bytes
     */
    public function getPlatformMediaLimits(string $platform): array
    {
        if ($platform === 'instagram') {
            return [
                'image' => 8 * 1024 * 1024,      // 8MB
                'video' => 25 * 1024 * 1024,     // 25MB
                'audio' => 25 * 1024 * 1024,     // 25MB
                'document' => 25 * 1024 * 1024,  // 25MB
                'max_images_per_message' => 10
            ];
        }

        // Messenger limits
        return [
            'image' => 25 * 1024 * 1024,         // 25MB
            'video' => 25 * 1024 * 1024,         // 25MB
            'audio' => 25 * 1024 * 1024,         // 25MB
            'document' => 25 * 1024 * 1024,      // 25MB
            'max_images_per_message' => 1
        ];
    }

    /**
     * Count placeholders in template text
     * 
     * @param string $templateText Template text with {{1}}, {{2}}, etc.
     * @return int Number of placeholders found
     */
    private function countPlaceholders(string $templateText): int
    {
        preg_match_all('/\{\{\d+\}\}/', $templateText, $matches);
        return count($matches[0] ?? []);
    }

    /**
     * Log conversion summary
     * 
     * Logs a summary of all conversions performed during request adaptation.
     * Useful for monitoring and debugging.
     * 
     * @param string $requestType Type of request (template, media, interactive_buttons, interactive_list)
     * @param array<string, mixed> $details Conversion details
     */
    public function logConversionSummary(string $requestType, array $details): void
    {
        $this->logger->info('Request adaptation summary', array_merge([
            'request_type' => $requestType,
            'provider' => 'meta',
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ], $details));
    }
}

