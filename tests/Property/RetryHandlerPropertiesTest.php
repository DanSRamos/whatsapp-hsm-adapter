<?php

declare(strict_types=1);

use WhatsApp\Adapter\Services\RetryHandler;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;

/**
 * Property 23: Retry with Exponential Backoff
 * 
 * For any erro temporário da API da Infobip (5xx, timeout, 429), 
 * o adapter deve implementar retry com backoff exponencial, 
 * respeitando headers Retry-After quando presentes
 * 
 * Validates: Requirements 13.1, 13.5
 * Feature: whatsapp-hsm-adapter, Property 23: Retry with Exponential Backoff
 */
describe('Property 23: Retry with Exponential Backoff', function () {
    
    test('retries on server errors (5xx) with exponential backoff', function () {
        $logger = new NullLogger();
        $retryHandler = new RetryHandler($logger, maxRetries: 3, initialDelayMs: 10);
        
        $attemptCount = 0;
        $startTime = microtime(true);
        
        try {
            $retryHandler->execute(function () use (&$attemptCount) {
                $attemptCount++;
                
                // Simula erro 500 nas primeiras 2 tentativas
                if ($attemptCount < 3) {
                    $request = new Request('POST', 'https://api.example.com');
                    $response = new Response(500, [], 'Internal Server Error');
                    throw new ServerException('Server error', $request, $response);
                }
                
                return 'success';
            });
        } catch (\Throwable $e) {
            // Pode falhar se não conseguir recuperar
        }
        
        $endTime = microtime(true);
        $elapsedMs = ($endTime - $startTime) * 1000;
        
        // Deve ter tentado múltiplas vezes
        expect($attemptCount)->toBeGreaterThan(1);
        
        // Deve ter esperado algum tempo (backoff)
        // Com initialDelayMs=10: 10ms + 20ms = 30ms mínimo para 3 tentativas
        expect($elapsedMs)->toBeGreaterThan(20);
    })->repeat(10);

    test('retries on connection errors with exponential backoff', function () {
        $logger = new NullLogger();
        $retryHandler = new RetryHandler($logger, maxRetries: 3, initialDelayMs: 10);
        
        $attemptCount = 0;
        
        try {
            $retryHandler->execute(function () use (&$attemptCount) {
                $attemptCount++;
                
                // Simula erro de conexão nas primeiras 2 tentativas
                if ($attemptCount < 3) {
                    $request = new Request('POST', 'https://api.example.com');
                    throw new ConnectException('Connection timeout', $request);
                }
                
                return 'success';
            });
        } catch (\Throwable $e) {
            // Pode falhar se não conseguir recuperar
        }
        
        // Deve ter tentado múltiplas vezes
        expect($attemptCount)->toBeGreaterThan(1);
    })->repeat(10);

    test('respects Retry-After header on 429 errors', function () {
        $logger = new NullLogger();
        $retryHandler = new RetryHandler($logger, maxRetries: 3, initialDelayMs: 10);
        
        $attemptCount = 0;
        $startTime = microtime(true);
        
        try {
            $retryHandler->execute(function () use (&$attemptCount) {
                $attemptCount++;
                
                // Simula erro 429 com Retry-After header
                if ($attemptCount < 2) {
                    $request = new Request('POST', 'https://api.example.com');
                    // Retry-After: 1 segundo
                    $response = new Response(429, ['Retry-After' => '1'], 'Too Many Requests');
                    throw new ClientException('Rate limited', $request, $response);
                }
                
                return 'success';
            });
        } catch (\Throwable $e) {
            // Pode falhar se não conseguir recuperar
        }
        
        $endTime = microtime(true);
        $elapsedMs = ($endTime - $startTime) * 1000;
        
        // Deve ter tentado pelo menos 2 vezes
        expect($attemptCount)->toBeGreaterThanOrEqual(2);
        
        // Deve ter respeitado o Retry-After (1 segundo = 1000ms)
        // Permitimos alguma margem para overhead de execução
        expect($elapsedMs)->toBeGreaterThan(900);
    })->repeat(5); // Menos repetições porque envolve sleep de 1s
});

/**
 * Property 24: Maximum Retry Attempts
 * 
 * For any operação que requer retry, o adapter deve tentar no máximo 3 vezes 
 * antes de falhar definitivamente, retornando erro com detalhes das tentativas
 * 
 * Validates: Requirements 13.2, 13.3
 * Feature: whatsapp-hsm-adapter, Property 24: Maximum Retry Attempts
 */
describe('Property 24: Maximum Retry Attempts', function () {
    
    test('fails after maximum retry attempts on persistent server errors', function () {
        $logger = new NullLogger();
        $retryHandler = new RetryHandler($logger, maxRetries: 3, initialDelayMs: 10);
        
        $attemptCount = 0;
        
        try {
            $retryHandler->execute(function () use (&$attemptCount) {
                $attemptCount++;
                
                // Sempre falha com erro 500
                $request = new Request('POST', 'https://api.example.com');
                $response = new Response(500, [], 'Internal Server Error');
                throw new ServerException('Server error', $request, $response);
            });
            
            // Não deve chegar aqui
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (ServerException $e) {
            // Deve ter tentado exatamente 3 vezes (maxRetries)
            expect($attemptCount)->toBe(3);
            expect($e->getMessage())->toContain('Server error');
        }
    })->repeat(10);

    test('fails after maximum retry attempts on persistent connection errors', function () {
        $logger = new NullLogger();
        $retryHandler = new RetryHandler($logger, maxRetries: 3, initialDelayMs: 10);
        
        $attemptCount = 0;
        
        try {
            $retryHandler->execute(function () use (&$attemptCount) {
                $attemptCount++;
                
                // Sempre falha com erro de conexão
                $request = new Request('POST', 'https://api.example.com');
                throw new ConnectException('Connection timeout', $request);
            });
            
            // Não deve chegar aqui
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (ConnectException $e) {
            // Deve ter tentado exatamente 3 vezes (maxRetries)
            expect($attemptCount)->toBe(3);
            expect($e->getMessage())->toContain('Connection timeout');
        }
    })->repeat(10);

    test('succeeds before maximum attempts if operation recovers', function () {
        $logger = new NullLogger();
        $retryHandler = new RetryHandler($logger, maxRetries: 3, initialDelayMs: 10);
        
        $attemptCount = 0;
        
        $result = $retryHandler->execute(function () use (&$attemptCount) {
            $attemptCount++;
            
            // Falha na primeira tentativa, sucesso na segunda
            if ($attemptCount === 1) {
                $request = new Request('POST', 'https://api.example.com');
                $response = new Response(500, [], 'Internal Server Error');
                throw new ServerException('Server error', $request, $response);
            }
            
            return 'success';
        });
        
        // Deve ter tentado 2 vezes (1 falha + 1 sucesso)
        expect($attemptCount)->toBe(2);
        expect($result)->toBe('success');
    })->repeat(10);
});

/**
 * Property 25: No Retry on Permanent Errors
 * 
 * For any erro permanente da API da Infobip (4xx exceto 429), 
 * o adapter não deve fazer retry e deve retornar erro imediatamente
 * 
 * Validates: Requirements 13.4
 * Feature: whatsapp-hsm-adapter, Property 25: No Retry on Permanent Errors
 */
describe('Property 25: No Retry on Permanent Errors', function () {
    
    test('does not retry on 400 Bad Request errors', function () {
        $logger = new NullLogger();
        $retryHandler = new RetryHandler($logger, maxRetries: 3, initialDelayMs: 10);
        
        $attemptCount = 0;
        
        try {
            $retryHandler->execute(function () use (&$attemptCount) {
                $attemptCount++;
                
                $request = new Request('POST', 'https://api.example.com');
                $response = new Response(400, [], 'Bad Request');
                throw new ClientException('Bad request', $request, $response);
            });
            
            // Não deve chegar aqui
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (ClientException $e) {
            // Deve ter tentado apenas 1 vez (sem retry)
            expect($attemptCount)->toBe(1);
            expect($e->getResponse()->getStatusCode())->toBe(400);
        }
    })->repeat(10);

    test('does not retry on 401 Unauthorized errors', function () {
        $logger = new NullLogger();
        $retryHandler = new RetryHandler($logger, maxRetries: 3, initialDelayMs: 10);
        
        $attemptCount = 0;
        
        try {
            $retryHandler->execute(function () use (&$attemptCount) {
                $attemptCount++;
                
                $request = new Request('POST', 'https://api.example.com');
                $response = new Response(401, [], 'Unauthorized');
                throw new ClientException('Unauthorized', $request, $response);
            });
            
            // Não deve chegar aqui
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (ClientException $e) {
            // Deve ter tentado apenas 1 vez (sem retry)
            expect($attemptCount)->toBe(1);
            expect($e->getResponse()->getStatusCode())->toBe(401);
        }
    })->repeat(10);

    test('does not retry on 404 Not Found errors', function () {
        $logger = new NullLogger();
        $retryHandler = new RetryHandler($logger, maxRetries: 3, initialDelayMs: 10);
        
        $attemptCount = 0;
        
        try {
            $retryHandler->execute(function () use (&$attemptCount) {
                $attemptCount++;
                
                $request = new Request('POST', 'https://api.example.com');
                $response = new Response(404, [], 'Not Found');
                throw new ClientException('Not found', $request, $response);
            });
            
            // Não deve chegar aqui
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (ClientException $e) {
            // Deve ter tentado apenas 1 vez (sem retry)
            expect($attemptCount)->toBe(1);
            expect($e->getResponse()->getStatusCode())->toBe(404);
        }
    })->repeat(10);

    test('does not retry on 422 Unprocessable Entity errors', function () {
        $logger = new NullLogger();
        $retryHandler = new RetryHandler($logger, maxRetries: 3, initialDelayMs: 10);
        
        $attemptCount = 0;
        
        try {
            $retryHandler->execute(function () use (&$attemptCount) {
                $attemptCount++;
                
                $request = new Request('POST', 'https://api.example.com');
                $response = new Response(422, [], 'Unprocessable Entity');
                throw new ClientException('Validation error', $request, $response);
            });
            
            // Não deve chegar aqui
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (ClientException $e) {
            // Deve ter tentado apenas 1 vez (sem retry)
            expect($attemptCount)->toBe(1);
            expect($e->getResponse()->getStatusCode())->toBe(422);
        }
    })->repeat(10);

    test('retries on 429 Too Many Requests (exception to 4xx rule)', function () {
        $logger = new NullLogger();
        $retryHandler = new RetryHandler($logger, maxRetries: 3, initialDelayMs: 10);
        
        $attemptCount = 0;
        
        try {
            $retryHandler->execute(function () use (&$attemptCount) {
                $attemptCount++;
                
                // Sempre falha com 429
                $request = new Request('POST', 'https://api.example.com');
                $response = new Response(429, [], 'Too Many Requests');
                throw new ClientException('Rate limited', $request, $response);
            });
            
            // Não deve chegar aqui
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (ClientException $e) {
            // Deve ter tentado 3 vezes (429 é retryable)
            expect($attemptCount)->toBe(3);
            expect($e->getResponse()->getStatusCode())->toBe(429);
        }
    })->repeat(10);
});
