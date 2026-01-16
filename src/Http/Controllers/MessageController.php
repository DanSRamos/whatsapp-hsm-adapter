<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Http\JsonResponse;
use WhatsApp\Adapter\Models\Requests\HSMRequest;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Models\Requests\MediaRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;
use WhatsApp\Adapter\Services\MessageService;

/**
 * Controller for message sending and status endpoints
 */
class MessageController
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * POST /api/messages/hsm
     * Envia uma mensagem HSM
     */
    public function sendHSM(ServerRequestInterface $request): ResponseInterface
    {
        try {
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

            $this->logger->info('POST /api/messages/hsm - Sending HSM message', [
                'to' => $body['to'] ?? null,
                'template' => $body['templateName'] ?? null
            ]);

            // Create HSM request (validation happens in constructor)
            $hsmRequest = new HSMRequest(
                to: $body['to'] ?? '',
                templateName: $body['templateName'] ?? '',
                templateLanguage: $body['templateLanguage'] ?? '',
                parameters: $body['parameters'] ?? [],
                notifyUrl: $body['notifyUrl'] ?? null
            );

            // Get provider from query params if specified
            $queryParams = $request->getQueryParams();
            $providerName = $queryParams['provider'] ?? null;

            // Send message
            $result = $this->messageService->sendHSM($hsmRequest, $providerName);

            if ($result->success) {
                $this->logger->info('HSM message sent successfully', [
                    'message_id' => $result->messageId
                ]);

                return new JsonResponse([
                    'success' => true,
                    'data' => [
                        'message_id' => $result->messageId,
                        'status' => $result->status
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'SEND_FAILED',
                        'message' => $result->error ?? 'Failed to send message'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 500);
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('HSM validation error', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 400);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send HSM message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Internal server error: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * POST /api/messages/text
     * Envia uma mensagem de texto livre
     */
    public function sendText(ServerRequestInterface $request): ResponseInterface
    {
        try {
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

            $this->logger->info('POST /api/messages/text - Sending text message', [
                'to' => $body['to'] ?? null
            ]);

            // Create text request
            $textRequest = new TextRequest(
                to: $body['to'] ?? '',
                text: $body['text'] ?? '',
                previewUrl: $body['previewUrl'] ?? false,
                notifyUrl: $body['notifyUrl'] ?? null
            );

            // Get provider from query params if specified
            $queryParams = $request->getQueryParams();
            $providerName = $queryParams['provider'] ?? null;

            // Send message
            $result = $this->messageService->sendText($textRequest, $providerName);

            if ($result->success) {
                $this->logger->info('Text message sent successfully', [
                    'message_id' => $result->messageId
                ]);

                return new JsonResponse([
                    'success' => true,
                    'data' => [
                        'message_id' => $result->messageId,
                        'status' => $result->status
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'SEND_FAILED',
                        'message' => $result->error ?? 'Failed to send message'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 500);
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Text validation error', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 400);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send text message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Internal server error: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * POST /api/messages/media
     * Envia media (imagem, documento, áudio, vídeo)
     */
    public function sendMedia(ServerRequestInterface $request): ResponseInterface
    {
        try {
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

            $this->logger->info('POST /api/messages/media - Sending media message', [
                'to' => $body['to'] ?? null,
                'media_type' => $body['mediaType'] ?? null
            ]);

            // Create media request
            $mediaRequest = new MediaRequest(
                to: $body['to'] ?? '',
                mediaType: $body['mediaType'] ?? '',
                mediaUrl: $body['mediaUrl'] ?? '',
                caption: $body['caption'] ?? null,
                filename: $body['filename'] ?? null,
                notifyUrl: $body['notifyUrl'] ?? null
            );

            // Get provider from query params if specified
            $queryParams = $request->getQueryParams();
            $providerName = $queryParams['provider'] ?? null;

            // Send message
            $result = $this->messageService->sendMedia($mediaRequest, $providerName);

            if ($result->success) {
                $this->logger->info('Media message sent successfully', [
                    'message_id' => $result->messageId
                ]);

                return new JsonResponse([
                    'success' => true,
                    'data' => [
                        'message_id' => $result->messageId,
                        'status' => $result->status
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'SEND_FAILED',
                        'message' => $result->error ?? 'Failed to send message'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 500);
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Media validation error', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 400);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send media message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Internal server error: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * POST /api/messages/interactive/buttons
     * Envia mensagem com botões interativos
     */
    public function sendInteractiveButtons(ServerRequestInterface $request): ResponseInterface
    {
        try {
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

            $this->logger->info('POST /api/messages/interactive/buttons - Sending interactive buttons', [
                'to' => $body['to'] ?? null,
                'button_count' => count($body['buttons'] ?? [])
            ]);

            // Create interactive buttons request
            $buttonsRequest = new InteractiveButtonsRequest(
                to: $body['to'] ?? '',
                bodyText: $body['bodyText'] ?? '',
                buttons: $body['buttons'] ?? [],
                headerText: $body['headerText'] ?? null,
                footerText: $body['footerText'] ?? null,
                notifyUrl: $body['notifyUrl'] ?? null
            );

            // Get provider from query params if specified
            $queryParams = $request->getQueryParams();
            $providerName = $queryParams['provider'] ?? null;

            // Send message
            $result = $this->messageService->sendInteractiveButtons($buttonsRequest, $providerName);

            if ($result->success) {
                $this->logger->info('Interactive buttons message sent successfully', [
                    'message_id' => $result->messageId
                ]);

                return new JsonResponse([
                    'success' => true,
                    'data' => [
                        'message_id' => $result->messageId,
                        'status' => $result->status
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'SEND_FAILED',
                        'message' => $result->error ?? 'Failed to send message'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 500);
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Interactive buttons validation error', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 400);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send interactive buttons message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Internal server error: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * POST /api/messages/interactive/list
     * Envia mensagem com lista interativa
     */
    public function sendInteractiveList(ServerRequestInterface $request): ResponseInterface
    {
        try {
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

            $this->logger->info('POST /api/messages/interactive/list - Sending interactive list', [
                'to' => $body['to'] ?? null,
                'section_count' => count($body['sections'] ?? [])
            ]);

            // Create interactive list request
            $listRequest = new InteractiveListRequest(
                to: $body['to'] ?? '',
                bodyText: $body['bodyText'] ?? '',
                buttonText: $body['buttonText'] ?? '',
                sections: $body['sections'] ?? [],
                headerText: $body['headerText'] ?? null,
                footerText: $body['footerText'] ?? null,
                notifyUrl: $body['notifyUrl'] ?? null
            );

            // Get provider from query params if specified
            $queryParams = $request->getQueryParams();
            $providerName = $queryParams['provider'] ?? null;

            // Send message
            $result = $this->messageService->sendInteractiveList($listRequest, $providerName);

            if ($result->success) {
                $this->logger->info('Interactive list message sent successfully', [
                    'message_id' => $result->messageId
                ]);

                return new JsonResponse([
                    'success' => true,
                    'data' => [
                        'message_id' => $result->messageId,
                        'status' => $result->status
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'SEND_FAILED',
                        'message' => $result->error ?? 'Failed to send message'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 500);
            }
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Interactive list validation error', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 400);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send interactive list message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Internal server error: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }

    /**
     * GET /api/messages/{messageId}/status
     * Consulta o estado de uma mensagem
     */
    public function getMessageStatus(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Extract messageId from route params
            $routeParams = $request->getAttribute('routeParams', []);
            $messageId = $routeParams['messageId'] ?? null;

            if (!$messageId) {
                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'MISSING_MESSAGE_ID',
                        'message' => 'Message ID is required'
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 400);
            }

            $this->logger->info('GET /api/messages/{messageId}/status - Querying message status', [
                'message_id' => $messageId
            ]);

            // Get provider from query params if specified
            $queryParams = $request->getQueryParams();
            $providerName = $queryParams['provider'] ?? null;

            // Query status
            $status = $this->messageService->getMessageStatus($messageId, $providerName);

            $this->logger->info('Message status retrieved', [
                'message_id' => $messageId,
                'status' => $status->status
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'message_id' => $status->messageId,
                    'status' => $status->status,
                    'to' => $status->to,
                    'sent_at' => $status->sentAt->format(\DateTimeInterface::ATOM),
                    'delivered_at' => $status->deliveredAt?->format(\DateTimeInterface::ATOM),
                    'read_at' => $status->readAt?->format(\DateTimeInterface::ATOM),
                    'error' => $status->error
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ]);
        } catch (\RuntimeException $e) {
            // Check if it's a "not found" error
            if (str_contains($e->getMessage(), 'not found') || str_contains($e->getMessage(), 'Not Found')) {
                $this->logger->warning('Message not found', [
                    'message_id' => $messageId ?? 'unknown',
                    'error' => $e->getMessage()
                ]);

                return new JsonResponse([
                    'success' => false,
                    'error' => [
                        'code' => 'MESSAGE_NOT_FOUND',
                        'message' => "Message not found: {$messageId}"
                    ],
                    'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
                ], 404);
            }

            $this->logger->error('Failed to query message status', [
                'message_id' => $messageId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'QUERY_STATUS_ERROR',
                    'message' => 'Failed to query message status: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to query message status', [
                'message_id' => $messageId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Internal server error: ' . $e->getMessage()
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 500);
        }
    }
}
