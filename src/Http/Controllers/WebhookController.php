<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Http\JsonResponse;
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Services\MessageService;
use WhatsApp\Adapter\Services\TemplateService;
use WhatsApp\Adapter\Services\WebhookErrorHandler;
use WhatsApp\Adapter\Services\DeadLetterQueue;

/**
 * Controller for webhook endpoints
 * 
 * Handles incoming webhooks from WhatsApp providers for:
 * - Delivery reports
 * - Incoming messages
 * - Template updates
 */
class WebhookController
{
    public function __construct(
        private readonly MessagingProviderFactory $providerFactory,
        private readonly MessageService $messageService,
        private readonly TemplateService $templateService,
        private readonly LoggerInterface $logger,
        private readonly ?WebhookErrorHandler $webhookErrorHandler = null,
        private readonly ?DeadLetterQueue $deadLetterQueue = null
    ) {}

    /**
     * GET/POST /webhooks/meta
     * Handles Meta (Instagram + Messenger) webhooks
     * 
     * GET: Webhook verification during setup
     * POST: Incoming messages and delivery reports
     */
    public function handleMetaWebhook(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();

        // Handle GET request for webhook verification
        if ($method === 'GET') {
            return $this->handleMetaWebhookVerification($request);
        }

        // Handle POST request for webhook events
        if ($method === 'POST') {
            return $this->handleMetaWebhookEvent($request);
        }

        // Method not allowed
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'METHOD_NOT_ALLOWED',
                'message' => 'Only GET and POST methods are supported'
            ],
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        ], 405);
    }

    /**
     * Handle Meta webhook verification (GET request)
     * 
     * Meta sends a GET request with hub.mode, hub.verify_token, and hub.challenge
     * during initial webhook setup.
     */
    private function handleMetaWebhookVerification(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('GET /webhooks/meta - Webhook verification request');

            // Get Meta provider
            $provider = $this->providerFactory->getProvider('meta');

            if (!$provider) {
                $this->logger->error('Meta provider not available');
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'PROVIDER_NOT_AVAILABLE',
                        'message' => 'Meta provider is not configured'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 500);
            }

            // Validate webhook (this will check verify_token)
            if (!$provider->validateWebhook($request)) {
                $this->logger->warning('Meta webhook verification failed');
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'VERIFICATION_FAILED',
                        'message' => 'Webhook verification failed'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 403);
            }

            // Get challenge from query params
            $params = $request->getQueryParams();
            $challenge = $params['hub_challenge'] ?? '';

            if (empty($challenge)) {
                $this->logger->error('Challenge parameter missing');
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'CHALLENGE_MISSING',
                        'message' => 'hub.challenge parameter is required'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            $this->logger->info('Meta webhook verification successful', [
                'challenge_length' => strlen($challenge)
            ]);

            // Return challenge as plain text (Meta expects this)
            $response = new \GuzzleHttp\Psr7\Response(
                200,
                ['Content-Type' => 'text/plain'],
                $challenge
            );

            return $response;

        } catch (\Throwable $e) {
            $this->logger->error('Failed to process Meta webhook verification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'VERIFICATION_ERROR',
                    'message' => 'Failed to process verification: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * Handle Meta webhook event (POST request)
     * 
     * Processes incoming messages, delivery reports, and other webhook events
     * from Instagram and Facebook Messenger.
     */
    private function handleMetaWebhookEvent(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('POST /webhooks/meta - Webhook event received');

            // Get Meta provider
            $provider = $this->providerFactory->getProvider('meta');

            if (!$provider) {
                $this->logger->error('Meta provider not available');
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'PROVIDER_NOT_AVAILABLE',
                        'message' => 'Meta provider is not configured'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 500);
            }

            // Validate webhook signature
            if (!$provider->validateWebhook($request)) {
                $this->logger->warning('Meta webhook signature validation failed');
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_SIGNATURE',
                        'message' => 'Webhook signature validation failed'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 401);
            }

            // Parse webhook payload
            $body = $request->getBody()->getContents();
            
            // Log raw body for debugging
            $this->logger->debug('Meta webhook raw body', [
                'body_length' => strlen($body),
                'body_preview' => substr($body, 0, 200)
            ]);
            
            // If body is empty, try to get from parsed body
            if (empty($body)) {
                $this->logger->warning('Request body is empty, trying getParsedBody()');
                $payload = $request->getParsedBody();
                
                if (!is_array($payload)) {
                    $this->logger->error('Could not parse request body');
                    return new JsonResponse([
                        'success' => false,
                        'error' => [
                            'code' => 'EMPTY_BODY',
                            'message' => 'Request body is empty'
                        ],
                        'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                    ], 400);
                }
            } else {
                $payload = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->logger->error('Invalid JSON in Meta webhook payload', [
                        'error' => json_last_error_msg(),
                        'body_preview' => substr($body, 0, 200)
                    ]);

                    return new JsonResponse([
                        'success' => false,
                        'error' => [
                            'code' => 'INVALID_JSON',
                            'message' => 'Invalid JSON in webhook payload'
                        ],
                        'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                    ], 400);
                }
            }

            // Log webhook payload for debugging
            $this->logger->debug('Meta webhook payload received', [
                'object' => $payload['object'] ?? 'unknown',
                'entry_count' => count($payload['entry'] ?? [])
            ]);

            // Process webhook with error handling
            $result = $this->processMetaWebhookWithRetry($payload);

            if ($result['success']) {
                $this->logger->info('Meta webhook event processed successfully', [
                    'object' => $payload['object'] ?? 'unknown'
                ]);

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Webhook received',
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]);
            }

            // If processing failed but shouldn't retry, log and return 200
            // (Meta expects 200 to avoid retries)
            $this->logger->warning('Meta webhook processing failed', [
                'error' => $result['error'],
                'should_retry' => $result['should_retry']
            ]);

            // Always return 200 to Meta to avoid their automatic retries
            // Our internal retry logic handles retries if needed
            return new JsonResponse([
                'success' => true,
                'message' => 'Webhook received (processing failed)',
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to process Meta webhook event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Still return 200 to Meta to avoid retries
            // Log the error for investigation
            return new JsonResponse([
                'success' => true,
                'message' => 'Webhook received (processing failed)',
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ]);
        }
    }

    /**
     * Process Meta webhook with retry logic
     * 
     * @param array<string, mixed> $payload
     * @return array{success: bool, should_retry: bool, error: ?string}
     */
    private function processMetaWebhookWithRetry(array $payload): array
    {
        $maxAttempts = $this->webhookErrorHandler?->getMaxRetryAttempts() ?? 1;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Process the webhook
                // TODO: Implement actual processing logic in later tasks
                // For now, just acknowledge receipt
                
                $this->logger->debug('Processing Meta webhook', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts
                ]);

                // Success
                return [
                    'success' => true,
                    'should_retry' => false,
                    'error' => null
                ];
            } catch (\Throwable $e) {
                $this->logger->warning('Meta webhook processing attempt failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);

                // If no error handler, fail immediately
                if ($this->webhookErrorHandler === null) {
                    return [
                        'success' => false,
                        'should_retry' => false,
                        'error' => $e->getMessage()
                    ];
                }

                // Use error handler to determine retry strategy
                $retryDecision = $this->webhookErrorHandler->handleError($e, $payload, $attempt);

                // If shouldn't retry, return failure
                if (!$retryDecision['should_retry']) {
                    return [
                        'success' => false,
                        'should_retry' => false,
                        'error' => $e->getMessage()
                    ];
                }

                // If this was the last attempt, return failure
                if ($attempt >= $maxAttempts) {
                    return [
                        'success' => false,
                        'should_retry' => false,
                        'error' => $e->getMessage()
                    ];
                }

                // Wait before retry
                if ($retryDecision['delay_ms'] > 0) {
                    usleep($retryDecision['delay_ms'] * 1000);
                }
            }
        }

        // Should never reach here, but return failure just in case
        return [
            'success' => false,
            'should_retry' => false,
            'error' => 'Max retry attempts reached'
        ];
    }

    /**
     * POST /webhooks/delivery-reports
     * Recebe relatórios de entrega dos provedores
     */
    public function handleDeliveryReport(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('POST /webhooks/delivery-reports - Received delivery report webhook');

            // Detect provider from webhook
            $provider = $this->providerFactory->detectProviderFromWebhook($request);

            if (!$provider) {
                $this->logger->warning('Webhook from unknown provider');
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'UNKNOWN_PROVIDER',
                        'message' => 'Could not identify provider from webhook'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Validate webhook signature
            if (!$provider->validateWebhook($request)) {
                $this->logger->warning('Invalid webhook signature', [
                    'provider' => $provider->getName()
                ]);

                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_SIGNATURE',
                        'message' => 'Webhook signature validation failed'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 401);
            }

            // Parse webhook payload
            $payload = json_decode($request->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid JSON in webhook payload', [
                    'provider' => $provider->getName(),
                    'error' => json_last_error_msg()
                ]);

                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_JSON',
                        'message' => 'Invalid JSON in webhook payload'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Process delivery report
            $this->messageService->processDeliveryReport($payload, $provider->getName());

            $this->logger->info('Delivery report processed successfully', [
                'provider' => $provider->getName()
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Delivery report processed',
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to process delivery report webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'WEBHOOK_PROCESSING_ERROR',
                    'message' => 'Failed to process webhook: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * POST /webhooks/incoming-messages
     * Recebe mensagens recebidas de clientes
     */
    public function handleIncomingMessage(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('POST /webhooks/incoming-messages - Received incoming message webhook');

            // Detect provider from webhook
            $provider = $this->providerFactory->detectProviderFromWebhook($request);

            if (!$provider) {
                $this->logger->warning('Webhook from unknown provider');
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'UNKNOWN_PROVIDER',
                        'message' => 'Could not identify provider from webhook'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Validate webhook signature
            if (!$provider->validateWebhook($request)) {
                $this->logger->warning('Invalid webhook signature', [
                    'provider' => $provider->getName()
                ]);

                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_SIGNATURE',
                        'message' => 'Webhook signature validation failed'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 401);
            }

            // Parse webhook payload
            $payload = json_decode($request->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid JSON in webhook payload', [
                    'provider' => $provider->getName(),
                    'error' => json_last_error_msg()
                ]);

                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_JSON',
                        'message' => 'Invalid JSON in webhook payload'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Process incoming message
            $incomingMessage = $this->messageService->processIncomingMessage($payload, $provider->getName());

            $this->logger->info('Incoming message processed successfully', [
                'provider' => $provider->getName(),
                'message_id' => $incomingMessage->messageId,
                'from' => $incomingMessage->from,
                'type' => $incomingMessage->type
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Incoming message processed',
                'data' => [
                    'message_id' => $incomingMessage->messageId,
                    'from' => $incomingMessage->from,
                    'type' => $incomingMessage->type
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to process incoming message webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'WEBHOOK_PROCESSING_ERROR',
                    'message' => 'Failed to process webhook: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * POST /webhooks/template-updates
     * Recebe notificações de alterações em templates
     */
    public function handleTemplateUpdate(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('POST /webhooks/template-updates - Received template update webhook');

            // Detect provider from webhook
            $provider = $this->providerFactory->detectProviderFromWebhook($request);

            if (!$provider) {
                $this->logger->warning('Webhook from unknown provider');
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'UNKNOWN_PROVIDER',
                        'message' => 'Could not identify provider from webhook'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Validate webhook signature
            if (!$provider->validateWebhook($request)) {
                $this->logger->warning('Invalid webhook signature', [
                    'provider' => $provider->getName()
                ]);

                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_SIGNATURE',
                        'message' => 'Webhook signature validation failed'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 401);
            }

            // Parse webhook payload
            $payload = json_decode($request->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid JSON in webhook payload', [
                    'provider' => $provider->getName(),
                    'error' => json_last_error_msg()
                ]);

                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_JSON',
                        'message' => 'Invalid JSON in webhook payload'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Process template update
            $this->templateService->processTemplateUpdate($payload);

            $this->logger->info('Template update processed successfully', [
                'provider' => $provider->getName(),
                'template_id' => $payload['id'] ?? 'unknown'
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Template update processed',
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to process template update webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'WEBHOOK_PROCESSING_ERROR',
                    'message' => 'Failed to process webhook: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }
}
