<?php

declare(strict_types=1);

use WhatsApp\Adapter\Http\Middleware\RateLimitMiddleware;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;

/**
 * Property 20: Rate Limiting Enforcement
 * 
 * For any sequência de pedidos aos endpoints do adapter, 
 * o sistema deve aplicar rate limiting para prevenir abuso
 * 
 * Validates: Requirements 11.5
 * Feature: whatsapp-hsm-adapter, Property 20: Rate Limiting Enforcement
 */
describe('Property 20: Rate Limiting Enforcement', function () {
    
    beforeEach(function () {
        // Criar instância Redis para testes
        $this->redis = new Redis();
        
        try {
            $this->redis->connect('127.0.0.1', 6379);
            $this->redisAvailable = true;
            
            // Limpar chaves de teste
            $keys = $this->redis->keys('rate_limit:test:*');
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
        } catch (\RedisException $e) {
            $this->redisAvailable = false;
            test()->markTestSkipped('Redis not available: ' . $e->getMessage());
        }
    });

    afterEach(function () {
        if ($this->redisAvailable ?? false) {
            // Limpar chaves de teste
            $keys = $this->redis->keys('rate_limit:test:*');
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
            
            $this->redis->close();
        }
    });

    test('enforces rate limit by IP after exceeding threshold', function () {
        $logger = new NullLogger();
        $limitPerMinute = 5; // Limite baixo para teste
        
        $middleware = new RateLimitMiddleware(
            $this->redis,
            $logger,
            $limitPerMinute,
            1000
        );
        
        // Handler mock que sempre retorna sucesso
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], json_encode(['success' => true]));
            }
        };
        
        $clientIp = '192.168.1.' . rand(1, 254);
        
        // Fazer pedidos até exceder o limite
        $successCount = 0;
        $rateLimitedCount = 0;
        
        for ($i = 0; $i < $limitPerMinute + 3; $i++) {
            $request = new ServerRequest(
                'GET',
                '/api/test',
                [],
                null,
                '1.1',
                ['REMOTE_ADDR' => $clientIp]
            );
            
            $response = $middleware->process($request, $handler);
            
            if ($response->getStatusCode() === 200) {
                $successCount++;
            } elseif ($response->getStatusCode() === 429) {
                $rateLimitedCount++;
                
                // Verificar que a resposta contém Retry-After header
                expect($response->hasHeader('Retry-After'))->toBeTrue();
                
                // Verificar corpo da resposta
                $body = json_decode((string)$response->getBody(), true);
                expect($body['success'])->toBeFalse();
                expect($body['error']['code'])->toBe('RATE_LIMIT_EXCEEDED');
            }
        }
        
        // Deve ter permitido exatamente $limitPerMinute pedidos
        expect($successCount)->toBe($limitPerMinute);
        
        // Deve ter bloqueado os pedidos excedentes
        expect($rateLimitedCount)->toBeGreaterThan(0);
    })->repeat(10);

    test('enforces rate limit by API key after exceeding threshold', function () {
        $logger = new NullLogger();
        $limitPerHour = 10; // Limite baixo para teste
        
        $middleware = new RateLimitMiddleware(
            $this->redis,
            $logger,
            1000, // Limite por IP alto para não interferir
            $limitPerHour
        );
        
        // Handler mock que sempre retorna sucesso
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], json_encode(['success' => true]));
            }
        };
        
        $apiKey = 'test_api_key_' . uniqid();
        
        // Fazer pedidos até exceder o limite
        $successCount = 0;
        $rateLimitedCount = 0;
        
        for ($i = 0; $i < $limitPerHour + 3; $i++) {
            $request = (new ServerRequest(
                'GET',
                '/api/test',
                [],
                null,
                '1.1',
                ['REMOTE_ADDR' => '192.168.1.100']
            ))->withAttribute('api_key', $apiKey);
            
            $response = $middleware->process($request, $handler);
            
            if ($response->getStatusCode() === 200) {
                $successCount++;
            } elseif ($response->getStatusCode() === 429) {
                $rateLimitedCount++;
                
                // Verificar que a resposta contém Retry-After header
                expect($response->hasHeader('Retry-After'))->toBeTrue();
            }
        }
        
        // Deve ter permitido exatamente $limitPerHour pedidos
        expect($successCount)->toBe($limitPerHour);
        
        // Deve ter bloqueado os pedidos excedentes
        expect($rateLimitedCount)->toBeGreaterThan(0);
    })->repeat(10);

    test('rate limits are independent per IP', function () {
        $logger = new NullLogger();
        $limitPerMinute = 5;
        
        $middleware = new RateLimitMiddleware(
            $this->redis,
            $logger,
            $limitPerMinute,
            1000
        );
        
        // Handler mock
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], json_encode(['success' => true]));
            }
        };
        
        $ip1 = '192.168.1.' . rand(1, 254);
        $ip2 = '192.168.1.' . rand(1, 254);
        
        // Garantir IPs diferentes
        while ($ip1 === $ip2) {
            $ip2 = '192.168.1.' . rand(1, 254);
        }
        
        // Fazer pedidos do IP1 até o limite
        for ($i = 0; $i < $limitPerMinute; $i++) {
            $request = new ServerRequest(
                'GET',
                '/api/test',
                [],
                null,
                '1.1',
                ['REMOTE_ADDR' => $ip1]
            );
            
            $response = $middleware->process($request, $handler);
            expect($response->getStatusCode())->toBe(200);
        }
        
        // IP1 deve estar bloqueado
        $request = new ServerRequest(
            'GET',
            '/api/test',
            [],
            null,
            '1.1',
            ['REMOTE_ADDR' => $ip1]
        );
        $response = $middleware->process($request, $handler);
        expect($response->getStatusCode())->toBe(429);
        
        // IP2 ainda deve poder fazer pedidos
        $request = new ServerRequest(
            'GET',
            '/api/test',
            [],
            null,
            '1.1',
            ['REMOTE_ADDR' => $ip2]
        );
        $response = $middleware->process($request, $handler);
        expect($response->getStatusCode())->toBe(200);
    })->repeat(10);

    test('rate limits are independent per API key', function () {
        $logger = new NullLogger();
        $limitPerHour = 5;
        
        $middleware = new RateLimitMiddleware(
            $this->redis,
            $logger,
            1000, // Limite por IP alto
            $limitPerHour
        );
        
        // Handler mock
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], json_encode(['success' => true]));
            }
        };
        
        $apiKey1 = 'test_key_' . uniqid();
        $apiKey2 = 'test_key_' . uniqid();
        
        // Fazer pedidos com apiKey1 até o limite
        for ($i = 0; $i < $limitPerHour; $i++) {
            $request = (new ServerRequest(
                'GET',
                '/api/test',
                [],
                null,
                '1.1',
                ['REMOTE_ADDR' => '192.168.1.100']
            ))->withAttribute('api_key', $apiKey1);
            
            $response = $middleware->process($request, $handler);
            expect($response->getStatusCode())->toBe(200);
        }
        
        // apiKey1 deve estar bloqueada
        $request = (new ServerRequest(
            'GET',
            '/api/test',
            [],
            null,
            '1.1',
            ['REMOTE_ADDR' => '192.168.1.100']
        ))->withAttribute('api_key', $apiKey1);
        $response = $middleware->process($request, $handler);
        expect($response->getStatusCode())->toBe(429);
        
        // apiKey2 ainda deve poder fazer pedidos
        $request = (new ServerRequest(
            'GET',
            '/api/test',
            [],
            null,
            '1.1',
            ['REMOTE_ADDR' => '192.168.1.100']
        ))->withAttribute('api_key', $apiKey2);
        $response = $middleware->process($request, $handler);
        expect($response->getStatusCode())->toBe(200);
    })->repeat(10);

    test('allows requests when Redis is unavailable (fail-open)', function () {
        $logger = new NullLogger();
        
        // Criar Redis com configuração inválida
        $redis = new Redis();
        // Não conectar - vai falhar nas operações
        
        $middleware = new RateLimitMiddleware(
            $redis,
            $logger,
            5,
            10
        );
        
        // Handler mock
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], json_encode(['success' => true]));
            }
        };
        
        $request = new ServerRequest(
            'GET',
            '/api/test',
            [],
            null,
            '1.1',
            ['REMOTE_ADDR' => '192.168.1.100']
        );
        
        // Deve permitir o pedido mesmo com Redis indisponível
        $response = $middleware->process($request, $handler);
        expect($response->getStatusCode())->toBe(200);
    })->repeat(5);

    test('respects X-Forwarded-For header for IP detection', function () {
        $logger = new NullLogger();
        $limitPerMinute = 5;
        
        $middleware = new RateLimitMiddleware(
            $this->redis,
            $logger,
            $limitPerMinute,
            1000
        );
        
        // Handler mock
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200, [], json_encode(['success' => true]));
            }
        };
        
        $realClientIp = '203.0.113.' . rand(1, 254);
        
        // Fazer pedidos até o limite usando X-Forwarded-For
        for ($i = 0; $i < $limitPerMinute; $i++) {
            $request = new ServerRequest(
                'GET',
                '/api/test',
                [],
                null,
                '1.1',
                [
                    'REMOTE_ADDR' => '10.0.0.1', // IP do proxy
                    'HTTP_X_FORWARDED_FOR' => $realClientIp . ', 10.0.0.1'
                ]
            );
            
            $response = $middleware->process($request, $handler);
            expect($response->getStatusCode())->toBe(200);
        }
        
        // Próximo pedido deve ser bloqueado
        $request = new ServerRequest(
            'GET',
            '/api/test',
            [],
            null,
            '1.1',
            [
                'REMOTE_ADDR' => '10.0.0.1',
                'HTTP_X_FORWARDED_FOR' => $realClientIp . ', 10.0.0.1'
            ]
        );
        $response = $middleware->process($request, $handler);
        expect($response->getStatusCode())->toBe(429);
    })->repeat(10);
});
