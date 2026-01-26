# Meta API Production Deployment Guide

Este guia descreve como preparar e configurar o Meta Provider (Instagram + Messenger) para ambiente de produção.

## Índice

1. [Visão Geral](#visão-geral)
2. [Rate Limiting](#rate-limiting)
3. [Retry Policies](#retry-policies)
4. [Circuit Breaker](#circuit-breaker)
5. [Alertas](#alertas)
6. [Monitoramento](#monitoramento)
7. [Configuração](#configuração)
8. [Checklist de Deploy](#checklist-de-deploy)

## Visão Geral

O Meta Provider implementa várias camadas de proteção e resiliência para garantir operação estável em produção:

- **Rate Limiting**: Previne exceder limites da Meta API
- **Retry Policies**: Recuperação automática de falhas transientes
- **Circuit Breaker**: Proteção contra falhas em cascata
- **Alertas**: Notificação proativa de problemas
- **Monitoramento**: Métricas e observabilidade

## Rate Limiting

### Limites da Meta API

A Meta API impõe os seguintes limites por Page Access Token:

- **200 requests/hora** - Limite global
- **4800 requests/dia** - Limite global
- **60 requests/minuto** - Endpoint de mensagens
- **30 requests/minuto** - Criação de templates
- **200 requests/minuto** - Insights/métricas

### Implementação

O `MetaRateLimiter` rastreia uso em tempo real usando Redis:

```php
use WhatsApp\Adapter\Services\MetaRateLimiter;

$rateLimiter = new MetaRateLimiter($redis, $logger);

// Verificar se pode fazer requisição
if ($rateLimiter->allowRequest($pageAccessToken, 'messages')) {
    // Fazer requisição
    $result = $metaProvider->sendText($request);

    // Registrar uso
    $rateLimiter->recordRequest($pageAccessToken, 'messages');
}
```

### Configuração

```bash
# .env
META_RATE_LIMITING_ENABLED=true
META_HOURLY_LIMIT=200
META_DAILY_LIMIT=4800
META_MESSAGES_LIMIT_PER_MIN=60
```

### Monitoramento de Uso

```php
// Obter uso atual
$usage = $rateLimiter->getUsage($pageAccessToken);

echo "Hourly: {$usage['hourly']['used']}/{$usage['hourly']['limit']}\n";
echo "Daily: {$usage['daily']['used']}/{$usage['daily']['limit']}\n";
```

## Retry Policies

### Estratégia de Retry

O `MetaRetryPolicy` implementa exponential backoff com os seguintes comportamentos:

**Erros com Retry:**

- Erros de servidor (5xx)
- Rate limit (429)
- Timeouts e erros de conexão

**Erros sem Retry (permanentes):**

- 36103: Account not eligible
- 2534068: Feature not available
- 190: Invalid token
- 200: Permission error
- 551: User not available
- 2022: Messaging window expired

### Implementação

```php
use WhatsApp\Adapter\Services\MetaRetryPolicy;

$retryPolicy = new MetaRetryPolicy($logger, $maxRetries = 3);

$result = $retryPolicy->execute(
    operation: fn() => $metaProvider->sendText($request),
    context: 'send_message'
);
```

### Configuração de Backoff

- **Tentativa 1**: 1 segundo
- **Tentativa 2**: 2 segundos
- **Tentativa 3**: 4 segundos
- **Tentativa 4**: 8 segundos
- **Máximo**: 16 segundos

### Configuração

```bash
# .env
META_RETRY_ENABLED=true
META_MAX_RETRIES=3
META_INITIAL_DELAY_MS=1000
META_MAX_DELAY_MS=16000
```

## Circuit Breaker

### Estados do Circuit Breaker

O `MetaCircuitBreaker` opera em três estados:

1. **CLOSED** (Normal)

   - Requisições passam normalmente
   - Falhas são contadas

2. **OPEN** (Bloqueado)

   - Requisições são bloqueadas imediatamente
   - Ativado após N falhas consecutivas
   - Permanece por X segundos

3. **HALF_OPEN** (Testando)
   - Permite requisições de teste
   - Se sucesso: volta para CLOSED
   - Se falha: volta para OPEN

### Implementação

```php
use WhatsApp\Adapter\Services\MetaCircuitBreaker;

$circuitBreaker = new MetaCircuitBreaker(
    redis: $redis,
    logger: $logger,
    failureThreshold: 5,
    successThreshold: 2,
    timeoutSeconds: 60
);

try {
    $result = $circuitBreaker->execute(
        serviceName: 'meta_api',
        operation: fn() => $metaProvider->sendText($request)
    );
} catch (CircuitBreakerOpenException $e) {
    // Circuit breaker está aberto
    // Implementar fallback ou retornar erro ao cliente
}
```

### Monitoramento

```php
// Verificar estado
$isAvailable = $circuitBreaker->isAvailable('meta_api');

// Obter estatísticas
$stats = $circuitBreaker->getStats('meta_api');
print_r($stats);
/*
Array (
    [service] => meta_api
    [state] => closed
    [failures] => 2
    [successes] => 0
    [failure_threshold] => 5
    [success_threshold] => 2
    [is_available] => true
)
*/
```

### Configuração

```bash
# .env
META_CIRCUIT_BREAKER_ENABLED=true
META_CB_FAILURE_THRESHOLD=5      # Falhas antes de abrir
META_CB_SUCCESS_THRESHOLD=2      # Sucessos para fechar
META_CB_TIMEOUT_SECONDS=60       # Tempo em OPEN
META_CB_WINDOW_SECONDS=300       # Janela para contar falhas
```

## Alertas

### Tipos de Alerta

O `MetaAlertManager` monitora e alerta sobre:

1. **API Errors** - Erros da Meta API
2. **Webhook Failures** - Falhas no processamento de webhooks
3. **Rate Limit** - Limites atingidos
4. **Circuit Breaker** - Circuit breaker aberto
5. **Performance** - Degradação de performance
6. **Token Expiring** - Token próximo da expiração

### Severidades

- **INFO**: Informativo, não requer ação
- **WARNING**: Atenção necessária
- **ERROR**: Erro que afeta funcionalidade
- **CRITICAL**: Erro crítico, ação imediata necessária

### Implementação

```php
use WhatsApp\Adapter\Services\MetaAlertManager;

$alertManager = new MetaAlertManager(
    logger: $logger,
    notifier: $criticalErrorNotifier,
    redis: $redis
);

// Alertar sobre erro de API
$alertManager->alertApiError(
    errorCode: '190',
    errorMessage: 'Invalid OAuth access token',
    context: ['platform' => 'instagram']
);

// Alertar sobre rate limit
$alertManager->alertRateLimitReached(
    limitType: 'hourly',
    currentUsage: 195,
    limit: 200,
    context: ['percentage' => 97.5]
);

// Alertar sobre circuit breaker
$alertManager->alertCircuitBreakerOpen(
    serviceName: 'meta_api',
    failureCount: 5,
    context: ['last_error' => 'Connection timeout']
);
```

### Cooldown de Alertas

Para evitar spam, alertas têm cooldown configurável:

- **INFO**: 1 hora
- **WARNING**: 30 minutos
- **ERROR**: 15 minutos
- **CRITICAL**: Sem cooldown (sempre alerta)

### Configuração

```bash
# .env
META_ALERTS_ENABLED=true
META_ALERT_COOLDOWN_INFO=3600
META_ALERT_COOLDOWN_WARNING=1800
META_ALERT_COOLDOWN_ERROR=900
META_ALERT_COOLDOWN_CRITICAL=0

# Thresholds de performance
META_PERF_RESPONSE_TIME_MS=5000
META_PERF_ERROR_RATE=5.0
META_PERF_SUCCESS_RATE=95.0

# Alerta de token expirando
META_TOKEN_EXPIRY_WARNING_DAYS=30
```

### Canais de Notificação

Alertas são enviados via `CriticalErrorNotifier`:

- **Email**: Configurar SMTP
- **Slack**: Webhook URL
- **Webhook**: Endpoint customizado

```bash
# .env
CRITICAL_ERROR_EMAIL_ENABLED=true
CRITICAL_ERROR_EMAIL_TO=ops@example.com,admin@example.com

CRITICAL_ERROR_SLACK_ENABLED=true
CRITICAL_ERROR_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/XXX

CRITICAL_ERROR_WEBHOOK_ENABLED=true
CRITICAL_ERROR_WEBHOOK_URL=https://monitoring.example.com/webhooks
```

## Monitoramento

### Métricas Coletadas

- **Request Count**: Número de requisições
- **Error Count**: Número de erros
- **Response Time**: Tempo de resposta
- **Rate Limit Usage**: Uso de rate limit
- **Circuit Breaker State**: Estado do circuit breaker
- **Webhook Count**: Webhooks recebidos

### Estatísticas de Alertas

```php
// Obter estatísticas de alertas das últimas 24 horas
$stats = $alertManager->getAlertStats(hours: 24);

print_r($stats);
/*
Array (
    [period_hours] => 24
    [by_type] => Array (
        [api_error] => 5
        [webhook_failure] => 2
        [rate_limit] => 3
        [circuit_breaker] => 1
        [performance] => 0
        [token_expiring] => 0
    )
    [by_severity] => Array (
        [info] => 0
        [warning] => 3
        [error] => 7
        [critical] => 1
    )
    [total] => 11
)
*/
```

### Configuração

```bash
# .env
META_MONITORING_ENABLED=true
META_METRICS_INTERVAL=60           # Agregação a cada 60s
META_METRICS_RETENTION_DAYS=30     # Reter por 30 dias
```

## Configuração

### Arquivo de Configuração

Todas as configurações estão em `config/meta_production.php`:

```php
return [
    'rate_limiting' => [...],
    'retry_policy' => [...],
    'circuit_breaker' => [...],
    'alerts' => [...],
    'monitoring' => [...],
    'timeouts' => [...],
    'cache' => [...],
    'dead_letter_queue' => [...],
    'logging' => [...],
];
```

### Variáveis de Ambiente

Copie `.env.example` para `.env` e configure:

```bash
cp .env.example .env
```

Edite as variáveis Meta:

```bash
# Credenciais Meta
META_PAGE_ACCESS_TOKEN=your_token_here
META_APP_ID=your_app_id
META_APP_SECRET=your_app_secret
META_PAGE_ID=your_page_id

# Rate Limiting
META_RATE_LIMITING_ENABLED=true
META_HOURLY_LIMIT=200
META_DAILY_LIMIT=4800

# Retry Policy
META_RETRY_ENABLED=true
META_MAX_RETRIES=3

# Circuit Breaker
META_CIRCUIT_BREAKER_ENABLED=true
META_CB_FAILURE_THRESHOLD=5

# Alerts
META_ALERTS_ENABLED=true
```

## Checklist de Deploy

### Pré-Deploy

- [ ] Configurar variáveis de ambiente no `.env`
- [ ] Validar credenciais Meta (Page Access Token, App Secret)
- [ ] Configurar Redis para rate limiting e circuit breaker
- [ ] Configurar canais de notificação (Email, Slack, Webhook)
- [ ] Revisar thresholds de rate limiting
- [ ] Revisar configurações de retry policy
- [ ] Revisar configurações de circuit breaker
- [ ] Configurar alertas e cooldowns

### Deploy

- [ ] Fazer backup do banco de dados
- [ ] Fazer deploy do código
- [ ] Executar migrações de banco de dados
- [ ] Verificar conectividade com Meta API
- [ ] Verificar conectividade com Redis
- [ ] Testar envio de mensagem de teste
- [ ] Testar recebimento de webhook
- [ ] Verificar logs de aplicação

### Pós-Deploy

- [ ] Monitorar métricas de rate limiting
- [ ] Monitorar estado do circuit breaker
- [ ] Verificar alertas sendo enviados corretamente
- [ ] Monitorar taxa de erro
- [ ] Monitorar tempo de resposta
- [ ] Verificar processamento de webhooks
- [ ] Revisar logs de erro
- [ ] Documentar quaisquer issues encontrados

### Monitoramento Contínuo

- [ ] Configurar dashboard de métricas
- [ ] Configurar alertas de SLA
- [ ] Revisar alertas semanalmente
- [ ] Revisar métricas de performance mensalmente
- [ ] Atualizar thresholds conforme necessário
- [ ] Documentar incidentes e resoluções

## Troubleshooting

### Rate Limit Atingido

**Sintoma**: Erro "Rate limit exceeded"

**Solução**:

1. Verificar uso atual: `$rateLimiter->getUsage($token)`
2. Aguardar reset do limite (1 hora ou 24 horas)
3. Considerar aumentar limites se possível
4. Implementar queue para distribuir carga

### Circuit Breaker Aberto

**Sintoma**: `CircuitBreakerOpenException`

**Solução**:

1. Verificar estado: `$circuitBreaker->getStats('meta_api')`
2. Investigar causa das falhas nos logs
3. Aguardar timeout (60 segundos padrão)
4. Resetar manualmente se necessário: `$circuitBreaker->reset('meta_api')`

### Alertas Excessivos

**Sintoma**: Muitos alertas sendo enviados

**Solução**:

1. Revisar cooldowns de alerta
2. Aumentar thresholds se apropriado
3. Verificar se há problema real causando alertas
4. Ajustar severidades de alertas

### Performance Degradada

**Sintoma**: Tempo de resposta alto

**Solução**:

1. Verificar métricas de response time
2. Verificar conectividade com Meta API
3. Verificar se rate limit está sendo atingido
4. Verificar se circuit breaker está abrindo frequentemente
5. Revisar logs para erros

## Suporte

Para problemas ou dúvidas:

1. Consultar logs em `storage/logs/`
2. Verificar documentação da Meta API: https://developers.facebook.com/docs/messenger-platform
3. Consultar troubleshooting guide: `docs/TROUBLESHOOTING.md`
4. Contatar equipe de desenvolvimento

## Referências

- [Meta Messenger Platform API](https://developers.facebook.com/docs/messenger-platform)
- [Meta Instagram Messaging API](https://developers.facebook.com/docs/messenger-platform/instagram)
- [Rate Limiting Best Practices](https://developers.facebook.com/docs/graph-api/overview/rate-limiting)
- [Circuit Breaker Pattern](https://martinfowler.com/bliki/CircuitBreaker.html)
