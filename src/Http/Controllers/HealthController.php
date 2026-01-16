<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Http\Controllers;

use WhatsApp\Adapter\Services\CacheInterface;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WhatsApp\Adapter\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;

class HealthController
{
    public function __construct(
        private PDO $database,
        private CacheInterface $cache,
        private ClientInterface $httpClient,
        private array $config,
        private LoggerInterface $logger
    ) {}

    /**
     * GET /health
     * Verifica o status do serviço e suas dependências
     */
    public function check(ServerRequestInterface $request): ResponseInterface
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'providers' => $this->checkProviders(),
        ];

        $allHealthy = !in_array(false, array_column($checks, 'healthy'), true);
        $status = $allHealthy ? 'healthy' : 'unhealthy';
        $httpStatus = $allHealthy ? 200 : 503;

        return new JsonResponse([
            'status' => $status,
            'timestamp' => date('c'),
            'checks' => $checks,
        ], $httpStatus);
    }

    /**
     * Verifica conectividade com a base de dados
     */
    private function checkDatabase(): array
    {
        try {
            $stmt = $this->database->query('SELECT 1');
            $result = $stmt->fetch();

            return [
                'healthy' => $result !== false,
                'message' => 'Database connection successful',
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Health check: Database connection failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'healthy' => false,
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica conectividade com Redis
     */
    private function checkRedis(): array
    {
        try {
            // Tenta fazer uma operação simples no cache
            $testKey = 'health_check_' . time();
            $testValue = 'test';
            
            $this->cache->set($testKey, $testValue, 10);
            $retrieved = $this->cache->get($testKey);
            $this->cache->delete($testKey);

            $healthy = $retrieved === $testValue;

            return [
                'healthy' => $healthy,
                'message' => $healthy ? 'Redis connection successful' : 'Redis connection failed',
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Health check: Redis connection failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'healthy' => false,
                'message' => 'Redis connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica conectividade com os provedores WhatsApp
     */
    private function checkProviders(): array
    {
        $providers = [];
        $allHealthy = true;

        foreach ($this->config['providers'] as $name => $providerConfig) {
            $check = $this->checkProvider($name, $providerConfig);
            $providers[$name] = $check;
            
            if (!$check['healthy']) {
                $allHealthy = false;
            }
        }

        return [
            'healthy' => $allHealthy,
            'message' => $allHealthy ? 'All providers accessible' : 'Some providers unavailable',
            'providers' => $providers,
        ];
    }

    /**
     * Verifica conectividade com um provedor específico
     */
    private function checkProvider(string $name, array $config): array
    {
        try {
            // Para Infobip, tenta fazer uma chamada simples à API
            if ($name === 'infobip') {
                $response = $this->httpClient->get(
                    $config['base_url'] . '/whatsapp/1/senders',
                    [
                        'headers' => [
                            'Authorization' => 'App ' . $config['api_key'],
                            'Accept' => 'application/json',
                        ],
                        'timeout' => 5,
                    ]
                );

                $healthy = $response->getStatusCode() === 200;

                return [
                    'healthy' => $healthy,
                    'message' => $healthy ? 'Provider accessible' : 'Provider returned error',
                ];
            }

            // Para Twilio, tenta fazer uma chamada simples à API
            if ($name === 'twilio') {
                $response = $this->httpClient->get(
                    sprintf(
                        'https://api.twilio.com/2010-04-01/Accounts/%s.json',
                        $config['account_sid']
                    ),
                    [
                        'auth' => [$config['account_sid'], $config['auth_token']],
                        'timeout' => 5,
                    ]
                );

                $healthy = $response->getStatusCode() === 200;

                return [
                    'healthy' => $healthy,
                    'message' => $healthy ? 'Provider accessible' : 'Provider returned error',
                ];
            }

            return [
                'healthy' => false,
                'message' => 'Unknown provider type',
            ];
        } catch (\Throwable $e) {
            $this->logger->warning("Health check: Provider {$name} check failed", [
                'provider' => $name,
                'error' => $e->getMessage(),
            ]);

            return [
                'healthy' => false,
                'message' => 'Provider check failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
