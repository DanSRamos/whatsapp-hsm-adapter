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
 * Infobip RCS (Rich Communication Services) provider implementation
 * 
 * Supports RCS messaging through Infobip's RCS API including:
 * - Text messages
 * - Rich cards with media
 * - Carousels
 * - Suggested actions and replies
 * - File attachments
 */
class InfobipRcsProvider implements MessagingProviderInterface
{
    private const API_VERSION = '2';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly array $config,
        private readonly LoggerInterface $logger
    ) {
        $this->validateConfig();
    }

    /**
     * Validate provider configuration
     */
    private function validateConfig(): void
    {
        $required = ['api_key', 'sender'];
        
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                throw new \InvalidArgumentException("Missing required config: {$key}");
            }
        }
    }

    public function getName(): string
    {
        return 'infobip-rcs';
    }

    /**
     * Send template message
     * Note: RCS doesn't use HSM templates like WhatsApp
     * This converts to a rich card message
     */
    public function sendTemplate(HSMRequest $request): ProviderSendResult
    {
        $this->logger->warning('RCS does not support HSM templates, converting to text message', [
            'template_name' => $request->templateName
        ]);

        // Convert template to text message
        $text = $request->templateName;
        if (!empty($request->parameters)) {
            $text .= ': ' . implode(', ', $request->parameters);
        }

        $textRequest = new TextRequest(
            to: $request->to,
            text: $text,
            notifyUrl: $request->notifyUrl
        );

        return $this->sendText($textRequest);
    }

    /**
     * Send text message
     */
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

        if ($request->notifyUrl) {
            $payload['messages'][0]['notifyUrl'] = $request->notifyUrl;
        }

        return $this->sendRequest(
            'POST',
            "/rcs/" . self::API_VERSION . "/message",
            $payload
        );
    }

    /**
     * Send media message (file)
     */
    public function sendMedia(MediaRequest $request): ProviderSendResult
    {
        $content = [
            'file' => [
                'url' => $request->mediaUrl
            ]
        ];

        if ($request->caption) {
            $content['text'] = $request->caption;
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
            "/rcs/" . self::API_VERSION . "/message",
            $payload
        );
    }

    /**
     * Send rich card with suggestions (buttons)
     */
    public function sendInteractiveButtons(InteractiveButtonsRequest $request): ProviderSendResult
    {
        $suggestions = [];
        
        foreach ($request->buttons as $button) {
            $suggestions[] = [
                'text' => $button['text'] ?? $button['title'],
                'postbackData' => $button['id']
            ];
        }

        $content = [
            'text' => $request->bodyText,
            'suggestions' => $suggestions
        ];

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
            '/rcs/' . self::API_VERSION . '/message/text',
            $payload
        );
    }

    /**
     * Send carousel (list of rich cards)
     */
    public function sendInteractiveList(InteractiveListRequest $request): ProviderSendResult
    {
        $cards = [];
        
        foreach ($request->sections as $section) {
            foreach ($section['items'] as $item) {
                $card = [
                    'title' => $item['title']
                ];

                if (!empty($item['description'])) {
                    $card['description'] = $item['description'];
                }

                if (!empty($item['image_url'])) {
                    $card['media'] = [
                        'file' => [
                            'url' => $item['image_url']
                        ],
                        'height' => 'MEDIUM'
                    ];
                }

                // Add suggestions (buttons) if provided
                if (!empty($item['buttons'])) {
                    $card['suggestions'] = array_map(function($button) {
                        return [
                            'text' => $button['text'] ?? $button['title'],
                            'postbackData' => $button['id'] ?? $button['payload']
                        ];
                    }, $item['buttons']);
                }

                $cards[] = $card;
            }
        }

        $content = [
            'cards' => $cards,
            'cardWidth' => 'MEDIUM'
        ];

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
            '/rcs/' . self::API_VERSION . '/message/carousel',
            $payload
        );
    }

    /**
     * Send rich card with media
     * 
     * @param string $to Recipient phone number
     * @param string $title Card title
     * @param string|null $description Card description
     * @param string|null $mediaUrl Media URL (image or video)
     * @param array $suggestions Array of suggestion buttons
     * @param string|null $notifyUrl Webhook URL for delivery reports
     * @return ProviderSendResult
     */
    public function sendRichCard(
        string $to,
        string $title,
        ?string $description = null,
        ?string $mediaUrl = null,
        array $suggestions = [],
        ?string $notifyUrl = null
    ): ProviderSendResult {
        $card = [
            'title' => $title
        ];

        if ($description) {
            $card['description'] = $description;
        }

        if ($mediaUrl) {
            $card['media'] = [
                'file' => [
                    'url' => $mediaUrl
                ],
                'height' => 'MEDIUM'
            ];
        }

        if (!empty($suggestions)) {
            $card['suggestions'] = array_map(function($suggestion) {
                if (is_string($suggestion)) {
                    return [
                        'text' => $suggestion,
                        'postbackData' => $suggestion
                    ];
                }
                return [
                    'text' => $suggestion['text'],
                    'postbackData' => $suggestion['postbackData'] ?? $suggestion['text']
                ];
            }, $suggestions);
        }

        $payload = [
            'messages' => [[
                'from' => $this->config['sender'],
                'to' => $to,
                'content' => $card
            ]]
        ];

        if ($notifyUrl) {
            $payload['messages'][0]['notifyUrl'] = $notifyUrl;
        }

        return $this->sendRequest(
            'POST',
            '/rcs/' . self::API_VERSION . '/message/card',
            $payload
        );
    }

    /**
     * Send carousel of rich cards
     * 
     * @param string $to Recipient phone number
     * @param array $cards Array of card objects
     * @param string $cardWidth Card width (SMALL, MEDIUM)
     * @param string|null $notifyUrl Webhook URL for delivery reports
     * @return ProviderSendResult
     */
    public function sendCarousel(
        string $to,
        array $cards,
        string $cardWidth = 'MEDIUM',
        ?string $notifyUrl = null
    ): ProviderSendResult {
        $content = [
            'cards' => $cards,
            'cardWidth' => $cardWidth
        ];

        $payload = [
            'messages' => [[
                'from' => $this->config['sender'],
                'to' => $to,
                'content' => $content
            ]]
        ];

        if ($notifyUrl) {
            $payload['messages'][0]['notifyUrl'] = $notifyUrl;
        }

        return $this->sendRequest(
            'POST',
            '/rcs/' . self::API_VERSION . '/message/carousel',
            $payload
        );
    }

    public function getMessageStatus(string $messageId): ProviderMessageStatus
    {
        $response = $this->sendRequest(
            'GET',
            "/rcs/" . self::API_VERSION . "/message/{$messageId}/status"
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
        // RCS doesn't use templates like WhatsApp
        $this->logger->info('RCS does not support templates');
        return [];
    }

    public function getTemplate(string $templateId): ?ProviderTemplate
    {
        // RCS doesn't use templates like WhatsApp
        $this->logger->info('RCS does not support templates');
        return null;
    }

    public function validateWebhook(ServerRequestInterface $request): bool
    {
        // Infobip RCS webhook validation using HMAC signature
        $signature = $request->getHeaderLine('X-Infobip-Signature');
        
        if (empty($signature)) {
            $this->logger->warning('RCS webhook signature header missing');
            return false;
        }

        if (!isset($this->config['webhook_secret'])) {
            $this->logger->warning('Webhook secret not configured for Infobip RCS');
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
        // RCS doesn't use templates
        throw new \RuntimeException('RCS does not support template updates');
    }

    /**
     * Send HTTP request to Infobip RCS API
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
            $this->logger->debug('Sending RCS request to Infobip', [
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
            $this->logger->error('Infobip RCS API request failed', [
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
     * Extract message content based on message type
     */
    private function extractMessageContent(array $message): mixed
    {
        $type = strtoupper($message['type'] ?? 'TEXT');

        return match($type) {
            'TEXT' => $message['text'] ?? '',
            'FILE' => [
                'url' => $message['url'] ?? $message['fileUrl'] ?? '',
                'filename' => $message['filename'] ?? null
            ],
            'SUGGESTION_RESPONSE' => [
                'postbackData' => $message['postbackData'] ?? '',
                'text' => $message['text'] ?? ''
            ],
            default => $message
        };
    }
}

