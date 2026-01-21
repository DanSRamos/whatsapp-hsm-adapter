<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers\Meta;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Services\WebhookErrorHandler;

/**
 * Handles webhook validation and processing for Meta Messaging Platform
 * 
 * Supports both Instagram and Facebook Messenger webhooks
 */
class MetaWebhookHandler
{
    private const SIGNATURE_HEADER = 'X-Hub-Signature-256';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ?WebhookErrorHandler $errorHandler = null
    ) {}

    /**
     * Validate webhook signature using HMAC SHA-256
     * 
     * Verifies the X-Hub-Signature-256 header against the computed HMAC
     * using the App Secret. Uses hash_equals for timing-attack-safe comparison.
     *
     * @param ServerRequestInterface $request The HTTP request
     * @param string $appSecret The Meta App Secret
     * @return bool True if signature is valid, false otherwise
     */
    public function validateSignature(ServerRequestInterface $request, string $appSecret): bool
    {
        $signature = $request->getHeaderLine(self::SIGNATURE_HEADER);
        
        if (empty($signature)) {
            $this->logger->warning('Meta webhook signature header missing', [
                'expected_header' => self::SIGNATURE_HEADER
            ]);
            return false;
        }

        // Get raw body content
        $body = (string) $request->getBody();
        
        if (empty($body)) {
            $this->logger->warning('Meta webhook body is empty');
            return false;
        }

        // Compute expected signature using HMAC SHA-256
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $body, $appSecret);

        // Use hash_equals for timing-attack-safe comparison
        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            $this->logger->warning('Meta webhook signature validation failed', [
                'expected_prefix' => substr($expectedSignature, 0, 15) . '...',
                'received_prefix' => substr($signature, 0, 15) . '...',
                'body_length' => strlen($body)
            ]);
        } else {
            $this->logger->debug('Meta webhook signature validated successfully');
        }

        return $isValid;
    }

    /**
     * Handle webhook verification challenge (GET request)
     * 
     * Meta sends a GET request during webhook setup with:
     * - hub.mode: Should be 'subscribe'
     * - hub.verify_token: Should match configured verify token
     * - hub.challenge: Random string to echo back
     *
     * @param array<string, mixed> $params Query parameters
     * @param string $verifyToken The configured verify token
     * @return string|null The challenge string if valid, null otherwise
     */
    public function handleVerification(array $params, string $verifyToken): ?string
    {
        $mode = $params['hub_mode'] ?? '';
        $token = $params['hub_verify_token'] ?? '';
        $challenge = $params['hub_challenge'] ?? '';

        $this->logger->debug('Meta webhook verification request received', [
            'mode' => $mode,
            'has_token' => !empty($token),
            'has_challenge' => !empty($challenge)
        ]);

        // Verify mode is 'subscribe'
        if ($mode !== 'subscribe') {
            $this->logger->warning('Meta webhook verification failed: invalid mode', [
                'expected' => 'subscribe',
                'received' => $mode
            ]);
            return null;
        }

        // Verify token matches using timing-attack-safe comparison
        if (!hash_equals($verifyToken, $token)) {
            $this->logger->warning('Meta webhook verification failed: token mismatch');
            return null;
        }

        // Verify challenge is present
        if (empty($challenge)) {
            $this->logger->warning('Meta webhook verification failed: challenge missing');
            return null;
        }

        $this->logger->info('Meta webhook verification successful', [
            'challenge_length' => strlen($challenge)
        ]);

        return $challenge;
    }

    /**
     * Extract messages from webhook payload
     *
     * @param array<string, mixed> $payload The webhook payload
     * @return array<array<string, mixed>> Array of message data
     */
    public function extractMessages(array $payload): array
    {
        $messages = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            // Instagram uses 'messaging' field
            foreach ($entry['messaging'] ?? [] as $messagingEvent) {
                if (isset($messagingEvent['message'])) {
                    $messages[] = $messagingEvent;
                }
            }
        }

        return $messages;
    }

    /**
     * Extract delivery reports from webhook payload
     *
     * @param array<string, mixed> $payload The webhook payload
     * @return array<array<string, mixed>> Array of delivery report data
     */
    public function extractDeliveryReports(array $payload): array
    {
        $reports = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            // Check for delivery events
            foreach ($entry['messaging'] ?? [] as $messagingEvent) {
                if (isset($messagingEvent['delivery'])) {
                    $reports[] = $messagingEvent;
                }
            }

            // Check for read events
            foreach ($entry['messaging'] ?? [] as $messagingEvent) {
                if (isset($messagingEvent['read'])) {
                    $reports[] = $messagingEvent;
                }
            }
        }

        return $reports;
    }

    /**
     * Detect platform (Instagram or Messenger) from webhook payload
     *
     * @param array<string, mixed> $messagingEvent The messaging event
     * @return string 'instagram' or 'messenger'
     */
    public function detectPlatform(array $messagingEvent): string
    {
        // Instagram-scoped IDs are typically longer and have different format
        // This is a simplified detection - can be enhanced based on actual ID patterns
        $senderId = $messagingEvent['sender']['id'] ?? '';
        
        // For now, we'll need additional context or configuration to determine platform
        // This will be enhanced in later tasks with MetaPlatformDetector
        return 'meta'; // Generic for now
    }

    /**
     * Process webhook with error handling and retry logic
     * 
     * @param array<string, mixed> $payload The webhook payload
     * @param callable $processor Function to process the webhook
     * @param int $attemptNumber Current attempt number (1-based)
     * @return array{success: bool, should_retry: bool, delay_ms: int, error: ?string}
     */
    public function processWithErrorHandling(
        array $payload,
        callable $processor,
        int $attemptNumber = 1
    ): array {
        try {
            $this->logger->debug('Processing webhook', [
                'attempt' => $attemptNumber,
                'payload_type' => $payload['object'] ?? 'unknown'
            ]);

            // Execute the processor
            $processor($payload);

            $this->logger->info('Webhook processed successfully', [
                'attempt' => $attemptNumber,
                'payload_type' => $payload['object'] ?? 'unknown'
            ]);

            return [
                'success' => true,
                'should_retry' => false,
                'delay_ms' => 0,
                'error' => null
            ];
        } catch (\Throwable $e) {
            // If no error handler, just log and fail
            if ($this->errorHandler === null) {
                $this->logger->error('Webhook processing failed (no error handler)', [
                    'error' => $e->getMessage(),
                    'attempt' => $attemptNumber
                ]);

                return [
                    'success' => false,
                    'should_retry' => false,
                    'delay_ms' => 0,
                    'error' => $e->getMessage()
                ];
            }

            // Use error handler to determine retry strategy
            $retryDecision = $this->errorHandler->handleError($e, $payload, $attemptNumber);

            return [
                'success' => false,
                'should_retry' => $retryDecision['should_retry'],
                'delay_ms' => $retryDecision['delay_ms'],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if error is related to 24-hour messaging window
     */
    public function isMessagingWindowError(\Throwable $error): bool
    {
        if ($this->errorHandler === null) {
            return false;
        }

        return $this->errorHandler->isMessagingWindowError($error);
    }

    /**
     * Check if error is related to account eligibility
     */
    public function isAccountEligibilityError(\Throwable $error): bool
    {
        if ($this->errorHandler === null) {
            return false;
        }

        return $this->errorHandler->isAccountEligibilityError($error);
    }

    /**
     * Get user-friendly error message
     */
    public function getUserFriendlyErrorMessage(\Throwable $error): string
    {
        if ($this->errorHandler === null) {
            return $error->getMessage();
        }

        return $this->errorHandler->getUserFriendlyMessage($error);
    }
}
