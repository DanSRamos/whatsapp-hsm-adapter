<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Psr\Log\LoggerInterface;

/**
 * Coletor de métricas para Meta API (Instagram + Messenger)
 * 
 * Coleta e armazena métricas sobre:
 * - Taxa de sucesso de envio
 * - Tempo de resposta da API
 * - Erros de janela de 24h
 * - Webhooks recebidos
 * - Mensagens enviadas/recebidas por plataforma
 * - Erros por tipo e código
 * 
 * Validates: Requirements 14.1, 14.2, 14.3 (Métricas e monitoramento)
 */
class MetaMetricsCollector
{
    // Prefixos de chaves Redis
    private const METRIC_PREFIX = 'meta_metrics';
    private const COUNTER_PREFIX = self::METRIC_PREFIX . ':counter';
    private const GAUGE_PREFIX = self::METRIC_PREFIX . ':gauge';
    private const HISTOGRAM_PREFIX = self::METRIC_PREFIX . ':histogram';
    private const TIMESERIES_PREFIX = self::METRIC_PREFIX . ':timeseries';

    // TTLs para diferentes tipos de métricas
    private const HOURLY_TTL = 3600;        // 1 hora
    private const DAILY_TTL = 86400;        // 24 horas
    private const WEEKLY_TTL = 604800;      // 7 dias
    private const MONTHLY_TTL = 2592000;    // 30 dias

    private \Redis $redis;
    private LoggerInterface $logger;

    public function __construct(
        \Redis $redis,
        LoggerInterface $logger
    ) {
        $this->redis = $redis;
        $this->logger = $logger;
    }

    /**
     * Registra uma mensagem enviada
     * 
     * @param string $platform Plataforma (instagram ou messenger)
     * @param bool $success Se o envio foi bem-sucedido
     * @param float $responseTime Tempo de resposta em segundos
     * @param string|null $errorCode Código de erro se falhou
     * @return void
     */
    public function recordMessageSent(
        string $platform,
        bool $success,
        float $responseTime,
        ?string $errorCode = null
    ): void {
        try {
            $timestamp = time();
            $hour = date('Y-m-d-H', $timestamp);
            $day = date('Y-m-d', $timestamp);

            // Incrementar contadores totais
            $this->incrementCounter("messages_sent:total", self::DAILY_TTL);
            $this->incrementCounter("messages_sent:{$platform}", self::DAILY_TTL);
            $this->incrementCounter("messages_sent:{$platform}:{$day}", self::WEEKLY_TTL);
            $this->incrementCounter("messages_sent:{$platform}:{$hour}", self::HOURLY_TTL);

            // Registrar sucesso ou falha
            if ($success) {
                $this->incrementCounter("messages_sent:success:total", self::DAILY_TTL);
                $this->incrementCounter("messages_sent:success:{$platform}", self::DAILY_TTL);
                $this->incrementCounter("messages_sent:success:{$platform}:{$day}", self::WEEKLY_TTL);
            } else {
                $this->incrementCounter("messages_sent:failed:total", self::DAILY_TTL);
                $this->incrementCounter("messages_sent:failed:{$platform}", self::DAILY_TTL);
                $this->incrementCounter("messages_sent:failed:{$platform}:{$day}", self::WEEKLY_TTL);

                // Registrar erro específico
                if ($errorCode !== null) {
                    $this->incrementCounter("errors:code:{$errorCode}", self::DAILY_TTL);
                    $this->incrementCounter("errors:code:{$errorCode}:{$platform}", self::DAILY_TTL);
                    $this->incrementCounter("errors:code:{$errorCode}:{$day}", self::WEEKLY_TTL);
                }
            }

            // Registrar tempo de resposta
            $this->recordResponseTime($platform, $responseTime);

            $this->logger->debug('Message sent metric recorded', [
                'platform' => $platform,
                'success' => $success,
                'response_time' => $responseTime,
                'error_code' => $errorCode
            ]);
        } catch (\RedisException $e) {
            $this->logger->error('Failed to record message sent metric', [
                'error' => $e->getMessage(),
                'platform' => $platform
            ]);
        }
    }

    /**
     * Registra um erro de janela de 24h
     * 
     * @param string $platform Plataforma (instagram ou messenger)
     * @param string $recipientId ID do destinatário
     * @return void
     */
    public function recordMessagingWindowError(string $platform, string $recipientId): void
    {
        try {
            $day = date('Y-m-d');

            $this->incrementCounter("errors:messaging_window:total", self::DAILY_TTL);
            $this->incrementCounter("errors:messaging_window:{$platform}", self::DAILY_TTL);
            $this->incrementCounter("errors:messaging_window:{$platform}:{$day}", self::WEEKLY_TTL);

            $this->logger->debug('Messaging window error recorded', [
                'platform' => $platform,
                'recipient_id' => $recipientId
            ]);
        } catch (\RedisException $e) {
            $this->logger->error('Failed to record messaging window error', [
                'error' => $e->getMessage(),
                'platform' => $platform
            ]);
        }
    }

    /**
     * Registra um webhook recebido
     * 
     * @param string $platform Plataforma (instagram ou messenger)
     * @param string $eventType Tipo de evento (message, delivery, read, postback)
     * @return void
     */
    public function recordWebhookReceived(string $platform, string $eventType): void
    {
        try {
            $timestamp = time();
            $hour = date('Y-m-d-H', $timestamp);
            $day = date('Y-m-d', $timestamp);

            $this->incrementCounter("webhooks:received:total", self::DAILY_TTL);
            $this->incrementCounter("webhooks:received:{$platform}", self::DAILY_TTL);
            $this->incrementCounter("webhooks:received:{$platform}:{$day}", self::WEEKLY_TTL);
            $this->incrementCounter("webhooks:received:{$platform}:{$hour}", self::HOURLY_TTL);
            
            // Por tipo de evento
            $this->incrementCounter("webhooks:type:{$eventType}:total", self::DAILY_TTL);
            $this->incrementCounter("webhooks:type:{$eventType}:{$platform}", self::DAILY_TTL);
            $this->incrementCounter("webhooks:type:{$eventType}:{$day}", self::WEEKLY_TTL);

            $this->logger->debug('Webhook received metric recorded', [
                'platform' => $platform,
                'event_type' => $eventType
            ]);
        } catch (\RedisException $e) {
            $this->logger->error('Failed to record webhook received metric', [
                'error' => $e->getMessage(),
                'platform' => $platform,
                'event_type' => $eventType
            ]);
        }
    }

    /**
     * Registra uma mensagem recebida
     * 
     * @param string $platform Plataforma (instagram ou messenger)
     * @param string $messageType Tipo de mensagem (text, image, video, etc)
     * @return void
     */
    public function recordMessageReceived(string $platform, string $messageType): void
    {
        try {
            $day = date('Y-m-d');

            $this->incrementCounter("messages_received:total", self::DAILY_TTL);
            $this->incrementCounter("messages_received:{$platform}", self::DAILY_TTL);
            $this->incrementCounter("messages_received:{$platform}:{$day}", self::WEEKLY_TTL);
            
            // Por tipo de mensagem
            $this->incrementCounter("messages_received:type:{$messageType}:total", self::DAILY_TTL);
            $this->incrementCounter("messages_received:type:{$messageType}:{$platform}", self::DAILY_TTL);

            $this->logger->debug('Message received metric recorded', [
                'platform' => $platform,
                'message_type' => $messageType
            ]);
        } catch (\RedisException $e) {
            $this->logger->error('Failed to record message received metric', [
                'error' => $e->getMessage(),
                'platform' => $platform
            ]);
        }
    }

    /**
     * Registra tempo de resposta da API
     * 
     * @param string $platform Plataforma (instagram ou messenger)
     * @param float $responseTime Tempo de resposta em segundos
     * @return void
     */
    private function recordResponseTime(string $platform, float $responseTime): void
    {
        try {
            $responseTimeMs = (int)($responseTime * 1000);

            // Adicionar ao histograma (últimos 1000 valores)
            $histogramKey = $this->getHistogramKey("response_time:{$platform}");
            
            $this->redis->multi();
            $this->redis->lPush($histogramKey, $responseTimeMs);
            $this->redis->lTrim($histogramKey, 0, 999); // Manter apenas últimos 1000
            $this->redis->expire($histogramKey, self::DAILY_TTL);
            $this->redis->exec();

            // Atualizar gauge com média móvel
            $this->updateResponseTimeGauge($platform);

        } catch (\RedisException $e) {
            $this->logger->error('Failed to record response time', [
                'error' => $e->getMessage(),
                'platform' => $platform
            ]);
        }
    }

    /**
     * Atualiza gauge de tempo de resposta médio
     * 
     * @param string $platform Plataforma
     * @return void
     */
    private function updateResponseTimeGauge(string $platform): void
    {
        try {
            $histogramKey = $this->getHistogramKey("response_time:{$platform}");
            $values = $this->redis->lRange($histogramKey, 0, 99); // Últimos 100 valores

            if (!empty($values)) {
                $average = array_sum($values) / count($values);
                $this->setGauge("response_time_avg:{$platform}", $average, self::HOURLY_TTL);
            }
        } catch (\RedisException $e) {
            $this->logger->error('Failed to update response time gauge', [
                'error' => $e->getMessage(),
                'platform' => $platform
            ]);
        }
    }

    /**
     * Obtém taxa de sucesso de envio
     * 
     * @param string|null $platform Plataforma específica ou null para todas
     * @param string $period Período (hour, day, week)
     * @return array Taxa de sucesso com detalhes
     */
    public function getSuccessRate(?string $platform = null, string $period = 'day'): array
    {
        try {
            $suffix = $this->getPeriodSuffix($period);
            $platformKey = $platform ? ":{$platform}" : ':total';

            $totalKey = $this->getCounterKey("messages_sent{$platformKey}{$suffix}");
            $successKey = $this->getCounterKey("messages_sent:success{$platformKey}{$suffix}");
            $failedKey = $this->getCounterKey("messages_sent:failed{$platformKey}{$suffix}");

            $total = (int)$this->redis->get($totalKey) ?: 0;
            $success = (int)$this->redis->get($successKey) ?: 0;
            $failed = (int)$this->redis->get($failedKey) ?: 0;

            $successRate = $total > 0 ? ($success / $total) * 100 : 0;

            return [
                'platform' => $platform ?? 'all',
                'period' => $period,
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'success_rate' => round($successRate, 2),
                'failure_rate' => round(100 - $successRate, 2)
            ];
        } catch (\RedisException $e) {
            $this->logger->error('Failed to get success rate', [
                'error' => $e->getMessage(),
                'platform' => $platform
            ]);

            return [
                'platform' => $platform ?? 'all',
                'period' => $period,
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'success_rate' => 0,
                'failure_rate' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtém tempo médio de resposta da API
     * 
     * @param string|null $platform Plataforma específica ou null para todas
     * @return array Tempo de resposta com estatísticas
     */
    public function getAverageResponseTime(?string $platform = null): array
    {
        try {
            $platforms = $platform ? [$platform] : ['instagram', 'messenger'];
            $results = [];

            foreach ($platforms as $plt) {
                $histogramKey = $this->getHistogramKey("response_time:{$plt}");
                $values = $this->redis->lRange($histogramKey, 0, 999);

                if (!empty($values)) {
                    $numericValues = array_map('intval', $values);
                    sort($numericValues);

                    $count = count($numericValues);
                    $average = array_sum($numericValues) / $count;
                    $min = $numericValues[0];
                    $max = $numericValues[$count - 1];
                    
                    // Calcular percentis
                    $p50Index = (int)($count * 0.50);
                    $p95Index = (int)($count * 0.95);
                    $p99Index = (int)($count * 0.99);

                    $results[$plt] = [
                        'platform' => $plt,
                        'average_ms' => round($average, 2),
                        'min_ms' => $min,
                        'max_ms' => $max,
                        'p50_ms' => $numericValues[$p50Index] ?? 0,
                        'p95_ms' => $numericValues[$p95Index] ?? 0,
                        'p99_ms' => $numericValues[$p99Index] ?? 0,
                        'sample_count' => $count
                    ];
                } else {
                    $results[$plt] = [
                        'platform' => $plt,
                        'average_ms' => 0,
                        'min_ms' => 0,
                        'max_ms' => 0,
                        'p50_ms' => 0,
                        'p95_ms' => 0,
                        'p99_ms' => 0,
                        'sample_count' => 0
                    ];
                }
            }

            return $platform ? $results[$platform] : $results;
        } catch (\RedisException $e) {
            $this->logger->error('Failed to get average response time', [
                'error' => $e->getMessage(),
                'platform' => $platform
            ]);

            return [
                'platform' => $platform ?? 'all',
                'average_ms' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtém contagem de erros de janela de 24h
     * 
     * @param string|null $platform Plataforma específica ou null para todas
     * @param string $period Período (hour, day, week)
     * @return array Contagem de erros
     */
    public function getMessagingWindowErrors(?string $platform = null, string $period = 'day'): array
    {
        try {
            $suffix = $this->getPeriodSuffix($period);
            $platformKey = $platform ? ":{$platform}" : ':total';

            $key = $this->getCounterKey("errors:messaging_window{$platformKey}{$suffix}");
            $count = (int)$this->redis->get($key) ?: 0;

            return [
                'platform' => $platform ?? 'all',
                'period' => $period,
                'count' => $count
            ];
        } catch (\RedisException $e) {
            $this->logger->error('Failed to get messaging window errors', [
                'error' => $e->getMessage(),
                'platform' => $platform
            ]);

            return [
                'platform' => $platform ?? 'all',
                'period' => $period,
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtém contagem de webhooks recebidos
     * 
     * @param string|null $platform Plataforma específica ou null para todas
     * @param string $period Período (hour, day, week)
     * @return array Contagem de webhooks
     */
    public function getWebhooksReceived(?string $platform = null, string $period = 'day'): array
    {
        try {
            $suffix = $this->getPeriodSuffix($period);
            $platformKey = $platform ? ":{$platform}" : ':total';

            $totalKey = $this->getCounterKey("webhooks:received{$platformKey}{$suffix}");
            $total = (int)$this->redis->get($totalKey) ?: 0;

            // Obter por tipo de evento
            $eventTypes = ['message', 'delivery', 'read', 'postback'];
            $byType = [];

            foreach ($eventTypes as $type) {
                $typeKey = $this->getCounterKey("webhooks:type:{$type}{$platformKey}{$suffix}");
                $byType[$type] = (int)$this->redis->get($typeKey) ?: 0;
            }

            return [
                'platform' => $platform ?? 'all',
                'period' => $period,
                'total' => $total,
                'by_type' => $byType
            ];
        } catch (\RedisException $e) {
            $this->logger->error('Failed to get webhooks received', [
                'error' => $e->getMessage(),
                'platform' => $platform
            ]);

            return [
                'platform' => $platform ?? 'all',
                'period' => $period,
                'total' => 0,
                'by_type' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtém estatísticas de erros por código
     * 
     * @param string $period Período (hour, day, week)
     * @param int $limit Número máximo de códigos de erro a retornar
     * @return array Top erros por código
     */
    public function getErrorsByCode(string $period = 'day', int $limit = 10): array
    {
        try {
            $suffix = $this->getPeriodSuffix($period);
            $pattern = $this->getCounterKey("errors:code:*{$suffix}");
            
            $keys = $this->redis->keys($pattern);
            $errors = [];

            foreach ($keys as $key) {
                // Extrair código de erro da chave
                if (preg_match('/errors:code:(\d+)/', $key, $matches)) {
                    $errorCode = $matches[1];
                    $count = (int)$this->redis->get($key) ?: 0;
                    
                    if ($count > 0) {
                        $errors[$errorCode] = $count;
                    }
                }
            }

            // Ordenar por contagem (maior para menor)
            arsort($errors);

            // Limitar resultados
            $errors = array_slice($errors, 0, $limit, true);

            return [
                'period' => $period,
                'errors' => $errors,
                'total_error_types' => count($errors)
            ];
        } catch (\RedisException $e) {
            $this->logger->error('Failed to get errors by code', [
                'error' => $e->getMessage()
            ]);

            return [
                'period' => $period,
                'errors' => [],
                'total_error_types' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtém resumo completo de métricas
     * 
     * @param string $period Período (hour, day, week)
     * @return array Resumo de todas as métricas
     */
    public function getMetricsSummary(string $period = 'day'): array
    {
        return [
            'period' => $period,
            'timestamp' => date('Y-m-d H:i:s'),
            'success_rate' => [
                'all' => $this->getSuccessRate(null, $period),
                'instagram' => $this->getSuccessRate('instagram', $period),
                'messenger' => $this->getSuccessRate('messenger', $period)
            ],
            'response_time' => $this->getAverageResponseTime(),
            'messaging_window_errors' => [
                'all' => $this->getMessagingWindowErrors(null, $period),
                'instagram' => $this->getMessagingWindowErrors('instagram', $period),
                'messenger' => $this->getMessagingWindowErrors('messenger', $period)
            ],
            'webhooks' => [
                'all' => $this->getWebhooksReceived(null, $period),
                'instagram' => $this->getWebhooksReceived('instagram', $period),
                'messenger' => $this->getWebhooksReceived('messenger', $period)
            ],
            'top_errors' => $this->getErrorsByCode($period, 10)
        ];
    }

    /**
     * Reseta todas as métricas (útil para testes)
     * 
     * @return void
     */
    public function resetAllMetrics(): void
    {
        try {
            $patterns = [
                $this->getCounterKey('*'),
                $this->getGaugeKey('*'),
                $this->getHistogramKey('*'),
                $this->getTimeseriesKey('*')
            ];

            foreach ($patterns as $pattern) {
                $keys = $this->redis->keys($pattern);
                if (!empty($keys)) {
                    $this->redis->del($keys);
                }
            }

            $this->logger->info('All Meta metrics reset');
        } catch (\RedisException $e) {
            $this->logger->error('Failed to reset metrics', [
                'error' => $e->getMessage()
            ]);
        }
    }

    // Métodos auxiliares para chaves Redis

    private function getCounterKey(string $name): string
    {
        return self::COUNTER_PREFIX . ':' . $name;
    }

    private function getGaugeKey(string $name): string
    {
        return self::GAUGE_PREFIX . ':' . $name;
    }

    private function getHistogramKey(string $name): string
    {
        return self::HISTOGRAM_PREFIX . ':' . $name;
    }

    private function getTimeseriesKey(string $name): string
    {
        return self::TIMESERIES_PREFIX . ':' . $name;
    }

    private function incrementCounter(string $name, int $ttl): void
    {
        $key = $this->getCounterKey($name);
        $this->redis->multi();
        $this->redis->incr($key);
        $this->redis->expire($key, $ttl);
        $this->redis->exec();
    }

    private function setGauge(string $name, float $value, int $ttl): void
    {
        $key = $this->getGaugeKey($name);
        $this->redis->setex($key, $ttl, (string)$value);
    }

    private function getPeriodSuffix(string $period): string
    {
        return match($period) {
            'hour' => ':' . date('Y-m-d-H'),
            'day' => ':' . date('Y-m-d'),
            'week' => '', // Sem sufixo para semanal (usa agregação)
            default => ':' . date('Y-m-d')
        };
    }
}
