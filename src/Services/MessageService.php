<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Models\IncomingMessage;
use WhatsApp\Adapter\Models\Message;
use WhatsApp\Adapter\Models\MessageStatus;
use WhatsApp\Adapter\Models\Requests\HSMRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;
use WhatsApp\Adapter\Models\Requests\MediaRequest;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Models\SendResult;
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Repositories\MessageRepositoryInterface;

/**
 * Service for managing WhatsApp messages
 * 
 * This service handles sending messages through providers, querying message status,
 * and processing incoming messages and delivery reports.
 */
class MessageService
{
    public function __construct(
        private readonly MessagingProviderFactory $providerFactory,
        private readonly MessageRepositoryInterface $messageRepository,
        private readonly RetryHandler $retryHandler,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Send an HSM/Template message
     *
     * @param HSMRequest $request The HSM message request
     * @param string|null $providerName Optional provider name (uses default if null)
     * @return SendResult The result of the send operation
     */
    public function sendHSM(HSMRequest $request, ?string $providerName = null): SendResult
    {
        $provider = $this->providerFactory->getProvider($providerName);
        
        $this->logger->info('Sending HSM message', [
            'provider' => $provider->getName(),
            'to' => $request->to,
            'template' => $request->templateName,
            'language' => $request->templateLanguage
        ]);

        // Apply Meta-specific validations
        if ($provider->getName() === 'meta') {
            $validationError = $this->validateMetaRequest($request->to);
            if ($validationError !== null) {
                $this->logger->error('Meta validation failed for HSM message', [
                    'to' => $request->to,
                    'template' => $request->templateName,
                    'error' => $validationError
                ]);
                
                return new SendResult(
                    success: false,
                    error: $validationError
                );
            }
        }

        try {
            $result = $this->retryHandler->execute(
                fn() => $provider->sendTemplate($request)
            );

            // Save message to database
            if ($result->success && $result->messageId) {
                $message = new Message(
                    id: $result->messageId,
                    type: 'hsm',
                    toNumber: $request->to,
                    fromNumber: '', // Will be set by provider
                    status: $result->status ?? 'PENDING',
                    content: [
                        'templateName' => $request->templateName,
                        'templateLanguage' => $request->templateLanguage,
                        'parameters' => $request->parameters
                    ],
                    sentAt: new \DateTimeImmutable(),
                    metadata: [
                        'provider' => $provider->getName(),
                        'notifyUrl' => $request->notifyUrl
                    ]
                );

                $this->messageRepository->save($message);

                $this->logger->info('HSM message sent successfully', [
                    'message_id' => $result->messageId,
                    'status' => $result->status
                ]);
            }

            return new SendResult(
                success: $result->success,
                messageId: $result->messageId,
                status: $result->status,
                error: $result->error,
                details: $result->details
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send HSM message', [
                'provider' => $provider->getName(),
                'to' => $request->to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new SendResult(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Send a free-text message
     *
     * @param TextRequest $request The text message request
     * @param string|null $providerName Optional provider name (uses default if null)
     * @return SendResult The result of the send operation
     */
    public function sendText(TextRequest $request, ?string $providerName = null): SendResult
    {
        $provider = $this->providerFactory->getProvider($providerName);
        
        $this->logger->info('Sending text message', [
            'provider' => $provider->getName(),
            'to' => $request->to,
            'text_length' => strlen($request->text)
        ]);

        // Apply Meta-specific validations
        if ($provider->getName() === 'meta') {
            $validationError = $this->validateMetaRequest($request->to);
            if ($validationError !== null) {
                $this->logger->error('Meta validation failed for text message', [
                    'to' => $request->to,
                    'error' => $validationError
                ]);
                
                return new SendResult(
                    success: false,
                    error: $validationError
                );
            }
        }

        try {
            $result = $this->retryHandler->execute(
                fn() => $provider->sendText($request)
            );

            // Save message to database
            if ($result->success && $result->messageId) {
                $message = new Message(
                    id: $result->messageId,
                    type: 'text',
                    toNumber: $request->to,
                    fromNumber: '',
                    status: $result->status ?? 'PENDING',
                    content: [
                        'text' => $request->text,
                        'previewUrl' => $request->previewUrl
                    ],
                    sentAt: new \DateTimeImmutable(),
                    metadata: [
                        'provider' => $provider->getName(),
                        'notifyUrl' => $request->notifyUrl
                    ]
                );

                $this->messageRepository->save($message);

                $this->logger->info('Text message sent successfully', [
                    'message_id' => $result->messageId,
                    'status' => $result->status
                ]);
            }

            return new SendResult(
                success: $result->success,
                messageId: $result->messageId,
                status: $result->status,
                error: $result->error,
                details: $result->details
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send text message', [
                'provider' => $provider->getName(),
                'to' => $request->to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new SendResult(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Send media (image, document, audio, video)
     *
     * @param MediaRequest $request The media message request
     * @param string|null $providerName Optional provider name (uses default if null)
     * @return SendResult The result of the send operation
     */
    public function sendMedia(MediaRequest $request, ?string $providerName = null): SendResult
    {
        $provider = $this->providerFactory->getProvider($providerName);
        
        $this->logger->info('Sending media message', [
            'provider' => $provider->getName(),
            'to' => $request->to,
            'media_type' => $request->mediaType,
            'media_url' => $request->mediaUrl
        ]);

        // Apply Meta-specific validations
        if ($provider->getName() === 'meta') {
            $validationError = $this->validateMetaRequest($request->to);
            if ($validationError !== null) {
                $this->logger->error('Meta validation failed for media message', [
                    'to' => $request->to,
                    'media_type' => $request->mediaType,
                    'error' => $validationError
                ]);
                
                return new SendResult(
                    success: false,
                    error: $validationError
                );
            }
            
            // Validate media limits for Meta
            $mediaValidationError = $this->validateMetaMediaLimits($request);
            if ($mediaValidationError !== null) {
                $this->logger->error('Meta media validation failed', [
                    'to' => $request->to,
                    'media_type' => $request->mediaType,
                    'error' => $mediaValidationError
                ]);
                
                return new SendResult(
                    success: false,
                    error: $mediaValidationError
                );
            }
        }

        try {
            $result = $this->retryHandler->execute(
                fn() => $provider->sendMedia($request)
            );

            // Save message to database
            if ($result->success && $result->messageId) {
                $message = new Message(
                    id: $result->messageId,
                    type: 'media_' . $request->mediaType,
                    toNumber: $request->to,
                    fromNumber: '',
                    status: $result->status ?? 'PENDING',
                    content: [
                        'mediaType' => $request->mediaType,
                        'mediaUrl' => $request->mediaUrl,
                        'caption' => $request->caption,
                        'filename' => $request->filename
                    ],
                    sentAt: new \DateTimeImmutable(),
                    metadata: [
                        'provider' => $provider->getName(),
                        'notifyUrl' => $request->notifyUrl
                    ]
                );

                $this->messageRepository->save($message);

                $this->logger->info('Media message sent successfully', [
                    'message_id' => $result->messageId,
                    'status' => $result->status,
                    'media_type' => $request->mediaType
                ]);
            }

            return new SendResult(
                success: $result->success,
                messageId: $result->messageId,
                status: $result->status,
                error: $result->error,
                details: $result->details
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send media message', [
                'provider' => $provider->getName(),
                'to' => $request->to,
                'media_type' => $request->mediaType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new SendResult(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Send interactive message with buttons
     *
     * @param InteractiveButtonsRequest $request The interactive buttons request
     * @param string|null $providerName Optional provider name (uses default if null)
     * @return SendResult The result of the send operation
     */
    public function sendInteractiveButtons(InteractiveButtonsRequest $request, ?string $providerName = null): SendResult
    {
        $provider = $this->providerFactory->getProvider($providerName);
        
        $this->logger->info('Sending interactive buttons message', [
            'provider' => $provider->getName(),
            'to' => $request->to,
            'button_count' => count($request->buttons)
        ]);

        // Apply Meta-specific validations
        if ($provider->getName() === 'meta') {
            $validationError = $this->validateMetaRequest($request->to);
            if ($validationError !== null) {
                $this->logger->error('Meta validation failed for interactive buttons', [
                    'to' => $request->to,
                    'error' => $validationError
                ]);
                
                return new SendResult(
                    success: false,
                    error: $validationError
                );
            }
        }

        try {
            $result = $this->retryHandler->execute(
                fn() => $provider->sendInteractiveButtons($request)
            );

            // Save message to database
            if ($result->success && $result->messageId) {
                $message = new Message(
                    id: $result->messageId,
                    type: 'interactive_buttons',
                    toNumber: $request->to,
                    fromNumber: '',
                    status: $result->status ?? 'PENDING',
                    content: [
                        'bodyText' => $request->bodyText,
                        'headerText' => $request->headerText,
                        'footerText' => $request->footerText,
                        'buttons' => $request->buttons
                    ],
                    sentAt: new \DateTimeImmutable(),
                    metadata: [
                        'provider' => $provider->getName(),
                        'notifyUrl' => $request->notifyUrl
                    ]
                );

                $this->messageRepository->save($message);

                $this->logger->info('Interactive buttons message sent successfully', [
                    'message_id' => $result->messageId,
                    'status' => $result->status
                ]);
            }

            return new SendResult(
                success: $result->success,
                messageId: $result->messageId,
                status: $result->status,
                error: $result->error,
                details: $result->details
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send interactive buttons message', [
                'provider' => $provider->getName(),
                'to' => $request->to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new SendResult(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Send interactive message with list
     *
     * @param InteractiveListRequest $request The interactive list request
     * @param string|null $providerName Optional provider name (uses default if null)
     * @return SendResult The result of the send operation
     */
    public function sendInteractiveList(InteractiveListRequest $request, ?string $providerName = null): SendResult
    {
        $provider = $this->providerFactory->getProvider($providerName);
        
        // Count total items across all sections
        $totalItems = array_reduce(
            $request->sections,
            fn($carry, $section) => $carry + count($section['items'] ?? []),
            0
        );
        
        $this->logger->info('Sending interactive list message', [
            'provider' => $provider->getName(),
            'to' => $request->to,
            'section_count' => count($request->sections),
            'total_items' => $totalItems
        ]);

        // Apply Meta-specific validations
        if ($provider->getName() === 'meta') {
            $validationError = $this->validateMetaRequest($request->to);
            if ($validationError !== null) {
                $this->logger->error('Meta validation failed for interactive list', [
                    'to' => $request->to,
                    'error' => $validationError
                ]);
                
                return new SendResult(
                    success: false,
                    error: $validationError
                );
            }
        }

        try {
            $result = $this->retryHandler->execute(
                fn() => $provider->sendInteractiveList($request)
            );

            // Save message to database
            if ($result->success && $result->messageId) {
                $message = new Message(
                    id: $result->messageId,
                    type: 'interactive_list',
                    toNumber: $request->to,
                    fromNumber: '',
                    status: $result->status ?? 'PENDING',
                    content: [
                        'bodyText' => $request->bodyText,
                        'buttonText' => $request->buttonText,
                        'headerText' => $request->headerText,
                        'footerText' => $request->footerText,
                        'sections' => $request->sections
                    ],
                    sentAt: new \DateTimeImmutable(),
                    metadata: [
                        'provider' => $provider->getName(),
                        'notifyUrl' => $request->notifyUrl
                    ]
                );

                $this->messageRepository->save($message);

                $this->logger->info('Interactive list message sent successfully', [
                    'message_id' => $result->messageId,
                    'status' => $result->status
                ]);
            }

            return new SendResult(
                success: $result->success,
                messageId: $result->messageId,
                status: $result->status,
                error: $result->error,
                details: $result->details
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send interactive list message', [
                'provider' => $provider->getName(),
                'to' => $request->to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new SendResult(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Query the status of a message
     *
     * @param string $messageId The message ID to query
     * @param string|null $providerName Optional provider name (uses default if null)
     * @return MessageStatus The message status
     * @throws \RuntimeException If message not found or provider error
     */
    public function getMessageStatus(string $messageId, ?string $providerName = null): MessageStatus
    {
        $provider = $this->providerFactory->getProvider($providerName);
        
        $this->logger->info('Querying message status', [
            'provider' => $provider->getName(),
            'message_id' => $messageId
        ]);

        try {
            $result = $this->retryHandler->execute(
                fn() => $provider->getMessageStatus($messageId)
            );

            $this->logger->info('Message status retrieved', [
                'message_id' => $messageId,
                'status' => $result->status
            ]);

            return new MessageStatus(
                messageId: $result->messageId,
                status: $result->status,
                to: $result->to,
                sentAt: $result->sentAt,
                deliveredAt: $result->deliveredAt,
                readAt: $result->readAt,
                error: $result->error
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to query message status', [
                'provider' => $provider->getName(),
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            throw new \RuntimeException(
                "Failed to query message status: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Process delivery report from webhook
     *
     * @param array $webhookData The webhook payload
     * @param string|null $providerName Optional provider name (auto-detected if null)
     * @return void
     */
    public function processDeliveryReport(array $webhookData, ?string $providerName = null): void
    {
        $provider = $this->providerFactory->getProvider($providerName);
        
        $this->logger->info('Processing delivery report', [
            'provider' => $provider->getName()
        ]);

        try {
            $deliveryReport = $provider->processDeliveryReport($webhookData);

            // Update message status in database
            $this->messageRepository->updateStatus(
                $deliveryReport->messageId,
                $deliveryReport->status,
                array_merge(
                    $deliveryReport->metadata ?? [],
                    [
                        'timestamp' => $deliveryReport->timestamp->format(\DateTimeInterface::ATOM),
                        'error' => $deliveryReport->error
                    ]
                )
            );

            $this->logger->info('Delivery report processed', [
                'message_id' => $deliveryReport->messageId,
                'status' => $deliveryReport->status
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to process delivery report', [
                'provider' => $provider->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Process incoming message from webhook
     *
     * @param array $webhookData The webhook payload
     * @param string|null $providerName Optional provider name (auto-detected if null)
     * @return IncomingMessage The processed incoming message
     */
    public function processIncomingMessage(array $webhookData, ?string $providerName = null): IncomingMessage
    {
        $provider = $this->providerFactory->getProvider($providerName);
        
        $this->logger->info('Processing incoming message', [
            'provider' => $provider->getName()
        ]);

        try {
            $incomingMessage = $provider->processIncomingMessage($webhookData);

            $this->logger->info('Incoming message processed', [
                'message_id' => $incomingMessage->messageId,
                'from' => $incomingMessage->from,
                'type' => $incomingMessage->type
            ]);

            return $incomingMessage;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to process incoming message', [
                'provider' => $provider->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Validate Meta-specific request requirements
     * 
     * This method validates:
     * - IGSID/PSID format
     * - 24-hour messaging window (if repository is available)
     * 
     * @param string $recipientId The recipient ID (IGSID or PSID)
     * @return string|null Error message if validation fails, null if valid
     */
    private function validateMetaRequest(string $recipientId): ?string
    {
        // Validate IGSID/PSID format
        $formatError = $this->validateMetaRecipientId($recipientId);
        if ($formatError !== null) {
            return $formatError;
        }

        // Validate 24-hour messaging window
        $windowError = $this->validateMetaMessagingWindow($recipientId);
        if ($windowError !== null) {
            return $windowError;
        }

        return null;
    }

    /**
     * Validate Meta recipient ID format (IGSID or PSID)
     * 
     * Valid IDs should be:
     * - Non-empty
     * - Numeric
     * - At least 10 characters long
     * 
     * @param string $recipientId The recipient ID to validate
     * @return string|null Error message if invalid, null if valid
     */
    private function validateMetaRecipientId(string $recipientId): ?string
    {
        if (empty($recipientId)) {
            return 'Recipient ID cannot be empty for Meta provider';
        }

        if (!is_numeric($recipientId)) {
            return sprintf(
                'Invalid recipient ID format for Meta provider: "%s". ' .
                'ID must be numeric (IGSID for Instagram or PSID for Messenger)',
                $recipientId
            );
        }

        if (strlen($recipientId) < 10) {
            return sprintf(
                'Invalid recipient ID format for Meta provider: "%s". ' .
                'ID must be at least 10 characters long',
                $recipientId
            );
        }

        return null;
    }

    /**
     * Validate Meta 24-hour messaging window
     * 
     * Meta (Instagram/Messenger) requires that messages be sent within 24 hours
     * of the last user message. This method checks if the messaging window is still open.
     * 
     * Note: This validation requires the message repository to check the last incoming message.
     * If repository is not available, validation is skipped with a warning.
     * 
     * @param string $recipientId The recipient ID (IGSID or PSID)
     * @return string|null Error message if window expired, null if valid or cannot validate
     */
    private function validateMetaMessagingWindow(string $recipientId): ?string
    {
        // Skip validation if repository is not available
        if ($this->messageRepository === null) {
            $this->logger->warning('Cannot validate Meta messaging window - repository not available', [
                'recipient_id' => $recipientId
            ]);
            return null;
        }

        try {
            // Find the last incoming message from this recipient
            $lastIncomingMessage = $this->messageRepository->findLastIncomingMessage($recipientId);

            // If no previous message, we cannot send (user must initiate conversation)
            if ($lastIncomingMessage === null) {
                $this->logger->info('No previous incoming message found for Meta recipient', [
                    'recipient_id' => $recipientId,
                    'note' => 'User must initiate conversation first'
                ]);
                
                return sprintf(
                    'Cannot send message to recipient %s via Meta provider. ' .
                    'The user must send a message first to initiate the conversation. ' .
                    'Meta (Instagram/Messenger) requires users to opt-in by sending the first message.',
                    $recipientId
                );
            }

            // Check if message is within 24-hour window
            $now = new \DateTimeImmutable();
            $messageAge = $now->getTimestamp() - $lastIncomingMessage->receivedAt->getTimestamp();
            $windowDuration = 24 * 60 * 60; // 24 hours in seconds

            if ($messageAge > $windowDuration) {
                $hoursAgo = round($messageAge / 3600, 1);
                $hoursRemaining = 0;
                
                $this->logger->warning('Meta messaging window expired', [
                    'recipient_id' => $recipientId,
                    'last_message_at' => $lastIncomingMessage->receivedAt->format('Y-m-d H:i:s'),
                    'hours_ago' => $hoursAgo,
                    'window_duration_hours' => 24
                ]);

                return sprintf(
                    'Cannot send message to recipient %s via Meta provider. ' .
                    'The 24-hour messaging window has expired. ' .
                    'Last message was received %.1f hours ago. ' .
                    'The user must send a new message to reopen the conversation window.',
                    $recipientId,
                    $hoursAgo
                );
            }

            // Window is still open
            $hoursRemaining = round(($windowDuration - $messageAge) / 3600, 1);
            
            $this->logger->debug('Meta messaging window is open', [
                'recipient_id' => $recipientId,
                'last_message_at' => $lastIncomingMessage->receivedAt->format('Y-m-d H:i:s'),
                'hours_remaining' => $hoursRemaining
            ]);

            return null;

        } catch (\Exception $e) {
            // If we can't validate, log warning but don't block the message
            $this->logger->warning('Error validating Meta messaging window', [
                'recipient_id' => $recipientId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Validate Meta media limits
     * 
     * Validates that media files meet Meta's size and format requirements.
     * Different limits apply for Instagram vs Messenger.
     * 
     * Note: This is a basic validation. The actual file size validation
     * happens on Meta's servers since we only have URLs.
     * 
     * @param MediaRequest $request The media request to validate
     * @return string|null Error message if validation fails, null if valid
     */
    private function validateMetaMediaLimits(MediaRequest $request): ?string
    {
        // Validate media URL format
        if (empty($request->mediaUrl)) {
            return 'Media URL cannot be empty for Meta provider';
        }

        if (!filter_var($request->mediaUrl, FILTER_VALIDATE_URL)) {
            return sprintf(
                'Invalid media URL format for Meta provider: "%s". Must be a valid URL.',
                $request->mediaUrl
            );
        }

        // Meta API requires HTTPS URLs
        if (!str_starts_with($request->mediaUrl, 'https://')) {
            return sprintf(
                'Media URL must use HTTPS protocol for Meta provider. HTTP URLs are not supported. URL: %s',
                $request->mediaUrl
            );
        }

        // Validate media type is supported
        $supportedTypes = ['image', 'video', 'audio', 'document'];
        if (!in_array($request->mediaType, $supportedTypes, true)) {
            return sprintf(
                'Unsupported media type "%s" for Meta provider. Supported types: %s',
                $request->mediaType,
                implode(', ', $supportedTypes)
            );
        }

        // Log media validation success
        $this->logger->debug('Meta media validation passed', [
            'media_type' => $request->mediaType,
            'media_url' => $request->mediaUrl
        ]);

        return null;
    }
}
