<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Http\JsonResponse;
use Psr\Log\LoggerInterface;

/**
 * Middleware de rate limiting usando Redis para tracking
 * Implementa limites por IP e por API key
 * 
 * Validates: Requirements 11.5
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private \Redis $redis;
    private LoggerInterface $logger;
    private int $limitPerMinuteByIp;
    private int $limitPerHourByApiKey;

    public function __construct(
        \Redis $redis,
        LoggerInterface $logger,
        int $limitPerMinuteByIp = 100,
        int $limitPerHourByApiKey = 1000
    ) {
        $this->redis = $redis;
        $this->logger = $logger;
        $this->limitPerMinuteByIp = $limitPerMinuteByIp;
        $this->limitPerHourByApiKey = $limitPerHourByApiKey;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientIp = $this->getClientIp($request);
        $apiKey = $request->getAttribute('api_key');

        // Verificar rate limit por IP
        $ipLimitExceeded = $this->checkRateLimit(
            "rate_limit:ip:{$clientIp}",
            $this->limitPerMinuteByIp,
            60
        );

        if ($ipLimitExceeded) {
            $this->logger->warning('Rate limit exceeded by IP', [
                'ip' => $clientIp,
                'path' => $request->getUri()->getPath()
            ]);

            return $this->createRateLimitResponse(60);
        }

        // Verificar rate limit por API key (se presente)
        if ($apiKey !== null) {
            $apiKeyLimitExceeded = $this->checkRateLimit(
                "rate_limit:api_key:{$apiKey}",
                $this->limitPerHourByApiKey,
                3600
            );

            if ($apiKeyLimitExceeded) {
                $this->logger->warning('Rate limit exceeded by API key', [
                    'api_key_prefix' => substr($apiKey, 0, 8) . '...',
                    'ip' => $clientIp,
                    'path' => $request->getUri()->getPath()
                ]);

                return $this->createRateLimitResponse(3600);
            }
        }

        return $handler->handle($request);
    }

    /**
     * Verifica se o rate limit foi excedido
     * 
     * @param string $key Chave Redis
     * @param int $limit Número máximo de pedidos
     * @param int $windowSeconds Janela de tempo em segundos
     * @return bool True se o limite foi excedido
     */
    private function checkRateLimit(string $key, int $limit, int $windowSeconds): bool
    {
        try {
            // Usar pipeline para operações atômicas
            $this->redis->multi();
            
            // Incrementar contador
            $this->redis->incr($key);
            
            // Definir TTL se a chave é nova
            $this->redis->expire($key, $windowSeconds);
            
            $results = $this->redis->exec();
            
            $currentCount = $results[0] ?? 0;
            
            return $currentCount > $limit;
        } catch (\RedisException $e) {
            // Se Redis falhar, permitir o pedido mas registar o erro
            $this->logger->error('Redis error in rate limiting', [
                'error' => $e->getMessage(),
                'key' => $key
            ]);
            
            return false;
        }
    }

    private function createRateLimitResponse(int $retryAfterSeconds): ResponseInterface
    {
        $retryAfter = (new \DateTimeImmutable())->modify("+{$retryAfterSeconds} seconds");
        
        $response = new JsonResponse([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter->format(\DateTimeInterface::ATOM)
            ],
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        ], 429);

        // Adicionar header Retry-After
        return $response->withHeader('Retry-After', (string)$retryAfterSeconds);
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        
        // Verificar headers de proxy
        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        
        if (!empty($serverParams['HTTP_X_REAL_IP'])) {
            return $serverParams['HTTP_X_REAL_IP'];
        }
        
        return $serverParams['REMOTE_ADDR'] ?? 'unknown';
    }
}
