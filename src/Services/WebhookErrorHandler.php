<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Psr\Log\LoggerInterface;

/**
 * Handles webhook processing errors with retry logic and dead letter queue
 * 
 * Provides specialized error handling for webhook events including:
 * - 24-hour messaging window violations
 * - Account eligibility errors
 * - Retry logic with exponential backoff
 * - Dead letter queue for failed webhooks
 */
class WebhookErrorHandler
{
    // Meta-specific error codes
    private const ERROR_ACCOUNT_NOT_ELIGIBLE = 36103;
    private const ERROR_FEATURE_NOT_AVAILABLE = 2534068;
    private const ERROR_MESSAGING_WINDOW_EXPIRED = 2022;
    private const ERROR_USER_NOT_AVAILABLE = 551;
    
    // Retry configuration
    private const MAX_RETRY_ATTEMPTS = 3;
    private const INITIAL_RETRY_DELAY_MS = 1000;
    private const MAX_RETRY_DELAY_MS = 30000;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly DeadLetterQueue $deadLetterQueue
    ) {}

    /**
     * Handle webhook processing error
     * 
     * Determines if error is retryable and takes appropriate action:
     * - Non-retryable errors: Log and move to dead letter queue
     * - Retryable errors: Return retry delay
     * 
     * @param \Throwable $error The error that occurred
     * @param array<string, mixed> $payload The webhook payload
     * @param int $attemptNumber Current attempt number (1-based)
     * @return array{should_retry: bool, delay_ms: int, reason: string}
     */
    public function handleError(\Throwable $error, array $payload, int $attemptNumber = 1): array
    {
        $errorCode = $this->extractErrorCode($error);
        $errorMessage = $error->getMessage();

        $this->logger->error('Webhook processing error', [
            'error' => $errorMessage,
            'error_code' => $errorCode,
            'error_class' => get_class($error),
            'attempt' => $attemptNumber,
            'payload_type' => $payload['object'] ?? 'unknown',
            'trace' => $error->getTraceAsString()
        ]);

        // Check if error is retryable
        $retryDecision = $this->shouldRetry($errorCode, $attemptNumber);

        if (!$retryDecision['should_retry']) {
            // Non-retryable error - add to dead letter queue
            $this->addToDeadLetterQueue($payload, $error, $attemptNumber);
            
            // Alert if critical error
            if ($this->isCriticalError($errorCode)) {
                $this->alertCriticalError($error, $payload);
            }
        }

        return $retryDecision;
    }

    /**
     * Check if error is related to 24-hour messaging window
     */
    public function isMessagingWindowError(\Throwable $error): bool
    {
        $errorCode = $this->extractErrorCode($error);
        return $errorCode === self::ERROR_MESSAGING_WINDOW_EXPIRED;
    }

    /**
     * Check if error is related to account eligibility
     */
    public function isAccountEligibilityError(\Throwable $error): bool
    {
        $errorCode = $this->extractErrorCode($error);
        return $errorCode === self::ERROR_ACCOUNT_NOT_ELIGIBLE;
    }

    /**
     * Check if error is related to feature availability
     */
    public function isFeatureNotAvailableError(\Throwable $error): bool
    {
        $errorCode = $this->extractErrorCode($error);
        return $errorCode === self::ERROR_FEATURE_NOT_AVAILABLE;
    }

    /**
     * Get user-friendly error message for webhook errors
     */
    public function getUserFriendlyMessage(\Throwable $error): string
    {
        $errorCode = $this->extractErrorCode($error);

        return match ($errorCode) {
            self::ERROR_ACCOUNT_NOT_ELIGIBLE => 
                'Conta não elegível para receber mensagens. Verifique as configurações da conta.',
            self::ERROR_FEATURE_NOT_AVAILABLE => 
                'Recurso não disponível para esta conta. Entre em contato com o suporte.',
            self::ERROR_MESSAGING_WINDOW_EXPIRED => 
                'Janela de mensagens de 24 horas expirada. Aguarde nova mensagem do usuário.',
            self::ERROR_USER_NOT_AVAILABLE => 
                'Usuário não disponível para receber mensagens.',
            default => 
                'Erro ao processar webhook: ' . $error->getMessage()
        };
    }

    /**
     * Determine if error should be retried
     * 
     * @return array{should_retry: bool, delay_ms: int, reason: string}
     */
    private function shouldRetry(int $errorCode, int $attemptNumber): array
    {
        // Check if max attempts reached
        if ($attemptNumber >= self::MAX_RETRY_ATTEMPTS) {
            return [
                'should_retry' => false,
                'delay_ms' => 0,
                'reason' => 'Max retry attempts reached'
            ];
        }

        // Non-retryable error codes
        $nonRetryableErrors = [
            self::ERROR_ACCOUNT_NOT_ELIGIBLE,
            self::ERROR_FEATURE_NOT_AVAILABLE,
            self::ERROR_MESSAGING_WINDOW_EXPIRED,
            self::ERROR_USER_NOT_AVAILABLE,
        ];

        if (in_array($errorCode, $nonRetryableErrors, true)) {
            return [
                'should_retry' => false,
                'delay_ms' => 0,
                'reason' => 'Non-retryable error code: ' . $errorCode
            ];
        }

        // Calculate retry delay with exponential backoff
        $delay = $this->calculateRetryDelay($attemptNumber);

        return [
            'should_retry' => true,
            'delay_ms' => $delay,
            'reason' => 'Transient error, will retry'
        ];
    }

    /**
     * Calculate retry delay using exponential backoff
     */
    private function calculateRetryDelay(int $attemptNumber): int
    {
        // Exponential backoff: 1s, 2s, 4s, 8s, etc.
        $delay = self::INITIAL_RETRY_DELAY_MS * (2 ** ($attemptNumber - 1));
        return min($delay, self::MAX_RETRY_DELAY_MS);
    }

    /**
     * Extract error code from exception
     */
    private function extractErrorCode(\Throwable $error): int
    {
        // Try to extract from exception code
        $code = $error->getCode();
        if (is_int($code) && $code > 0) {
            return $code;
        }

        // Try to extract from message (Meta API errors often include code in message)
        if (preg_match('/\b(\d{3,5})\b/', $error->getMessage(), $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Check if error is critical and requires immediate attention
     */
    private function isCriticalError(int $errorCode): bool
    {
        // Critical errors that require immediate attention
        $criticalErrors = [
            self::ERROR_ACCOUNT_NOT_ELIGIBLE,
            self::ERROR_FEATURE_NOT_AVAILABLE,
        ];

        return in_array($errorCode, $criticalErrors, true);
    }

    /**
     * Add failed webhook to dead letter queue
     */
    private function addToDeadLetterQueue(
        array $payload,
        \Throwable $error,
        int $attemptNumber
    ): void {
        try {
            $this->deadLetterQueue->add(
                payload: $payload,
                error: $error->getMessage(),
                errorCode: $this->extractErrorCode($error),
                attemptNumber: $attemptNumber,
                metadata: [
                    'error_class' => get_class($error),
                    'trace' => $error->getTraceAsString(),
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]
            );

            $this->logger->info('Webhook added to dead letter queue', [
                'error_code' => $this->extractErrorCode($error),
                'attempt_number' => $attemptNumber,
                'payload_type' => $payload['object'] ?? 'unknown'
            ]);
        } catch (\Throwable $e) {
            // If we can't add to DLQ, log the error but don't throw
            $this->logger->error('Failed to add webhook to dead letter queue', [
                'error' => $e->getMessage(),
                'original_error' => $error->getMessage()
            ]);
        }
    }

    /**
     * Alert team about critical error
     */
    private function alertCriticalError(\Throwable $error, array $payload): void
    {
        $this->logger->critical('Critical webhook error detected', [
            'error' => $error->getMessage(),
            'error_code' => $this->extractErrorCode($error),
            'payload_type' => $payload['object'] ?? 'unknown',
            'user_message' => $this->getUserFriendlyMessage($error),
            'requires_action' => true
        ]);

        // TODO: Integrate with alerting system (email, Slack, PagerDuty, etc.)
        // For now, critical log level should trigger monitoring alerts
    }

    /**
     * Get maximum retry attempts
     */
    public function getMaxRetryAttempts(): int
    {
        return self::MAX_RETRY_ATTEMPTS;
    }
}

