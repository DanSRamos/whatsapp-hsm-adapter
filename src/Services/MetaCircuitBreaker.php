<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use Psr\Log\LoggerInterface;

/**
 * Circuit Breaker para Meta API (Instagram + Messenger)
 * 
 * Implementa o padrão Circuit Breaker para proteger o sistema contra falhas
 * em cascata quando a Meta API está indisponível ou com problemas.
 * 
 * Estados:
 * - CLOSED: Funcionamento normal, requisições passam
 * - OPEN: Muitas falhas detectadas, requisições são bloqueadas
 * - HALF_OPEN: Testando se o serviço se recuperou
 * 
 * Validates: Requirements 14.7 (Fault tolerance)
 */
class MetaCircuitBreaker
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    // Thresholds padrão
    private const DEFAULT_FAILURE_THRESHOLD = 5;      // Falhas antes de abrir
    private const DEFAULT_SUCCESS_THRESHOLD = 2;      // Sucessos para fechar
    private const DEFAULT_TIMEOUT_SECONDS = 60;       // Tempo em OPEN antes de HALF_OPEN
    private const DEFAULT_WINDOW_SECONDS = 300;       // Janela de tempo para contar falhas

    private \Redis $redis;
    private LoggerInterface $logger;
    private int $failureThreshold;
    private int $successThreshold;
    private int $timeoutSeconds;
    private int $windowSeconds;

    public function __construct(
        \Redis $redis,
        LoggerInterface $logger,
        int $failureThreshold = self::DEFAULT_FAILURE_THRESHOLD,
        int $successThreshold = self::DEFAULT_SUCCESS_THRESHOLD,
        int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
        int $windowSeconds = self::DEFAULT_WINDOW_SECONDS
    ) {
        $this->redis = $redis;
        $this->logger = $logger;
        $this->failureThreshold = $failureThreshold;
        $this->successThreshold = $successThreshold;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->windowSeconds = $windowSeconds;
    }

    /**
     * Executa uma operação protegida pelo circuit breaker
     * 
     * @template T
     * @param string $serviceName Nome do serviço (ex: 'meta_api', 'meta_messages')
     * @param callable(): T $operation Operação a ser executada
     * @return T Resultado da operação
     * @throws CircuitBreakerOpenException Se o circuit breaker está aberto
     * @throws \Throwable Se a operação falhar
     */
    public function execute(string $serviceName, callable $operation): mixed
    {
        $state = $this->getState($serviceName);

        // Se está OPEN, bloquear requisição
        if ($state === self::STATE_OPEN) {
            $this->logger->warning('Circuit breaker is OPEN, blocking request', [
                'service' => $serviceName,
                'state' => $state
            ]);
            
            throw new CircuitBreakerOpenException(
                "Circuit breaker is OPEN for service: {$serviceName}. " .
                "The service is temporarily unavailable due to repeated failures."
            );
        }

        // Se está HALF_OPEN, permitir apenas uma requisição de teste
        if ($state === self::STATE_HALF_OPEN) {
            $this->logger->info('Circuit breaker is HALF_OPEN, allowing test request', [
                'service' => $serviceName
            ]);
        }

        try {
            // Executar operação
            $result = $operation();

            // Registrar sucesso
            $this->recordSuccess($serviceName);

            return $result;
        } catch (\Throwable $e) {
            // Registrar falha
            $this->recordFailure($serviceName);

            throw $e;
        }
    }

    /**
     * Verifica se o circuit breaker permite requisições
     * 
     * @param string $serviceName Nome do serviço
     * @return bool True se requisições são permitidas
     */
    public function isAvailable(string $serviceName): bool
    {
        return $this->getState($serviceName) !== self::STATE_OPEN;
    }

    /**
     * Obtém o estado atual do circuit breaker
     * 
     * @param string $serviceName Nome do serviço
     * @return string Estado atual (closed, open, half_open)
     */
    public function getState(string $serviceName): string
    {
        try {
            $stateKey = $this->getStateKey($serviceName);
            $state = $this->redis->get($stateKey);

            if ($state === false || $state === null) {
                return self::STATE_CLOSED;
            }

            // Se está OPEN, verificar se deve mudar para HALF_OPEN
            if ($state === self::STATE_OPEN) {
                $openedAt = $this->redis->get($this->getOpenedAtKey($serviceName));
                if ($openedAt !== false && $openedAt !== null) {
                    $elapsedSeconds = time() - (int)$openedAt;
                    if ($elapsedSeconds >= $this->timeoutSeconds) {
                        $this->logger->info('Circuit breaker timeout elapsed, transitioning to HALF_OPEN', [
                            'service' => $serviceName,
                            'elapsed_seconds' => $elapsedSeconds
                        ]);
                        $this->setState($serviceName, self::STATE_HALF_OPEN);
                        return self::STATE_HALF_OPEN;
                    }
                }
            }

            return $state;
        } catch (\RedisException $e) {
            $this->logger->error('Failed to get circuit breaker state', [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
            // Em caso de erro, assumir CLOSED para não bloquear requisições
            return self::STATE_CLOSED;
        }
    }

    /**
     * Obtém estatísticas do circuit breaker
     * 
     * @param string $serviceName Nome do serviço
     * @return array Estatísticas
     */
    public function getStats(string $serviceName): array
    {
        try {
            $state = $this->getState($serviceName);
            $failures = (int)$this->redis->get($this->getFailureCountKey($serviceName)) ?: 0;
            $successes = (int)$this->redis->get($this->getSuccessCountKey($serviceName)) ?: 0;
            
            $stats = [
                'service' => $serviceName,
                'state' => $state,
                'failures' => $failures,
                'successes' => $successes,
                'failure_threshold' => $this->failureThreshold,
                'success_threshold' => $this->successThreshold,
                'is_available' => $state !== self::STATE_OPEN
            ];

            if ($state === self::STATE_OPEN) {
                $openedAt = $this->redis->get($this->getOpenedAtKey($serviceName));
                if ($openedAt !== false && $openedAt !== null) {
                    $elapsedSeconds = time() - (int)$openedAt;
                    $stats['opened_at'] = date('Y-m-d H:i:s', (int)$openedAt);
                    $stats['elapsed_seconds'] = $elapsedSeconds;
                    $stats['remaining_seconds'] = max(0, $this->timeoutSeconds - $elapsedSeconds);
                }
            }

            return $stats;
        } catch (\RedisException $e) {
            $this->logger->error('Failed to get circuit breaker stats', [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
            
            return [
                'service' => $serviceName,
                'state' => 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reseta o circuit breaker para o estado CLOSED
     * 
     * @param string $serviceName Nome do serviço
     * @return void
     */
    public function reset(string $serviceName): void
    {
        try {
            $this->redis->del([
                $this->getStateKey($serviceName),
                $this->getFailureCountKey($serviceName),
                $this->getSuccessCountKey($serviceName),
                $this->getOpenedAtKey($serviceName)
            ]);

            $this->logger->info('Circuit breaker reset', [
                'service' => $serviceName
            ]);
        } catch (\RedisException $e) {
            $this->logger->error('Failed to reset circuit breaker', [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Registra uma falha
     * 
     * @param string $serviceName Nome do serviço
     * @return void
     */
    private function recordFailure(string $serviceName): void
    {
        try {
            $state = $this->getState($serviceName);
            $failureKey = $this->getFailureCountKey($serviceName);

            // Incrementar contador de falhas
            $this->redis->multi();
            $this->redis->incr($failureKey);
            $this->redis->expire($failureKey, $this->windowSeconds);
            $results = $this->redis->exec();

            $failures = $results[0] ?? 0;

            $this->logger->debug('Failure recorded', [
                'service' => $serviceName,
                'failures' => $failures,
                'threshold' => $this->failureThreshold,
                'state' => $state
            ]);

            // Se está CLOSED e atingiu o threshold, abrir
            if ($state === self::STATE_CLOSED && $failures >= $this->failureThreshold) {
                $this->logger->warning('Failure threshold reached, opening circuit breaker', [
                    'service' => $serviceName,
                    'failures' => $failures,
                    'threshold' => $this->failureThreshold
                ]);
                $this->open($serviceName);
            }

            // Se está HALF_OPEN, voltar para OPEN
            if ($state === self::STATE_HALF_OPEN) {
                $this->logger->warning('Failure in HALF_OPEN state, reopening circuit breaker', [
                    'service' => $serviceName
                ]);
                $this->open($serviceName);
            }
        } catch (\RedisException $e) {
            $this->logger->error('Failed to record failure', [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Registra um sucesso
     * 
     * @param string $serviceName Nome do serviço
     * @return void
     */
    private function recordSuccess(string $serviceName): void
    {
        try {
            $state = $this->getState($serviceName);

            // Se está CLOSED, resetar contador de falhas
            if ($state === self::STATE_CLOSED) {
                $this->redis->del($this->getFailureCountKey($serviceName));
                return;
            }

            // Se está HALF_OPEN, incrementar sucessos
            if ($state === self::STATE_HALF_OPEN) {
                $successKey = $this->getSuccessCountKey($serviceName);
                
                $this->redis->multi();
                $this->redis->incr($successKey);
                $this->redis->expire($successKey, $this->windowSeconds);
                $results = $this->redis->exec();

                $successes = $results[0] ?? 0;

                $this->logger->debug('Success recorded in HALF_OPEN state', [
                    'service' => $serviceName,
                    'successes' => $successes,
                    'threshold' => $this->successThreshold
                ]);

                // Se atingiu o threshold de sucessos, fechar
                if ($successes >= $this->successThreshold) {
                    $this->logger->info('Success threshold reached, closing circuit breaker', [
                        'service' => $serviceName,
                        'successes' => $successes,
                        'threshold' => $this->successThreshold
                    ]);
                    $this->close($serviceName);
                }
            }
        } catch (\RedisException $e) {
            $this->logger->error('Failed to record success', [
                'service' => $serviceName,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Abre o circuit breaker
     * 
     * @param string $serviceName Nome do serviço
     * @return void
     */
    private function open(string $serviceName): void
    {
        $this->setState($serviceName, self::STATE_OPEN);
        $this->redis->set($this->getOpenedAtKey($serviceName), time());
        
        // Resetar contadores
        $this->redis->del([
            $this->getFailureCountKey($serviceName),
            $this->getSuccessCountKey($serviceName)
        ]);
    }

    /**
     * Fecha o circuit breaker
     * 
     * @param string $serviceName Nome do serviço
     * @return void
     */
    private function close(string $serviceName): void
    {
        $this->setState($serviceName, self::STATE_CLOSED);
        
        // Resetar contadores e timestamp
        $this->redis->del([
            $this->getFailureCountKey($serviceName),
            $this->getSuccessCountKey($serviceName),
            $this->getOpenedAtKey($serviceName)
        ]);
    }

    /**
     * Define o estado do circuit breaker
     * 
     * @param string $serviceName Nome do serviço
     * @param string $state Novo estado
     * @return void
     */
    private function setState(string $serviceName, string $state): void
    {
        $this->redis->set($this->getStateKey($serviceName), $state);
    }

    // Métodos auxiliares para chaves Redis

    private function getStateKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:state";
    }

    private function getFailureCountKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:failures";
    }

    private function getSuccessCountKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:successes";
    }

    private function getOpenedAtKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:opened_at";
    }
}

/**
 * Exceção lançada quando o circuit breaker está aberto
 */
class CircuitBreakerOpenException extends \RuntimeException
{
    public function __construct(string $message = "", int $code = 503, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
