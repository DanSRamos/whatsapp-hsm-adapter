# Design Document: WhatsApp HSM Adapter

## Overview

Este documento descreve o design de um adapter PHP para integração com APIs WhatsApp de múltiplos provedores (Infobip, Twilio, etc.). O adapter fornece uma camada de abstração unificada que simplifica o envio e recepção de mensagens WhatsApp, incluindo HSM (Highly Structured Messages), mensagens de texto livre, media, e mensagens interativas.

O sistema é construído como uma API RESTful que expõe endpoints para operações de envio e webhooks para recepção de notificações. A arquitetura segue princípios de separação de responsabilidades e usa o padrão Strategy para suportar múltiplos provedores de forma transparente, permitindo trocar de provedor sem alterar o código cliente.

## Architecture

### High-Level Architecture

```mermaid
graph TB
    Client[Cliente da API] --> Router[Router HTTP]
    Router --> Auth[Middleware de Autenticação]
    Auth --> Controller[Controllers]
    Controller --> Service[Services]
    Service --> ProviderFactory[Provider Factory]
    ProviderFactory --> InfobipProvider[Infobip Provider]
    ProviderFactory --> TwilioProvider[Twilio Provider]
    ProviderFactory --> OtherProvider[Other Providers...]
    Service --> Repository[Repositories]
    Repository --> Database[(Database)]

    InfobipProvider --> InfobipAPI[Infobip API]
    TwilioProvider --> TwilioAPI[Twilio API]

    InfobipAPI --> InfobipWebhook[Infobip Webhook Handler]
    TwilioAPI --> TwilioWebhook[Twilio Webhook Handler]
    InfobipWebhook --> WebhookValidator[Webhook Validator]
    TwilioWebhook --> WebhookValidator
    WebhookValidator --> WebhookProcessor[Webhook Processor]
    WebhookProcessor --> Repository
    WebhookProcessor --> EventDispatcher[Event Dispatcher]
```

````

### Technology Stack

- **PHP**: 8.1 ou superior
- **Framework**: Slim Framework 4 ou Symfony (componentes standalone)
- **HTTP Client**: Guzzle 7
- **Database**: MySQL/PostgreSQL para persistência de mensagens e logs
- **Cache**: Redis para rate limiting e sessões
- **Logging**: Monolog
- **Testing**: PHPUnit para unit tests, Pest para property-based tests

### Design Patterns

- **Repository Pattern**: Para abstração de acesso a dados
- **Service Layer**: Para lógica de negócio
- **Factory Pattern**: Para criação de objetos de mensagem e providers
- **Strategy Pattern**: Para suportar múltiplos provedores WhatsApp (Infobip, Twilio, etc.)
- **Adapter Pattern**: Para normalizar APIs diferentes de provedores
- **Observer Pattern**: Para eventos de webhook

### Multi-Provider Support

O sistema usa o padrão Strategy/Adapter para suportar múltiplos provedores de WhatsApp de forma transparente:

```mermaid
graph TB
    Service[Message Service] --> Factory[Provider Factory]
    Factory --> |config: infobip| InfobipProvider[Infobip Provider]
    Factory --> |config: twilio| TwilioProvider[Twilio Provider]
    Factory --> |config: other| OtherProvider[Other Provider]

    InfobipProvider --> |implements| ProviderInterface[WhatsApp Provider Interface]
    TwilioProvider --> |implements| ProviderInterface
    OtherProvider --> |implements| ProviderInterface

    InfobipProvider --> InfobipAPI[Infobip API]
    TwilioProvider --> TwilioAPI[Twilio API]
```

#### WhatsApp Provider Interface

```php
interface WhatsAppProviderInterface
{
    /**
     * Envia uma mensagem HSM/Template
     */
    public function sendTemplate(TemplateMessage $message): ProviderSendResult;

    /**
     * Envia uma mensagem de texto livre
     */
    public function sendText(TextMessage $message): ProviderSendResult;

    /**
     * Envia media (imagem, documento, áudio, vídeo)
     */
    public function sendMedia(MediaMessage $message): ProviderSendResult;

    /**
     * Envia mensagem interativa com botões
     */
    public function sendInteractiveButtons(InteractiveButtonsMessage $message): ProviderSendResult;

    /**
     * Envia mensagem interativa com lista
     */
    public function sendInteractiveList(InteractiveListMessage $message): ProviderSendResult;

    /**
     * Consulta o estado de uma mensagem
     */
    public function getMessageStatus(string $messageId): ProviderMessageStatus;

    /**
     * Recupera templates disponíveis
     */
    public function getTemplates(): array;

    /**
     * Recupera um template específico
     */
    public function getTemplate(string $templateId): ?ProviderTemplate;

    /**
     * Valida webhook recebido do provedor
     */
    public function validateWebhook(ServerRequestInterface $request): bool;

    /**
     * Processa webhook de delivery report
     */
    public function processDeliveryReport(array $payload): DeliveryReport;

    /**
     * Processa webhook de mensagem recebida
     */
    public function processIncomingMessage(array $payload): IncomingMessage;

    /**
     * Processa webhook de atualização de template
     */
    public function processTemplateUpdate(array $payload): TemplateUpdate;

    /**
     * Retorna o nome do provedor
     */
    public function getName(): string;
}
```

#### Provider Factory

```php
class WhatsAppProviderFactory
{
    private array $providers = [];

    public function __construct(
        private array $config,
        private ClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    /**
     * Cria ou retorna provider configurado
     */
    public function getProvider(?string $providerName = null): WhatsAppProviderInterface
    {
        $providerName = $providerName ?? $this->config['default_provider'];

        if (!isset($this->providers[$providerName])) {
            $this->providers[$providerName] = $this->createProvider($providerName);
        }

        return $this->providers[$providerName];
    }

    private function createProvider(string $providerName): WhatsAppProviderInterface
    {
        return match($providerName) {
            'infobip' => new InfobipProvider(
                $this->httpClient,
                $this->config['providers']['infobip'],
                $this->logger
            ),
            'twilio' => new TwilioProvider(
                $this->httpClient,
                $this->config['providers']['twilio'],
                $this->logger
            ),
            default => throw new \InvalidArgumentException("Unknown provider: {$providerName}")
        };
    }

    /**
     * Detecta provedor baseado no webhook recebido
     */
    public function detectProviderFromWebhook(ServerRequestInterface $request): ?WhatsAppProviderInterface
    {
        foreach ($this->config['providers'] as $name => $config) {
            $provider = $this->getProvider($name);
            if ($provider->validateWebhook($request)) {
                return $provider;
            }
        }

        return null;
    }
}
```

#### Provider-Specific Implementations

**InfobipProvider**
```php
class InfobipProvider implements WhatsAppProviderInterface
{
    public function __construct(
        private ClientInterface $httpClient,
        private array $config,
        private LoggerInterface $logger
    ) {}

    public function getName(): string
    {
        return 'infobip';
    }

    public function sendTemplate(TemplateMessage $message): ProviderSendResult
    {
        $payload = [
            'messages' => [[
                'from' => $this->config['sender'],
                'to' => $message->to,
                'messageId' => $message->id,
                'content' => [
                    'templateName' => $message->templateName,
                    'templateData' => [
                        'body' => [
                            'placeholders' => $message->parameters
                        ]
                    ],
                    'language' => $message->language
                ]
            ]]
        ];

        $response = $this->httpClient->post(
            $this->config['base_url'] . '/whatsapp/1/message/template',
            [
                'headers' => [
                    'Authorization' => 'App ' . $this->config['api_key'],
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]
        );

        return $this->parseResponse($response);
    }

    // ... outras implementações
}
```

**TwilioProvider**
```php
class TwilioProvider implements WhatsAppProviderInterface
{
    public function __construct(
        private ClientInterface $httpClient,
        private array $config,
        private LoggerInterface $logger
    ) {}

    public function getName(): string
    {
        return 'twilio';
    }

    public function sendTemplate(TemplateMessage $message): ProviderSendResult
    {
        // Twilio usa ContentSid para templates
        $payload = [
            'From' => 'whatsapp:' . $this->config['sender'],
            'To' => 'whatsapp:' . $message->to,
            'ContentSid' => $message->templateName, // Twilio usa SID
            'ContentVariables' => json_encode($message->parameters)
        ];

        $response = $this->httpClient->post(
            sprintf(
                'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json',
                $this->config['account_sid']
            ),
            [
                'auth' => [$this->config['account_sid'], $this->config['auth_token']],
                'form_params' => $payload
            ]
        );

        return $this->parseResponse($response);
    }

    // ... outras implementações
}
```

#### Configuration Example

```php
// config/whatsapp.php
return [
    'default_provider' => env('WHATSAPP_PROVIDER', 'infobip'),

    'providers' => [
        'infobip' => [
            'api_key' => env('INFOBIP_API_KEY'),
            'base_url' => env('INFOBIP_BASE_URL', 'https://api.infobip.com'),
            'sender' => env('INFOBIP_SENDER'),
            'webhook_secret' => env('INFOBIP_WEBHOOK_SECRET'),
        ],

        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'sender' => env('TWILIO_SENDER'),
            'webhook_secret' => env('TWILIO_WEBHOOK_SECRET'),
        ],
    ],
];
```

#### Updated Service Layer

```php
class MessageService
{
    public function __construct(
        private WhatsAppProviderFactory $providerFactory,
        private MessageRepository $messageRepository,
        private MessageFactory $messageFactory,
        private RetryHandler $retryHandler,
        private LoggerInterface $logger
    ) {}

    /**
     * Envia uma mensagem HSM usando o provedor configurado
     */
    public function sendHSM(HSMRequest $request, ?string $providerName = null): SendResult
    {
        $provider = $this->providerFactory->getProvider($providerName);
        $message = $this->messageFactory->createTemplateMessage($request);

        try {
            $result = $this->retryHandler->execute(
                fn() => $provider->sendTemplate($message)
            );

            $this->messageRepository->save($message);

            return new SendResult(
                success: true,
                messageId: $result->messageId,
                status: $result->status
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send HSM', [
                'provider' => $provider->getName(),
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    // ... outros métodos similares
}
```

#### Webhook Routing

```php
class WebhookController
{
    public function __construct(
        private WhatsAppProviderFactory $providerFactory,
        private WebhookProcessor $webhookProcessor,
        private LoggerInterface $logger
    ) {}

    /**
     * POST /webhooks/delivery-reports
     * Detecta automaticamente o provedor e processa
     */
    public function handleDeliveryReport(ServerRequestInterface $request): ResponseInterface
    {
        $provider = $this->providerFactory->detectProviderFromWebhook($request);

        if (!$provider) {
            $this->logger->warning('Webhook from unknown provider');
            return new JsonResponse(['error' => 'Invalid webhook'], 400);
        }

        if (!$provider->validateWebhook($request)) {
            $this->logger->warning('Invalid webhook signature', [
                'provider' => $provider->getName()
            ]);
            return new JsonResponse(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($request->getBody()->getContents(), true);
        $deliveryReport = $provider->processDeliveryReport($payload);

        $this->webhookProcessor->processDeliveryReport($deliveryReport);

        return new JsonResponse(['success' => true]);
    }

    // ... outros métodos similares
}
```

### Benefits of Multi-Provider Architecture

1. **Flexibility**: Trocar de provedor sem alterar código cliente
2. **Redundancy**: Usar múltiplos provedores para alta disponibilidade
3. **Cost Optimization**: Escolher provedor baseado em custo/região
4. **Testing**: Facilita testes com mock providers
5. **Future-Proof**: Adicionar novos provedores sem refactoring

## Components and Interfaces

### 1. HTTP Layer

#### Router
Responsável por mapear URLs para controllers.

```php
interface RouterInterface
{
    public function addRoute(string $method, string $path, callable $handler): void;
    public function dispatch(ServerRequestInterface $request): ResponseInterface;
}
````

#### Controllers

**TemplateController**

```php
class TemplateController
{
    public function __construct(
        private TemplateService $templateService,
        private LoggerInterface $logger
    ) {}

    /**
     * GET /api/templates
     * Recupera todos os templates disponíveis
     */
    public function getTemplates(ServerRequestInterface $request): ResponseInterface;

    /**
     * GET /api/templates/{templateId}
     * Recupera um template específico
     */
    public function getTemplate(ServerRequestInterface $request, string $templateId): ResponseInterface;

    /**
     * POST /api/templates/sync
     * Sincroniza templates manualmente do provedor para a base de dados local
     * Query params: ?provider=infobip (opcional, default: todos os provedores)
     */
    public function syncTemplates(ServerRequestInterface $request): ResponseInterface;
}
```

**MessageController**

```php
class MessageController
{
    public function __construct(
        private MessageService $messageService,
        private LoggerInterface $logger
    ) {}

    /**
     * POST /api/messages/hsm
     * Envia uma mensagem HSM
     */
    public function sendHSM(ServerRequestInterface $request): ResponseInterface;

    /**
     * POST /api/messages/text
     * Envia uma mensagem de texto livre
     */
    public function sendText(ServerRequestInterface $request): ResponseInterface;

    /**
     * POST /api/messages/media
     * Envia media (imagem, documento, áudio, vídeo)
     */
    public function sendMedia(ServerRequestInterface $request): ResponseInterface;

    /**
     * POST /api/messages/interactive/buttons
     * Envia mensagem com botões interativos
     */
    public function sendInteractiveButtons(ServerRequestInterface $request): ResponseInterface;

    /**
     * POST /api/messages/interactive/list
     * Envia mensagem com lista interativa
     */
    public function sendInteractiveList(ServerRequestInterface $request): ResponseInterface;

    /**
     * GET /api/messages/{messageId}/status
     * Consulta o estado de uma mensagem
     */
    public function getMessageStatus(ServerRequestInterface $request, string $messageId): ResponseInterface;
}
```

**WebhookController**

```php
class WebhookController
{
    public function __construct(
        private WebhookProcessor $webhookProcessor,
        private WebhookValidator $webhookValidator,
        private LoggerInterface $logger
    ) {}

    /**
     * POST /webhooks/delivery-reports
     * Recebe relatórios de entrega da Infobip
     */
    public function handleDeliveryReport(ServerRequestInterface $request): ResponseInterface;

    /**
     * POST /webhooks/incoming-messages
     * Recebe mensagens recebidas de clientes
     */
    public function handleIncomingMessage(ServerRequestInterface $request): ResponseInterface;

    /**
     * POST /webhooks/template-updates
     * Recebe notificações de alterações em templates
     */
    public function handleTemplateUpdate(ServerRequestInterface $request): ResponseInterface;
}
```

### 2. Service Layer

#### TemplateService

```php
class TemplateService
{
    public function __construct(
        private WhatsAppProviderFactory $providerFactory,
        private TemplateRepository $templateRepository,
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    /**
     * Recupera todos os templates (do cache ou da base de dados)
     * Usa cache para reduzir chamadas à base de dados
     */
    public function getAllTemplates(): array;

    /**
     * Recupera um template específico por ID
     */
    public function getTemplateById(string $templateId): ?Template;

    /**
     * Sincroniza templates manualmente do provedor para a base de dados
     * @param string|null $providerName Nome do provedor (null = todos)
     * @return array Estatísticas da sincronização (added, updated, deleted)
     */
    public function syncTemplates(?string $providerName = null): array;

    /**
     * Processa atualização de template recebida via webhook
     */
    public function processTemplateUpdate(array $webhookData): void;

    /**
     * Invalida cache de templates
     */
    public function invalidateCache(): void;
}
```

#### MessageService

```php
class MessageService
{
    public function __construct(
        private InfobipClient $infobipClient,
        private MessageRepository $messageRepository,
        private MessageFactory $messageFactory,
        private RetryHandler $retryHandler,
        private LoggerInterface $logger
    ) {}

    /**
     * Envia uma mensagem HSM
     */
    public function sendHSM(HSMRequest $request): SendResult;

    /**
     * Envia uma mensagem de texto livre
     */
    public function sendText(TextRequest $request): SendResult;

    /**
     * Envia media
     */
    public function sendMedia(MediaRequest $request): SendResult;

    /**
     * Envia mensagem interativa com botões
     */
    public function sendInteractiveButtons(InteractiveButtonsRequest $request): SendResult;

    /**
     * Envia mensagem interativa com lista
     */
    public function sendInteractiveList(InteractiveListRequest $request): SendResult;

    /**
     * Consulta o estado de uma mensagem
     */
    public function getMessageStatus(string $messageId): MessageStatus;

    /**
     * Processa relatório de entrega
     */
    public function processDeliveryReport(array $webhookData): void;

    /**
     * Processa mensagem recebida
     */
    public function processIncomingMessage(array $webhookData): IncomingMessage;
}
```

### 3. Provider Implementations

As implementações específicas de cada provedor (InfobipProvider, TwilioProvider, etc.) são detalhadas na seção "Multi-Provider Support" acima. Cada provider implementa a interface `WhatsAppProviderInterface` e adapta as chamadas para a API específica do provedor.

**Nota**: O código legado que referenciava diretamente `InfobipClient` foi refatorado para usar o `WhatsAppProviderFactory`, permitindo suporte a múltiplos provedores.

### 4. Data Models

#### Template

```php
class Template
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $language,
        public readonly string $status,
        public readonly string $category,
        public readonly array $components,
        public readonly ?string $rejectionReason = null
    ) {}

    public function isApproved(): bool;
    public function getParameters(): array;
}
```

#### Message Request Objects

```php
class HSMRequest
{
    public function __construct(
        public readonly string $to,
        public readonly string $templateName,
        public readonly string $templateLanguage,
        public readonly array $parameters = [],
        public readonly ?string $notifyUrl = null
    ) {}
}

class TextRequest
{
    public function __construct(
        public readonly string $to,
        public readonly string $text,
        public readonly bool $previewUrl = false,
        public readonly ?string $notifyUrl = null
    ) {}
}

class MediaRequest
{
    public function __construct(
        public readonly string $to,
        public readonly string $mediaType, // image, document, audio, video
        public readonly string $mediaUrl,
        public readonly ?string $caption = null,
        public readonly ?string $filename = null,
        public readonly ?string $notifyUrl = null
    ) {}
}

class InteractiveButtonsRequest
{
    public function __construct(
        public readonly string $to,
        public readonly string $bodyText,
        public readonly array $buttons, // max 3
        public readonly ?string $headerText = null,
        public readonly ?string $footerText = null,
        public readonly ?string $notifyUrl = null
    ) {}
}

class InteractiveListRequest
{
    public function __construct(
        public readonly string $to,
        public readonly string $bodyText,
        public readonly string $buttonText,
        public readonly array $sections, // cada section tem items (max 10 total)
        public readonly ?string $headerText = null,
        public readonly ?string $footerText = null,
        public readonly ?string $notifyUrl = null
    ) {}
}
```

#### Response Objects

```php
class SendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $messageId = null,
        public readonly ?string $status = null,
        public readonly ?string $error = null,
        public readonly ?array $details = null
    ) {}
}

class MessageStatus
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $status, // PENDING, SENT, DELIVERED, READ, FAILED
        public readonly string $to,
        public readonly \DateTimeImmutable $sentAt,
        public readonly ?\DateTimeImmutable $deliveredAt = null,
        public readonly ?\DateTimeImmutable $readAt = null,
        public readonly ?string $error = null
    ) {}
}

class IncomingMessage
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $from,
        public readonly string $to,
        public readonly string $type, // text, image, document, audio, video, button, list
        public readonly mixed $content,
        public readonly \DateTimeImmutable $receivedAt,
        public readonly ?string $contextMessageId = null // ID da mensagem a que está a responder
    ) {}
}
```

### 5. Repositories

```php
interface MessageRepositoryInterface
{
    public function save(Message $message): void;
    public function findById(string $messageId): ?Message;
    public function updateStatus(string $messageId, string $status, array $metadata): void;
}

interface TemplateRepositoryInterface
{
    public function save(Template $template): void;
    public function findById(string $templateId): ?Template;
    public function findAll(): array;
    public function delete(string $templateId): void;
}
```

### 6. Webhook Processing

```php
class WebhookValidator
{
    /**
     * Valida a autenticidade de um webhook da Infobip
     * Pode usar assinatura HMAC ou validação de IP
     */
    public function validate(ServerRequestInterface $request): bool;
}

class WebhookProcessor
{
    public function __construct(
        private MessageService $messageService,
        private TemplateService $templateService,
        private EventDispatcher $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    public function processDeliveryReport(array $data): void;
    public function processIncomingMessage(array $data): void;
    public function processTemplateUpdate(array $data): void;
}
```

### 7. Retry Handler

```php
class RetryHandler
{
    public function __construct(
        private int $maxRetries = 3,
        private int $initialDelayMs = 1000,
        private LoggerInterface $logger
    ) {}

    /**
     * Executa uma operação com retry e backoff exponencial
     */
    public function execute(callable $operation): mixed;

    /**
     * Determina se um erro é retryable
     */
    private function isRetryableError(\Throwable $error): bool;
}
```

## Data Models

### Database Schema

```sql
-- Tabela de mensagens enviadas
CREATE TABLE messages (
    id VARCHAR(255) PRIMARY KEY,
    type VARCHAR(50) NOT NULL, -- hsm, text, media, interactive_buttons, interactive_list
    to_number VARCHAR(20) NOT NULL,
    from_number VARCHAR(20) NOT NULL,
    status VARCHAR(50) NOT NULL, -- pending, sent, delivered, read, failed
    content JSON NOT NULL,
    sent_at TIMESTAMP NOT NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    error_message TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_to_number (to_number),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
);

-- Tabela de mensagens recebidas
CREATE TABLE incoming_messages (
    id VARCHAR(255) PRIMARY KEY,
    from_number VARCHAR(20) NOT NULL,
    to_number VARCHAR(20) NOT NULL,
    type VARCHAR(50) NOT NULL, -- text, image, document, audio, video, button, list
    content JSON NOT NULL,
    context_message_id VARCHAR(255) NULL, -- mensagem a que está a responder
    received_at TIMESTAMP NOT NULL,
    processed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_from_number (from_number),
    INDEX idx_received_at (received_at),
    INDEX idx_processed (processed),
    FOREIGN KEY (context_message_id) REFERENCES messages(id)
);

-- Tabela de templates (cache local)
CREATE TABLE templates (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    language VARCHAR(10) NOT NULL,
    status VARCHAR(50) NOT NULL, -- approved, pending, rejected, paused
    category VARCHAR(50) NOT NULL,
    components JSON NOT NULL,
    rejection_reason TEXT NULL,
    last_synced_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name_language (name, language),
    INDEX idx_status (status)
);

-- Tabela de logs de webhooks
CREATE TABLE webhook_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL, -- delivery_report, incoming_message, template_update
    payload JSON NOT NULL,
    processed BOOLEAN DEFAULT FALSE,
    error_message TEXT NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    INDEX idx_type (type),
    INDEX idx_processed (processed),
    INDEX idx_received_at (received_at)
);
```

## Correctness Properties

_Uma propriedade (property) é uma característica ou comportamento que deve ser verdadeiro em todas as execuções válidas de um sistema - essencialmente, uma declaração formal sobre o que o sistema deve fazer. As propriedades servem como ponte entre especificações legíveis por humanos e garantias de correção verificáveis por máquina._

### Property Reflection

Após análise dos critérios de aceitação, identifiquei várias propriedades que podem ser consolidadas:

**Propriedades de Validação de Autenticidade de Webhooks (2.4, 5.3, 8.4, 10.4, 11.3)**:

- Todas estas propriedades testam a mesma funcionalidade: validação de autenticidade de webhooks
- Podem ser consolidadas numa única propriedade que testa validação de webhooks em geral

**Propriedades de Rejeição de Notificações Inválidas (2.5, 5.5)**:

- Ambas testam que notificações inválidas são rejeitadas
- Podem ser consolidadas numa única propriedade

**Propriedades de Retorno de ID de Mensagem (3.3, 6.4, 7.6, 9.4)**:

- Todas testam que após envio bem-sucedido, retornamos ID da mensagem
- Podem ser consolidadas numa única propriedade que testa qualquer tipo de envio

**Propriedades de Validação de Parâmetros Obrigatórios (3.1, 3.4, 6.1)**:

- Todas testam validação de parâmetros obrigatórios em pedidos
- Podem ser consolidadas numa única propriedade que testa validação de entrada

**Propriedades de Tratamento de Erros da Infobip (1.3, 3.5)**:

- Ambas testam que erros da Infobip são tratados adequadamente
- Podem ser consolidadas numa única propriedade

**Propriedades de Validação de Media (7.1, 7.2, 7.3, 7.4, 7.7)**:

- Todas testam validação de diferentes tipos de media
- Podem ser consolidadas numa única propriedade que testa validação de media em geral

**Propriedades de Logging (12.1, 12.2, 12.3)**:

- Todas testam que diferentes tipos de eventos são registados
- Podem ser consolidadas numa única propriedade que testa logging em geral

### Correctness Properties

Property 1: Template Response Format Consistency
_For any_ resposta da API da Infobip contendo templates, o adapter deve formatar os dados num formato consistente que inclui todos os campos obrigatórios (ID, nome, idioma, parâmetros, status de aprovação)
**Validates: Requirements 1.2, 1.4**

Property 2: Webhook Authentication Validation
_For any_ webhook recebido (delivery report, incoming message, template update, interactive response), o adapter deve validar a autenticidade antes de processar, rejeitando webhooks inválidos e registando a tentativa
**Validates: Requirements 2.4, 2.5, 5.3, 5.5, 8.4, 10.4, 11.3**

Property 3: Template Update Persistence
_For any_ notificação válida de alteração ou remoção de template (via webhook ou sincronização manual), o adapter deve registar a mudança na base de dados
**Validates: Requirements 2.2, 2.3, 2.4**

Property 3.1: Manual Template Synchronization
_For any_ pedido de sincronização manual de templates, o adapter deve buscar todos os templates do provedor especificado (ou todos os provedores se não especificado), comparar com a base de dados local, e atualizar/adicionar/remover templates conforme necessário, retornando estatísticas da operação
**Validates: Requirements 2.1, 2.7**

Property 4: Request Parameter Validation
_For any_ pedido de envio de mensagem (HSM, texto, media, interativa), o adapter deve validar todos os parâmetros obrigatórios e retornar erro de validação específico se algum estiver em falta ou inválido
**Validates: Requirements 3.1, 3.4, 6.1, 7.7, 9.5**

Property 5: Template Parameter Substitution
_For any_ template HSM com parâmetros dinâmicos, o adapter deve substituir corretamente todos os placeholders pelos valores fornecidos
**Validates: Requirements 3.6**

Property 6: Successful Send Response
_For any_ envio de mensagem bem-sucedido (qualquer tipo), o adapter deve retornar o ID da mensagem e status de envio
**Validates: Requirements 3.3, 6.4, 7.6, 9.4**

Property 7: Error Response Handling
_For any_ erro retornado pela API da Infobip, o adapter deve retornar uma mensagem de erro descritiva com código de status HTTP apropriado
**Validates: Requirements 1.3, 3.5**

Property 8: Message Status Query Response
_For any_ consulta de estado com ID de mensagem válido, o adapter deve retornar informações completas de estado (status, timestamps de envio/entrega/leitura)
**Validates: Requirements 4.1, 4.2**

Property 9: Invalid Message ID Handling
_For any_ consulta de estado com ID de mensagem inválido ou inexistente, o adapter deve retornar erro 404 com mensagem descritiva
**Validates: Requirements 4.3**

Property 10: Incoming Message Content Extraction
_For any_ mensagem recebida de cliente (texto, media, localização, contactos, resposta interativa), o adapter deve extrair todo o conteúdo, identificar o remetente, e associar à conversa correta
**Validates: Requirements 5.2, 8.2, 8.3, 10.1, 10.2, 10.3**

Property 11: Incoming Message Persistence
_For any_ mensagem válida recebida (resposta de cliente ou mensagem normal), o adapter deve armazenar ou encaminhar para o sistema de gestão de conversas
**Validates: Requirements 5.4, 8.5, 10.5**

Property 12: Text Content Type Support
_For any_ mensagem de texto livre contendo texto simples, texto formatado, ou emojis, o adapter deve enviar corretamente através da API da Infobip
**Validates: Requirements 6.3**

Property 13: Media Validation
_For any_ pedido de envio de media (imagem, documento, áudio, vídeo), o adapter deve validar formato e tamanho/duração máxima, rejeitando media inválida com erro específico
**Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.7**

Property 14: Media Upload Method Support
_For any_ media enviada, o adapter deve suportar tanto envio através de URL quanto upload direto
**Validates: Requirements 7.5**

Property 15: Interactive Button Count Validation
_For any_ mensagem com botões interativos, o adapter deve validar que existem no máximo 3 botões, retornando erro se o limite for excedido
**Validates: Requirements 9.1, 9.5**

Property 16: Interactive List Item Count Validation
_For any_ mensagem com lista interativa, o adapter deve validar que existem no máximo 10 itens no total, retornando erro se o limite for excedido
**Validates: Requirements 9.2, 9.5**

Property 17: Interactive Element Uniqueness
_For any_ botão ou item de lista, o adapter deve validar que tem um ID único e texto descritivo
**Validates: Requirements 9.3**

Property 18: Interactive Button Type Support
_For any_ botão interativo (resposta rápida, URL, chamada telefónica), o adapter deve suportar o envio através da API da Infobip
**Validates: Requirements 9.6**

Property 19: API Request Authentication
_For any_ pedido enviado à API da Infobip, o adapter deve incluir credenciais de autenticação válidas
**Validates: Requirements 11.2**

Property 20: Rate Limiting Enforcement
_For any_ sequência de pedidos aos endpoints do adapter, o sistema deve aplicar rate limiting para prevenir abuso
**Validates: Requirements 11.5**

Property 21: Comprehensive Logging
_For any_ pedido recebido, resposta da Infobip, ou erro ocorrido, o adapter deve registar o evento com timestamps e contexto suficiente, excluindo informações sensíveis
**Validates: Requirements 12.1, 12.2, 12.3, 12.5**

Property 22: Critical Error Notification
_For any_ erro crítico que ocorra, o adapter deve notificar os administradores do sistema
**Validates: Requirements 12.4**

Property 23: Retry with Exponential Backoff
_For any_ erro temporário da API da Infobip (5xx, timeout, 429), o adapter deve implementar retry com backoff exponencial, respeitando headers Retry-After quando presentes
**Validates: Requirements 13.1, 13.5**

Property 24: Maximum Retry Attempts
_For any_ operação que requer retry, o adapter deve tentar no máximo 3 vezes antes de falhar definitivamente, retornando erro com detalhes das tentativas
**Validates: Requirements 13.2, 13.3**

Property 25: No Retry on Permanent Errors
_For any_ erro permanente da API da Infobip (4xx exceto 429), o adapter não deve fazer retry e deve retornar erro imediatamente
**Validates: Requirements 13.4**

## Error Handling

### Error Categories

1. **Validation Errors (4xx)**

   - Missing required parameters
   - Invalid parameter format
   - Exceeded limits (buttons, list items, file size)
   - Invalid media format
   - Response: 400 Bad Request with detailed error message

2. **Authentication Errors (401, 403)**

   - Invalid API key
   - Expired credentials
   - Insufficient permissions
   - Response: 401 Unauthorized or 403 Forbidden

3. **Not Found Errors (404)**

   - Message ID not found
   - Template ID not found
   - Response: 404 Not Found with descriptive message

4. **Rate Limiting (429)**

   - Too many requests
   - Response: 429 Too Many Requests with Retry-After header
   - Action: Respect Retry-After and implement exponential backoff

5. **Infobip API Errors (5xx)**

   - Temporary service unavailability
   - Timeout errors
   - Response: 503 Service Unavailable
   - Action: Retry with exponential backoff (max 3 attempts)

6. **Internal Errors (500)**
   - Unexpected exceptions
   - Database errors
   - Response: 500 Internal Server Error
   - Action: Log with full context, notify administrators

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Missing required parameter: to",
    "details": {
      "field": "to",
      "reason": "Field is required"
    }
  },
  "timestamp": "2026-01-16T10:30:00Z",
  "requestId": "req_abc123"
}
```

### Retry Strategy

```php
class RetryStrategy
{
    private const MAX_RETRIES = 3;
    private const INITIAL_DELAY_MS = 1000;
    private const MAX_DELAY_MS = 30000;

    public function shouldRetry(\Throwable $error, int $attempt): bool
    {
        // Não fazer retry em erros permanentes
        if ($error instanceof ClientException) {
            $statusCode = $error->getResponse()->getStatusCode();
            if ($statusCode >= 400 && $statusCode < 500 && $statusCode !== 429) {
                return false;
            }
        }

        // Fazer retry em erros temporários
        if ($error instanceof ServerException || $error instanceof ConnectException) {
            return $attempt < self::MAX_RETRIES;
        }

        return false;
    }

    public function getDelay(int $attempt, ?\DateTimeInterface $retryAfter = null): int
    {
        // Respeitar Retry-After se presente
        if ($retryAfter !== null) {
            $delay = $retryAfter->getTimestamp() - time();
            return max(0, min($delay * 1000, self::MAX_DELAY_MS));
        }

        // Backoff exponencial: 1s, 2s, 4s
        $delay = self::INITIAL_DELAY_MS * (2 ** ($attempt - 1));
        return min($delay, self::MAX_DELAY_MS);
    }
}
```

## Testing Strategy

### Dual Testing Approach

O sistema será testado usando uma combinação de **unit tests** e **property-based tests**, que são complementares e necessários para cobertura abrangente:

- **Unit tests**: Verificam exemplos específicos, casos extremos e condições de erro
- **Property tests**: Verificam propriedades universais através de todos os inputs

### Property-Based Testing

Usaremos a biblioteca **Pest** com o plugin **Pest Property Testing** para PHP. Cada teste de propriedade deve:

- Executar no mínimo **100 iterações** devido à randomização
- Referenciar a propriedade do documento de design
- Usar o formato de tag: **Feature: whatsapp-hsm-adapter, Property {número}: {texto da propriedade}**

### Test Coverage

**Unit Tests** devem cobrir:

- Exemplos específicos de cada tipo de mensagem
- Casos extremos (limites de botões, tamanhos de media)
- Condições de erro específicas (API indisponível, credenciais inválidas)
- Integração entre componentes

**Property Tests** devem cobrir:

- Validação de entrada para todos os tipos de pedidos
- Formatação consistente de respostas
- Tratamento de erros em geral
- Persistência de dados
- Autenticação e segurança
- Retry e backoff exponencial

### Example Property Test

```php
use function Pest\Property\forAll;

test('Property 4: Request Parameter Validation', function () {
    forAll(
        // Generator para pedidos com parâmetros em falta
        fn() => [
            'to' => rand(0, 1) ? null : '+351' . rand(900000000, 999999999),
            'templateName' => rand(0, 1) ? null : 'welcome_message',
            'templateLanguage' => rand(0, 1) ? null : 'pt',
        ]
    )->then(function ($request) {
        $service = app(MessageService::class);

        // Se algum parâmetro obrigatório está em falta
        if (!$request['to'] || !$request['templateName'] || !$request['templateLanguage']) {
            // Deve lançar ValidationException
            expect(fn() => $service->sendHSM(new HSMRequest(...$request)))
                ->toThrow(ValidationException::class);
        }
    });
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter');
```

### Test Organization

```
tests/
├── Unit/
│   ├── Services/
│   │   ├── TemplateServiceTest.php
│   │   ├── MessageServiceTest.php
│   │   └── WebhookProcessorTest.php
│   ├── Controllers/
│   │   ├── TemplateControllerTest.php
│   │   ├── MessageControllerTest.php
│   │   └── WebhookControllerTest.php
│   └── Clients/
│       └── InfobipClientTest.php
├── Property/
│   ├── ValidationPropertiesTest.php
│   ├── MessageSendingPropertiesTest.php
│   ├── WebhookPropertiesTest.php
│   ├── ErrorHandlingPropertiesTest.php
│   └── RetryPropertiesTest.php
└── Integration/
    ├── EndToEndMessageFlowTest.php
    └── WebhookIntegrationTest.php
```

## Security Considerations

### Credential Management

- API keys armazenadas em variáveis de ambiente
- Nunca fazer commit de credenciais no código
- Usar vault (como HashiCorp Vault) em produção

### Webhook Validation

- Validar assinatura HMAC de webhooks da Infobip
- Validar IPs de origem (whitelist de IPs da Infobip)
- Rejeitar webhooks sem assinatura válida

### Rate Limiting

- Implementar rate limiting por IP e por API key
- Limites sugeridos:
  - 100 pedidos por minuto por IP
  - 1000 pedidos por hora por API key
- Usar Redis para tracking de rate limits

### Data Protection

- Não registar conteúdo completo de mensagens em logs
- Não registar tokens ou API keys
- Encriptar dados sensíveis na base de dados
- Implementar retenção de dados (GDPR compliance)

### HTTPS

- Todos os endpoints devem usar HTTPS
- Certificados SSL válidos
- Rejeitar conexões HTTP

## Performance Considerations

### Caching

- Cache de templates (TTL: 1 hora)
- Cache de rate limiting (Redis)
- Invalidação de cache em webhooks de template update

### Database Optimization

- Índices em campos frequentemente consultados
- Particionamento de tabelas por data para mensagens antigas
- Limpeza periódica de logs antigos

### Async Processing

- Processar webhooks de forma assíncrona quando possível
- Usar filas (Redis Queue ou RabbitMQ) para processamento de mensagens recebidas
- Responder rapidamente aos webhooks (< 200ms) para evitar retries da Infobip

### Connection Pooling

- Usar connection pooling para HTTP client (Guzzle)
- Usar connection pooling para database
- Limitar número de conexões simultâneas

## Deployment Considerations

### Environment Variables

```env
INFOBIP_API_KEY=your_api_key_here
INFOBIP_BASE_URL=https://api.infobip.com
INFOBIP_SENDER=447860099299
WEBHOOK_SECRET=your_webhook_secret_here
DATABASE_URL=mysql://user:pass@localhost/whatsapp_adapter
REDIS_URL=redis://localhost:6379
LOG_LEVEL=info
RATE_LIMIT_ENABLED=true
RATE_LIMIT_PER_MINUTE=100
```

### Health Checks

- Endpoint `/health` para verificar status do serviço
- Verificar conectividade com Infobip API
- Verificar conectividade com database
- Verificar conectividade com Redis

### Monitoring

- Métricas de latência de pedidos
- Métricas de taxa de erro
- Métricas de rate limiting
- Alertas para erros críticos
- Dashboard com estatísticas de mensagens enviadas/recebidas

### Logging

- Structured logging (JSON format)
- Níveis de log: DEBUG, INFO, WARNING, ERROR, CRITICAL
- Rotação de logs
- Centralização de logs (ELK stack ou similar)
