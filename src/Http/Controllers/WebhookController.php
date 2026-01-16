<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Http\JsonResponse;
use WhatsApp\Adapter\Providers\WhatsAppProviderFactory;
use WhatsApp\Adapter\Services\MessageService;
use WhatsApp\Adapter\Services\TemplateService;

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
        private readonly WhatsAppProviderFactory $providerFactory,
        private readonly MessageService $messageService,
        private readonly TemplateService $templateService,
        private readonly LoggerInterface $logger
    ) {}

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
