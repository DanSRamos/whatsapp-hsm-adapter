# Design Document: Meta Messaging Integration (Instagram + Facebook Messenger)

## Overview

Este documento descreve o design técnico para integração do Instagram Messaging API e Facebook Messenger API ao WhatsApp HSM Adapter. A solução aproveita a arquitetura modular existente baseada em providers, adicionando Instagram e Messenger como um único provider Meta sem modificar a estrutura core do sistema.

**Nota Importante**: Instagram e Facebook Messenger usam a mesma Messenger Platform API da Meta, compartilhando endpoints, autenticação e estrutura de webhooks. Portanto, serão implementados como um único provider que suporta ambas as plataformas.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Admin Panel                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │WhatsApp  │  │Instagram │  │Messenger │  │ Messages │   │
│  │Interface │  │Interface │  │Interface │  │  Viewer  │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                     MessageService                           │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  sendText() │ sendMedia() │ sendInteractive()        │   │
│  │  processIncomingMessage() │ processDeliveryReport()  │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              WhatsAppProviderFactory                         │
│  ┌──────────────┐              ┌──────────────┐            │
│  │   Infobip    │              │     Meta     │            │
│  │   Provider   │              │   Provider   │            │
│  │  (WhatsApp)  │              │(IG+Messenger)│            │
│  └──────────────┘              └──────────────┘            │
└─────────────────────────────────────────────────────────────┘
         │                                │
         ▼                                ▼
┌──────────────────┐          ┌──────────────────────┐
│  Infobip API     │          │  Meta Graph API      │
│  (WhatsApp)      │          │  (IG + Messenger)    │
└──────────────────┘          └──────────────────────┘
```

### Provider Architecture

O Meta Provider suporta Instagram e Messenger com código compartilhado:

```
src/Providers/Meta/
├── MetaProvider.php                # Provider principal (IG + Messenger)
├── MetaWebhookHandler.php          # Processamento de webhooks
├── MetaMessageFormatter.php        # Formatação de mensagens
├── MetaPlatformDetector.php        # Detecta Instagram vs Messenger
└── Models/
    ├── MetaRecipient.php           # IGSID ou PSID
    ├── MetaAttachment.php          # Anexos
    └── MetaQuickReply.php          # Quick replies
```

## Components and Interfaces

### 1. InstagramProvider

Implementa `WhatsAppProviderInterface` para Instagram Messaging API.

```php
class InstagramProvider implements WhatsAppProviderInterface
{
    private const API_VERSION = 'v21.0';
    private const BASE_URL = 'https://graph.facebook.com';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly array $config,
        private readonly LoggerInterface $logger
    ) {}

    public function getName(): string
    {
        return 'instagram';
    }

    public function sendText(TextRequest $request): ProviderSendResult
    {
        // POST /{page-id}/messages
        // payload: { recipient: { id: IGSID }, message: { text: "..." } }
    }

    public function sendMedia(MediaRequest $request): ProviderSendResult
    {
        // Suporta: image, video, audio, file
        // Múltiplas imagens: attachments array (até 10)
    }

    public function sendInteractiveButtons(InteractiveButtonsRequest $request): ProviderSendResult
    {
        // Converte para Quick Replies
        // payload: { message: { text: "...", quick_replies: [...] } }
    }

    public function sendInteractiveList(InteractiveListRequest $request): ProviderSendResult
    {
        // Converte para Generic Template
        // payload: { message: { attachment: { type: "template", payload: {...} } } }
    }

    public function sendTemplate(HSMRequest $request): ProviderSendResult
    {
        // Instagram não suporta templates HSM
        // Converte para texto simples substituindo placeholders
        // Adiciona warning no log
    }

    public function getMessageStatus(string $messageId): ProviderMessageStatus
    {
        // Instagram não tem endpoint direto
        // Consulta repositório local (atualizado via webhook)
    }

    public function validateWebhook(ServerRequestInterface $request): bool
    {
        // Valida X-Hub-Signature-256 usando App Secret
    }

    public function processIncomingMessage(array $payload): IncomingMessage
    {
        // Processa webhook de mensagem recebida
    }

    public function processDeliveryReport(array $payload): DeliveryReport
    {
        // Processa webhook de status de entrega
    }
}
```

### 2. InstagramWebhookHandler

Processa webhooks específicos do Instagram.

```php
class InstagramWebhookHandler
{
    public function validateSignature(
        string $body,
        string $signature,
        string $appSecret
    ): bool {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $body, $appSecret);
        return hash_equals($expectedSignature, $signature);
    }

    public function handleVerification(array $params, string $verifyToken): ?string
    {
        if ($params['hub_verify_token'] === $verifyToken) {
            return $params['hub_challenge'];
        }
        return null;
    }

    public function extractMessages(array $payload): array
    {
        // Extrai mensagens do payload do webhook
        // entry[].messaging[].message
    }

    public function extractDeliveryReports(array $payload): array
    {
        // Extrai delivery reports do payload
        // entry[].messaging[].delivery
    }
}
```

### 3. InstagramMessageFormatter

Formata mensagens para o formato esperado pela API.

```php
class InstagramMessageFormatter
{
    public function formatTextMessage(string $igsid, string $text): array
    {
        return [
            'recipient' => ['id' => $igsid],
            'message' => ['text' => $text]
        ];
    }

    public function formatMediaMessage(
        string $igsid,
        string $type,
        string $url
    ): array {
        return [
            'recipient' => ['id' => $igsid],
            'message' => [
                'attachment' => [
                    'type' => $type,
                    'payload' => ['url' => $url]
                ]
            ]
        ];
    }

    public function formatMultipleImages(string $igsid, array $urls): array
    {
        $attachments = array_map(fn($url) => [
            'type' => 'image',
            'payload' => ['url' => $url]
        ], $urls);

        return [
            'recipient' => ['id' => $igsid],
            'message' => ['attachments' => $attachments]
        ];
    }

    public function formatQuickReplies(
        string $igsid,
        string $text,
        array $buttons
    ): array {
        $quickReplies = array_map(fn($button) => [
            'content_type' => 'text',
            'title' => $button['title'],
            'payload' => $button['id']
        ], $buttons);

        return [
            'recipient' => ['id' => $igsid],
            'message' => [
                'text' => $text,
                'quick_replies' => $quickReplies
            ]
        ];
    }

    public function convertTemplateToText(
        string $templateText,
        array $parameters
    ): string {
        // Substitui {{1}}, {{2}}, etc. pelos parâmetros
        $text = $templateText;
        foreach ($parameters as $index => $value) {
            $placeholder = '{{' . ($index + 1) . '}}';
            $text = str_replace($placeholder, $value, $text);
        }
        return $text;
    }
}
```

### 4. Models

#### InstagramRecipient

```php
class InstagramRecipient
{
    public function __construct(
        public readonly string $igsid
    ) {
        $this->validateIGSID($igsid);
    }

    private function validateIGSID(string $igsid): void
    {
        // Valida formato do IGSID
        if (empty($igsid) || !is_numeric($igsid)) {
            throw new \InvalidArgumentException('Invalid IGSID format');
        }
    }
}
```

#### InstagramAttachment

```php
class InstagramAttachment
{
    public function __construct(
        public readonly string $type,
        public readonly string $url,
        public readonly ?int $size = null
    ) {
        $this->validateType($type);
        $this->validateSize($type, $size);
    }

    private function validateType(string $type): void
    {
        $validTypes = ['image', 'video', 'audio', 'file'];
        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid attachment type: $type");
        }
    }

    private function validateSize(string $type, ?int $size): void
    {
        if ($size === null) return;

        $limits = [
            'image' => 8 * 1024 * 1024,  // 8MB
            'video' => 25 * 1024 * 1024, // 25MB
            'audio' => 25 * 1024 * 1024, // 25MB
            'file' => 25 * 1024 * 1024   // 25MB
        ];

        if ($size > $limits[$type]) {
            throw new \InvalidArgumentException(
                "Attachment size exceeds limit for type $type"
            );
        }
    }
}
```

#### InstagramQuickReply

```php
class InstagramQuickReply
{
    public function __construct(
        public readonly string $title,
        public readonly string $payload,
        public readonly string $contentType = 'text'
    ) {
        $this->validateTitle($title);
    }

    private function validateTitle(string $title): void
    {
        if (strlen($title) > 20) {
            throw new \InvalidArgumentException(
                'Quick reply title must be 20 characters or less'
            );
        }
    }
}
```

## Data Models

### Configuration

```php
// config/instagram.php
return [
    'page_access_token' => env('INSTAGRAM_PAGE_ACCESS_TOKEN'),
    'app_id' => env('INSTAGRAM_APP_ID'),
    'app_secret' => env('INSTAGRAM_APP_SECRET'),
    'page_id' => env('INSTAGRAM_PAGE_ID'),
    'verify_token' => env('INSTAGRAM_VERIFY_TOKEN'),
    'api_version' => env('INSTAGRAM_API_VERSION', 'v21.0'),
    'base_url' => 'https://graph.facebook.com',

    'limits' => [
        'quick_replies' => 13,
        'images_per_message' => 10,
        'image_size' => 8 * 1024 * 1024,
        'video_size' => 25 * 1024 * 1024,
        'audio_size' => 25 * 1024 * 1024,
        'file_size' => 25 * 1024 * 1024,
    ],

    'messaging_window' => 24 * 60 * 60, // 24 hours in seconds
];
```

### Message Metadata

Mensagens Instagram terão metadata específico:

```php
[
    'provider' => 'instagram',
    'igsid' => '1234567890',
    'page_id' => '9876543210',
    'messaging_window_expires_at' => '2024-01-17T10:30:00Z',
    'conversation_id' => 'conv_abc123',
]
```

## Correctness Properties

_A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees._

### Property 1: Authentication Header Consistency

_For any_ request to Instagram API, the Authorization header should contain a valid Page Access Token in the format "Bearer {token}".
**Validates: Requirements 1.1, 1.7**

### Property 2: IGSID Format Validation

_For any_ recipient IGSID, it should be a non-empty numeric string.
**Validates: Requirements 2.2**

### Property 3: Media Size Validation

_For any_ media attachment, the file size should not exceed the limit for its type (8MB for images, 25MB for video/audio/file).
**Validates: Requirements 3.5, 3.6, 3.7**

### Property 4: Multiple Images Limit

_For any_ message with multiple images, the number of images should not exceed 10.
**Validates: Requirements 3.8**

### Property 5: Quick Replies Limit

_For any_ message with quick replies, the number of quick replies should not exceed 13.
**Validates: Requirements 4.2**

### Property 6: Template Placeholder Substitution

_For any_ template text with placeholders {{1}}, {{2}}, etc., after substitution with parameters, no placeholders should remain in the text.
**Validates: Requirements 5.2, 5.5**

### Property 7: Webhook Signature Validation

_For any_ webhook request, if the X-Hub-Signature-256 header matches the HMAC SHA-256 of the body using App Secret, the webhook should be accepted; otherwise rejected.
**Validates: Requirements 6.1, 6.2, 6.3**

### Property 8: Webhook Verification Challenge

_For any_ GET request to webhook endpoint with valid verify_token, the response should be the hub.challenge value.
**Validates: Requirements 6.4, 6.5**

### Property 9: Message Status Persistence

_For any_ delivery report received via webhook, the message status in the repository should be updated to match the reported status.
**Validates: Requirements 7.3**

### Property 10: Messaging Window Validation

_For any_ message send attempt, if the last user message was more than 24 hours ago, the send should fail with a descriptive error.
**Validates: Requirements 9.2, 9.3, 9.4**

### Property 11: Provider Factory Resolution

_For any_ request for provider 'instagram', the WhatsAppProviderFactory should return an instance of InstagramProvider.
**Validates: Requirements 11.2**

### Property 12: Interface Implementation Completeness

_For any_ method in WhatsAppProviderInterface, InstagramProvider should have a corresponding implementation.
**Validates: Requirements 12.1**

### Property 13: Message Persistence with Provider Metadata

_For any_ message sent via Instagram, the persisted message should include 'instagram' in the provider metadata field.
**Validates: Requirements 12.4**

### Property 14: Error Code Mapping

_For any_ Instagram API error with code 36103, the system should return error message "Conta não elegível para mensagens".
**Validates: Requirements 10.1**

### Property 15: Transient Error Marking

_For any_ Instagram API error marked as is_transient=true, the system should mark it as transient for retry logic.
**Validates: Requirements 10.6**

## Error Handling

### Instagram-Specific Errors

| Error Code | Description              | Handling                              |
| ---------- | ------------------------ | ------------------------------------- |
| 36103      | Account not eligible     | Return descriptive error, don't retry |
| 2534068    | Feature not available    | Return descriptive error, don't retry |
| 10         | Permission denied        | Check permissions, return error       |
| 100        | Invalid parameter        | Validate input, return error          |
| 190        | Invalid token            | Refresh token, alert admin            |
| 200        | Permission error         | Check app permissions                 |
| 551        | User not available       | Return error, mark conversation       |
| 2022       | Messaging window expired | Return error with time info           |

### Retry Strategy

```php
class InstagramRetryStrategy
{
    public function shouldRetry(int $errorCode, bool $isTransient): bool
    {
        // Não fazer retry para erros permanentes
        $permanentErrors = [36103, 2534068, 10, 100, 190, 200, 551, 2022];

        if (in_array($errorCode, $permanentErrors)) {
            return false;
        }

        // Fazer retry para erros transientes
        return $isTransient;
    }

    public function getBackoffDelay(int $attempt): int
    {
        // Exponential backoff: 1s, 2s, 4s, 8s, 16s
        return min(pow(2, $attempt), 16);
    }
}
```

### Webhook Error Handling

```php
class WebhookErrorHandler
{
    public function handleWebhookError(\Throwable $e, array $payload): void
    {
        $this->logger->error('Webhook processing failed', [
            'error' => $e->getMessage(),
            'payload' => $payload,
            'trace' => $e->getTraceAsString()
        ]);

        // Adicionar a dead letter queue para reprocessamento
        $this->deadLetterQueue->add($payload, $e->getMessage());

        // Alertar equipe se erro crítico
        if ($this->isCriticalError($e)) {
            $this->alerting->sendAlert('Instagram webhook error', $e);
        }
    }
}
```

## Testing Strategy

### Unit Tests

**Framework**: PHPUnit 10.x

**Coverage Target**: Mínimo 80%

#### Test Files

1. **InstagramProviderTest.php**

   - Test sendText() com sucesso e erro
   - Test sendMedia() para cada tipo (image, video, audio, file)
   - Test sendMedia() com múltiplas imagens
   - Test sendInteractiveButtons() com quick replies
   - Test sendInteractiveList() com generic template
   - Test sendTemplate() conversão para texto
   - Test getMessageStatus() consulta no repositório
   - Test validateWebhook() com signature válida/inválida
   - Test processIncomingMessage() para cada tipo
   - Test processDeliveryReport() para cada status

2. **InstagramWebhookHandlerTest.php**

   - Test validateSignature() com diferentes payloads
   - Test handleVerification() com token válido/inválido
   - Test extractMessages() com diferentes estruturas
   - Test extractDeliveryReports() com diferentes status

3. **InstagramMessageFormatterTest.php**

   - Test formatTextMessage()
   - Test formatMediaMessage() para cada tipo
   - Test formatMultipleImages() com 1-10 imagens
   - Test formatQuickReplies() com 1-13 replies
   - Test convertTemplateToText() com diferentes placeholders

4. **InstagramModelsTest.php**
   - Test InstagramRecipient validação de IGSID
   - Test InstagramAttachment validação de tipo e tamanho
   - Test InstagramQuickReply validação de título

### Property-Based Tests

**Framework**: Eris (PHP property-based testing library)

**Configuration**: Mínimo 100 iterações por teste

#### Property Tests

1. **Property 1: Authentication Header Consistency**

   ```php
   /**
    * Feature: instagram-messaging-integration, Property 1
    * For any request to Instagram API, Authorization header contains valid token
    */
   public function testAuthenticationHeaderConsistency()
   {
       $this->forAll(
           Generator\string()
       )->then(function ($pageAccessToken) {
           $provider = new InstagramProvider(/* ... */);
           $headers = $provider->getAuthHeaders();

           $this->assertArrayHasKey('Authorization', $headers);
           $this->assertStringStartsWith('Bearer ', $headers['Authorization']);
       });
   }
   ```

2. **Property 2: IGSID Format Validation**

   ```php
   /**
    * Feature: instagram-messaging-integration, Property 2
    * For any recipient IGSID, it should be non-empty numeric string
    */
   public function testIGSIDFormatValidation()
   {
       $this->forAll(
           Generator\string()
       )->then(function ($igsid) {
           if (empty($igsid) || !is_numeric($igsid)) {
               $this->expectException(\InvalidArgumentException::class);
           }

           new InstagramRecipient($igsid);
       });
   }
   ```

3. **Property 3: Media Size Validation**

   ```php
   /**
    * Feature: instagram-messaging-integration, Property 3
    * For any media attachment, size should not exceed type limit
    */
   public function testMediaSizeValidation()
   {
       $this->forAll(
           Generator\elements(['image', 'video', 'audio', 'file']),
           Generator\int(0, 50 * 1024 * 1024)
       )->then(function ($type, $size) {
           $limits = [
               'image' => 8 * 1024 * 1024,
               'video' => 25 * 1024 * 1024,
               'audio' => 25 * 1024 * 1024,
               'file' => 25 * 1024 * 1024
           ];

           if ($size > $limits[$type]) {
               $this->expectException(\InvalidArgumentException::class);
           }

           new InstagramAttachment($type, 'https://example.com/file', $size);
       });
   }
   ```

4. **Property 6: Template Placeholder Substitution**

   ```php
   /**
    * Feature: instagram-messaging-integration, Property 6
    * For any template with placeholders, after substitution no placeholders remain
    */
   public function testTemplatePlaceholderSubstitution()
   {
       $this->forAll(
           Generator\string(),
           Generator\seq(Generator\string())
       )->then(function ($template, $parameters) {
           $formatter = new InstagramMessageFormatter();
           $result = $formatter->convertTemplateToText($template, $parameters);

           // Não deve haver placeholders restantes
           $this->assertStringNotContainsString('{{', $result);
           $this->assertStringNotContainsString('}}', $result);
       });
   }
   ```

### Integration Tests

**Database**: MySQL test database

**External Services**: Mocked HTTP responses

#### Test Files

1. **InstagramMessageFlowTest.php**

   - Test fluxo completo: envio → webhook → status update
   - Test múltiplos tipos de mensagem
   - Test erro de janela de 24h

2. **InstagramMessageServiceTest.php**
   - Test integração via MessageService
   - Test switch entre providers (WhatsApp ↔ Instagram)
   - Test fallback em caso de erro

## Implementation Notes

### Diferenças Críticas vs WhatsApp

1. **Templates**: Instagram não suporta templates HSM. Converter para texto simples.
2. **Identificadores**: Usar IGSID em vez de número de telefone.
3. **Múltiplas Imagens**: Instagram permite até 10 imagens por mensagem.
4. **Autenticação**: Page Access Token em vez de API Key.
5. **Webhooks**: Validação HMAC diferente (X-Hub-Signature-256).
6. **Status**: Não há endpoint direto, usar webhooks + repositório local.

### Performance Considerations

- **Rate Limiting**: Instagram tem rate limits por Page. Implementar rate limiter.
- **Webhook Processing**: Processar webhooks de forma assíncrona para não bloquear.
- **Cache**: Cachear status de mensagens para reduzir consultas ao banco.
- **Connection Pooling**: Reutilizar conexões HTTP para Instagram API.

### Security Considerations

- **Token Storage**: Armazenar Page Access Token de forma segura (encrypted).
- **Webhook Validation**: Sempre validar signature antes de processar.
- **IGSID Privacy**: Não logar IGSID completo, usar hash ou mascaramento.
- **Rate Limit Protection**: Implementar circuit breaker para proteger contra abuse.
