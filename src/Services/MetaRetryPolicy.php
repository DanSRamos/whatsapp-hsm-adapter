<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Política de retry específica para Meta API (Instagram + Messenger)
 * 
 * Implementa retry com exponential backoff para erros transientes da Meta API.
 * Não faz retry para erros permanentes como:
 * - 36103: Account not eligible
 * - 2534068: Feature not available
 * - 190: Invalid token
 * - 200: Permission error
 * 
 * Validates: Requirements 10.3, 10.6 (Retry logic e erros transientes)
 */
class MetaRetryPolicy
{
    // Códigos de erro permanentes que não devem ter retry
    private const PERMANENT_ERROR_CODES = [
        36103,   // Account not eligible for messaging
        2534068, // Feature not available
        10,      // Permission denied
        100,     // Invalid parameter
        190,     // Invalid access token
        200,     // Permission error
        551,     // User not available to receive messages
        2022,    // Messaging window expired
    ];

    // Configuração de retry
    private const MAX_RETRIES = 3;
    private const INITIAL_DELAY_MS = 1000;
    private const MAX_DELAY_MS = 16000;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly int $maxRetries = self::MAX_RETRIES
    ) {}

    /**
     * Executa uma operação com retry policy específica para Meta API
     * 
     * @template T
     * @param callable(): T $operation Operação a ser executada
     * @param string $context Contexto da operação (para logging)
     * @return T Resultado da operação
     * @throws \Throwable Se todas as tentativas falharem
     */
    public function execute(callable $operation, string $context = 'meta_api_call'): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                $this->logger->debug('Executing Meta API operation', [
                    'context' => $context,
                    'attempt' => $attempt,
                    'max_retries' => $this->maxRetries
                ]);

                return $operation();
            } catch (\Throwable $e) {
                $lastException = $e;

                $errorInfo = $this->extractErrorInfo($e);
                
                $this->logger->warning('Meta API operation failed', [
                    'context' => $context,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'error_code' => $errorInfo['code'],
                    'error_subcode' => $errorInfo['subcode'],
                    'is_retryable' => $errorInfo['is_retryable']
                ]);

                // Se não deve fazer retry, lança exceção imediatamente
                if (!$errorInfo['is_retryable']) {
                    $this->logger->info('Meta API error is not retryable, failing immediately', [
                        'context' => $context,
                        'error_code' => $errorInfo['code'],
                        'error_message' => $errorInfo['message']
                    ]);
                    throw $e;
                }

                // Se atingiu o máximo de tentativas, lança exceção
                if ($attempt >= $this->maxRetries) {
                    $this->logger->error('Max retry attempts reached for Meta API', [
                        'context' => $context,
                        'attempts' => $attempt,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }

                // Calcula delay e aguarda antes de retry
                $delay = $this->calculateDelay($attempt, $errorInfo);
                $this->logger->info('Retrying Meta API operation after delay', [
                    'context' => $context,
                    'delay_ms' => $delay,
                    'next_attempt' => $attempt + 1
                ]);

                usleep($delay * 1000); // Converte ms para microsegundos
            }
        }

        // Nunca deve chegar aqui, mas por segurança
        throw $lastException ?? new \RuntimeException('Meta API operation failed without exception');
    }

    /**
     * Determina se um erro é retryable
     * 
     * @param int|null $errorCode Código de erro da Meta API
     * @param int|null $httpStatusCode Código HTTP da resposta
     * @return bool True se o erro é retryable
     */
    public function isRetryable(?int $errorCode, ?int $httpStatusCode): bool
    {
        // Erros permanentes não são retryable
        if ($errorCode !== null && in_array($errorCode, self::PERMANENT_ERROR_CODES, true)) {
            return false;
        }

        // Rate limit (429) é retryable
        if ($httpStatusCode === 429) {
            return true;
        }

        // Erros de servidor (5xx) são retryable
        if ($httpStatusCode !== null && $httpStatusCode >= 500 && $httpStatusCode < 600) {
            return true;
        }

        // Timeout e erros de conexão são retryable
        if ($httpStatusCode === null) {
            return true;
        }

        // Outros erros 4xx não são retryable
        if ($httpStatusCode >= 400 && $httpStatusCode < 500) {
            return false;
        }

        // Por padrão, considerar retryable
        return true;
    }

    /**
     * Extrai informações de erro da exceção
     * 
     * @param \Throwable $exception Exceção capturada
     * @return array Informações do erro
     */
    private function extractErrorInfo(\Throwable $exception): array
    {
        $info = [
            'code' => null,
            'subcode' => null,
            'message' => $exception->getMessage(),
            'http_status' => null,
            'is_retryable' => false,
            'retry_after' => null
        ];

        // Se é uma exceção HTTP do Guzzle
        if ($exception instanceof RequestException && $exception->hasResponse()) {
            $response = $exception->getResponse();
            $info['http_status'] = $response->getStatusCode();

            // Tentar extrair erro da resposta JSON
            try {
                $body = (string)$response->getBody();
                $data = json_decode($body, true);

                if (isset($data['error'])) {
                    $error = $data['error'];
                    $info['code'] = $error['code'] ?? null;
                    $info['subcode'] = $error['error_subcode'] ?? null;
                    $info['message'] = $error['message'] ?? $info['message'];
                }
            } catch (\Throwable $e) {
                // Ignorar erros ao parsear resposta
            }

            // Extrair Retry-After header se presente
            if ($response->hasHeader('Retry-After')) {
                $retryAfter = $response->getHeaderLine('Retry-After');
                if (is_numeric($retryAfter)) {
                    $info['retry_after'] = (int)$retryAfter;
                }
            }
        }

        // Determinar se é retryable
        $info['is_retryable'] = $this->isRetryable($info['code'], $info['http_status']);

        return $info;
    }

    /**
     * Calcula o delay antes do próximo retry usando exponential backoff
     * 
     * @param int $attempt Número da tentativa atual
     * @param array $errorInfo Informações do erro
     * @return int Delay em milissegundos
     */
    private function calculateDelay(int $attempt, array $errorInfo): int
    {
        // Se há Retry-After header, usar esse valor
        if ($errorInfo['retry_after'] !== null) {
            $delay = $errorInfo['retry_after'] * 1000; // Converter para ms
            $delay = min($delay, self::MAX_DELAY_MS);
            
            $this->logger->debug('Using Retry-After header for delay', [
                'retry_after_seconds' => $errorInfo['retry_after'],
                'delay_ms' => $delay
            ]);
            
            return $delay;
        }

        // Exponential backoff: 1s, 2s, 4s, 8s, 16s (max)
        $delay = self::INITIAL_DELAY_MS * (2 ** ($attempt - 1));
        $delay = min($delay, self::MAX_DELAY_MS);

        $this->logger->debug('Using exponential backoff for delay', [
            'attempt' => $attempt,
            'delay_ms' => $delay
        ]);

        return $delay;
    }

    /**
     * Retorna o número máximo de tentativas configurado
     * 
     * @return int Número máximo de retries
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Verifica se um código de erro específico é permanente
     * 
     * @param int $errorCode Código de erro da Meta API
     * @return bool True se o erro é permanente
     */
    public function isPermanentError(int $errorCode): bool
    {
        return in_array($errorCode, self::PERMANENT_ERROR_CODES, true);
    }
}
