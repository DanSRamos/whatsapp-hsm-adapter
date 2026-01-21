<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers\Infobip;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Models\IncomingMessage;
use WhatsApp\Adapter\Models\Requests\HSMRequest;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Models\Requests\MediaRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;
use WhatsApp\Adapter\Providers\Models\DeliveryReport;
use WhatsApp\Adapter\Providers\Models\ProviderMessageStatus;
use WhatsApp\Adapter\Providers\Models\ProviderSendResult;
use WhatsApp\Adapter\Providers\Models\ProviderTemplate;
use WhatsApp\Adapter\Providers\Models\TemplateUpdate;
use WhatsApp\Adapter\Providers\MessagingProviderInterface;

/**
 * Infobip WhatsApp provider implementation
 */
class InfobipProvider implements MessagingProviderInterface
{
    private const API_VERSION = '1';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly array $config,
        private readonly LoggerInterface $logger
    ) {}

    public function getName(): string
    {
        return 'infobip';
    }

    public function sendTemplate(HSMRequest $request): ProviderSendResult
    {
        $payload = [
            'messages' => [[
                'from' => $this->config['sender'],
                'to' => $request->to,
                'content' => [
                    'templateName' => $request->templateName,
                    'templateData' => [
                        'body' => [
                            'placeholders' => $request->parameters
                        ]
                    ],
                    'language' => $request->templateLanguage
                ]
            ]]
        ];

        if ($request->notifyUrl) {
            $payload['messages'][0]['notifyUrl'] = $request->notifyUrl;
        }

        return $this->sendRequest(
            'POST',
            "/whatsapp/" . self::API_VERSION . "/message/template",
            $payload
        );
    }

    public function sendText(TextRequest $request): ProviderSendResult
    {
        $payload = [
            'messages' => [[
                'from' => $this->config['sender'],
                'to' => $request->to,
                'content' => [
                    'text' => $request->text
                ]
            ]]
        ];

        if ($request->previewUrl) {
            $payload['messages'][0]['content']['previewUrl'] = true;
        }

        if ($request->notifyUrl) {
            $payload['messages'][0]['notifyUrl'] = $request->notifyUrl;
        }

        return $this->sendRequest(
            'POST',
            "/whatsapp/" . self::API_VERSION . "/message/text",
            $payload
        );
    }

    public function sendMedia(MediaRequest $request): ProviderSendResult
    {
        $content = [
            'mediaUrl' => $request->mediaUrl
        ];

        if ($request->caption) {
            $content['caption'] = $request->caption;
        }

        if ($request->filename) {
            $content['filename'] = $request->filename;
        }

        $payload = [
            'messages' => [[
                'from' => $this->config['sender'],
                'to' => $request->to,
                'content' => $content
            ]]
        ];

        if ($request->notifyUrl) {
            $payload['messages'][0]['notifyUrl'] = $request->notifyUrl;
        }

        $endpoint = match($request->mediaType) {
            'image' => "/whatsapp/" . self::API_VERSION . "/message/image",
            'document' => "/whatsapp/" . self::API_VERSION . "/message/document",
            'audio' => "/whatsapp/" . self::API_VERSION . "/message/audio",
            'video' => "/whatsapp/" . self::API_VERSION . "/message/video",
            default => throw new \InvalidArgumentException("Unsupported media type: {$request->mediaType}")
        };

        return $this->sendRequest('POST', $endpoint, $payload);
    }

    public function sendInteractiveButtons(InteractiveButtonsRequest $request): ProviderSendResult
    {
        $content = [
            'body' => [
                'text' => $request->bodyText
            ],
            'action' => [
                'buttons' => array_map(fn($button) => [
                    'type' => $button['type'] ?? 'REPLY',
                    'id' => $button['id'],
                    'title' => $button['title']
                ], $request->buttons)
            ]
        ];

        if ($request->headerText) {
            $content['header'] = ['type' => 'TEXT', 'text' => $request->headerText];
        }

        if ($request->footerText) {
            $content['footer'] = ['text' => $request->footerText];
        }

        $payload = [
            'messages' => [[
                'from' => $this->config['sender'],
                'to' => $request->to,
                'content' => $content
            ]]
        ];

        if ($request->notifyUrl) {
            $payload['messages'][0]['notifyUrl'] = $request->notifyUrl;
        }

        return $this->sendRequest(
            'POST',
            "/whatsapp/" . self::API_VERSION . "/message/interactive/buttons",
            $payload
        );
    }

    public function sendInteractiveList(InteractiveListRequest $request): ProviderSendResult
    {
        $content = [
            'body' => [
                'text' => $request->bodyText
            ],
            'action' => [
                'title' => $request->buttonText,
                'sections' => $request->sections
            ]
        ];

        if ($request->headerText) {
            $content['header'] = ['type' => 'TEXT', 'text' => $request->headerText];
        }

        if ($request->footerText) {
            $content['footer'] = ['text' => $request->footerText];
        }

        $payload = [
            'messages' => [[
                'from' => $this->config['sender'],
                'to' => $request->to,
                'content' => $content
            ]]
        ];

        if ($request->notifyUrl) {
            $payload['messages'][0]['notifyUrl'] = $request->notifyUrl;
        }

        return $this->sendRequest(
            'POST',
            "/whatsapp/" . self::API_VERSION . "/message/interactive/list",
            $payload
        );
    }

    public function getMessageStatus(string $messageId): ProviderMessageStatus
    {
        $response = $this->sendRequest(
            'GET',
            "/whatsapp/" . self::API_VERSION . "/message/{$messageId}/status"
        );

        if (!$response->success || !$response->details) {
            throw new \RuntimeException("Failed to get message status: " . ($response->error ?? 'Unknown error'));
        }

        $data = $response->details;
        
        return new ProviderMessageStatus(
            messageId: $messageId,
            status: $data['status'] ?? 'UNKNOWN',
            to: $data['to'] ?? '',
            sentAt: isset($data['sentAt']) ? new \DateTimeImmutable($data['sentAt']) : new \DateTimeImmutable(),
            deliveredAt: isset($data['deliveredAt']) ? new \DateTimeImmutable($data['deliveredAt']) : null,
            readAt: isset($data['readAt']) ? new \DateTimeImmutable($data['readAt']) : null,
            error: $data['error'] ?? null
        );
    }

    public function getTemplates(): array
    {
        $response = $this->sendRequest(
            'GET',
            "/whatsapp/" . self::API_VERSION . "/senders/{$this->config['sender']}/templates"
        );

        if (!$response->success || !$response->details) {
            $this->logger->error('Failed to get templates', ['error' => $response->error]);
            return [];
        }

        $templates = [];
        foreach ($response->details['templates'] ?? [] as $templateData) {
            $templates[] = $this->mapToProviderTemplate($templateData);
        }

        return $templates;
    }

    public function getTemplate(string $templateId): ?ProviderTemplate
    {
        $response = $this->sendRequest(
            'GET',
            "/whatsapp/" . self::API_VERSION . "/senders/{$this->config['sender']}/templates/{$templateId}"
        );

        if (!$response->success || !$response->details) {
            return null;
        }

        return $this->mapToProviderTemplate($response->details);
    }

    public function validateWebhook(ServerRequestInterface $request): bool
    {
        // Infobip webhook validation using HMAC signature
        $signature = $request->getHeaderLine('X-Infobip-Signature');
        
        if (empty($signature)) {
            return false;
        }

        if (!isset($this->config['webhook_secret'])) {
            $this->logger->warning('Webhook secret not configured for Infobip');
            return false;
        }

        $body = (string) $request->getBody();
        $expectedSignature = hash_hmac('sha256', $body, $this->config['webhook_secret']);

        return hash_equals($expectedSignature, $signature);
    }

    public function processDeliveryReport(array $payload): DeliveryReport
    {
        $result = $payload['results'][0] ?? $payload;
        
        return new DeliveryReport(
            messageId: $result['messageId'] ?? '',
            status: $result['status']['groupName'] ?? $result['status'] ?? 'UNKNOWN',
            timestamp: isset($result['doneAt']) 
                ? new \DateTimeImmutable($result['doneAt']) 
                : new \DateTimeImmutable(),
            error: $result['error']['description'] ?? null,
            metadata: $result
        );
    }

    public function processIncomingMessage(array $payload): IncomingMessage
    {
        $result = $payload['results'][0] ?? $payload;
        $message = $result['message'] ?? $result;
        
        return new IncomingMessage(
            messageId: $result['messageId'] ?? '',
            from: $result['from'] ?? '',
            to: $result['to'] ?? '',
            type: $message['type'] ?? 'TEXT',
            content: $this->extractMessageContent($message),
            receivedAt: isset($result['receivedAt']) 
                ? new \DateTimeImmutable($result['receivedAt']) 
                : new \DateTimeImmutable(),
            contextMessageId: $result['context']['messageId'] ?? null
        );
    }

    public function processTemplateUpdate(array $payload): TemplateUpdate
    {
        $template = $payload['template'] ?? null;
        
        return new TemplateUpdate(
            templateId: $payload['templateId'] ?? '',
            action: $payload['action'] ?? 'updated',
            timestamp: isset($payload['timestamp']) 
                ? new \DateTimeImmutable($payload['timestamp']) 
                : new \DateTimeImmutable(),
            template: $template ? $this->mapToProviderTemplate($template) : null,
            reason: $payload['reason'] ?? null
        );
    }

    /**
     * Send HTTP request to Infobip API
     */
    private function sendRequest(string $method, string $endpoint, ?array $payload = null): ProviderSendResult
    {
        $baseUrl = $this->config['base_url'] ?? 'https://api.infobip.com';
        $url = $baseUrl . $endpoint;

        $options = [
            'headers' => [
                'Authorization' => 'App ' . $this->config['api_key'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];

        if ($payload !== null) {
            $options['json'] = $payload;
        }

        try {
            $this->logger->debug('Sending request to Infobip', [
                'method' => $method,
                'endpoint' => $endpoint
            ]);

            $response = $this->httpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $messageId = $body['messages'][0]['messageId'] ?? $body['messageId'] ?? null;
                $status = $body['messages'][0]['status']['groupName'] ?? 'SENT';

                return new ProviderSendResult(
                    success: true,
                    messageId: $messageId,
                    status: $status,
                    details: $body
                );
            }

            return new ProviderSendResult(
                success: false,
                error: $body['requestError']['serviceException']['text'] ?? 'Unknown error',
                details: $body
            );

        } catch (GuzzleException $e) {
            $this->logger->error('Infobip API request failed', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint
            ]);

            return new ProviderSendResult(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    /**
     * Map Infobip template data to ProviderTemplate
     */
    private function mapToProviderTemplate(array $data): ProviderTemplate
    {
        return new ProviderTemplate(
            id: $data['id'] ?? '',
            name: $data['name'] ?? '',
            language: $data['language'] ?? '',
            status: $data['status'] ?? 'UNKNOWN',
            category: $data['category'] ?? '',
            components: $data['structure']['body'] ?? $data['components'] ?? [],
            rejectionReason: $data['rejectionReason'] ?? null
        );
    }

    /**
     * Check if a phone number has WhatsApp
     *
     * @param string $phoneNumber Phone number in E.164 format
     * @return \WhatsApp\Adapter\Services\WhatsAppNumberValidationResult
     */
    public function checkWhatsAppNumber(string $phoneNumber): \WhatsApp\Adapter\Services\WhatsAppNumberValidationResult
    {
        $this->logger->debug('Checking WhatsApp number with Infobip', [
            'phone_number' => substr($phoneNumber, 0, 4) . '***' . substr($phoneNumber, -2)
        ]);

        try {
            // Infobip endpoint to check if number has WhatsApp
            $response = $this->sendRequest(
                'GET',
                "/whatsapp/" . self::API_VERSION . "/contacts/{$phoneNumber}"
            );

            if (!$response->success) {
                // If contact not found, number doesn't have WhatsApp
                if (isset($response->details['requestError']['serviceException']['messageId']) &&
                    $response->details['requestError']['serviceException']['messageId'] === 'NOT_FOUND') {
                    
                    return new \WhatsApp\Adapter\Services\WhatsAppNumberValidationResult(
                        phoneNumber: $phoneNumber,
                        hasWhatsApp: false,
                        provider: $this->getName(),
                        metadata: ['reason' => 'Contact not found in WhatsApp']
                    );
                }

                // Other errors
                return new \WhatsApp\Adapter\Services\WhatsAppNumberValidationResult(
                    phoneNumber: $phoneNumber,
                    hasWhatsApp: null,
                    error: $response->error,
                    provider: $this->getName()
                );
            }

            // Number has WhatsApp
            $details = $response->details;
            $accountType = $details['type'] ?? 'consumer'; // consumer or business

            return new \WhatsApp\Adapter\Services\WhatsAppNumberValidationResult(
                phoneNumber: $phoneNumber,
                hasWhatsApp: true,
                accountType: $accountType,
                provider: $this->getName(),
                metadata: $details
            );

        } catch (\Exception $e) {
            $this->logger->error('Failed to check WhatsApp number', [
                'error' => $e->getMessage(),
                'phone_number' => substr($phoneNumber, 0, 4) . '***' . substr($phoneNumber, -2)
            ]);

            return new \WhatsApp\Adapter\Services\WhatsAppNumberValidationResult(
                phoneNumber: $phoneNumber,
                hasWhatsApp: null,
                error: $e->getMessage(),
                provider: $this->getName()
            );
        }
    }

    /**
     * Extract message content based on message type
     */
    private function extractMessageContent(array $message): mixed
    {
        $type = strtoupper($message['type'] ?? 'TEXT');

        return match($type) {
            'TEXT' => $message['text'] ?? '',
            'IMAGE', 'DOCUMENT', 'AUDIO', 'VIDEO' => [
                'url' => $message['url'] ?? $message['mediaUrl'] ?? '',
                'caption' => $message['caption'] ?? null,
                'filename' => $message['filename'] ?? null
            ],
            'BUTTON' => [
                'buttonId' => $message['payload'] ?? '',
                'text' => $message['text'] ?? ''
            ],
            'LIST' => [
                'listId' => $message['listReply']['id'] ?? '',
                'title' => $message['listReply']['title'] ?? '',
                'description' => $message['listReply']['description'] ?? ''
            ],
            default => $message
        };
    }
}
