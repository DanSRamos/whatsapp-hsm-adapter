<?php

declare(strict_types=1);

use WhatsApp\Adapter\Http\Controllers\WebhookController;
use WhatsApp\Adapter\Providers\MessagingProviderFactory;
use WhatsApp\Adapter\Providers\MessagingProviderInterface;
use WhatsApp\Adapter\Services\MessageService;
use WhatsApp\Adapter\Services\TemplateService;
use WhatsApp\Adapter\Models\IncomingMessage;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->providerFactory = Mockery::mock(MessagingProviderFactory::class);
    $this->messageService = Mockery::mock(MessageService::class);
    $this->templateService = Mockery::mock(TemplateService::class);
    $this->logger = new NullLogger();
    
    $this->controller = new WebhookController(
        $this->providerFactory,
        $this->messageService,
        $this->templateService,
        $this->logger
    );
});

afterEach(function () {
    Mockery::close();
});

describe('WebhookController Property Tests', function () {
    
    /**
     * Property 2: Webhook Authentication Validation
     * 
     * For any webhook recebido (delivery report, incoming message, template update, 
     * interactive response), o adapter deve validar a autenticidade antes de processar, 
     * rejeitando webhooks inválidos e registando a tentativa
     * 
     * Validates: Requirements 2.4, 2.5, 5.3, 5.5, 8.4, 10.4, 11.3
     * 
     * Feature: whatsapp-hsm-adapter, Property 2: Webhook Authentication Validation
     */
    test('Property 2: Webhook Authentication Validation - delivery reports', function () {
        // Test with multiple random webhook scenarios
        for ($i = 0; $i < 100; $i++) {
            $isValidSignature = (bool) rand(0, 1);
            $canDetectProvider = (bool) rand(0, 1);
            
            $payload = [
                'messageId' => 'msg_' . rand(1000, 9999),
                'status' => ['SENT', 'DELIVERED', 'READ', 'FAILED'][rand(0, 3)],
                'timestamp' => time()
            ];
            
            $provider = Mockery::mock(MessagingProviderInterface::class);
            $provider->shouldReceive('getName')
                ->andReturn('test-provider');
            $provider->shouldReceive('validateWebhook')
                ->andReturn($isValidSignature);
            
            if ($canDetectProvider) {
                $this->providerFactory->shouldReceive('detectProviderFromWebhook')
                    ->once()
                    ->andReturn($provider);
            } else {
                $this->providerFactory->shouldReceive('detectProviderFromWebhook')
                    ->once()
                    ->andReturn(null);
            }
            
            $stream = Mockery::mock(StreamInterface::class);
            $stream->shouldReceive('getContents')
                ->andReturn(json_encode($payload));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getBody')
                ->andReturn($stream);
            
            // If provider can be detected and signature is valid, process webhook
            if ($canDetectProvider && $isValidSignature) {
                $this->messageService->shouldReceive('processDeliveryReport')
                    ->once()
                    ->andReturnNull();
                
                $response = $this->controller->handleDeliveryReport($request);
                
                // Should return success
                expect($response->getStatusCode())->toBe(200);
                $body = json_decode((string) $response->getBody(), true);
                expect($body['success'])->toBeTrue();
            } 
            // If provider cannot be detected, reject with 400
            elseif (!$canDetectProvider) {
                $response = $this->controller->handleDeliveryReport($request);
                
                // Should reject with 400
                expect($response->getStatusCode())->toBe(400);
                $body = json_decode((string) $response->getBody(), true);
                expect($body['success'])->toBeFalse();
                expect($body['error']['code'])->toBe('UNKNOWN_PROVIDER');
            }
            // If signature is invalid, reject with 401
            else {
                $response = $this->controller->handleDeliveryReport($request);
                
                // Should reject with 401
                expect($response->getStatusCode())->toBe(401);
                $body = json_decode((string) $response->getBody(), true);
                expect($body['success'])->toBeFalse();
                expect($body['error']['code'])->toBe('INVALID_SIGNATURE');
            }
            
            Mockery::close();
            
            // Reset mocks for next iteration
            $this->providerFactory = Mockery::mock(MessagingProviderFactory::class);
            $this->messageService = Mockery::mock(MessageService::class);
            $this->templateService = Mockery::mock(TemplateService::class);
            $this->controller = new WebhookController(
                $this->providerFactory,
                $this->messageService,
                $this->templateService,
                $this->logger
            );
        }
    })->group('property-tests', 'whatsapp-hsm-adapter');
    
    test('Property 2: Webhook Authentication Validation - incoming messages', function () {
        // Test with multiple random webhook scenarios
        for ($i = 0; $i < 100; $i++) {
            $isValidSignature = (bool) rand(0, 1);
            $canDetectProvider = (bool) rand(0, 1);
            
            $payload = [
                'messageId' => 'msg_' . rand(1000, 9999),
                'from' => '+351' . rand(900000000, 999999999),
                'to' => '+351' . rand(900000000, 999999999),
                'type' => ['text', 'image', 'document'][rand(0, 2)],
                'content' => 'Test message'
            ];
            
            $provider = Mockery::mock(MessagingProviderInterface::class);
            $provider->shouldReceive('getName')
                ->andReturn('test-provider');
            $provider->shouldReceive('validateWebhook')
                ->andReturn($isValidSignature);
            
            if ($canDetectProvider) {
                $this->providerFactory->shouldReceive('detectProviderFromWebhook')
                    ->once()
                    ->andReturn($provider);
            } else {
                $this->providerFactory->shouldReceive('detectProviderFromWebhook')
                    ->once()
                    ->andReturn(null);
            }
            
            $stream = Mockery::mock(StreamInterface::class);
            $stream->shouldReceive('getContents')
                ->andReturn(json_encode($payload));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getBody')
                ->andReturn($stream);
            
            // If provider can be detected and signature is valid, process webhook
            if ($canDetectProvider && $isValidSignature) {
                $incomingMessage = new IncomingMessage(
                    messageId: $payload['messageId'],
                    from: $payload['from'],
                    to: $payload['to'],
                    type: $payload['type'],
                    content: $payload['content'],
                    receivedAt: new \DateTimeImmutable()
                );
                
                $this->messageService->shouldReceive('processIncomingMessage')
                    ->once()
                    ->andReturn($incomingMessage);
                
                $response = $this->controller->handleIncomingMessage($request);
                
                // Should return success
                expect($response->getStatusCode())->toBe(200);
                $body = json_decode((string) $response->getBody(), true);
                expect($body['success'])->toBeTrue();
            } 
            // If provider cannot be detected, reject with 400
            elseif (!$canDetectProvider) {
                $response = $this->controller->handleIncomingMessage($request);
                
                // Should reject with 400
                expect($response->getStatusCode())->toBe(400);
                $body = json_decode((string) $response->getBody(), true);
                expect($body['success'])->toBeFalse();
                expect($body['error']['code'])->toBe('UNKNOWN_PROVIDER');
            }
            // If signature is invalid, reject with 401
            else {
                $response = $this->controller->handleIncomingMessage($request);
                
                // Should reject with 401
                expect($response->getStatusCode())->toBe(401);
                $body = json_decode((string) $response->getBody(), true);
                expect($body['success'])->toBeFalse();
                expect($body['error']['code'])->toBe('INVALID_SIGNATURE');
            }
            
            Mockery::close();
            
            // Reset mocks for next iteration
            $this->providerFactory = Mockery::mock(MessagingProviderFactory::class);
            $this->messageService = Mockery::mock(MessageService::class);
            $this->templateService = Mockery::mock(TemplateService::class);
            $this->controller = new WebhookController(
                $this->providerFactory,
                $this->messageService,
                $this->templateService,
                $this->logger
            );
        }
    })->group('property-tests', 'whatsapp-hsm-adapter');
    
    test('Property 2: Webhook Authentication Validation - template updates', function () {
        // Test with multiple random webhook scenarios
        for ($i = 0; $i < 100; $i++) {
            $isValidSignature = (bool) rand(0, 1);
            $canDetectProvider = (bool) rand(0, 1);
            
            $payload = [
                'id' => 'template_' . rand(1000, 9999),
                'action' => ['updated', 'deleted', 'approved'][rand(0, 2)],
                'status' => ['approved', 'rejected', 'pending'][rand(0, 2)]
            ];
            
            $provider = Mockery::mock(MessagingProviderInterface::class);
            $provider->shouldReceive('getName')
                ->andReturn('test-provider');
            $provider->shouldReceive('validateWebhook')
                ->andReturn($isValidSignature);
            
            if ($canDetectProvider) {
                $this->providerFactory->shouldReceive('detectProviderFromWebhook')
                    ->once()
                    ->andReturn($provider);
            } else {
                $this->providerFactory->shouldReceive('detectProviderFromWebhook')
                    ->once()
                    ->andReturn(null);
            }
            
            $stream = Mockery::mock(StreamInterface::class);
            $stream->shouldReceive('getContents')
                ->andReturn(json_encode($payload));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getBody')
                ->andReturn($stream);
            
            // If provider can be detected and signature is valid, process webhook
            if ($canDetectProvider && $isValidSignature) {
                $this->templateService->shouldReceive('processTemplateUpdate')
                    ->once()
                    ->andReturnNull();
                
                $response = $this->controller->handleTemplateUpdate($request);
                
                // Should return success
                expect($response->getStatusCode())->toBe(200);
                $body = json_decode((string) $response->getBody(), true);
                expect($body['success'])->toBeTrue();
            } 
            // If provider cannot be detected, reject with 400
            elseif (!$canDetectProvider) {
                $response = $this->controller->handleTemplateUpdate($request);
                
                // Should reject with 400
                expect($response->getStatusCode())->toBe(400);
                $body = json_decode((string) $response->getBody(), true);
                expect($body['success'])->toBeFalse();
                expect($body['error']['code'])->toBe('UNKNOWN_PROVIDER');
            }
            // If signature is invalid, reject with 401
            else {
                $response = $this->controller->handleTemplateUpdate($request);
                
                // Should reject with 401
                expect($response->getStatusCode())->toBe(401);
                $body = json_decode((string) $response->getBody(), true);
                expect($body['success'])->toBeFalse();
                expect($body['error']['code'])->toBe('INVALID_SIGNATURE');
            }
            
            Mockery::close();
            
            // Reset mocks for next iteration
            $this->providerFactory = Mockery::mock(MessagingProviderFactory::class);
            $this->messageService = Mockery::mock(MessageService::class);
            $this->templateService = Mockery::mock(TemplateService::class);
            $this->controller = new WebhookController(
                $this->providerFactory,
                $this->messageService,
                $this->templateService,
                $this->logger
            );
        }
    })->group('property-tests', 'whatsapp-hsm-adapter');
});

