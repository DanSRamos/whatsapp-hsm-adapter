<?php

declare(strict_types=1);

use WhatsApp\Adapter\Http\Controllers\MessageController;
use WhatsApp\Adapter\Services\MessageService;
use WhatsApp\Adapter\Models\SendResult;
use WhatsApp\Adapter\Models\MessageStatus;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->messageService = Mockery::mock(MessageService::class);
    $this->logger = new NullLogger();
    
    $this->controller = new MessageController(
        $this->messageService,
        $this->logger
    );
});

afterEach(function () {
    Mockery::close();
});

describe('MessageController', function () {
    
    describe('sendHSM', function () {
        
        test('sends HSM message successfully', function () {
            $requestBody = [
                'to' => '+351912345678',
                'templateName' => 'welcome_message',
                'templateLanguage' => 'pt',
                'parameters' => ['John']
            ];
            
            $sendResult = new SendResult(
                success: true,
                messageId: 'msg_123',
                status: 'SENT'
            );
            
            $this->messageService->shouldReceive('sendHSM')
                ->once()
                ->andReturn($sendResult);
            
            $stream = Mockery::mock(StreamInterface::class);
            $stream->shouldReceive('getContents')
                ->once()
                ->andReturn(json_encode($requestBody));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getBody')
                ->once()
                ->andReturn($stream);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->sendHSM($request);
            
            expect($response->getStatusCode())->toBe(200);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data']['message_id'])->toBe('msg_123');
            expect($body['data']['status'])->toBe('SENT');
        });
        
        test('returns validation error for missing required fields', function () {
            $requestBody = [
                'to' => '+351912345678'
                // Missing templateName and templateLanguage
            ];
            
            $stream = Mockery::mock(StreamInterface::class);
            $stream->shouldReceive('getContents')
                ->once()
                ->andReturn(json_encode($requestBody));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getBody')
                ->once()
                ->andReturn($stream);
            
            $response = $this->controller->sendHSM($request);
            
            expect($response->getStatusCode())->toBe(400);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeFalse();
            expect($body['error']['code'])->toBe('VALIDATION_ERROR');
        });
        
        test('returns error when send fails', function () {
            $requestBody = [
                'to' => '+351912345678',
                'templateName' => 'welcome_message',
                'templateLanguage' => 'pt'
            ];
            
            $sendResult = new SendResult(
                success: false,
                error: 'Provider error'
            );
            
            $this->messageService->shouldReceive('sendHSM')
                ->once()
                ->andReturn($sendResult);
            
            $stream = Mockery::mock(StreamInterface::class);
            $stream->shouldReceive('getContents')
                ->once()
                ->andReturn(json_encode($requestBody));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getBody')
                ->once()
                ->andReturn($stream);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->sendHSM($request);
            
            expect($response->getStatusCode())->toBe(500);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeFalse();
            expect($body['error']['code'])->toBe('SEND_FAILED');
        });
    });
    
    describe('sendText', function () {
        
        test('sends text message successfully', function () {
            $requestBody = [
                'to' => '+351912345678',
                'text' => 'Hello, world!'
            ];
            
            $sendResult = new SendResult(
                success: true,
                messageId: 'msg_456',
                status: 'SENT'
            );
            
            $this->messageService->shouldReceive('sendText')
                ->once()
                ->andReturn($sendResult);
            
            $stream = Mockery::mock(StreamInterface::class);
            $stream->shouldReceive('getContents')
                ->once()
                ->andReturn(json_encode($requestBody));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getBody')
                ->once()
                ->andReturn($stream);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->sendText($request);
            
            expect($response->getStatusCode())->toBe(200);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data']['message_id'])->toBe('msg_456');
        });
        
        test('returns validation error for missing text', function () {
            $requestBody = [
                'to' => '+351912345678'
                // Missing text
            ];
            
            $stream = Mockery::mock(StreamInterface::class);
            $stream->shouldReceive('getContents')
                ->once()
                ->andReturn(json_encode($requestBody));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getBody')
                ->once()
                ->andReturn($stream);
            
            $response = $this->controller->sendText($request);
            
            expect($response->getStatusCode())->toBe(400);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeFalse();
            expect($body['error']['code'])->toBe('VALIDATION_ERROR');
        });
    });
    
    describe('sendMedia', function () {
        
        test('sends media message successfully', function () {
            $requestBody = [
                'to' => '+351912345678',
                'mediaType' => 'image',
                'mediaUrl' => 'https://example.com/image.jpg',
                'caption' => 'Check this out'
            ];
            
            $sendResult = new SendResult(
                success: true,
                messageId: 'msg_789',
                status: 'SENT'
            );
            
            $this->messageService->shouldReceive('sendMedia')
                ->once()
                ->andReturn($sendResult);
            
            $stream = Mockery::mock(StreamInterface::class);
            $stream->shouldReceive('getContents')
                ->once()
                ->andReturn(json_encode($requestBody));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getBody')
                ->once()
                ->andReturn($stream);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->sendMedia($request);
            
            expect($response->getStatusCode())->toBe(200);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data']['message_id'])->toBe('msg_789');
        });
    });
    
    describe('sendInteractiveButtons', function () {
        
        test('sends interactive buttons message successfully', function () {
            $requestBody = [
                'to' => '+351912345678',
                'bodyText' => 'Choose an option',
                'buttons' => [
                    ['id' => 'btn1', 'text' => 'Option 1'],
                    ['id' => 'btn2', 'text' => 'Option 2']
                ]
            ];
            
            $sendResult = new SendResult(
                success: true,
                messageId: 'msg_101',
                status: 'SENT'
            );
            
            $this->messageService->shouldReceive('sendInteractiveButtons')
                ->once()
                ->andReturn($sendResult);
            
            $stream = Mockery::mock(StreamInterface::class);
            $stream->shouldReceive('getContents')
                ->once()
                ->andReturn(json_encode($requestBody));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getBody')
                ->once()
                ->andReturn($stream);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->sendInteractiveButtons($request);
            
            expect($response->getStatusCode())->toBe(200);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data']['message_id'])->toBe('msg_101');
        });
    });
    
    describe('sendInteractiveList', function () {
        
        test('sends interactive list message successfully', function () {
            $requestBody = [
                'to' => '+351912345678',
                'bodyText' => 'Choose from list',
                'buttonText' => 'View Options',
                'sections' => [
                    [
                        'title' => 'Section 1',
                        'items' => [
                            ['id' => 'item1', 'title' => 'Item 1']
                        ]
                    ]
                ]
            ];
            
            $sendResult = new SendResult(
                success: true,
                messageId: 'msg_202',
                status: 'SENT'
            );
            
            $this->messageService->shouldReceive('sendInteractiveList')
                ->once()
                ->andReturn($sendResult);
            
            $stream = Mockery::mock(StreamInterface::class);
            $stream->shouldReceive('getContents')
                ->once()
                ->andReturn(json_encode($requestBody));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getBody')
                ->once()
                ->andReturn($stream);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->sendInteractiveList($request);
            
            expect($response->getStatusCode())->toBe(200);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data']['message_id'])->toBe('msg_202');
        });
    });
    
    describe('getMessageStatus', function () {
        
        test('returns message status successfully', function () {
            $messageStatus = new MessageStatus(
                messageId: 'msg_123',
                status: 'DELIVERED',
                to: '+351912345678',
                sentAt: new \DateTimeImmutable('2026-01-16 10:00:00'),
                deliveredAt: new \DateTimeImmutable('2026-01-16 10:01:00')
            );
            
            $this->messageService->shouldReceive('getMessageStatus')
                ->with('msg_123', null)
                ->once()
                ->andReturn($messageStatus);
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getAttribute')
                ->with('routeParams', [])
                ->once()
                ->andReturn(['messageId' => 'msg_123']);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->getMessageStatus($request);
            
            expect($response->getStatusCode())->toBe(200);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeTrue();
            expect($body['data']['message_id'])->toBe('msg_123');
            expect($body['data']['status'])->toBe('DELIVERED');
        });
        
        test('returns 404 when message not found', function () {
            $this->messageService->shouldReceive('getMessageStatus')
                ->with('msg_999', null)
                ->once()
                ->andThrow(new \RuntimeException('Message not found'));
            
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getAttribute')
                ->with('routeParams', [])
                ->once()
                ->andReturn(['messageId' => 'msg_999']);
            $request->shouldReceive('getQueryParams')
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->getMessageStatus($request);
            
            expect($response->getStatusCode())->toBe(404);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeFalse();
            expect($body['error']['code'])->toBe('MESSAGE_NOT_FOUND');
        });
        
        test('returns 400 when message ID is missing', function () {
            $request = Mockery::mock(ServerRequestInterface::class);
            $request->shouldReceive('getAttribute')
                ->with('routeParams', [])
                ->once()
                ->andReturn([]);
            
            $response = $this->controller->getMessageStatus($request);
            
            expect($response->getStatusCode())->toBe(400);
            
            $body = json_decode((string) $response->getBody(), true);
            expect($body['success'])->toBeFalse();
            expect($body['error']['code'])->toBe('MISSING_MESSAGE_ID');
        });
    });
});

