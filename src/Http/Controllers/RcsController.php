<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Http\JsonResponse;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Models\Requests\MediaRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;
use WhatsApp\Adapter\Models\Requests\RcsCardRequest;
use WhatsApp\Adapter\Models\Requests\RcsCarouselRequest;
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Providers\Infobip\InfobipRcsProvider;

/**
 * Controller for RCS (Rich Communication Services) messaging endpoints
 * 
 * Handles RCS-specific message types through Infobip:
 * - Text messages
 * - File messages
 * - Rich cards
 * - Carousels
 * - Suggested actions
 */
class RcsController
{
    public function __construct(
        private readonly MessagingProviderFactory $providerFactory,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * POST /api/rcs/text
     * Send RCS text message
     */
    public function sendText(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('POST /api/rcs/text - Sending RCS text message');

            $body = json_decode($request->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_JSON',
                        'message' => 'Invalid JSON in request body'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Validate required fields
            if (empty($body['to']) || empty($body['text'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'MISSING_REQUIRED_FIELDS',
                        'message' => 'Missing required fields: to, text'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Get RCS provider
            $provider = $this->getRcsProvider();

            // Create request
            $textRequest = new TextRequest(
                to: $body['to'],
                text: $body['text'],
                notifyUrl: $body['notifyUrl'] ?? null
            );

            // Send message
            $result = $provider->sendText($textRequest);

            if ($result->success) {
                return new JsonResponse([
                    'success' => true,
                    'data' => [
                        'message_id' => $result->messageId,
                        'status' => $result->status,
                        'to' => $body['to']
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'SEND_FAILED',
                    'message' => $result->error ?? 'Failed to send message'
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to send RCS text message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * POST /api/rcs/file
     * Send RCS file message
     */
    public function sendFile(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('POST /api/rcs/file - Sending RCS file message');

            $body = json_decode($request->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_JSON',
                        'message' => 'Invalid JSON in request body'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Validate required fields
            if (empty($body['to']) || empty($body['fileUrl'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'MISSING_REQUIRED_FIELDS',
                        'message' => 'Missing required fields: to, fileUrl'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Get RCS provider
            $provider = $this->getRcsProvider();

            // Create request
            $mediaRequest = new MediaRequest(
                to: $body['to'],
                mediaType: 'document',
                mediaUrl: $body['fileUrl'],
                caption: $body['caption'] ?? null,
                filename: $body['filename'] ?? null,
                notifyUrl: $body['notifyUrl'] ?? null
            );

            // Send message
            $result = $provider->sendMedia($mediaRequest);

            if ($result->success) {
                return new JsonResponse([
                    'success' => true,
                    'data' => [
                        'message_id' => $result->messageId,
                        'status' => $result->status,
                        'to' => $body['to']
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'SEND_FAILED',
                    'message' => $result->error ?? 'Failed to send file'
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to send RCS file message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * POST /api/rcs/card
     * Send RCS rich card message
     */
    public function sendCard(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('POST /api/rcs/card - Sending RCS rich card');

            $body = json_decode($request->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_JSON',
                        'message' => 'Invalid JSON in request body'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Validate required fields
            if (empty($body['to']) || empty($body['title'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'MISSING_REQUIRED_FIELDS',
                        'message' => 'Missing required fields: to, title'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Get RCS provider
            $provider = $this->getRcsProvider();

            // Create request
            $cardRequest = new RcsCardRequest(
                to: $body['to'],
                title: $body['title'],
                description: $body['description'] ?? null,
                mediaUrl: $body['mediaUrl'] ?? null,
                mediaHeight: $body['mediaHeight'] ?? 'MEDIUM',
                suggestions: $body['suggestions'] ?? [],
                notifyUrl: $body['notifyUrl'] ?? null
            );

            // Send message
            $result = $provider->sendRichCard(
                to: $cardRequest->to,
                title: $cardRequest->title,
                description: $cardRequest->description,
                mediaUrl: $cardRequest->mediaUrl,
                suggestions: $cardRequest->suggestions,
                notifyUrl: $cardRequest->notifyUrl
            );

            if ($result->success) {
                return new JsonResponse([
                    'success' => true,
                    'data' => [
                        'message_id' => $result->messageId,
                        'status' => $result->status,
                        'to' => $body['to']
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'SEND_FAILED',
                    'message' => $result->error ?? 'Failed to send card'
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to send RCS card', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * POST /api/rcs/carousel
     * Send RCS carousel message
     */
    public function sendCarousel(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('POST /api/rcs/carousel - Sending RCS carousel');

            $body = json_decode($request->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_JSON',
                        'message' => 'Invalid JSON in request body'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Validate required fields
            if (empty($body['to']) || empty($body['cards'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'MISSING_REQUIRED_FIELDS',
                        'message' => 'Missing required fields: to, cards'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Get RCS provider
            $provider = $this->getRcsProvider();

            // Create request
            $carouselRequest = new RcsCarouselRequest(
                to: $body['to'],
                cards: $body['cards'],
                cardWidth: $body['cardWidth'] ?? 'MEDIUM',
                notifyUrl: $body['notifyUrl'] ?? null
            );

            // Send message
            $result = $provider->sendCarousel(
                to: $carouselRequest->to,
                cards: $carouselRequest->cards,
                cardWidth: $carouselRequest->cardWidth,
                notifyUrl: $carouselRequest->notifyUrl
            );

            if ($result->success) {
                return new JsonResponse([
                    'success' => true,
                    'data' => [
                        'message_id' => $result->messageId,
                        'status' => $result->status,
                        'to' => $body['to'],
                        'card_count' => count($body['cards'])
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'SEND_FAILED',
                    'message' => $result->error ?? 'Failed to send carousel'
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to send RCS carousel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * POST /api/rcs/suggestions
     * Send RCS text with suggested replies/actions
     */
    public function sendWithSuggestions(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info('POST /api/rcs/suggestions - Sending RCS message with suggestions');

            $body = json_decode($request->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_JSON',
                        'message' => 'Invalid JSON in request body'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Validate required fields
            if (empty($body['to']) || empty($body['text']) || empty($body['suggestions'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'MISSING_REQUIRED_FIELDS',
                        'message' => 'Missing required fields: to, text, suggestions'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            // Get RCS provider
            $provider = $this->getRcsProvider();

            // Map suggestions to buttons format
            $buttons = array_map(function($suggestion, $index) {
                return [
                    'id' => $suggestion['postbackData'] ?? "suggestion_{$index}",
                    'text' => $suggestion['text'],
                    'title' => $suggestion['text']
                ];
            }, $body['suggestions'], array_keys($body['suggestions']));

            // Create request
            $buttonsRequest = new InteractiveButtonsRequest(
                to: $body['to'],
                bodyText: $body['text'],
                buttons: $buttons,
                notifyUrl: $body['notifyUrl'] ?? null
            );

            // Send message
            $result = $provider->sendInteractiveButtons($buttonsRequest);

            if ($result->success) {
                return new JsonResponse([
                    'success' => true,
                    'data' => [
                        'message_id' => $result->messageId,
                        'status' => $result->status,
                        'to' => $body['to'],
                        'suggestion_count' => count($body['suggestions'])
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]);
            }

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'SEND_FAILED',
                    'message' => $result->error ?? 'Failed to send message with suggestions'
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to send RCS message with suggestions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * Get RCS provider instance
     */
    private function getRcsProvider(): InfobipRcsProvider
    {
        $provider = $this->providerFactory->getProvider('infobip-rcs');

        if (!$provider instanceof InfobipRcsProvider) {
            throw new \RuntimeException('RCS provider not configured');
        }

        return $provider;
    }
}

