<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use WhatsApp\Adapter\Http\JsonResponse;
use WhatsApp\Adapter\Services\MetaMetricsCollector;
use WhatsApp\Adapter\Services\MetaAlertManager;
use WhatsApp\Adapter\Services\MetaCircuitBreaker;
use WhatsApp\Adapter\Services\MetaRateLimiter;

/**
 * Controller para expor métricas do Meta Provider
 * 
 * Endpoints:
 * - GET /metrics/meta - Resumo geral de métricas
 * - GET /metrics/meta/success-rate - Taxa de sucesso de envio
 * - GET /metrics/meta/response-time - Tempo de resposta da API
 * - GET /metrics/meta/errors - Erros por código
 * - GET /metrics/meta/webhooks - Webhooks recebidos
 * - GET /metrics/meta/alerts - Estatísticas de alertas
 * - GET /metrics/meta/circuit-breaker - Status do circuit breaker
 * - GET /metrics/meta/rate-limit - Status do rate limiter
 */
class MetricsController
{
    public function __construct(
        private readonly MetaMetricsCollector $metricsCollector,
        private readonly MetaAlertManager $alertManager,
        private readonly MetaCircuitBreaker $circuitBreaker,
        private readonly MetaRateLimiter $rateLimiter,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Obtém resumo geral de métricas
     * 
     * GET /metrics/meta?period=day
     * 
     * Query params:
     * - period: hour, day, week (default: day)
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getSummary(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $params = $request->getQueryParams();
            $period = $params['period'] ?? 'day';

            // Validar período
            if (!in_array($period, ['hour', 'day', 'week'], true)) {
                return JsonResponse::error('Invalid period. Must be: hour, day, or week', 400);
            }

            $summary = $this->metricsCollector->getMetricsSummary($period);

            return JsonResponse::success($summary);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get metrics summary', [
                'error' => $e->getMessage()
            ]);

            return JsonResponse::error('Failed to retrieve metrics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtém taxa de sucesso de envio
     * 
     * GET /metrics/meta/success-rate?platform=instagram&period=day
     * 
     * Query params:
     * - platform: instagram, messenger, all (default: all)
     * - period: hour, day, week (default: day)
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getSuccessRate(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $params = $request->getQueryParams();
            $platform = $params['platform'] ?? null;
            $period = $params['period'] ?? 'day';

            // Validar plataforma
            if ($platform !== null && !in_array($platform, ['instagram', 'messenger'], true)) {
                return JsonResponse::error('Invalid platform. Must be: instagram or messenger', 400);
            }

            // Validar período
            if (!in_array($period, ['hour', 'day', 'week'], true)) {
                return JsonResponse::error('Invalid period. Must be: hour, day, or week', 400);
            }

            $successRate = $this->metricsCollector->getSuccessRate($platform, $period);

            return JsonResponse::success($successRate);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get success rate', [
                'error' => $e->getMessage()
            ]);

            return JsonResponse::error('Failed to retrieve success rate: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtém tempo de resposta da API
     * 
     * GET /metrics/meta/response-time?platform=instagram
     * 
     * Query params:
     * - platform: instagram, messenger, all (default: all)
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getResponseTime(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $params = $request->getQueryParams();
            $platform = $params['platform'] ?? null;

            // Validar plataforma
            if ($platform !== null && !in_array($platform, ['instagram', 'messenger'], true)) {
                return JsonResponse::error('Invalid platform. Must be: instagram or messenger', 400);
            }

            $responseTime = $this->metricsCollector->getAverageResponseTime($platform);

            return JsonResponse::success($responseTime);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get response time', [
                'error' => $e->getMessage()
            ]);

            return JsonResponse::error('Failed to retrieve response time: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtém erros por código
     * 
     * GET /metrics/meta/errors?period=day&limit=10
     * 
     * Query params:
     * - period: hour, day, week (default: day)
     * - limit: número máximo de erros (default: 10)
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getErrors(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $params = $request->getQueryParams();
            $period = $params['period'] ?? 'day';
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;

            // Validar período
            if (!in_array($period, ['hour', 'day', 'week'], true)) {
                return JsonResponse::error('Invalid period. Must be: hour, day, or week', 400);
            }

            // Validar limite
            if ($limit < 1 || $limit > 100) {
                return JsonResponse::error('Invalid limit. Must be between 1 and 100', 400);
            }

            $errors = $this->metricsCollector->getErrorsByCode($period, $limit);

            return JsonResponse::success($errors);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get errors', [
                'error' => $e->getMessage()
            ]);

            return JsonResponse::error('Failed to retrieve errors: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtém webhooks recebidos
     * 
     * GET /metrics/meta/webhooks?platform=instagram&period=day
     * 
     * Query params:
     * - platform: instagram, messenger, all (default: all)
     * - period: hour, day, week (default: day)
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getWebhooks(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $params = $request->getQueryParams();
            $platform = $params['platform'] ?? null;
            $period = $params['period'] ?? 'day';

            // Validar plataforma
            if ($platform !== null && !in_array($platform, ['instagram', 'messenger'], true)) {
                return JsonResponse::error('Invalid platform. Must be: instagram or messenger', 400);
            }

            // Validar período
            if (!in_array($period, ['hour', 'day', 'week'], true)) {
                return JsonResponse::error('Invalid period. Must be: hour, day, or week', 400);
            }

            $webhooks = $this->metricsCollector->getWebhooksReceived($platform, $period);

            return JsonResponse::success($webhooks);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get webhooks', [
                'error' => $e->getMessage()
            ]);

            return JsonResponse::error('Failed to retrieve webhooks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtém erros de janela de 24h
     * 
     * GET /metrics/meta/messaging-window-errors?platform=instagram&period=day
     * 
     * Query params:
     * - platform: instagram, messenger, all (default: all)
     * - period: hour, day, week (default: day)
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getMessagingWindowErrors(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $params = $request->getQueryParams();
            $platform = $params['platform'] ?? null;
            $period = $params['period'] ?? 'day';

            // Validar plataforma
            if ($platform !== null && !in_array($platform, ['instagram', 'messenger'], true)) {
                return JsonResponse::error('Invalid platform. Must be: instagram or messenger', 400);
            }

            // Validar período
            if (!in_array($period, ['hour', 'day', 'week'], true)) {
                return JsonResponse::error('Invalid period. Must be: hour, day, or week', 400);
            }

            $errors = $this->metricsCollector->getMessagingWindowErrors($platform, $period);

            return JsonResponse::success($errors);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get messaging window errors', [
                'error' => $e->getMessage()
            ]);

            return JsonResponse::error('Failed to retrieve messaging window errors: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtém estatísticas de alertas
     * 
     * GET /metrics/meta/alerts?hours=24
     * 
     * Query params:
     * - hours: número de horas para buscar (default: 24)
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getAlerts(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $params = $request->getQueryParams();
            $hours = isset($params['hours']) ? (int)$params['hours'] : 24;

            // Validar horas
            if ($hours < 1 || $hours > 168) { // Máximo 7 dias
                return JsonResponse::error('Invalid hours. Must be between 1 and 168', 400);
            }

            $alerts = $this->alertManager->getAlertStats($hours);

            return JsonResponse::success($alerts);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get alerts', [
                'error' => $e->getMessage()
            ]);

            return JsonResponse::error('Failed to retrieve alerts: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtém status do circuit breaker
     * 
     * GET /metrics/meta/circuit-breaker?service=meta_api
     * 
     * Query params:
     * - service: nome do serviço (default: meta_api)
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getCircuitBreakerStatus(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $params = $request->getQueryParams();
            $service = $params['service'] ?? 'meta_api';

            $stats = $this->circuitBreaker->getStats($service);

            return JsonResponse::success($stats);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get circuit breaker status', [
                'error' => $e->getMessage()
            ]);

            return JsonResponse::error('Failed to retrieve circuit breaker status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtém status do rate limiter
     * 
     * GET /metrics/meta/rate-limit
     * 
     * Headers:
     * - X-Page-Access-Token: Page Access Token para verificar uso
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getRateLimitStatus(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Obter token do header
            $token = $request->getHeaderLine('X-Page-Access-Token');

            if (empty($token)) {
                return JsonResponse::error('Missing X-Page-Access-Token header', 400);
            }

            $usage = $this->rateLimiter->getUsage($token);

            return JsonResponse::success($usage);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get rate limit status', [
                'error' => $e->getMessage()
            ]);

            return JsonResponse::error('Failed to retrieve rate limit status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtém health check completo
     * 
     * GET /metrics/meta/health
     * 
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getHealthCheck(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'circuit_breaker' => [
                    'meta_api' => $this->circuitBreaker->getStats('meta_api'),
                    'meta_messages' => $this->circuitBreaker->getStats('meta_messages')
                ],
                'alerts' => $this->alertManager->getAlertStats(1), // Última hora
                'metrics' => [
                    'success_rate_last_hour' => $this->metricsCollector->getSuccessRate(null, 'hour'),
                    'response_time' => $this->metricsCollector->getAverageResponseTime()
                ]
            ];

            // Determinar status geral
            $circuitBreakerOpen = false;
            foreach ($health['circuit_breaker'] as $cb) {
                if ($cb['state'] === 'open') {
                    $circuitBreakerOpen = true;
                    break;
                }
            }

            if ($circuitBreakerOpen) {
                $health['status'] = 'degraded';
            }

            // Verificar taxa de sucesso
            $successRate = $health['metrics']['success_rate_last_hour']['success_rate'] ?? 100;
            if ($successRate < 50) {
                $health['status'] = 'unhealthy';
            } elseif ($successRate < 80) {
                $health['status'] = 'degraded';
            }

            $statusCode = match($health['status']) {
                'healthy' => 200,
                'degraded' => 200,
                'unhealthy' => 503,
                default => 200
            };

            return JsonResponse::success($health, $statusCode);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get health check', [
                'error' => $e->getMessage()
            ]);

            return JsonResponse::error('Failed to retrieve health check: ' . $e->getMessage(), 500);
        }
    }
}
