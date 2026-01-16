<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;

class RetryHandler
{
    private const MAX_RETRIES = 3;
    private const INITIAL_DELAY_MS = 1000;
    private const MAX_DELAY_MS = 30000;

    public function __construct(
        private LoggerInterface $logger,
        private int $maxRetries = self::MAX_RETRIES,
        private int $initialDelayMs = self::INITIAL_DELAY_MS
    ) {}

    /**
     * Executa uma operação com retry e backoff exponencial
     *
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws \Throwable
     */
    public function execute(callable $operation): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                $this->logger->debug('Executing operation', [
                    'attempt' => $attempt,
                    'max_retries' => $this->maxRetries
                ]);

                return $operation();
            } catch (\Throwable $e) {
                $lastException = $e;

                $this->logger->warning('Operation failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e)
                ]);

                // Se não deve fazer retry, lança exceção imediatamente
                if (!$this->isRetryableError($e)) {
                    $this->logger->info('Error is not retryable, failing immediately', [
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }

                // Se atingiu o máximo de tentativas, lança exceção
                if ($attempt >= $this->maxRetries) {
                    $this->logger->error('Max retry attempts reached', [
                        'attempts' => $attempt,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }

                // Calcula delay e aguarda antes de retry
                $delay = $this->getDelay($attempt, $e);
                $this->logger->info('Retrying after delay', [
                    'delay_ms' => $delay,
                    'next_attempt' => $attempt + 1
                ]);

                usleep($delay * 1000); // Converte ms para microsegundos
            }
        }

        // Nunca deve chegar aqui, mas por segurança
        throw $lastException ?? new \RuntimeException('Operation failed without exception');
    }

    /**
     * Determina se um erro é retryable
     */
    private function isRetryableError(\Throwable $error): bool
    {
        // Erros de conexão são sempre retryable
        if ($error instanceof ConnectException) {
            return true;
        }

        // Erros de servidor (5xx) são retryable
        if ($error instanceof ServerException) {
            return true;
        }

        // Erros de cliente (4xx) não são retryable, exceto 429 (rate limit)
        if ($error instanceof ClientException) {
            $statusCode = $error->getResponse()->getStatusCode();
            return $statusCode === 429;
        }

        // Outros erros não são retryable por padrão
        return false;
    }

    /**
     * Calcula o delay antes do próximo retry
     */
    private function getDelay(int $attempt, \Throwable $error): int
    {
        // Se é erro 429, tenta respeitar header Retry-After
        if ($error instanceof ClientException && $error->getResponse()->getStatusCode() === 429) {
            $retryAfter = $this->getRetryAfterHeader($error);
            if ($retryAfter !== null) {
                $delay = max(0, min($retryAfter * 1000, self::MAX_DELAY_MS));
                $this->logger->debug('Using Retry-After header', [
                    'retry_after_seconds' => $retryAfter,
                    'delay_ms' => $delay
                ]);
                return $delay;
            }
        }

        // Backoff exponencial: 1s, 2s, 4s, 8s, etc.
        $delay = $this->initialDelayMs * (2 ** ($attempt - 1));
        $delay = min($delay, self::MAX_DELAY_MS);

        $this->logger->debug('Using exponential backoff', [
            'attempt' => $attempt,
            'delay_ms' => $delay
        ]);

        return $delay;
    }

    /**
     * Extrai o valor do header Retry-After da resposta
     */
    private function getRetryAfterHeader(ClientException $error): ?int
    {
        $response = $error->getResponse();
        if (!$response->hasHeader('Retry-After')) {
            return null;
        }

        $retryAfter = $response->getHeaderLine('Retry-After');

        // Retry-After pode ser um número de segundos ou uma data HTTP
        if (is_numeric($retryAfter)) {
            return (int) $retryAfter;
        }

        // Tenta parsear como data HTTP
        $retryDate = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC7231, $retryAfter);
        if ($retryDate !== false) {
            $now = new \DateTimeImmutable();
            $diff = $retryDate->getTimestamp() - $now->getTimestamp();
            return max(0, $diff);
        }

        return null;
    }

    /**
     * Retorna o número máximo de tentativas configurado
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }
}
