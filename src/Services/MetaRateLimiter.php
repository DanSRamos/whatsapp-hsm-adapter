<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Psr\Log\LoggerInterface;

/**
 * Rate limiter específico para Meta API (Instagram + Messenger)
 * 
 * Implementa rate limiting baseado nos limites da Meta Platform:
 * - 200 requests por hora por Page Access Token
 * - 4800 requests por dia por Page Access Token
 * - Limites específicos por endpoint
 * 
 * Validates: Requirements 14.7 (Rate limiting)
 */
class MetaRateLimiter
{
    private const DEFAULT_HOURLY_LIMIT = 200;
    private const DEFAULT_DAILY_LIMIT = 4800;
    
    // Limites específicos por endpoint (requests por minuto)
    private const ENDPOINT_LIMITS = [
        'messages' => 60,        // Envio de mensagens: 60/min
        'message_creatives' => 30, // Criação de templates: 30/min
        'insights' => 200,       // Métricas: 200/min
        'webhooks' => 1000,      // Webhooks: sem limite prático
    ];

    private \Redis $redis;
    private LoggerInterface $logger;
    private int $hourlyLimit;
    private int $dailyLimit;

    public function __construct(
        \Redis $redis,
        LoggerInterface $logger,
        int $hourlyLimit = self::DEFAULT_HOURLY_LIMIT,
        int $dailyLimit = self::DEFAULT_DAILY_LIMIT
    ) {
        $this->redis = $redis;
        $this->logger = $logger;
        $this->hourlyLimit = $hourlyLimit;
        $this->dailyLimit = $dailyLimit;
    }

    /**
     * Verifica se uma requisição pode ser feita
     * 
     * @param string $pageAccessToken Token da página (usado como identificador)
     * @param string $endpoint Endpoint sendo acessado (messages, insights, etc)
     * @return bool True se a requisição pode ser feita
     */
    public function allowRequest(string $pageAccessToken, string $endpoint = 'messages'): bool
    {
        $tokenHash = $this->hashToken($pageAccessToken);
        
        // Verificar limite por hora
        if (!$this->checkLimit($tokenHash, 'hourly', $this->hourlyLimit, 3600)) {
            $this->logger->warning('Meta API hourly rate limit exceeded', [
                'token_hash' => $tokenHash,
                'endpoint' => $endpoint,
                'limit' => $this->hourlyLimit
            ]);
            return false;
        }

        // Verificar limite por dia
        if (!$this->checkLimit($tokenHash, 'daily', $this->dailyLimit, 86400)) {
            $this->logger->warning('Meta API daily rate limit exceeded', [
                'token_hash' => $tokenHash,
                'endpoint' => $endpoint,
                'limit' => $this->dailyLimit
            ]);
            return false;
        }

        // Verificar limite específico do endpoint (por minuto)
        $endpointLimit = self::ENDPOINT_LIMITS[$endpoint] ?? 60;
        if (!$this->checkLimit($tokenHash, "endpoint:{$endpoint}", $endpointLimit, 60)) {
            $this->logger->warning('Meta API endpoint rate limit exceeded', [
                'token_hash' => $tokenHash,
                'endpoint' => $endpoint,
                'limit' => $endpointLimit
            ]);
            return false;
        }

        return true;
    }

    /**
     * Registra uma requisição feita
     * 
     * @param string $pageAccessToken Token da página
     * @param string $endpoint Endpoint acessado
     * @return void
     */
    public function recordRequest(string $pageAccessToken, string $endpoint = 'messages'): void
    {
        $tokenHash = $this->hashToken($pageAccessToken);
        
        try {
            // Incrementar contadores
            $this->incrementCounter($tokenHash, 'hourly', 3600);
            $this->incrementCounter($tokenHash, 'daily', 86400);
            $this->incrementCounter($tokenHash, "endpoint:{$endpoint}", 60);
            
            $this->logger->debug('Meta API request recorded', [
                'token_hash' => $tokenHash,
                'endpoint' => $endpoint
            ]);
        } catch (\RedisException $e) {
            $this->logger->error('Failed to record Meta API request', [
                'error' => $e->getMessage(),
                'token_hash' => $tokenHash,
                'endpoint' => $endpoint
            ]);
        }
    }

    /**
     * Obtém o uso atual de rate limit
     * 
     * @param string $pageAccessToken Token da página
     * @return array Informações sobre uso atual
     */
    public function getUsage(string $pageAccessToken): array
    {
        $tokenHash = $this->hashToken($pageAccessToken);
        
        try {
            $hourlyUsage = (int)$this->redis->get("meta_rate_limit:{$tokenHash}:hourly") ?: 0;
            $dailyUsage = (int)$this->redis->get("meta_rate_limit:{$tokenHash}:daily") ?: 0;
            
            return [
                'hourly' => [
                    'used' => $hourlyUsage,
                    'limit' => $this->hourlyLimit,
                    'remaining' => max(0, $this->hourlyLimit - $hourlyUsage),
                    'percentage' => $this->hourlyLimit > 0 ? ($hourlyUsage / $this->hourlyLimit) * 100 : 0
                ],
                'daily' => [
                    'used' => $dailyUsage,
                    'limit' => $this->dailyLimit,
                    'remaining' => max(0, $this->dailyLimit - $dailyUsage),
                    'percentage' => $this->dailyLimit > 0 ? ($dailyUsage / $this->dailyLimit) * 100 : 0
                ]
            ];
        } catch (\RedisException $e) {
            $this->logger->error('Failed to get Meta API rate limit usage', [
                'error' => $e->getMessage(),
                'token_hash' => $tokenHash
            ]);
            
            return [
                'hourly' => ['used' => 0, 'limit' => $this->hourlyLimit, 'remaining' => $this->hourlyLimit, 'percentage' => 0],
                'daily' => ['used' => 0, 'limit' => $this->dailyLimit, 'remaining' => $this->dailyLimit, 'percentage' => 0]
            ];
        }
    }

    /**
     * Reseta os contadores de rate limit (útil para testes)
     * 
     * @param string $pageAccessToken Token da página
     * @return void
     */
    public function reset(string $pageAccessToken): void
    {
        $tokenHash = $this->hashToken($pageAccessToken);
        
        try {
            $this->redis->del([
                "meta_rate_limit:{$tokenHash}:hourly",
                "meta_rate_limit:{$tokenHash}:daily"
            ]);
            
            // Resetar contadores de endpoints
            foreach (array_keys(self::ENDPOINT_LIMITS) as $endpoint) {
                $this->redis->del("meta_rate_limit:{$tokenHash}:endpoint:{$endpoint}");
            }
            
            $this->logger->info('Meta API rate limit counters reset', [
                'token_hash' => $tokenHash
            ]);
        } catch (\RedisException $e) {
            $this->logger->error('Failed to reset Meta API rate limit counters', [
                'error' => $e->getMessage(),
                'token_hash' => $tokenHash
            ]);
        }
    }

    /**
     * Verifica se um limite foi excedido
     * 
     * @param string $tokenHash Hash do token
     * @param string $type Tipo de limite (hourly, daily, endpoint:xxx)
     * @param int $limit Limite máximo
     * @param int $windowSeconds Janela de tempo em segundos
     * @return bool True se ainda há capacidade disponível
     */
    private function checkLimit(string $tokenHash, string $type, int $limit, int $windowSeconds): bool
    {
        try {
            $key = "meta_rate_limit:{$tokenHash}:{$type}";
            $currentCount = (int)$this->redis->get($key) ?: 0;
            
            return $currentCount < $limit;
        } catch (\RedisException $e) {
            // Se Redis falhar, permitir a requisição mas registrar o erro
            $this->logger->error('Redis error in Meta rate limiting check', [
                'error' => $e->getMessage(),
                'type' => $type
            ]);
            
            return true;
        }
    }

    /**
     * Incrementa um contador de rate limit
     * 
     * @param string $tokenHash Hash do token
     * @param string $type Tipo de contador
     * @param int $ttl TTL em segundos
     * @return void
     */
    private function incrementCounter(string $tokenHash, string $type, int $ttl): void
    {
        $key = "meta_rate_limit:{$tokenHash}:{$type}";
        
        $this->redis->multi();
        $this->redis->incr($key);
        $this->redis->expire($key, $ttl);
        $this->redis->exec();
    }

    /**
     * Cria um hash do token para usar como chave
     * 
     * @param string $token Token completo
     * @return string Hash do token (primeiros 12 caracteres do SHA256)
     */
    private function hashToken(string $token): string
    {
        return substr(hash('sha256', $token), 0, 12);
    }
}
