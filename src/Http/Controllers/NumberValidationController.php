<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Http\JsonResponse;
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Services\WhatsAppNumberValidator;

/**
 * Controller for WhatsApp number validation
 */
class NumberValidationController
{
    public function __construct(
        private readonly MessagingProviderFactory $providerFactory,
        private readonly WhatsAppNumberValidator $validator,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Check if a phone number has WhatsApp
     *
     * GET /api/whatsapp/check-number?phoneNumber=+351912345678&provider=infobip
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function checkNumber(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $queryParams = $request->getQueryParams();
            
            // Validate required parameters
            if (!isset($queryParams['phoneNumber'])) {
                return JsonResponse::error(
                    'Missing required parameter: phoneNumber',
                    'VALIDATION_ERROR',
                    400
                );
            }

            $phoneNumber = $queryParams['phoneNumber'];
            $providerName = $queryParams['provider'] ?? 'infobip'; // Default to Infobip

            // Validate phone number format
            if (!preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber)) {
                return JsonResponse::error(
                    'Invalid phone number format. Use E.164 format (e.g., +351912345678)',
                    'INVALID_PHONE_FORMAT',
                    400
                );
            }

            $this->logger->info('WhatsApp number validation requested', [
                'provider' => $providerName,
                'phone_number' => substr($phoneNumber, 0, 4) . '***' . substr($phoneNumber, -2)
            ]);

            // Get provider
            try {
                $provider = $this->providerFactory->create($providerName);
            } catch (\Exception $e) {
                return JsonResponse::error(
                    "Provider '{$providerName}' not found or not configured",
                    'PROVIDER_NOT_FOUND',
                    404
                );
            }

            // Validate number
            $result = $this->validator->validateNumber($provider, $phoneNumber);

            // Return result
            if ($result->error) {
                return JsonResponse::error(
                    $result->error,
                    'VALIDATION_FAILED',
                    500,
                    $result->toArray()
                );
            }

            return JsonResponse::success($result->toArray());

        } catch (\Exception $e) {
            $this->logger->error('WhatsApp number validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return JsonResponse::error(
                'Internal server error: ' . $e->getMessage(),
                'INTERNAL_ERROR',
                500
            );
        }
    }

    /**
     * Batch check multiple phone numbers
     *
     * POST /api/whatsapp/check-numbers
     * Body: {"phoneNumbers": ["+351912345678", "+351987654321"], "provider": "infobip"}
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function checkNumbers(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $body = json_decode((string) $request->getBody(), true);
            
            // Validate required parameters
            if (!isset($body['phoneNumbers']) || !is_array($body['phoneNumbers'])) {
                return JsonResponse::error(
                    'Missing or invalid parameter: phoneNumbers (must be an array)',
                    'VALIDATION_ERROR',
                    400
                );
            }

            $phoneNumbers = $body['phoneNumbers'];
            $providerName = $body['provider'] ?? 'infobip';

            // Limit batch size
            if (count($phoneNumbers) > 100) {
                return JsonResponse::error(
                    'Too many phone numbers. Maximum 100 per request.',
                    'BATCH_SIZE_EXCEEDED',
                    400
                );
            }

            $this->logger->info('Batch WhatsApp number validation requested', [
                'provider' => $providerName,
                'count' => count($phoneNumbers)
            ]);

            // Get provider
            try {
                $provider = $this->providerFactory->create($providerName);
            } catch (\Exception $e) {
                return JsonResponse::error(
                    "Provider '{$providerName}' not found or not configured",
                    'PROVIDER_NOT_FOUND',
                    404
                );
            }

            // Validate all numbers
            $results = [];
            foreach ($phoneNumbers as $phoneNumber) {
                // Validate format
                if (!preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber)) {
                    $results[] = [
                        'phoneNumber' => $phoneNumber,
                        'hasWhatsApp' => null,
                        'error' => 'Invalid phone number format',
                        'provider' => $providerName
                    ];
                    continue;
                }

                // Validate number
                $result = $this->validator->validateNumber($provider, $phoneNumber);
                $results[] = $result->toArray();
            }

            return JsonResponse::success([
                'results' => $results,
                'total' => count($results),
                'provider' => $providerName
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Batch WhatsApp number validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return JsonResponse::error(
                'Internal server error: ' . $e->getMessage(),
                'INTERNAL_ERROR',
                500
            );
        }
    }
}
