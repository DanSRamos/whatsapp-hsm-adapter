<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Psr\Log\LoggerInterface;

/**
 * Gerenciador de alertas específico para Meta API (Instagram + Messenger)
 * 
 * Monitora e alerta sobre:
 * - Erros de API
 * - Falhas de webhook
 * - Rate limit atingido
 * - Circuit breaker aberto
 * - Degradação de performance
 * 
 * Validates: Requirements 14.7, 14.8 (Monitoring e alertas)
 */
class MetaAlertManager
{
    // Tipos de alerta
    public const ALERT_API_ERROR = 'api_error';
    public const ALERT_WEBHOOK_FAILURE = 'webhook_failure';
    public const ALERT_RATE_LIMIT = 'rate_limit';
    public const ALERT_CIRCUIT_BREAKER = 'circuit_breaker';
    public const ALERT_PERFORMANCE = 'performance';
    public const ALERT_TOKEN_EXPIRING = 'token_expiring';

    // Severidades
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_CRITICAL = 'critical';

    private LoggerInterface $logger;
    private CriticalErrorNotifier $notifier;
    private \Redis $redis;
    private array $config;

    public function __construct(
        LoggerInterface $logger,
        CriticalErrorNotifier $notifier,
        \Redis $redis,
        array $config = []
    ) {
        $this->logger = $logger;
        $this->notifier = $notifier;
        $this->redis = $redis;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Alerta sobre erro de API
     * 
     * @param string $errorCode Código de erro da Meta API
     * @param string $errorMessage Mensagem de erro
     * @param array $context Contexto adicional
     * @return void
     */
    public function alertApiError(string $errorCode, string $errorMessage, array $context = []): void
    {
        $severity = $this->determineApiErrorSeverity($errorCode);
        
        // Verificar se deve alertar (evitar spam)
        if (!$this->shouldAlert(self::ALERT_API_ERROR, $errorCode, $severity)) {
            return;
        }

        $message = sprintf(
            'Meta API Error: [%s] %s',
            $errorCode,
            $errorMessage
        );

        $fullContext = array_merge($context, [
            'alert_type' => self::ALERT_API_ERROR,
            'error_code' => $errorCode,
            'severity' => $severity,
            'platform' => $context['platform'] ?? 'unknown'
        ]);

        $this->sendAlert($message, $severity, $fullContext);
        $this->recordAlert(self::ALERT_API_ERROR, $errorCode, $severity);
    }

    /**
     * Alerta sobre falha de webhook
     * 
     * @param string $reason Razão da falha
     * @param array $context Contexto adicional
     * @return void
     */
    public function alertWebhookFailure(string $reason, array $context = []): void
    {
        $severity = self::SEVERITY_ERROR;
        
        if (!$this->shouldAlert(self::ALERT_WEBHOOK_FAILURE, $reason, $severity)) {
            return;
        }

        $message = sprintf('Meta Webhook Failure: %s', $reason);

        $fullContext = array_merge($context, [
            'alert_type' => self::ALERT_WEBHOOK_FAILURE,
            'reason' => $reason,
            'severity' => $severity
        ]);

        $this->sendAlert($message, $severity, $fullContext);
        $this->recordAlert(self::ALERT_WEBHOOK_FAILURE, $reason, $severity);
    }

    /**
     * Alerta sobre rate limit atingido
     * 
     * @param string $limitType Tipo de limite (hourly, daily, endpoint)
     * @param int $currentUsage Uso atual
     * @param int $limit Limite máximo
     * @param array $context Contexto adicional
     * @return void
     */
    public function alertRateLimitReached(
        string $limitType,
        int $currentUsage,
        int $limit,
        array $context = []
    ): void {
        $severity = self::SEVERITY_WARNING;
        
        if (!$this->shouldAlert(self::ALERT_RATE_LIMIT, $limitType, $severity)) {
            return;
        }

        $percentage = $limit > 0 ? ($currentUsage / $limit) * 100 : 0;
        
        $message = sprintf(
            'Meta API Rate Limit Reached: %s limit at %.1f%% (%d/%d)',
            $limitType,
            $percentage,
            $currentUsage,
            $limit
        );

        $fullContext = array_merge($context, [
            'alert_type' => self::ALERT_RATE_LIMIT,
            'limit_type' => $limitType,
            'current_usage' => $currentUsage,
            'limit' => $limit,
            'percentage' => $percentage,
            'severity' => $severity
        ]);

        $this->sendAlert($message, $severity, $fullContext);
        $this->recordAlert(self::ALERT_RATE_LIMIT, $limitType, $severity);
    }

    /**
     * Alerta sobre circuit breaker aberto
     * 
     * @param string $serviceName Nome do serviço
     * @param int $failureCount Número de falhas
     * @param array $context Contexto adicional
     * @return void
     */
    public function alertCircuitBreakerOpen(
        string $serviceName,
        int $failureCount,
        array $context = []
    ): void {
        $severity = self::SEVERITY_CRITICAL;
        
        if (!$this->shouldAlert(self::ALERT_CIRCUIT_BREAKER, $serviceName, $severity)) {
            return;
        }

        $message = sprintf(
            'Meta API Circuit Breaker OPEN: %s (failures: %d)',
            $serviceName,
            $failureCount
        );

        $fullContext = array_merge($context, [
            'alert_type' => self::ALERT_CIRCUIT_BREAKER,
            'service' => $serviceName,
            'failure_count' => $failureCount,
            'severity' => $severity
        ]);

        $this->sendAlert($message, $severity, $fullContext);
        $this->recordAlert(self::ALERT_CIRCUIT_BREAKER, $serviceName, $severity);
    }

    /**
     * Alerta sobre degradação de performance
     * 
     * @param string $metric Métrica afetada
     * @param float $value Valor atual
     * @param float $threshold Threshold configurado
     * @param array $context Contexto adicional
     * @return void
     */
    public function alertPerformanceDegradation(
        string $metric,
        float $value,
        float $threshold,
        array $context = []
    ): void {
        $severity = self::SEVERITY_WARNING;
        
        if (!$this->shouldAlert(self::ALERT_PERFORMANCE, $metric, $severity)) {
            return;
        }

        $message = sprintf(
            'Meta API Performance Degradation: %s = %.2f (threshold: %.2f)',
            $metric,
            $value,
            $threshold
        );

        $fullContext = array_merge($context, [
            'alert_type' => self::ALERT_PERFORMANCE,
            'metric' => $metric,
            'value' => $value,
            'threshold' => $threshold,
            'severity' => $severity
        ]);

        $this->sendAlert($message, $severity, $fullContext);
        $this->recordAlert(self::ALERT_PERFORMANCE, $metric, $severity);
    }

    /**
     * Alerta sobre token expirando
     * 
     * @param int $daysUntilExpiry Dias até expiração
     * @param array $context Contexto adicional
     * @return void
     */
    public function alertTokenExpiring(int $daysUntilExpiry, array $context = []): void
    {
        $severity = $daysUntilExpiry <= 7 ? self::SEVERITY_ERROR : self::SEVERITY_WARNING;
        
        if (!$this->shouldAlert(self::ALERT_TOKEN_EXPIRING, 'token', $severity)) {
            return;
        }

        $message = sprintf(
            'Meta API Token Expiring: %d days remaining',
            $daysUntilExpiry
        );

        $fullContext = array_merge($context, [
            'alert_type' => self::ALERT_TOKEN_EXPIRING,
            'days_until_expiry' => $daysUntilExpiry,
            'severity' => $severity
        ]);

        $this->sendAlert($message, $severity, $fullContext);
        $this->recordAlert(self::ALERT_TOKEN_EXPIRING, 'token', $severity);
    }

    /**
     * Obtém estatísticas de alertas
     * 
     * @param int $hours Número de horas para buscar (padrão: 24)
     * @return array Estatísticas
     */
    public function getAlertStats(int $hours = 24): array
    {
        try {
            $stats = [
                'period_hours' => $hours,
                'by_type' => [],
                'by_severity' => [],
                'total' => 0
            ];

            $types = [
                self::ALERT_API_ERROR,
                self::ALERT_WEBHOOK_FAILURE,
                self::ALERT_RATE_LIMIT,
                self::ALERT_CIRCUIT_BREAKER,
                self::ALERT_PERFORMANCE,
                self::ALERT_TOKEN_EXPIRING
            ];

            foreach ($types as $type) {
                $key = "meta_alerts:{$type}:count";
                $count = (int)$this->redis->get($key) ?: 0;
                $stats['by_type'][$type] = $count;
                $stats['total'] += $count;
            }

            $severities = [
                self::SEVERITY_INFO,
                self::SEVERITY_WARNING,
                self::SEVERITY_ERROR,
                self::SEVERITY_CRITICAL
            ];

            foreach ($severities as $severity) {
                $key = "meta_alerts:severity:{$severity}:count";
                $count = (int)$this->redis->get($key) ?: 0;
                $stats['by_severity'][$severity] = $count;
            }

            return $stats;
        } catch (\RedisException $e) {
            $this->logger->error('Failed to get alert stats', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'period_hours' => $hours,
                'by_type' => [],
                'by_severity' => [],
                'total' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Determina a severidade de um erro de API
     * 
     * @param string $errorCode Código de erro
     * @return string Severidade
     */
    private function determineApiErrorSeverity(string $errorCode): string
    {
        // Erros críticos que impedem funcionamento
        $criticalErrors = ['190', '200']; // Invalid token, permission error
        if (in_array($errorCode, $criticalErrors, true)) {
            return self::SEVERITY_CRITICAL;
        }

        // Erros que afetam funcionalidade mas não são críticos
        $errorErrors = ['36103', '2534068', '551', '2022'];
        if (in_array($errorCode, $errorErrors, true)) {
            return self::SEVERITY_ERROR;
        }

        // Rate limit e erros temporários
        if ($errorCode === '429' || $errorCode === '4') {
            return self::SEVERITY_WARNING;
        }

        // Outros erros
        return self::SEVERITY_ERROR;
    }

    /**
     * Verifica se deve enviar alerta (evita spam)
     * 
     * @param string $alertType Tipo de alerta
     * @param string $identifier Identificador único do alerta
     * @param string $severity Severidade
     * @return bool True se deve alertar
     */
    private function shouldAlert(string $alertType, string $identifier, string $severity): bool
    {
        try {
            // Alertas críticos sempre passam
            if ($severity === self::SEVERITY_CRITICAL) {
                return true;
            }

            // Verificar se já alertou recentemente
            $key = "meta_alerts:{$alertType}:{$identifier}:last";
            $lastAlert = $this->redis->get($key);

            if ($lastAlert === false || $lastAlert === null) {
                return true;
            }

            $lastAlertTime = (int)$lastAlert;
            $cooldownSeconds = $this->config['cooldown_seconds'][$severity] ?? 3600;
            $elapsedSeconds = time() - $lastAlertTime;

            return $elapsedSeconds >= $cooldownSeconds;
        } catch (\RedisException $e) {
            $this->logger->error('Failed to check alert cooldown', [
                'error' => $e->getMessage()
            ]);
            // Em caso de erro, permitir alerta
            return true;
        }
    }

    /**
     * Envia um alerta
     * 
     * @param string $message Mensagem do alerta
     * @param string $severity Severidade
     * @param array $context Contexto
     * @return void
     */
    private function sendAlert(string $message, string $severity, array $context): void
    {
        // Log sempre
        $logMethod = match ($severity) {
            self::SEVERITY_CRITICAL => 'critical',
            self::SEVERITY_ERROR => 'error',
            self::SEVERITY_WARNING => 'warning',
            default => 'info'
        };

        $this->logger->$logMethod($message, $context);

        // Notificar via CriticalErrorNotifier para alertas críticos e erros
        if (in_array($severity, [self::SEVERITY_CRITICAL, self::SEVERITY_ERROR], true)) {
            $this->notifier->notifyCriticalError($message, $context);
        }
    }

    /**
     * Registra um alerta no Redis
     * 
     * @param string $alertType Tipo de alerta
     * @param string $identifier Identificador
     * @param string $severity Severidade
     * @return void
     */
    private function recordAlert(string $alertType, string $identifier, string $severity): void
    {
        try {
            // Registrar timestamp do último alerta
            $lastKey = "meta_alerts:{$alertType}:{$identifier}:last";
            $this->redis->set($lastKey, time());
            $this->redis->expire($lastKey, 86400); // 24 horas

            // Incrementar contadores
            $typeKey = "meta_alerts:{$alertType}:count";
            $severityKey = "meta_alerts:severity:{$severity}:count";

            $this->redis->multi();
            $this->redis->incr($typeKey);
            $this->redis->expire($typeKey, 86400);
            $this->redis->incr($severityKey);
            $this->redis->expire($severityKey, 86400);
            $this->redis->exec();
        } catch (\RedisException $e) {
            $this->logger->error('Failed to record alert', [
                'error' => $e->getMessage(),
                'alert_type' => $alertType
            ]);
        }
    }

    /**
     * Obtém configuração padrão
     * 
     * @return array Configuração
     */
    private function getDefaultConfig(): array
    {
        return [
            'cooldown_seconds' => [
                self::SEVERITY_INFO => 3600,      // 1 hora
                self::SEVERITY_WARNING => 1800,   // 30 minutos
                self::SEVERITY_ERROR => 900,      // 15 minutos
                self::SEVERITY_CRITICAL => 0      // Sem cooldown
            ]
        ];
    }
}
