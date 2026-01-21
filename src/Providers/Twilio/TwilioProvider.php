<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Providers\Twilio;

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
 * Twilio WhatsApp provider implementation
 */
class TwilioProvider implements MessagingProviderInterface
{
    private const API_VERSION = '2010-04-01';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly array $config,
        private readonly LoggerInterface $logger
    ) {}

    public function getName(): string
    {
        return 'twilio';
    }

    public function sendTemplate(HSMRequest $request): ProviderSendResult
    {
        // Twilio uses ContentSid for templates
        $params = [
            'From' => 'whatsapp:' . $this->config['sender'],
            'To' => 'whatsapp:' . $request->to,
            'ContentSid' => $request->templateName, // Twilio uses SID instead of name
            'ContentVariables' => json_encode(array_combine(
                array_map(fn($i) => (string)($i + 1), array_keys($request->parameters)),
                $request->parameters
            ))
        ];

        if ($request->notifyUrl) {
            $params['StatusCallback'] = $request->notifyUrl;
        }

        return $this->sendRequest('POST', '/Messages.json', $params);
    }

    public function sendText(TextRequest $request): ProviderSendResult
    {
        $params = [
            'From' => 'whatsapp:' . $this->config['sender'],
            'To' => 'whatsapp:' . $request->to,
            'Body' => $request->text
        ];

        if ($request->notifyUrl) {
            $params['StatusCallback'] = $request->notifyUrl;
        }

        return $this->sendRequest('POST', '/Messages.json', $params);
    }

    public function sendMedia(MediaRequest $request): ProviderSendResult
    {
        $params = [
            'From' => 'whatsapp:' . $this->config['sender'],
            'To' => 'whatsapp:' . $request->to,
            'MediaUrl' => $request->mediaUrl
        ];

        if ($request->caption) {
            $params['Body'] = $request->caption;
        }

        if ($request->notifyUrl) {
            $params['StatusCallback'] = $request->notifyUrl;
        }

        return $this->sendRequest('POST', '/Messages.json', $params);
    }

    public function sendInteractiveButtons(InteractiveButtonsRequest $request): ProviderSendResult
    {
        // Twilio doesn't support interactive buttons in the same way as Infobip
        // This is a simplified implementation
        throw new \RuntimeException('Interactive buttons not fully supported by Twilio WhatsApp API');
    }

    public function sendInteractiveList(InteractiveListRequest $request): ProviderSendResult
    {
        // Twilio doesn't support interactive lists in the same way as Infobip
        // This is a simplified implementation
        throw new \RuntimeException('Interactive lists not fully supported by Twilio WhatsApp API');
    }

    public function getMessageStatus(string $messageId): ProviderMessageStatus
    {
        $response = $this->sendRequest('GET', "/Messages/{$messageId}.json");

        if (!$response->success || !$response->details) {
            throw new \RuntimeException("Failed to get message status: " . ($response->error ?? 'Unknown error'));
        }

        $data = $response->details;
        
        return new ProviderMessageStatus(
            messageId: $messageId,
            status: $this->mapTwilioStatus($data['status'] ?? 'unknown'),
            to: str_replace('whatsapp:', '', $data['to'] ?? ''),
            sentAt: isset($data['date_created']) ? new \DateTimeImmutable($data['date_created']) : new \DateTimeImmutable(),
            deliveredAt: isset($data['date_sent']) ? new \DateTimeImmutable($data['date_sent']) : null,
            readAt: null, // Twilio doesn't provide read receipts in the same way
            error: $data['error_message'] ?? null
        );
    }

    public function getTemplates(): array
    {
        // Twilio uses Content API for templates
        $response = $this->sendRequest('GET', '/Content');

        if (!$response->success || !$response->details) {
            $this->logger->error('Failed to get templates', ['error' => $response->error]);
            return [];
        }

        $templates = [];
        foreach ($response->details['contents'] ?? [] as $contentData) {
            $templates[] = $this->mapToProviderTemplate($contentData);
        }

        return $templates;
    }

    public function getTemplate(string $templateId): ?ProviderTemplate
    {
        $response = $this->sendRequest('GET', "/Content/{$templateId}");

        if (!$response->success || !$response->details) {
            return null;
        }

        return $this->mapToProviderTemplate($response->details);
    }

    public function validateWebhook(ServerRequestInterface $request): bool
    {
        // Twilio webhook validation using X-Twilio-Signature
        $signature = $request->getHeaderLine('X-Twilio-Signature');
        
        if (empty($signature)) {
            return false;
        }

        if (!isset($this->config['auth_token'])) {
            $this->logger->warning('Auth token not configured for Twilio');
            return false;
        }

        // Twilio signature validation
        $url = (string) $request->getUri();
        $params = $request->getParsedBody() ?? [];
        
        ksort($params);
        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }

        $expectedSignature = base64_encode(hash_hmac('sha1', $data, $this->config['auth_token'], true));

        return hash_equals($expectedSignature, $signature);
    }

    public function processDeliveryReport(array $payload): DeliveryReport
    {
        return new DeliveryReport(
            messageId: $payload['MessageSid'] ?? $payload['SmsSid'] ?? '',
            status: $this->mapTwilioStatus($payload['MessageStatus'] ?? $payload['SmsStatus'] ?? 'unknown'),
            timestamp: new \DateTimeImmutable(),
            error: $payload['ErrorMessage'] ?? null,
            metadata: $payload
        );
    }

    public function processIncomingMessage(array $payload): IncomingMessage
    {
        $from = str_replace('whatsapp:', '', $payload['From'] ?? '');
        $to = str_replace('whatsapp:', '', $payload['To'] ?? '');
        
        return new IncomingMessage(
            messageId: $payload['MessageSid'] ?? $payload['SmsSid'] ?? '',
            from: $from,
            to: $to,
            type: isset($payload['MediaUrl0']) ? 'media' : 'text',
            content: $this->extractMessageContent($payload),
            receivedAt: new \DateTimeImmutable(),
            contextMessageId: null
        );
    }

    public function processTemplateUpdate(array $payload): TemplateUpdate
    {
        return new TemplateUpdate(
            templateId: $payload['ContentSid'] ?? '',
            action: $payload['Action'] ?? 'updated',
            timestamp: new \DateTimeImmutable(),
            template: null,
            reason: $payload['Reason'] ?? null
        );
    }

    /**
     * Send HTTP request to Twilio API
     */
    private function sendRequest(string $method, string $endpoint, ?array $params = null): ProviderSendResult
    {
        $accountSid = $this->config['account_sid'];
        $authToken = $this->config['auth_token'];
        $baseUrl = $this->config['base_url'] ?? 'https://api.twilio.com';
        
        $url = "{$baseUrl}/" . self::API_VERSION . "/Accounts/{$accountSid}{$endpoint}";

        $options = [
            'auth' => [$accountSid, $authToken],
            'headers' => [
                'Accept' => 'application/json'
            ]
        ];

        if ($params !== null) {
            if ($method === 'POST') {
                $options['form_params'] = $params;
            } else {
                $options['query'] = $params;
            }
        }

        try {
            $this->logger->debug('Sending request to Twilio', [
                'method' => $method,
                'endpoint' => $endpoint
            ]);

            $response = $this->httpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $messageId = $body['sid'] ?? null;
                $status = $this->mapTwilioStatus($body['status'] ?? 'sent');

                return new ProviderSendResult(
                    success: true,
                    messageId: $messageId,
                    status: $status,
                    details: $body
                );
            }

            return new ProviderSendResult(
                success: false,
                error: $body['message'] ?? 'Unknown error',
                details: $body
            );

        } catch (GuzzleException $e) {
            $this->logger->error('Twilio API request failed', [
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
     * Map Twilio status to standard status
     */
    private function mapTwilioStatus(string $twilioStatus): string
    {
        return match(strtolower($twilioStatus)) {
            'queued', 'accepted' => 'PENDING',
            'sending', 'sent' => 'SENT',
            'delivered' => 'DELIVERED',
            'read' => 'READ',
            'failed', 'undelivered' => 'FAILED',
            default => 'UNKNOWN'
        };
    }

    /**
     * Map Twilio content data to ProviderTemplate
     */
    private function mapToProviderTemplate(array $data): ProviderTemplate
    {
        return new ProviderTemplate(
            id: $data['sid'] ?? '',
            name: $data['friendly_name'] ?? '',
            language: $data['language'] ?? 'en',
            status: $data['approval_status'] ?? 'approved',
            category: $data['types']['twilio/text']['body'] ?? 'UTILITY',
            components: $data['types'] ?? [],
            rejectionReason: $data['rejection_reason'] ?? null
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
        $this->logger->debug('Checking WhatsApp number with Twilio', [
            'phone_number' => substr($phoneNumber, 0, 4) . '***' . substr($phoneNumber, -2)
        ]);

        try {
            // Twilio doesn't have a direct endpoint to check WhatsApp availability
            // We use the Lookup API with WhatsApp carrier information
            $accountSid = $this->config['account_sid'];
            $authToken = $this->config['auth_token'];
            $baseUrl = 'https://lookups.twilio.com';
            
            // Remove + from phone number for Twilio Lookup API
            $cleanNumber = ltrim($phoneNumber, '+');
            $url = "{$baseUrl}/v2/PhoneNumbers/{$cleanNumber}?Fields=line_type_intelligence";

            $options = [
                'auth' => [$accountSid, $authToken],
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ];

            $response = $this->httpClient->request('GET', $url, $options);
            $statusCode = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                // Check if WhatsApp is available
                // Twilio Lookup doesn't directly tell if number has WhatsApp
                // We can only verify if it's a valid mobile number
                $lineType = $body['line_type_intelligence']['type'] ?? null;
                $carrier = $body['line_type_intelligence']['carrier_name'] ?? null;

                // If it's a mobile number, it potentially has WhatsApp
                // But we can't be 100% certain without actually trying to send
                $isMobile = in_array($lineType, ['mobile', 'voip']);

                return new \WhatsApp\Adapter\Services\WhatsAppNumberValidationResult(
                    phoneNumber: $phoneNumber,
                    hasWhatsApp: $isMobile ? null : false, // null = uncertain, false = definitely not
                    accountType: $isMobile ? 'unknown' : null,
                    provider: $this->getName(),
                    metadata: [
                        'line_type' => $lineType,
                        'carrier' => $carrier,
                        'note' => 'Twilio cannot definitively confirm WhatsApp availability. This is based on line type.'
                    ]
                );
            }

            return new \WhatsApp\Adapter\Services\WhatsAppNumberValidationResult(
                phoneNumber: $phoneNumber,
                hasWhatsApp: null,
                error: $body['message'] ?? 'Failed to lookup phone number',
                provider: $this->getName()
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
     * Extract message content from Twilio payload
     */
    private function extractMessageContent(array $payload): mixed
    {
        if (isset($payload['MediaUrl0'])) {
            return [
                'url' => $payload['MediaUrl0'],
                'contentType' => $payload['MediaContentType0'] ?? null,
                'caption' => $payload['Body'] ?? null
            ];
        }

        return $payload['Body'] ?? '';
    }
}
