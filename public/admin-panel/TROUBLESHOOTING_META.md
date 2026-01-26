# Troubleshooting Guide

Guia completo para resolver problemas comuns no WhatsApp HSM Adapter, incluindo WhatsApp, Instagram e Facebook Messenger.

## Índice

- [Problemas Gerais](#problemas-gerais)
- [Problemas WhatsApp](#problemas-whatsapp)
- [Problemas Meta (Instagram/Messenger)](#problemas-meta-instagrammessenger)
- [Problemas de Webhook](#problemas-de-webhook)
- [Problemas de Performance](#problemas-de-performance)
- [Problemas de Banco de Dados](#problemas-de-banco-de-dados)
- [Debugging](#debugging)
- [Logs e Monitoramento](#logs-e-monitoramento)

## Problemas Gerais

### Mensagens não são enviadas

**Sintomas:**

- API retorna erro
- Mensagens não chegam ao destinatário
- Timeout nas requisições

**Diagnóstico:**

1. Verifique as credenciais no `.env`:

```bash
php bin/verify-config.php
```

2. Teste conectividade com a API:

```bash
curl -X GET https://your-domain.com/health
```

3. Verifique os logs:

```bash
tail -f storage/logs/whatsapp-adapter.log | jq
```

**Soluções:**

- **Credenciais inválidas**: Regenere as credenciais no painel do provider
- **Timeout**: Aumente o timeout em `config/providers.php`
- **Rate limit**: Implemente retry com backoff exponencial
- **Firewall**: Verifique se o servidor pode acessar as APIs externas

### Erro: "Provider not found"

**Causa:** Provider não está configurado ou nome incorreto.

**Solução:**

1. Verifique o nome do provider em `config/providers.php`:

```php
'providers' => [
    'infobip' => [...],
    'twilio' => [...],
    'meta' => [...],
]
```

2. Verifique se está usando o nome correto na requisição:

```json
{
  "provider": "meta" // não "instagram" ou "messenger"
}
```

3. Limpe o cache de configuração:

```bash
php bin/clear-cache.php
```

### Erro: "Invalid authentication credentials"

**Causa:** API key ou token inválido/expirado.

**Solução:**

1. **WhatsApp (Infobip/Twilio):**

   - Verifique `INFOBIP_API_KEY` ou `TWILIO_AUTH_TOKEN`
   - Regenere a chave no painel do provider

2. **Meta (Instagram/Messenger):**

   - Verifique `META_PAGE_ACCESS_TOKEN`
   - Gere novo token de longa duração:

   ```bash
   curl -X GET "https://graph.facebook.com/v21.0/oauth/access_token?\
   grant_type=fb_exchange_token&\
   client_id=YOUR_APP_ID&\
   client_secret=YOUR_APP_SECRET&\
   fb_exchange_token=YOUR_SHORT_TOKEN"
   ```

3. Teste o token:

```bash
# Meta
curl -X GET "https://graph.facebook.com/v21.0/me?access_token=YOUR_TOKEN"

# Infobip
curl -X GET "https://api.infobip.com/whatsapp/1/senders" \
  -H "Authorization: App YOUR_API_KEY"
```

### Erro: "Rate limit exceeded"

**Causa:** Muitas requisições em curto período.

**Solução:**

1. Implemente rate limiting no cliente:

```php
use App\Services\RateLimiter;

$limiter = new RateLimiter(100, 60); // 100 req/min
if ($limiter->allow($userId)) {
    $provider->sendText($request);
} else {
    throw new RateLimitException('Rate limit exceeded');
}
```

2. Use exponential backoff para retries:

```php
$maxRetries = 3;
$delay = 1;

for ($i = 0; $i < $maxRetries; $i++) {
    try {
        return $provider->sendText($request);
    } catch (RateLimitException $e) {
        if ($i === $maxRetries - 1) throw $e;
        sleep($delay);
        $delay *= 2;
    }
}
```

3. Distribua requisições ao longo do tempo:

```php
// Use queue para processar mensagens
$queue->push(new SendMessageJob($message));
```

4. Ajuste limites em `.env`:

```bash
RATE_LIMIT_PER_MINUTE=200
```

## Problemas WhatsApp

### Erro: "Template not found"

**Causa:** Template não existe ou não foi sincronizado.

**Solução:**

1. Sincronize templates:

```bash
curl -X POST https://your-domain.com/api/templates/sync \
  -H "Authorization: Bearer YOUR_API_KEY"
```

2. Verifique se o template existe:

```bash
curl -X GET https://your-domain.com/api/templates \
  -H "Authorization: Bearer YOUR_API_KEY"
```

3. Verifique o nome e idioma do template:

```json
{
  "templateName": "welcome_message", // Nome exato
  "templateLanguage": "pt" // Código correto
}
```

4. Aguarde aprovação do template no painel do provider (pode levar horas/dias).

### Erro: "Invalid phone number format"

**Causa:** Número de telefone em formato incorreto.

**Solução:**

Use formato E.164 (sem espaços, hífens ou parênteses):

```
✅ Correto: +351912345678
❌ Errado: 351 912 345 678
❌ Errado: +351-912-345-678
❌ Errado: (351) 912345678
```

Validação:

```php
function validatePhoneNumber(string $phone): bool {
    return preg_match('/^\+[1-9]\d{1,14}$/', $phone);
}
```

### Erro: "Template parameter mismatch"

**Causa:** Número de parâmetros não corresponde ao template.

**Solução:**

1. Verifique quantos placeholders o template tem:

```
Template: "Olá {{1}}, seu código é {{2}}"
Parâmetros necessários: 2
```

2. Forneça o número correto de parâmetros:

```json
{
  "templateName": "verification_code",
  "parameters": ["João", "123456"] // 2 parâmetros
}
```

3. Ordem importa - {{1}} = primeiro parâmetro, {{2}} = segundo, etc.

## Problemas Meta (Instagram/Messenger)

### Erro: "Invalid OAuth access token" (190)

**Causa:** Token expirado, inválido ou sem permissões.

**Solução:**

1. Verifique se o token é válido:

```bash
curl -X GET "https://graph.facebook.com/v21.0/me?access_token=YOUR_TOKEN"
```

2. Verifique permissões do token:

```bash
curl -X GET "https://graph.facebook.com/v21.0/me/permissions?access_token=YOUR_TOKEN"
```

Deve incluir:

- `pages_messaging`
- `pages_read_engagement`
- `instagram_manage_messages` (para Instagram)

3. Gere novo token de longa duração:

```bash
curl -X GET "https://graph.facebook.com/v21.0/oauth/access_token?\
grant_type=fb_exchange_token&\
client_id=YOUR_APP_ID&\
client_secret=YOUR_APP_SECRET&\
fb_exchange_token=YOUR_SHORT_TOKEN"
```

4. Certifique-se de usar **Page Access Token**, não User Access Token.

### Erro: "Account not eligible for messages" (36103)

**Causa:** Conta Instagram não é Professional/Business ou não está conectada.

**Solução:**

1. **Converter conta para Professional:**

   - Abra Instagram app
   - Settings → Account → Switch to Professional Account
   - Escolha Business ou Creator

2. **Conectar à Facebook Page:**

   - Instagram Settings → Linked Accounts → Facebook
   - Ou via Facebook Page Settings → Instagram

3. **Verificar conexão:**

```bash
curl -X GET "https://graph.facebook.com/v21.0/YOUR_PAGE_ID?\
fields=instagram_business_account&\
access_token=YOUR_TOKEN"
```

4. **Aceitar solicitação de mensagem:**
   - Usuário deve ter enviado mensagem primeiro
   - Ou aceito solicitação de mensagem da sua conta

### Erro: "Feature not available" (2534068)

**Causa:** Recurso não disponível na região ou para a conta.

**Solução:**

1. Verifique disponibilidade regional:

   - Alguns recursos não estão disponíveis em todos os países
   - Consulte [Meta Platform Status](https://developers.facebook.com/status/)

2. Verifique permissões do app:

   - Vá para App Dashboard → App Review
   - Solicite permissões necessárias

3. Verifique tipo de conta:

   - Instagram deve ser Professional ou Business
   - Facebook Page deve estar ativa

4. Aguarde aprovação do App Review (se necessário).

### Erro: "This message is sent outside of allowed window" (2022)

**Causa:** Tentando enviar mensagem após 24 horas da última mensagem do usuário.

**Solução:**

1. **Verifique timestamp da última mensagem:**

```php
$lastMessage = $repository->getLastUserMessage($igsid);
$hoursSince = (time() - $lastMessage->timestamp) / 3600;

if ($hoursSince > 24) {
    throw new MessagingWindowExpiredException(
        "Messaging window expired {$hoursSince} hours ago"
    );
}
```

2. **Use Message Tags (casos específicos):**

```json
{
  "recipient": "IGSID",
  "message": {
    "text": "Seu pedido foi enviado!",
    "tag": "POST_PURCHASE_UPDATE"
  }
}
```

Tags disponíveis:

- `CONFIRMED_EVENT_UPDATE` - Atualizações de eventos confirmados
- `POST_PURCHASE_UPDATE` - Atualizações pós-compra
- `ACCOUNT_UPDATE` - Atualizações de conta

3. **Aguarde usuário enviar nova mensagem** para reabrir janela.

4. **Implemente notificação:**

```php
if ($hoursSince > 24) {
    // Enviar email/SMS em vez de mensagem direta
    $notifier->sendEmail($user, $message);
}
```

### Erro: "Invalid IGSID/PSID format"

**Causa:** Identificador em formato incorreto.

**Solução:**

1. **IGSID (Instagram):**

   - Deve ser string numérica
   - Exemplo: `"1234567890"`
   - Obtido de webhooks de mensagens recebidas

2. **PSID (Messenger):**

   - Deve ser string numérica
   - Exemplo: `"9876543210"`
   - Obtido de webhooks de mensagens recebidas

3. **Não use:**

   - Username do Instagram (@username)
   - Nome da pessoa
   - Email ou telefone

4. **Obter IGSID/PSID:**

```php
// De webhook
$igsid = $payload['entry'][0]['messaging'][0]['sender']['id'];

// Armazenar para uso futuro
$repository->saveUserIdentifier($userId, $igsid, 'instagram');
```

### Erro: "Permission denied" (10)

**Causa:** App não tem permissões necessárias.

**Solução:**

1. **Verifique permissões atuais:**

```bash
curl -X GET "https://graph.facebook.com/v21.0/YOUR_APP_ID/permissions?access_token=YOUR_TOKEN"
```

2. **Solicite permissões no App Review:**

   - App Dashboard → App Review → Permissions and Features
   - Solicite: `pages_messaging`, `instagram_manage_messages`
   - Forneça vídeo demonstrativo
   - Explique caso de uso

3. **Use Standard Access para testes:**

   - Durante desenvolvimento, Standard Access permite testar
   - Funciona apenas com contas de teste/admin

4. **Aguarde aprovação** (pode levar dias/semanas).

## Problemas de Webhook

### Webhook não recebe mensagens

**Sintomas:**

- Mensagens enviadas mas webhook não é chamado
- Logs não mostram requisições de webhook
- Status de entrega não atualiza

**Diagnóstico:**

1. Verifique se URL é acessível:

```bash
curl -X GET https://your-domain.com/webhook/meta
```

2. Verifique logs do servidor web:

```bash
# Nginx
tail -f /var/log/nginx/access.log | grep webhook

# Apache
tail -f /var/log/apache2/access.log | grep webhook
```

3. Teste webhook manualmente:

```bash
curl -X POST https://your-domain.com/webhook/meta \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=test" \
  -d '{"test": true}'
```

**Soluções:**

1. **URL não acessível:**

   - Certifique-se de usar HTTPS (obrigatório)
   - Verifique firewall/security groups
   - Teste com [webhook.site](https://webhook.site) primeiro

2. **Certificado SSL inválido:**

```bash
# Verificar certificado
openssl s_client -connect your-domain.com:443 -servername your-domain.com

# Renovar Let's Encrypt
certbot renew
```

3. **Eventos não subscritos:**

   - Vá para App Dashboard → Webhooks
   - Verifique se subscreveu aos eventos corretos
   - Subscreva: messages, messaging_postbacks, message_deliveries, message_reads

4. **Webhook local (desenvolvimento):**

```bash
# Use ngrok para expor localhost
ngrok http 8000

# Use a URL gerada no dashboard Meta
# Exemplo: https://abc123.ngrok.io/webhook/meta
```

### Erro: "Webhook verification failed"

**Causa:** Verify token incorreto ou validação de assinatura falhou.

**Solução:**

1. **Verificação GET inicial:**

```php
// Deve responder com hub.challenge
if ($_GET['hub_mode'] === 'subscribe' &&
    $_GET['hub_verify_token'] === getenv('META_VERIFY_TOKEN')) {
    echo $_GET['hub_challenge'];
    exit;
}
```

2. **Validação de assinatura POST:**

```php
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'];
$body = file_get_contents('php://input');
$expected = 'sha256=' . hash_hmac('sha256', $body, getenv('META_APP_SECRET'));

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}
```

3. **Verifique App Secret:**

```bash
# Deve estar correto no .env
META_APP_SECRET=your_app_secret_here
```

4. **Use hash_equals para comparação segura** (previne timing attacks).

### Webhook recebe mas não processa

**Causa:** Erro no processamento do payload.

**Solução:**

1. **Log do payload completo:**

```php
$payload = json_decode(file_get_contents('php://input'), true);
$logger->info('Webhook received', ['payload' => $payload]);
```

2. **Verifique estrutura do payload:**

```php
// Instagram
$entry = $payload['entry'][0] ?? null;
$messaging = $entry['messaging'][0] ?? null;

if (!$messaging) {
    $logger->error('Invalid webhook structure', ['payload' => $payload]);
    return;
}
```

3. **Trate erros gracefully:**

```php
try {
    $handler->processIncomingMessage($payload);
} catch (\Exception $e) {
    $logger->error('Webhook processing failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    // Adicionar a dead letter queue
    $deadLetterQueue->add($payload, $e->getMessage());
}
```

4. **Sempre retorne 200 OK rapidamente:**

```php
// Processar assincronamente
$queue->push(new ProcessWebhookJob($payload));

// Retornar imediatamente
http_response_code(200);
echo 'OK';
```

### Webhook timeout

**Causa:** Processamento muito lento.

**Solução:**

1. **Processar assincronamente:**

```php
// Receber webhook
$payload = json_decode(file_get_contents('php://input'), true);

// Adicionar a queue
$queue->push(new ProcessWebhookJob($payload));

// Retornar imediatamente
http_response_code(200);
exit('OK');
```

2. **Otimizar queries:**

```php
// Ruim: N+1 queries
foreach ($messages as $message) {
    $user = $repository->findUser($message->userId);
}

// Bom: 1 query
$userIds = array_column($messages, 'userId');
$users = $repository->findUsers($userIds);
```

3. **Usar cache:**

```php
$user = $cache->remember("user:{$userId}", 3600, function() use ($userId) {
    return $repository->findUser($userId);
});
```

4. **Aumentar timeout do servidor:**

```nginx
# Nginx
fastcgi_read_timeout 300;
proxy_read_timeout 300;
```

## Problemas de Performance

### Requisições lentas

**Sintomas:**

- API demora para responder
- Timeout em requisições
- Alta latência

**Diagnóstico:**

1. **Medir tempo de resposta:**

```bash
time curl -X POST https://your-domain.com/api/messages/send \
  -H "Content-Type: application/json" \
  -d '{"provider": "meta", "recipient": "123", "message": {"text": "test"}}'
```

2. **Verificar logs de performance:**

```bash
grep "duration" storage/logs/whatsapp-adapter.log | tail -20
```

3. **Usar profiler:**

```php
$start = microtime(true);
$provider->sendText($request);
$duration = microtime(true) - $start;
$logger->info('Send duration', ['duration' => $duration]);
```

**Soluções:**

1. **Implementar cache:**

```php
// Cache de templates
$templates = $cache->remember('templates:meta', 3600, function() {
    return $provider->getTemplates();
});

// Cache de status
$status = $cache->remember("message:{$messageId}:status", 300, function() {
    return $repository->getMessageStatus($messageId);
});
```

2. **Connection pooling:**

```php
// Reutilizar conexões HTTP
$client = new Client([
    'base_uri' => 'https://graph.facebook.com',
    'timeout' => 30,
    'connect_timeout' => 10,
    'http_errors' => false,
    'handler' => HandlerStack::create()
]);
```

3. **Otimizar queries:**

```php
// Usar índices
CREATE INDEX idx_messages_provider ON messages(provider);
CREATE INDEX idx_messages_status ON messages(status);
CREATE INDEX idx_messages_created_at ON messages(created_at);

// Usar EXPLAIN para analisar queries
EXPLAIN SELECT * FROM messages WHERE provider = 'meta' AND status = 'sent';
```

4. **Usar queue para operações pesadas:**

```php
// Enviar via queue
$queue->push(new SendMessageJob($message));

// Processar assincronamente
php bin/queue-worker.php
```

### Alto uso de memória

**Sintomas:**

- PHP Fatal error: Allowed memory size exhausted
- Servidor lento
- OOM (Out of Memory) errors

**Diagnóstico:**

```php
echo "Memory usage: " . memory_get_usage(true) / 1024 / 1024 . " MB\n";
echo "Peak memory: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB\n";
```

**Soluções:**

1. **Processar em lotes:**

```php
// Ruim: carregar tudo na memória
$messages = $repository->getAllMessages();

// Bom: processar em chunks
$repository->chunkMessages(1000, function($messages) {
    foreach ($messages as $message) {
        processMessage($message);
    }
});
```

2. **Liberar memória:**

```php
foreach ($messages as $message) {
    processMessage($message);
    unset($message); // Liberar memória
}
gc_collect_cycles(); // Forçar garbage collection
```

3. **Aumentar limite de memória:**

```php
// php.ini
memory_limit = 512M

// Ou no código (temporário)
ini_set('memory_limit', '512M');
```

4. **Usar generators:**

```php
function getMessages(): \Generator {
    $offset = 0;
    $limit = 100;

    while (true) {
        $messages = $repository->getMessages($offset, $limit);
        if (empty($messages)) break;

        foreach ($messages as $message) {
            yield $message;
        }

        $offset += $limit;
    }
}

// Uso
foreach (getMessages() as $message) {
    processMessage($message);
}
```

### Muitas conexões ao banco

**Sintomas:**

- "Too many connections" error
- Queries lentas
- Timeouts

**Solução:**

1. **Connection pooling:**

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST'),
    'port' => env('DB_PORT'),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
    'options' => [
        PDO::ATTR_PERSISTENT => true, // Connection pooling
        PDO::ATTR_TIMEOUT => 5,
    ],
],
```

2. **Fechar conexões:**

```php
try {
    $result = $repository->save($message);
} finally {
    $connection->close();
}
```

3. **Aumentar limite no MySQL:**

```sql
SET GLOBAL max_connections = 500;
```

4. **Monitorar conexões:**

```sql
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Threads_connected';
```

## Problemas de Banco de Dados

### Erro: "Table doesn't exist"

**Causa:** Migrations não foram executadas.

**Solução:**

```bash
# Executar todas as migrations
php bin/migrate.php

# Ou manualmente
mysql -u root -p whatsapp_adapter < database/migrations/001_create_messages_table.sql
mysql -u root -p whatsapp_adapter < database/migrations/002_create_incoming_messages_table.sql
mysql -u root -p whatsapp_adapter < database/migrations/003_create_templates_table.sql
mysql -u root -p whatsapp_adapter < database/migrations/004_create_webhook_logs_table.sql
```

### Erro: "Duplicate entry"

**Causa:** Tentando inserir registro com chave primária duplicada.

**Solução:**

1. **Usar UPSERT:**

```php
// MySQL
INSERT INTO messages (id, provider, recipient, text)
VALUES (?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
    text = VALUES(text),
    updated_at = NOW();
```

2. **Verificar antes de inserir:**

```php
$existing = $repository->findById($messageId);
if ($existing) {
    $repository->update($messageId, $data);
} else {
    $repository->insert($data);
}
```

3. **Usar UUID em vez de auto-increment:**

```php
use Ramsey\Uuid\Uuid;

$messageId = Uuid::uuid4()->toString();
```

### Queries lentas

**Diagnóstico:**

```sql
-- Habilitar slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Ver queries lentas
SELECT * FROM mysql.slow_log ORDER BY query_time DESC LIMIT 10;
```

**Solução:**

1. **Adicionar índices:**

```sql
-- Índices comuns
CREATE INDEX idx_messages_provider ON messages(provider);
CREATE INDEX idx_messages_status ON messages(status);
CREATE INDEX idx_messages_created_at ON messages(created_at);
CREATE INDEX idx_messages_recipient ON messages(recipient);

-- Índice composto
CREATE INDEX idx_messages_provider_status ON messages(provider, status);
```

2. **Analisar queries:**

```sql
EXPLAIN SELECT * FROM messages
WHERE provider = 'meta' AND status = 'sent'
ORDER BY created_at DESC
LIMIT 100;
```

3. **Otimizar queries:**

```php
// Ruim: SELECT *
$messages = $db->query("SELECT * FROM messages");

// Bom: SELECT apenas campos necessários
$messages = $db->query("SELECT id, provider, recipient, status FROM messages");
```

4. **Usar paginação:**

```php
// Ruim: carregar tudo
$messages = $repository->getAllMessages();

// Bom: paginar
$messages = $repository->getMessages($page, $perPage);
```

## Debugging

### Habilitar modo debug

```bash
# .env
LOG_LEVEL=debug
APP_DEBUG=true
```

### Logs detalhados

```php
// Adicionar contexto aos logs
$logger->debug('Sending message', [
    'provider' => 'meta',
    'recipient' => $igsid,
    'message_type' => 'text',
    'request_id' => $requestId
]);

// Log de exceções
try {
    $provider->sendText($request);
} catch (\Exception $e) {
    $logger->error('Send failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'request' => $request
    ]);
    throw $e;
}
```

### Testar componentes isoladamente

```php
// test-meta-provider.php
<?php
require 'vendor/autoload.php';

use App\Providers\Meta\MetaProvider;
use GuzzleHttp\Client;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('test');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

$config = require 'config/meta.php';
$client = new Client();

$provider = new MetaProvider($client, $config, $logger);

// Testar envio
$request = new TextRequest('1234567890', 'Test message');
$result = $provider->sendText($request);

var_dump($result);
```

### Usar debugger

```php
// Xdebug
xdebug_break();

// Ou var_dump
var_dump($variable);
die();

// Ou print_r
print_r($array);
exit;
```

### Capturar requisições HTTP

```bash
# Usar tcpdump
sudo tcpdump -i any -A 'host graph.facebook.com'

# Ou usar proxy como mitmproxy
mitmproxy -p 8080

# Configurar proxy no código
$client = new Client([
    'proxy' => 'http://localhost:8080',
    'verify' => false
]);
```

## Logs e Monitoramento

### Estrutura de Logs

```json
{
  "timestamp": "2025-01-19T10:30:00Z",
  "level": "info",
  "message": "Message sent successfully",
  "context": {
    "provider": "meta",
    "platform": "instagram",
    "message_id": "msg_abc123",
    "recipient": "1234567890",
    "duration_ms": 245,
    "request_id": "req_xyz789"
  }
}
```

### Visualizar logs

```bash
# Logs em tempo real
tail -f storage/logs/whatsapp-adapter.log | jq

# Filtrar por provider
grep "meta" storage/logs/whatsapp-adapter.log | jq

# Filtrar por nível
grep "error" storage/logs/whatsapp-adapter.log | jq

# Últimos 100 erros
grep "error" storage/logs/whatsapp-adapter.log | tail -100 | jq
```

### Métricas importantes

1. **Taxa de sucesso:**

```bash
# Total de mensagens enviadas
grep "Message sent" storage/logs/whatsapp-adapter.log | wc -l

# Total de erros
grep "Send failed" storage/logs/whatsapp-adapter.log | wc -l

# Taxa de sucesso
echo "scale=2; (success / (success + errors)) * 100" | bc
```

2. **Tempo de resposta:**

```bash
# Tempo médio de resposta
grep "duration_ms" storage/logs/whatsapp-adapter.log | \
  jq -r '.context.duration_ms' | \
  awk '{sum+=$1; count++} END {print sum/count}'
```

3. **Erros por tipo:**

```bash
grep "error" storage/logs/whatsapp-adapter.log | \
  jq -r '.context.error_code' | \
  sort | uniq -c | sort -rn
```

### Alertas

Configure alertas para erros críticos:

```php
// src/Services/AlertManager.php
class AlertManager
{
    public function sendAlert(string $message, array $context = []): void
    {
        // Email
        mail('admin@example.com', 'WhatsApp Adapter Alert', $message);

        // Slack
        $this->slack->send([
            'text' => $message,
            'attachments' => [['text' => json_encode($context)]]
        ]);

        // SMS (via Twilio)
        $this->twilio->sendSms('+351912345678', $message);
    }
}

// Uso
if ($errorRate > 0.1) { // 10% de erros
    $alertManager->sendAlert('High error rate detected', [
        'error_rate' => $errorRate,
        'provider' => 'meta'
    ]);
}
```

### Monitoramento com Prometheus

```php
// Expor métricas
// public/metrics.php
<?php
require 'vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

$registry = new CollectorRegistry(new Redis());

// Contador de mensagens enviadas
$counter = $registry->getOrRegisterCounter(
    'whatsapp_adapter',
    'messages_sent_total',
    'Total messages sent',
    ['provider', 'status']
);

// Histograma de latência
$histogram = $registry->getOrRegisterHistogram(
    'whatsapp_adapter',
    'message_send_duration_seconds',
    'Message send duration',
    ['provider']
);

// Renderizar métricas
$renderer = new RenderTextFormat();
echo $renderer->render($registry->getMetricFamilySamples());
```

### Dashboard com Grafana

Exemplo de query Prometheus:

```promql
# Taxa de mensagens por segundo
rate(whatsapp_adapter_messages_sent_total[5m])

# Latência p95
histogram_quantile(0.95, rate(whatsapp_adapter_message_send_duration_seconds_bucket[5m]))

# Taxa de erro
rate(whatsapp_adapter_messages_sent_total{status="error"}[5m]) /
rate(whatsapp_adapter_messages_sent_total[5m])
```

## Comandos Úteis

### Verificar configuração

```bash
# Verificar todas as configurações
php bin/verify-config.php

# Verificar apenas Meta
php bin/verify-config.php --provider=meta

# Testar conectividade
php bin/test-connection.php --provider=meta
```

### Limpar cache

```bash
# Limpar cache Redis
redis-cli FLUSHDB

# Limpar cache de arquivos
rm -rf storage/cache/*

# Limpar logs antigos
find storage/logs -name "*.log" -mtime +30 -delete
```

### Reprocessar mensagens falhas

```bash
# Listar mensagens na dead letter queue
php bin/dlq-list.php

# Reprocessar mensagem específica
php bin/dlq-retry.php --message-id=msg_abc123

# Reprocessar todas
php bin/dlq-retry-all.php
```

### Backup e restore

```bash
# Backup do banco de dados
mysqldump -u root -p whatsapp_adapter > backup_$(date +%Y%m%d).sql

# Restore
mysql -u root -p whatsapp_adapter < backup_20250119.sql

# Backup de logs
tar -czf logs_$(date +%Y%m%d).tar.gz storage/logs/
```

## Recursos Adicionais

### Documentação

- [API Documentation](API.md)
- [Instagram Setup Guide](INSTAGRAM_SETUP.md)
- [Meta Request Adapter](META_REQUEST_ADAPTER.md)
- [Meta Credentials Setup](META_CREDENTIALS_SETUP.md)

### Links Externos

- [Meta for Developers](https://developers.facebook.com/)
- [Messenger Platform Docs](https://developers.facebook.com/docs/messenger-platform)
- [Instagram Messaging API](https://developers.facebook.com/docs/messenger-platform/instagram)
- [Meta Platform Status](https://developers.facebook.com/status/)
- [Meta Developer Community](https://developers.facebook.com/community/)

### Ferramentas

- [Graph API Explorer](https://developers.facebook.com/tools/explorer/)
- [Access Token Debugger](https://developers.facebook.com/tools/debug/accesstoken/)
- [Webhook Tester](https://webhook.site/)
- [ngrok](https://ngrok.com/) - Expor localhost
- [Postman](https://www.postman.com/) - Testar APIs

### Suporte

Se o problema persistir:

1. Verifique [GitHub Issues](https://github.com/your-org/whatsapp-hsm-adapter/issues)
2. Consulte [Stack Overflow](https://stackoverflow.com/questions/tagged/facebook-graph-api)
3. Entre em contato: support@example.com
4. Abra uma issue no GitHub com:
   - Descrição do problema
   - Logs relevantes
   - Passos para reproduzir
   - Versão do PHP e dependências

---

**Última Atualização**: Janeiro 2025  
**Versão**: 2.0  
**Autor**: WhatsApp HSM Adapter Team
