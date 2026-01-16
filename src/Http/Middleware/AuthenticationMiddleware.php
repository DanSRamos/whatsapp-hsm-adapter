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
 * Middleware de autenticação que valida API keys em pedidos
 * 
 * Validates: Requirements 11.2
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    private array $validApiKeys;
    private LoggerInterface $logger;

    public function __construct(array $validApiKeys, LoggerInterface $logger)
    {
        $this->validApiKeys = $validApiKeys;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Extrair API key do header Authorization
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader)) {
            $this->logger->warning('Missing Authorization header', [
                'ip' => $this->getClientIp($request),
                'path' => $request->getUri()->getPath()
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'MISSING_API_KEY',
                    'message' => 'Authorization header is required'
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 401);
        }

        // Suportar formato "Bearer {api_key}" ou apenas "{api_key}"
        $apiKey = $this->extractApiKey($authHeader);

        if (!$this->isValidApiKey($apiKey)) {
            $this->logger->warning('Invalid API key', [
                'ip' => $this->getClientIp($request),
                'path' => $request->getUri()->getPath(),
                'api_key_prefix' => substr($apiKey, 0, 8) . '...'
            ]);
            
            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_API_KEY',
                    'message' => 'Invalid API key'
                ],
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
            ], 401);
        }

        // Adicionar API key ao request para uso posterior (ex: rate limiting)
        $request = $request->withAttribute('api_key', $apiKey);

        $this->logger->debug('Request authenticated', [
            'api_key_prefix' => substr($apiKey, 0, 8) . '...',
            'path' => $request->getUri()->getPath()
        ]);

        return $handler->handle($request);
    }

    private function extractApiKey(string $authHeader): string
    {
        // Remover "Bearer " se presente
        if (stripos($authHeader, 'Bearer ') === 0) {
            return trim(substr($authHeader, 7));
        }

        return trim($authHeader);
    }

    private function isValidApiKey(string $apiKey): bool
    {
        return in_array($apiKey, $this->validApiKeys, true);
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
