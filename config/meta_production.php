<?php

declare(strict_types=1);

/**
 * Configurações de produção para Meta API (Instagram + Messenger)
 * 
 * Este arquivo contém configurações específicas para ambiente de produção,
 * incluindo rate limiting, retry policies, circuit breaker e alertas.
 */

return [
    // Rate Limiting
    'rate_limiting' => [
        'enabled' => (bool) getenv('META_RATE_LIMITING_ENABLED') ?: true,
        
        // Limites globais por Page Access Token
        'hourly_limit' => (int) getenv('META_HOURLY_LIMIT') ?: 200,
        'daily_limit' => (int) getenv('META_DAILY_LIMIT') ?: 4800,
        
        // Limites específicos por endpoint (requests por minuto)
        'endpoint_limits' => [
            'messages' => (int) getenv('META_MESSAGES_LIMIT_PER_MIN') ?: 60,
            'message_creatives' => (int) getenv('META_CREATIVES_LIMIT_PER_MIN') ?: 30,
            'insights' => (int) getenv('META_INSIGHTS_LIMIT_PER_MIN') ?: 200,
            'webhooks' => (int) getenv('META_WEBHOOKS_LIMIT_PER_MIN') ?: 1000,
        ],
        
        // Ação quando rate limit é atingido
        'on_limit_reached' => 'queue', // 'reject', 'queue', 'wait'
    ],

    // Retry Policy
    'retry_policy' => [
        'enabled' => (bool) getenv('META_RETRY_ENABLED') ?: true,
        
        // Número máximo de tentativas
        'max_retries' => (int) getenv('META_MAX_RETRIES') ?: 3,
        
        // Delay inicial em milissegundos
        'initial_delay_ms' => (int) getenv('META_INITIAL_DELAY_MS') ?: 1000,
        
        // Delay máximo em milissegundos
        'max_delay_ms' => (int) getenv('META_MAX_DELAY_MS') ?: 16000,
        
        // Códigos de erro permanentes (não fazer retry)
        'permanent_error_codes' => [
            36103,   // Account not eligible
            2534068, // Feature not available
            10,      // Permission denied
            100,     // Invalid parameter
            190,     // Invalid token
            200,     // Permission error
            551,     // User not available
            2022,    // Messaging window expired
        ],
        
        // Respeitar header Retry-After
        'respect_retry_after' => true,
    ],

    // Circuit Breaker
    'circuit_breaker' => [
        'enabled' => (bool) getenv('META_CIRCUIT_BREAKER_ENABLED') ?: true,
        
        // Número de falhas antes de abrir o circuit breaker
        'failure_threshold' => (int) getenv('META_CB_FAILURE_THRESHOLD') ?: 5,
        
        // Número de sucessos para fechar o circuit breaker
        'success_threshold' => (int) getenv('META_CB_SUCCESS_THRESHOLD') ?: 2,
        
        // Tempo em segundos no estado OPEN antes de tentar HALF_OPEN
        'timeout_seconds' => (int) getenv('META_CB_TIMEOUT_SECONDS') ?: 60,
        
        // Janela de tempo em segundos para contar falhas
        'window_seconds' => (int) getenv('META_CB_WINDOW_SECONDS') ?: 300,
        
        // Serviços monitorados
        'services' => [
            'meta_api' => true,
            'meta_messages' => true,
            'meta_webhooks' => true,
        ],
    ],

    // Alertas
    'alerts' => [
        'enabled' => (bool) getenv('META_ALERTS_ENABLED') ?: true,
        
        // Cooldown entre alertas do mesmo tipo (em segundos)
        'cooldown_seconds' => [
            'info' => (int) getenv('META_ALERT_COOLDOWN_INFO') ?: 3600,      // 1 hora
            'warning' => (int) getenv('META_ALERT_COOLDOWN_WARNING') ?: 1800, // 30 min
            'error' => (int) getenv('META_ALERT_COOLDOWN_ERROR') ?: 900,      // 15 min
            'critical' => (int) getenv('META_ALERT_COOLDOWN_CRITICAL') ?: 0,  // Sem cooldown
        ],
        
        // Tipos de alerta habilitados
        'alert_types' => [
            'api_error' => true,
            'webhook_failure' => true,
            'rate_limit' => true,
            'circuit_breaker' => true,
            'performance' => true,
            'token_expiring' => true,
        ],
        
        // Thresholds para alertas de performance
        'performance_thresholds' => [
            'response_time_ms' => (int) getenv('META_PERF_RESPONSE_TIME_MS') ?: 5000,
            'error_rate_percent' => (float) getenv('META_PERF_ERROR_RATE') ?: 5.0,
            'success_rate_percent' => (float) getenv('META_PERF_SUCCESS_RATE') ?: 95.0,
        ],
        
        // Alertar quando token expira em X dias
        'token_expiry_warning_days' => (int) getenv('META_TOKEN_EXPIRY_WARNING_DAYS') ?: 30,
    ],

    // Monitoramento
    'monitoring' => [
        'enabled' => (bool) getenv('META_MONITORING_ENABLED') ?: true,
        
        // Métricas a coletar
        'metrics' => [
            'request_count' => true,
            'error_count' => true,
            'response_time' => true,
            'rate_limit_usage' => true,
            'circuit_breaker_state' => true,
            'webhook_count' => true,
        ],
        
        // Intervalo de agregação de métricas (em segundos)
        'aggregation_interval' => (int) getenv('META_METRICS_INTERVAL') ?: 60,
        
        // Retenção de métricas (em dias)
        'retention_days' => (int) getenv('META_METRICS_RETENTION_DAYS') ?: 30,
    ],

    // Timeouts
    'timeouts' => [
        // Timeout para requisições HTTP à Meta API (em segundos)
        'http_timeout' => (int) getenv('META_HTTP_TIMEOUT') ?: 30,
        
        // Timeout para conexão (em segundos)
        'connect_timeout' => (int) getenv('META_CONNECT_TIMEOUT') ?: 10,
        
        // Timeout para processamento de webhook (em segundos)
        'webhook_timeout' => (int) getenv('META_WEBHOOK_TIMEOUT') ?: 15,
    ],

    // Cache
    'cache' => [
        'enabled' => (bool) getenv('META_CACHE_ENABLED') ?: true,
        
        // TTL para cache de status de mensagens (em segundos)
        'message_status_ttl' => (int) getenv('META_CACHE_MESSAGE_STATUS_TTL') ?: 3600,
        
        // TTL para cache de rate limit (em segundos)
        'rate_limit_ttl' => (int) getenv('META_CACHE_RATE_LIMIT_TTL') ?: 60,
        
        // TTL para cache de circuit breaker state (em segundos)
        'circuit_breaker_ttl' => (int) getenv('META_CACHE_CB_TTL') ?: 300,
    ],

    // Dead Letter Queue
    'dead_letter_queue' => [
        'enabled' => (bool) getenv('META_DLQ_ENABLED') ?: true,
        
        // Número máximo de tentativas antes de enviar para DLQ
        'max_attempts' => (int) getenv('META_DLQ_MAX_ATTEMPTS') ?: 3,
        
        // Retenção de mensagens na DLQ (em dias)
        'retention_days' => (int) getenv('META_DLQ_RETENTION_DAYS') ?: 7,
    ],

    // Logging
    'logging' => [
        // Nível de log para operações Meta
        'level' => getenv('META_LOG_LEVEL') ?: 'info',
        
        // Incluir payloads completos nos logs (cuidado com dados sensíveis)
        'log_payloads' => (bool) getenv('META_LOG_PAYLOADS') ?: false,
        
        // Incluir headers nos logs
        'log_headers' => (bool) getenv('META_LOG_HEADERS') ?: false,
        
        // Mascarar dados sensíveis nos logs
        'mask_sensitive_data' => (bool) getenv('META_MASK_SENSITIVE_DATA') ?: true,
    ],
];
