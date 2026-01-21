<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Providers\MessagingProviderInterface;

/**
 * Service to validate if a phone number has WhatsApp
 */
class WhatsAppNumberValidator
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Check if a phone number has WhatsApp
     *
     * @param MessagingProviderInterface $provider The messaging provider
     * @param string $phoneNumber Phone number in E.164 format (e.g., +351912345678)
     * @return WhatsAppNumberValidationResult Validation result
     */
    public function validateNumber(
        MessagingProviderInterface $provider,
        string $phoneNumber
    ): WhatsAppNumberValidationResult {
        $this->logger->info('Validating WhatsApp number', [
            'provider' => $provider->getName(),
            'phone_number' => $this->maskPhoneNumber($phoneNumber)
        ]);

        // Validate phone number format
        if (!$this->isValidPhoneFormat($phoneNumber)) {
            return new WhatsAppNumberValidationResult(
                phoneNumber: $phoneNumber,
                hasWhatsApp: false,
                error: 'Invalid phone number format. Use E.164 format (e.g., +351912345678)',
                provider: $provider->getName()
            );
        }

        try {
            // Check if provider supports number validation
            if (!method_exists($provider, 'checkWhatsAppNumber')) {
                $this->logger->warning('Provider does not support WhatsApp number validation', [
                    'provider' => $provider->getName()
                ]);

                return new WhatsAppNumberValidationResult(
                    phoneNumber: $phoneNumber,
                    hasWhatsApp: null,
                    error: 'Provider does not support WhatsApp number validation',
                    provider: $provider->getName()
                );
            }

            // Call provider-specific validation
            $result = $provider->checkWhatsAppNumber($phoneNumber);

            $this->logger->info('WhatsApp number validation completed', [
                'provider' => $provider->getName(),
                'phone_number' => $this->maskPhoneNumber($phoneNumber),
                'has_whatsapp' => $result->hasWhatsApp,
                'account_type' => $result->accountType
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('WhatsApp number validation failed', [
                'provider' => $provider->getName(),
                'phone_number' => $this->maskPhoneNumber($phoneNumber),
                'error' => $e->getMessage()
            ]);

            return new WhatsAppNumberValidationResult(
                phoneNumber: $phoneNumber,
                hasWhatsApp: null,
                error: $e->getMessage(),
                provider: $provider->getName()
            );
        }
    }

    /**
     * Validate phone number format (E.164)
     */
    private function isValidPhoneFormat(string $phoneNumber): bool
    {
        // E.164 format: +[country code][number]
        // Example: +351912345678
        return preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber) === 1;
    }

    /**
     * Mask phone number for logging (keep first 4 and last 2 digits)
     */
    private function maskPhoneNumber(string $phoneNumber): string
    {
        if (strlen($phoneNumber) <= 6) {
            return $phoneNumber;
        }

        $start = substr($phoneNumber, 0, 4);
        $end = substr($phoneNumber, -2);
        $middle = str_repeat('*', strlen($phoneNumber) - 6);

        return $start . $middle . $end;
    }
}

/**
 * Result of WhatsApp number validation
 */
class WhatsAppNumberValidationResult
{
    public function __construct(
        public readonly string $phoneNumber,
        public readonly ?bool $hasWhatsApp,
        public readonly ?string $accountType = null,
        public readonly ?string $error = null,
        public readonly ?string $provider = null,
        public readonly ?array $metadata = null
    ) {}

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'phoneNumber' => $this->phoneNumber,
            'hasWhatsApp' => $this->hasWhatsApp,
            'accountType' => $this->accountType,
            'error' => $this->error,
            'provider' => $this->provider,
            'metadata' => $this->metadata
        ];
    }
}
