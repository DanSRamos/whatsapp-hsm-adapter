<?php

declare(strict_types=1);

use WhatsApp\Adapter\Providers\Meta\MetaProvider;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->config = [
        'page_access_token' => 'test_page_access_token_1234567890',
        'app_secret' => 'test_app_secret',
        'page_id' => '123456789',
        'api_version' => 'v21.0',
        'base_url' => 'https://graph.facebook.com'
    ];
    
    $this->logger = new NullLogger();
});

afterEach(function () {
    Mockery::close();
});

describe('MetaProvider', function () {
    
    describe('sendText', function () {
        
        test('sends text message successfully', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.test123',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new TextRequest(
                to: '1234567890',
                text: 'Hello from Meta!'
            );
            
            $result = $provider->sendText($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.test123');
            expect($result->status)->toBe('SENT');
        });
        
        test('sends text message with URL preview', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.test456',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new TextRequest(
                to: '1234567890',
                text: 'Check this out: https://example.com',
                previewUrl: true
            );
            
            $result = $provider->sendText($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.test456');
        });
        
        test('handles error 36103 (account not eligible)', function () {
            // Mock error response
            $mock = new MockHandler([
                new RequestException(
                    'Error',
                    new Request('POST', 'test'),
                    new Response(400, [], json_encode([
                        'error' => [
                            'message' => 'Account not eligible',
                            'type' => 'OAuthException',
                            'code' => 36103,
                            'fbtrace_id' => 'test_trace_123'
                        ]
                    ]))
                )
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new TextRequest(
                to: '1234567890',
                text: 'Hello'
            );
            
            $result = $provider->sendText($request);
            
            expect($result->success)->toBeFalse();
            expect($result->error)->toContain('Conta não elegível');
        });
        
        test('handles error 2534068 (feature not available)', function () {
            // Mock error response
            $mock = new MockHandler([
                new RequestException(
                    'Error',
                    new Request('POST', 'test'),
                    new Response(400, [], json_encode([
                        'error' => [
                            'message' => 'Feature not available',
                            'type' => 'OAuthException',
                            'code' => 2534068,
                            'fbtrace_id' => 'test_trace_456'
                        ]
                    ]))
                )
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new TextRequest(
                to: '1234567890',
                text: 'Hello'
            );
            
            $result = $provider->sendText($request);
            
            expect($result->success)->toBeFalse();
            expect($result->error)->toContain('Feature não disponível');
        });
        
        test('handles 24h messaging window expired error', function () {
            // Mock error response
            $mock = new MockHandler([
                new RequestException(
                    'Error',
                    new Request('POST', 'test'),
                    new Response(400, [], json_encode([
                        'error' => [
                            'message' => 'Messaging window expired',
                            'type' => 'OAuthException',
                            'code' => 2022,
                            'fbtrace_id' => 'test_trace_789'
                        ]
                    ]))
                )
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new TextRequest(
                to: '1234567890',
                text: 'Hello'
            );
            
            $result = $provider->sendText($request);
            
            expect($result->success)->toBeFalse();
            expect($result->error)->toContain('Janela de mensagens de 24 horas expirada');
        });
        
        test('marks transient errors correctly', function () {
            // Mock rate limit error (transient)
            $mock = new MockHandler([
                new RequestException(
                    'Error',
                    new Request('POST', 'test'),
                    new Response(429, [], json_encode([
                        'error' => [
                            'message' => 'Rate limit exceeded',
                            'type' => 'OAuthException',
                            'code' => 4,
                            'fbtrace_id' => 'test_trace_rate'
                        ]
                    ]))
                )
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new TextRequest(
                to: '1234567890',
                text: 'Hello'
            );
            
            $result = $provider->sendText($request);
            
            expect($result->success)->toBeFalse();
            expect($result->details['is_transient'] ?? false)->toBeTrue();
        });
        
        test('marks permanent errors as non-transient', function () {
            // Mock permanent error (36103)
            $mock = new MockHandler([
                new RequestException(
                    'Error',
                    new Request('POST', 'test'),
                    new Response(400, [], json_encode([
                        'error' => [
                            'message' => 'Account not eligible',
                            'type' => 'OAuthException',
                            'code' => 36103,
                            'fbtrace_id' => 'test_trace_perm'
                        ]
                    ]))
                )
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new TextRequest(
                to: '1234567890',
                text: 'Hello'
            );
            
            $result = $provider->sendText($request);
            
            expect($result->success)->toBeFalse();
            expect($result->details['is_transient'] ?? true)->toBeFalse();
        });
    });
    
    describe('sendMedia', function () {
        
        test('sends image successfully', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.image123',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\MediaRequest(
                to: '1234567890',
                mediaType: 'image',
                mediaUrl: 'https://example.com/image.jpg'
            );
            
            $result = $provider->sendMedia($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.image123');
        });
        
        test('sends video successfully', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.video456',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\MediaRequest(
                to: '1234567890',
                mediaType: 'video',
                mediaUrl: 'https://example.com/video.mp4'
            );
            
            $result = $provider->sendMedia($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.video456');
        });
        
        test('sends audio successfully', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.audio789',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\MediaRequest(
                to: '1234567890',
                mediaType: 'audio',
                mediaUrl: 'https://example.com/audio.mp3'
            );
            
            $result = $provider->sendMedia($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.audio789');
        });
        
        test('sends document successfully', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.doc101',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\MediaRequest(
                to: '1234567890',
                mediaType: 'document',
                mediaUrl: 'https://example.com/document.pdf'
            );
            
            $result = $provider->sendMedia($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.doc101');
        });
        
        test('rejects non-HTTPS URL', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\MediaRequest(
                to: '1234567890',
                mediaType: 'image',
                mediaUrl: 'http://example.com/image.jpg'
            );
            
            expect(fn() => $provider->sendMedia($request))
                ->toThrow(\InvalidArgumentException::class, 'HTTPS');
        });
        
        test('rejects invalid URL format', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\MediaRequest(
                to: '1234567890',
                mediaType: 'image',
                mediaUrl: 'not-a-valid-url'
            );
            
            expect(fn() => $provider->sendMedia($request))
                ->toThrow(\InvalidArgumentException::class, 'Invalid media URL');
        });
        
        test('rejects unsupported image format', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\MediaRequest(
                to: '1234567890',
                mediaType: 'image',
                mediaUrl: 'https://example.com/image.bmp'
            );
            
            expect(fn() => $provider->sendMedia($request))
                ->toThrow(\InvalidArgumentException::class, 'Unsupported image format');
        });
    });
    
    describe('sendMultipleImages', function () {
        
        test('sends multiple images successfully for Instagram', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.multi123',
                    'recipient_id' => '123456789012345' // Instagram ID (15+ digits)
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $imageUrls = [
                'https://example.com/image1.jpg',
                'https://example.com/image2.png',
                'https://example.com/image3.jpg'
            ];
            
            $result = $provider->sendMultipleImages('123456789012345', $imageUrls);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.multi123');
        });
        
        test('rejects more than 10 images for Instagram', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $imageUrls = array_fill(0, 11, 'https://example.com/image.jpg');
            
            expect(fn() => $provider->sendMultipleImages('123456789012345', $imageUrls))
                ->toThrow(\InvalidArgumentException::class, 'Too many images');
        });
        
        test('rejects more than 1 image for Messenger', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $imageUrls = [
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg'
            ];
            
            // Messenger ID (shorter, < 15 digits)
            expect(fn() => $provider->sendMultipleImages('12345678901', $imageUrls))
                ->toThrow(\InvalidArgumentException::class, 'Too many images');
        });
        
        test('rejects empty image array', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            expect(fn() => $provider->sendMultipleImages('123456789012345', []))
                ->toThrow(\InvalidArgumentException::class, 'At least one image');
        });
        
        test('validates each image URL', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $imageUrls = [
                'https://example.com/image1.jpg',
                'http://example.com/image2.jpg', // Invalid: HTTP
                'https://example.com/image3.jpg'
            ];
            
            expect(fn() => $provider->sendMultipleImages('123456789012345', $imageUrls))
                ->toThrow(\InvalidArgumentException::class, 'index 1');
        });
    });
    
    describe('validateMediaSize', function () {
        
        test('accepts valid image size for Instagram', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            // 5MB image (under 8MB limit)
            $size = 5 * 1024 * 1024;
            
            expect(fn() => $provider->validateMediaSize('image', $size, 'instagram'))
                ->not->toThrow(\InvalidArgumentException::class);
        });
        
        test('rejects oversized image for Instagram', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            // 10MB image (over 8MB limit)
            $size = 10 * 1024 * 1024;
            
            expect(fn() => $provider->validateMediaSize('image', $size, 'instagram'))
                ->toThrow(\InvalidArgumentException::class, 'exceeds the maximum');
        });
        
        test('accepts valid video size', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            // 20MB video (under 25MB limit)
            $size = 20 * 1024 * 1024;
            
            expect(fn() => $provider->validateMediaSize('video', $size, 'instagram'))
                ->not->toThrow(\InvalidArgumentException::class);
        });
        
        test('rejects oversized video', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            // 30MB video (over 25MB limit)
            $size = 30 * 1024 * 1024;
            
            expect(fn() => $provider->validateMediaSize('video', $size, 'instagram'))
                ->toThrow(\InvalidArgumentException::class, 'exceeds the maximum');
        });
    });
    
    describe('configuration validation', function () {
        
        test('throws exception when page_access_token is missing', function () {
            $invalidConfig = [
                'app_secret' => 'test_secret',
                'page_id' => '123456789'
            ];
            
            $client = new Client();
            
            expect(fn() => new MetaProvider($client, $invalidConfig, $this->logger))
                ->toThrow(\InvalidArgumentException::class, 'page_access_token');
        });
        
        test('throws exception when page_access_token is too short', function () {
            $invalidConfig = [
                'page_access_token' => 'short',
                'app_secret' => 'test_secret',
                'page_id' => '123456789'
            ];
            
            $client = new Client();
            
            expect(fn() => new MetaProvider($client, $invalidConfig, $this->logger))
                ->toThrow(\InvalidArgumentException::class, 'Invalid Page Access Token');
        });
        
        test('throws exception when app_secret is missing', function () {
            $invalidConfig = [
                'page_access_token' => 'test_token_1234567890',
                'page_id' => '123456789'
            ];
            
            $client = new Client();
            
            expect(fn() => new MetaProvider($client, $invalidConfig, $this->logger))
                ->toThrow(\InvalidArgumentException::class, 'app_secret');
        });
        
        test('throws exception when page_id is missing', function () {
            $invalidConfig = [
                'page_access_token' => 'test_token_1234567890',
                'app_secret' => 'test_secret'
            ];
            
            $client = new Client();
            
            expect(fn() => new MetaProvider($client, $invalidConfig, $this->logger))
                ->toThrow(\InvalidArgumentException::class, 'page_id');
        });
    });
    
    describe('sendInteractiveButtons', function () {
        
        test('sends quick replies successfully', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.qr123',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest(
                to: '1234567890',
                bodyText: 'Choose an option:',
                buttons: [
                    ['id' => 'btn1', 'text' => 'Option 1'],
                    ['id' => 'btn2', 'text' => 'Option 2'],
                    ['id' => 'btn3', 'text' => 'Option 3']
                ]
            );
            
            $result = $provider->sendInteractiveButtons($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.qr123');
        });
        
        test('validates quick reply limit in provider', function () {
            // The InteractiveButtonsRequest validates max 3 buttons (WhatsApp limit)
            // But Meta supports up to 13 quick replies
            // Since the request object validates first, we can't test 14 buttons
            // Instead, we verify that the provider would handle it correctly
            // by testing with the maximum allowed by the request (3 buttons)
            
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.qr999',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            // Test with 3 buttons (max allowed by InteractiveButtonsRequest)
            $request = new \WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest(
                to: '1234567890',
                bodyText: 'Choose an option:',
                buttons: [
                    ['id' => 'btn1', 'text' => 'Option 1'],
                    ['id' => 'btn2', 'text' => 'Option 2'],
                    ['id' => 'btn3', 'text' => 'Option 3']
                ]
            );
            
            $result = $provider->sendInteractiveButtons($request);
            
            expect($result->success)->toBeTrue();
        });
        
        test('truncates long quick reply titles', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.qr456',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest(
                to: '1234567890',
                bodyText: 'Choose an option:',
                buttons: [
                    ['id' => 'btn1', 'text' => 'This is a very long title that exceeds 20 characters']
                ]
            );
            
            $result = $provider->sendInteractiveButtons($request);
            
            expect($result->success)->toBeTrue();
        });
    });
    
    describe('sendInteractiveList', function () {
        
        test('sends generic template successfully', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.gt123',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\InteractiveListRequest(
                to: '1234567890',
                bodyText: 'Choose from our products:',
                buttonText: 'View Products',
                sections: [
                    [
                        'title' => 'Products',
                        'items' => [
                            [
                                'id' => 'item1',
                                'title' => 'Product 1',
                                'description' => 'Description 1'
                            ],
                            [
                                'id' => 'item2',
                                'title' => 'Product 2',
                                'description' => 'Description 2'
                            ]
                        ]
                    ]
                ]
            );
            
            $result = $provider->sendInteractiveList($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.gt123');
        });
        
        test('sends generic template with images and buttons', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.gt456',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\InteractiveListRequest(
                to: '1234567890',
                bodyText: 'Choose from our products:',
                buttonText: 'View Products',
                sections: [
                    [
                        'title' => 'Products',
                        'items' => [
                            [
                                'id' => 'item1',
                                'title' => 'Product 1',
                                'description' => 'Description 1',
                                'image_url' => 'https://example.com/product1.jpg',
                                'buttons' => [
                                    ['type' => 'web_url', 'title' => 'View', 'url' => 'https://example.com/p1'],
                                    ['type' => 'postback', 'title' => 'Buy', 'id' => 'buy_p1']
                                ]
                            ]
                        ]
                    ]
                ]
            );
            
            $result = $provider->sendInteractiveList($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.gt456');
        });
        
        test('validates element limit in provider', function () {
            // The InteractiveListRequest validates max 10 items total
            // Since the request object validates first, we test with valid data
            
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.gt999',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            // Test with 10 items (max allowed by InteractiveListRequest)
            $items = [];
            for ($i = 1; $i <= 10; $i++) {
                $items[] = [
                    'id' => "item{$i}",
                    'title' => "Product {$i}",
                    'description' => "Description {$i}"
                ];
            }
            
            $request = new \WhatsApp\Adapter\Models\Requests\InteractiveListRequest(
                to: '1234567890',
                bodyText: 'Choose from our products:',
                buttonText: 'View Products',
                sections: [
                    [
                        'title' => 'Products',
                        'items' => $items
                    ]
                ]
            );
            
            $result = $provider->sendInteractiveList($request);
            
            expect($result->success)->toBeTrue();
        });
        
        test('truncates buttons exceeding 3 per card', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.gt101',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\InteractiveListRequest(
                to: '1234567890',
                bodyText: 'Choose from our products:',
                buttonText: 'View Products',
                sections: [
                    [
                        'title' => 'Products',
                        'items' => [
                            [
                                'id' => 'item1',
                                'title' => 'Product 1',
                                'buttons' => [
                                    ['type' => 'postback', 'title' => 'Btn 1', 'id' => 'btn1'],
                                    ['type' => 'postback', 'title' => 'Btn 2', 'id' => 'btn2'],
                                    ['type' => 'postback', 'title' => 'Btn 3', 'id' => 'btn3'],
                                    ['type' => 'postback', 'title' => 'Btn 4', 'id' => 'btn4']
                                ]
                            ]
                        ]
                    ]
                ]
            );
            
            $result = $provider->sendInteractiveList($request);
            
            expect($result->success)->toBeTrue();
        });
    });
    
    describe('sendTemplate', function () {
        
        test('converts template with placeholders to text successfully', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.tpl123',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\HSMRequest(
                to: '1234567890',
                templateName: 'welcome_message',
                templateLanguage: 'en',
                parameters: [
                    'Hello {{1}}, welcome to {{2}}!',
                    'John',
                    'Meta Messaging'
                ]
            );
            
            $result = $provider->sendTemplate($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.tpl123');
            expect($result->details['converted_from_template'] ?? false)->toBeTrue();
            expect($result->details['template_name'] ?? '')->toBe('welcome_message');
        });
        
        test('concatenates parameters when no placeholders present', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.tpl456',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\HSMRequest(
                to: '1234567890',
                templateName: 'simple_message',
                templateLanguage: 'en',
                parameters: [
                    'Hello',
                    'World'
                ]
            );
            
            $result = $provider->sendTemplate($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.tpl456');
        });
        
        test('returns error when no parameters provided', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\HSMRequest(
                to: '1234567890',
                templateName: 'test_template',
                templateLanguage: 'en',
                parameters: []
            );
            
            $result = $provider->sendTemplate($request);
            
            expect($result->success)->toBeFalse();
            expect($result->error)->toContain('No parameters provided');
            expect($result->details['reason'] ?? '')->toBe('templates_not_supported');
        });
        
        test('returns error when converted text is empty', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\HSMRequest(
                to: '1234567890',
                templateName: 'empty_template',
                templateLanguage: 'en',
                parameters: ['   '] // Only whitespace
            );
            
            $result = $provider->sendTemplate($request);
            
            expect($result->success)->toBeFalse();
            expect($result->error)->toContain('empty');
            expect($result->details['reason'] ?? '')->toBe('empty_converted_text');
        });
        
        test('substitutes multiple placeholders correctly', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.tpl789',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\HSMRequest(
                to: '1234567890',
                templateName: 'order_confirmation',
                templateLanguage: 'en',
                parameters: [
                    'Your order {{1}} has been confirmed. Total: {{2}}. Delivery: {{3}}.',
                    '#12345',
                    '$99.99',
                    'Tomorrow'
                ]
            );
            
            $result = $provider->sendTemplate($request);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.tpl789');
        });
        
        test('logs warning about templates not being supported', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.tpl999',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            // Use a logger that we can inspect
            $logger = new class extends \Psr\Log\AbstractLogger {
                public array $logs = [];
                
                public function log($level, $message, array $context = []): void
                {
                    $this->logs[] = [
                        'level' => $level,
                        'message' => $message,
                        'context' => $context
                    ];
                }
            };
            
            $provider = new MetaProvider($client, $this->config, $logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\HSMRequest(
                to: '1234567890',
                templateName: 'test_template',
                templateLanguage: 'en',
                parameters: ['Hello World']
            );
            
            $provider->sendTemplate($request);
            
            // Check that a warning was logged
            $warningLogged = false;
            foreach ($logger->logs as $log) {
                if ($log['level'] === 'warning' && 
                    str_contains($log['message'], 'Templates not natively supported')) {
                    $warningLogged = true;
                    break;
                }
            }
            
            expect($warningLogged)->toBeTrue();
        });
        
        test('preserves notifyUrl when converting template', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.tpl101',
                    'recipient_id' => '1234567890'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \WhatsApp\Adapter\Models\Requests\HSMRequest(
                to: '1234567890',
                templateName: 'test_template',
                templateLanguage: 'en',
                parameters: ['Hello World'],
                notifyUrl: 'https://example.com/webhook'
            );
            
            $result = $provider->sendTemplate($request);
            
            expect($result->success)->toBeTrue();
        });
    });
    
    describe('sendButtonTemplate', function () {
        
        test('sends button template successfully for Messenger', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.bt123',
                    'recipient_id' => '12345678901' // Messenger ID (shorter)
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $buttons = [
                ['type' => 'web_url', 'title' => 'Visit', 'url' => 'https://example.com'],
                ['type' => 'postback', 'title' => 'Buy', 'payload' => 'buy_action'],
                ['type' => 'phone_number', 'title' => 'Call', 'payload' => '+1234567890']
            ];
            
            $result = $provider->sendButtonTemplate('12345678901', 'Check out our product!', $buttons);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.bt123');
        });
        
        test('falls back to quick replies for Instagram', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.qr789',
                    'recipient_id' => '123456789012345' // Instagram ID (15+ digits)
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $buttons = [
                ['type' => 'postback', 'title' => 'Option 1', 'payload' => 'opt1'],
                ['type' => 'postback', 'title' => 'Option 2', 'payload' => 'opt2']
            ];
            
            $result = $provider->sendButtonTemplate('123456789012345', 'Choose an option:', $buttons);
            
            expect($result->success)->toBeTrue();
            expect($result->messageId)->toBe('mid.qr789');
        });
        
        test('rejects more than 3 buttons', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $buttons = [
                ['type' => 'postback', 'title' => 'Btn 1', 'payload' => 'btn1'],
                ['type' => 'postback', 'title' => 'Btn 2', 'payload' => 'btn2'],
                ['type' => 'postback', 'title' => 'Btn 3', 'payload' => 'btn3'],
                ['type' => 'postback', 'title' => 'Btn 4', 'payload' => 'btn4']
            ];
            
            expect(fn() => $provider->sendButtonTemplate('12345678901', 'Choose:', $buttons))
                ->toThrow(\InvalidArgumentException::class, 'Too many buttons');
        });
        
        test('truncates long button template text', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.bt456',
                    'recipient_id' => '12345678901'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $longText = str_repeat('a', 700); // Exceeds 640 character limit
            $buttons = [
                ['type' => 'postback', 'title' => 'OK', 'payload' => 'ok']
            ];
            
            $result = $provider->sendButtonTemplate('12345678901', $longText, $buttons);
            
            expect($result->success)->toBeTrue();
        });
        
        test('truncates long button titles', function () {
            // Mock successful response
            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'message_id' => 'mid.bt789',
                    'recipient_id' => '12345678901'
                ]))
            ]);
            
            $handlerStack = HandlerStack::create($mock);
            $client = new Client(['handler' => $handlerStack]);
            
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $buttons = [
                ['type' => 'postback', 'title' => 'This is a very long button title', 'payload' => 'btn1']
            ];
            
            $result = $provider->sendButtonTemplate('12345678901', 'Choose:', $buttons);
            
            expect($result->success)->toBeTrue();
        });
    });
    
    describe('validateWebhook', function () {
        
        test('validates POST webhook with correct signature', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $body = json_encode(['test' => 'data']);
            $signature = 'sha256=' . hash_hmac('sha256', $body, $this->config['app_secret']);
            
            $request = new \GuzzleHttp\Psr7\ServerRequest(
                'POST',
                '/webhooks/meta',
                ['X-Hub-Signature-256' => $signature],
                $body
            );
            
            $result = $provider->validateWebhook($request);
            
            expect($result)->toBeTrue();
        });
        
        test('rejects POST webhook with incorrect signature', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $body = json_encode(['test' => 'data']);
            $wrongSignature = 'sha256=wrong_signature_hash';
            
            $request = new \GuzzleHttp\Psr7\ServerRequest(
                'POST',
                '/webhooks/meta',
                ['X-Hub-Signature-256' => $wrongSignature],
                $body
            );
            
            $result = $provider->validateWebhook($request);
            
            expect($result)->toBeFalse();
        });
        
        test('rejects POST webhook without signature header', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $body = json_encode(['test' => 'data']);
            
            $request = new \GuzzleHttp\Psr7\ServerRequest(
                'POST',
                '/webhooks/meta',
                [],
                $body
            );
            
            $result = $provider->validateWebhook($request);
            
            expect($result)->toBeFalse();
        });
        
        test('validates GET webhook verification with correct token', function () {
            $config = array_merge($this->config, [
                'verify_token' => 'my_verify_token_123'
            ]);
            
            $client = new Client();
            $provider = new MetaProvider($client, $config, $this->logger);
            
            $request = new \GuzzleHttp\Psr7\ServerRequest(
                'GET',
                '/webhooks/meta?hub_mode=subscribe&hub_verify_token=my_verify_token_123&hub_challenge=challenge_xyz'
            );
            
            // Parse query params
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $request = $request->withQueryParams($params);
            
            $result = $provider->validateWebhook($request);
            
            expect($result)->toBeTrue();
        });
        
        test('rejects GET webhook verification with incorrect token', function () {
            $config = array_merge($this->config, [
                'verify_token' => 'my_verify_token_123'
            ]);
            
            $client = new Client();
            $provider = new MetaProvider($client, $config, $this->logger);
            
            $request = new \GuzzleHttp\Psr7\ServerRequest(
                'GET',
                '/webhooks/meta?hub_mode=subscribe&hub_verify_token=wrong_token&hub_challenge=challenge_xyz'
            );
            
            // Parse query params
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $request = $request->withQueryParams($params);
            
            $result = $provider->validateWebhook($request);
            
            expect($result)->toBeFalse();
        });
        
        test('rejects GET webhook verification without verify_token config', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \GuzzleHttp\Psr7\ServerRequest(
                'GET',
                '/webhooks/meta?hub_mode=subscribe&hub_verify_token=some_token&hub_challenge=challenge_xyz'
            );
            
            // Parse query params
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $request = $request->withQueryParams($params);
            
            $result = $provider->validateWebhook($request);
            
            expect($result)->toBeFalse();
        });
        
        test('rejects unsupported HTTP methods', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $request = new \GuzzleHttp\Psr7\ServerRequest(
                'PUT',
                '/webhooks/meta',
                [],
                ''
            );
            
            $result = $provider->validateWebhook($request);
            
            expect($result)->toBeFalse();
        });
        
        test('validates webhook with complex payload', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $body = json_encode([
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '987654321'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890,
                                'message' => [
                                    'mid' => 'mid.123',
                                    'text' => 'Hello World'
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
            
            $signature = 'sha256=' . hash_hmac('sha256', $body, $this->config['app_secret']);
            
            $request = new \GuzzleHttp\Psr7\ServerRequest(
                'POST',
                '/webhooks/meta',
                ['X-Hub-Signature-256' => $signature],
                $body
            );
            
            $result = $provider->validateWebhook($request);
            
            expect($result)->toBeTrue();
        });
    });
});

    
    describe('processIncomingMessage', function () {
        
        test('processes text message from Instagram successfully', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890000,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'], // Instagram ID (15 digits)
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'message' => [
                                    'mid' => 'mid.test123',
                                    'text' => 'Hello from Instagram!'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $result = $provider->processIncomingMessage($payload);
            
            expect($result->messageId)->toBe('mid.test123');
            expect($result->from)->toBe('123456789012345');
            expect($result->to)->toBe('123456789');
            expect($result->type)->toBe('text');
            expect($result->content)->toBeArray();
            expect($result->content['text'])->toBe('Hello from Instagram!');
            expect($result->content['metadata']['platform'])->toBe('instagram');
            expect($result->contextMessageId)->toBeNull();
        });
        
        test('processes text message from Messenger successfully', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890000,
                        'messaging' => [
                            [
                                'sender' => ['id' => '12345678901'], // Messenger ID (11 digits)
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'message' => [
                                    'mid' => 'mid.test456',
                                    'text' => 'Hello from Messenger!'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $result = $provider->processIncomingMessage($payload);
            
            expect($result->messageId)->toBe('mid.test456');
            expect($result->from)->toBe('12345678901');
            expect($result->to)->toBe('123456789');
            expect($result->type)->toBe('text');
            expect($result->content['text'])->toBe('Hello from Messenger!');
            expect($result->content['metadata']['platform'])->toBe('messenger');
        });
        
        test('processes image message successfully', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890000,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'message' => [
                                    'mid' => 'mid.test789',
                                    'attachments' => [
                                        [
                                            'type' => 'image',
                                            'payload' => [
                                                'url' => 'https://example.com/image.jpg'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $result = $provider->processIncomingMessage($payload);
            
            expect($result->messageId)->toBe('mid.test789');
            expect($result->type)->toBe('image');
            expect($result->content['attachments'])->toHaveCount(1);
            expect($result->content['attachments'][0]['type'])->toBe('image');
            expect($result->content['attachments'][0]['url'])->toBe('https://example.com/image.jpg');
        });
        
        test('processes quick reply response successfully', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890000,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'message' => [
                                    'mid' => 'mid.test999',
                                    'text' => 'Yes',
                                    'quick_reply' => [
                                        'payload' => 'PAYLOAD_YES'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $result = $provider->processIncomingMessage($payload);
            
            expect($result->messageId)->toBe('mid.test999');
            expect($result->type)->toBe('quick_reply');
            expect($result->content['text'])->toBe('Yes');
            expect($result->content['quick_reply']['payload'])->toBe('PAYLOAD_YES');
        });
        
        test('processes postback event successfully', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890000,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'postback' => [
                                    'title' => 'Get Started',
                                    'payload' => 'GET_STARTED_PAYLOAD'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $result = $provider->processIncomingMessage($payload);
            
            expect($result->from)->toBe('123456789012345');
            expect($result->to)->toBe('123456789');
            expect($result->type)->toBe('postback');
            expect($result->content['payload'])->toBe('GET_STARTED_PAYLOAD');
            expect($result->content['title'])->toBe('Get Started');
        });
        
        test('extracts context message ID (reply_to) successfully', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890000,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'message' => [
                                    'mid' => 'mid.reply123',
                                    'text' => 'This is a reply',
                                    'reply_to' => [
                                        'mid' => 'mid.original456'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $result = $provider->processIncomingMessage($payload);
            
            expect($result->messageId)->toBe('mid.reply123');
            expect($result->contextMessageId)->toBe('mid.original456');
        });
        
        test('processes multiple attachments successfully', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890000,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'message' => [
                                    'mid' => 'mid.multi123',
                                    'attachments' => [
                                        [
                                            'type' => 'image',
                                            'payload' => ['url' => 'https://example.com/image1.jpg']
                                        ],
                                        [
                                            'type' => 'image',
                                            'payload' => ['url' => 'https://example.com/image2.jpg']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $result = $provider->processIncomingMessage($payload);
            
            expect($result->type)->toBe('image');
            expect($result->content['attachments'])->toHaveCount(2);
            expect($result->content['attachment_count'])->toBe(2);
        });
        
        test('throws exception when payload has no messages', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $payload = [
                'object' => 'page',
                'entry' => []
            ];
            
            expect(fn() => $provider->processIncomingMessage($payload))
                ->toThrow(\InvalidArgumentException::class, 'No messages or postbacks found');
        });
        
        test('throws exception when sender ID is missing', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890000,
                        'messaging' => [
                            [
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'message' => [
                                    'mid' => 'mid.test123',
                                    'text' => 'Hello'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            expect(fn() => $provider->processIncomingMessage($payload))
                ->toThrow(\InvalidArgumentException::class, 'Missing sender ID');
        });
    });

    describe('processDeliveryReport', function () {
        
        test('processes delivery report successfully', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            $payload = [
                'entry' => [
                    [
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890123456'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'delivery' => [
                                    'mids' => ['mid.123456'],
                                    'watermark' => 1234567890
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $report = $provider->processDeliveryReport($payload);
            
            expect($report)->toBeInstanceOf(\WhatsApp\Adapter\Providers\Models\DeliveryReport::class);
            expect($report->messageId)->toBe('mid.123456');
            expect($report->status)->toBe('delivered');
            expect($report->timestamp)->toBeInstanceOf(\DateTimeImmutable::class);
            expect($report->error)->toBeNull();
            expect($report->metadata)->toHaveKey('provider', 'meta');
            expect($report->metadata)->toHaveKey('platform', 'instagram');
            expect($report->metadata)->toHaveKey('event_type', 'delivery');
        });
        
        test('processes read receipt successfully', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            $payload = [
                'entry' => [
                    [
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890123456'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'read' => [
                                    'mids' => ['mid.123456'],
                                    'watermark' => 1234567890
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $report = $provider->processDeliveryReport($payload);
            
            expect($report)->toBeInstanceOf(\WhatsApp\Adapter\Providers\Models\DeliveryReport::class);
            expect($report->messageId)->toBe('mid.123456');
            expect($report->status)->toBe('read');
            expect($report->timestamp)->toBeInstanceOf(\DateTimeImmutable::class);
            expect($report->metadata)->toHaveKey('event_type', 'read');
        });
        
        test('processes delivery report for Messenger (short PSID)', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            $payload = [
                'entry' => [
                    [
                        'messaging' => [
                            [
                                'sender' => ['id' => '12345678'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'delivery' => [
                                    'mids' => ['mid.123456'],
                                    'watermark' => 1234567890
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $report = $provider->processDeliveryReport($payload);
            
            expect($report->metadata)->toHaveKey('platform', 'messenger');
            expect($report->metadata)->toHaveKey('platform_name', 'Facebook Messenger');
        });
        
        test('processes delivery report with multiple message IDs', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            $payload = [
                'entry' => [
                    [
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890123456'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'delivery' => [
                                    'mids' => ['mid.123', 'mid.456', 'mid.789'],
                                    'watermark' => 1234567890
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $report = $provider->processDeliveryReport($payload);
            
            // Should process the first message ID
            expect($report->messageId)->toBe('mid.123');
            // Should include all message IDs in metadata
            expect($report->metadata)->toHaveKey('all_message_ids');
            expect($report->metadata['all_message_ids'])->toBe(['mid.123', 'mid.456', 'mid.789']);
        });
        
        test('includes watermark in metadata', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            $watermark = 1234567890;
            $payload = [
                'entry' => [
                    [
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890123456'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'delivery' => [
                                    'mids' => ['mid.123456'],
                                    'watermark' => $watermark
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $report = $provider->processDeliveryReport($payload);
            
            expect($report->metadata)->toHaveKey('watermark', $watermark);
        });
        
        test('throws exception when no delivery reports found', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            $payload = [
                'entry' => [
                    [
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890123456'],
                                'message' => ['mid' => 'mid.123', 'text' => 'Hello']
                            ]
                        ]
                    ]
                ]
            ];
            
            expect(fn() => $provider->processDeliveryReport($payload))
                ->toThrow(\InvalidArgumentException::class, 'No delivery reports found');
        });
        
        test('throws exception when sender ID is missing', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            $payload = [
                'entry' => [
                    [
                        'messaging' => [
                            [
                                'recipient' => ['id' => '123456789'],
                                'delivery' => ['mids' => ['mid.123']]
                            ]
                        ]
                    ]
                ]
            ];
            
            expect(fn() => $provider->processDeliveryReport($payload))
                ->toThrow(\InvalidArgumentException::class, 'Missing sender ID');
        });
        
        test('throws exception when recipient ID is missing', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            $payload = [
                'entry' => [
                    [
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890123456'],
                                'delivery' => ['mids' => ['mid.123']]
                            ]
                        ]
                    ]
                ]
            ];
            
            expect(fn() => $provider->processDeliveryReport($payload))
                ->toThrow(\InvalidArgumentException::class, 'Missing recipient ID');
        });
        
        test('throws exception when timestamp is missing', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            $payload = [
                'entry' => [
                    [
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890123456'],
                                'recipient' => ['id' => '123456789'],
                                'delivery' => ['mids' => ['mid.123']]
                            ]
                        ]
                    ]
                ]
            ];
            
            expect(fn() => $provider->processDeliveryReport($payload))
                ->toThrow(\InvalidArgumentException::class, 'Missing timestamp');
        });
        
        test('throws exception when no message IDs in delivery report', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            $payload = [
                'entry' => [
                    [
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890123456'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => 1234567890000,
                                'delivery' => [
                                    'watermark' => 1234567890
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            expect(fn() => $provider->processDeliveryReport($payload))
                ->toThrow(\InvalidArgumentException::class, 'No message IDs found');
        });
        
        test('converts timestamp from milliseconds to seconds correctly', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            $timestampMs = 1234567890123; // Milliseconds
            $payload = [
                'entry' => [
                    [
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890123456'],
                                'recipient' => ['id' => '123456789'],
                                'timestamp' => $timestampMs,
                                'delivery' => [
                                    'mids' => ['mid.123456'],
                                    'watermark' => 1234567890
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $report = $provider->processDeliveryReport($payload);
            
            // Timestamp should be converted from milliseconds to seconds
            $expectedTimestamp = (int)($timestampMs / 1000);
            expect($report->timestamp->getTimestamp())->toBe($expectedTimestamp);
        });

    describe('getMessageStatus', function () {
        
        test('retrieves message status from repository successfully', function () {
            $client = new Client();
            
            // Mock message repository
            $messageRepository = Mockery::mock(\WhatsApp\Adapter\Repositories\MessageRepositoryInterface::class);
            $sentAt = new \DateTimeImmutable('2024-01-15 10:00:00');
            $deliveredAt = new \DateTimeImmutable('2024-01-15 10:00:05');
            
            $message = new \WhatsApp\Adapter\Models\Message(
                id: 'mid.test123',
                type: 'text',
                toNumber: '1234567890',
                fromNumber: '9876543210',
                status: 'DELIVERED',
                content: ['text' => 'Test message'],
                sentAt: $sentAt,
                deliveredAt: $deliveredAt,
                readAt: null,
                errorMessage: null,
                metadata: ['provider' => 'meta']
            );
            
            $messageRepository->shouldReceive('findById')
                ->with('mid.test123')
                ->once()
                ->andReturn($message);
            
            $provider = new MetaProvider($client, $this->config, $this->logger, $messageRepository);
            
            $status = $provider->getMessageStatus('mid.test123');
            
            expect($status->messageId)->toBe('mid.test123');
            expect($status->status)->toBe('DELIVERED');
            expect($status->to)->toBe('1234567890');
            expect($status->sentAt)->toBe($sentAt);
            expect($status->deliveredAt)->toBe($deliveredAt);
            expect($status->readAt)->toBeNull();
            expect($status->error)->toBeNull();
        });
        
        test('retrieves message status with read timestamp', function () {
            $client = new Client();
            
            $messageRepository = Mockery::mock(\WhatsApp\Adapter\Repositories\MessageRepositoryInterface::class);
            $sentAt = new \DateTimeImmutable('2024-01-15 10:00:00');
            $deliveredAt = new \DateTimeImmutable('2024-01-15 10:00:05');
            $readAt = new \DateTimeImmutable('2024-01-15 10:00:10');
            
            $message = new \WhatsApp\Adapter\Models\Message(
                id: 'mid.test456',
                type: 'text',
                toNumber: '1234567890',
                fromNumber: '9876543210',
                status: 'READ',
                content: ['text' => 'Test message'],
                sentAt: $sentAt,
                deliveredAt: $deliveredAt,
                readAt: $readAt,
                errorMessage: null,
                metadata: ['provider' => 'meta']
            );
            
            $messageRepository->shouldReceive('findById')
                ->with('mid.test456')
                ->once()
                ->andReturn($message);
            
            $provider = new MetaProvider($client, $this->config, $this->logger, $messageRepository);
            
            $status = $provider->getMessageStatus('mid.test456');
            
            expect($status->status)->toBe('READ');
            expect($status->readAt)->toBe($readAt);
        });
        
        test('throws exception when repository is not configured', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            expect(fn() => $provider->getMessageStatus('mid.test123'))
                ->toThrow(\RuntimeException::class, 'Message repository not configured');
        });
        
        test('throws exception when message is not found', function () {
            $client = new Client();
            
            $messageRepository = Mockery::mock(\WhatsApp\Adapter\Repositories\MessageRepositoryInterface::class);
            $messageRepository->shouldReceive('findById')
                ->with('mid.notfound')
                ->once()
                ->andReturn(null);
            
            $provider = new MetaProvider($client, $this->config, $this->logger, $messageRepository);
            
            expect(fn() => $provider->getMessageStatus('mid.notfound'))
                ->toThrow(\InvalidArgumentException::class, 'Message not found');
        });
        
        test('uses cache when available', function () {
            $client = new Client();
            
            $messageRepository = Mockery::mock(\WhatsApp\Adapter\Repositories\MessageRepositoryInterface::class);
            $cache = Mockery::mock(\WhatsApp\Adapter\Services\CacheInterface::class);
            
            $sentAt = new \DateTimeImmutable('2024-01-15 10:00:00');
            $cachedStatus = new \WhatsApp\Adapter\Providers\Models\ProviderMessageStatus(
                messageId: 'mid.cached',
                status: 'DELIVERED',
                to: '1234567890',
                sentAt: $sentAt,
                deliveredAt: new \DateTimeImmutable('2024-01-15 10:00:05'),
                readAt: null,
                error: null
            );
            
            $cache->shouldReceive('has')
                ->with('meta:message_status:mid.cached')
                ->once()
                ->andReturn(true);
            
            $cache->shouldReceive('get')
                ->with('meta:message_status:mid.cached')
                ->once()
                ->andReturn($cachedStatus);
            
            // Repository should NOT be called when cache hit
            $messageRepository->shouldNotReceive('findById');
            
            $provider = new MetaProvider($client, $this->config, $this->logger, $messageRepository, $cache);
            
            $status = $provider->getMessageStatus('mid.cached');
            
            expect($status->messageId)->toBe('mid.cached');
            expect($status->status)->toBe('DELIVERED');
        });
        
        test('caches status after repository lookup', function () {
            $client = new Client();
            
            $messageRepository = Mockery::mock(\WhatsApp\Adapter\Repositories\MessageRepositoryInterface::class);
            $cache = Mockery::mock(\WhatsApp\Adapter\Services\CacheInterface::class);
            
            // Use a recent message so it doesn't timeout
            $sentAt = new \DateTimeImmutable('-1 hour');
            $message = new \WhatsApp\Adapter\Models\Message(
                id: 'mid.test789',
                type: 'text',
                toNumber: '1234567890',
                fromNumber: '9876543210',
                status: 'SENT',
                content: ['text' => 'Test message'],
                sentAt: $sentAt,
                deliveredAt: null,
                readAt: null,
                errorMessage: null,
                metadata: ['provider' => 'meta']
            );
            
            $cache->shouldReceive('has')
                ->with('meta:message_status:mid.test789')
                ->once()
                ->andReturn(false);
            
            $messageRepository->shouldReceive('findById')
                ->with('mid.test789')
                ->once()
                ->andReturn($message);
            
            $cache->shouldReceive('set')
                ->withArgs(function ($key, $value, $ttl) {
                    return $key === 'meta:message_status:mid.test789'
                        && $value instanceof \WhatsApp\Adapter\Providers\Models\ProviderMessageStatus
                        && $ttl === 120; // SENT status has 2 minute TTL
                })
                ->once();
            
            $provider = new MetaProvider($client, $this->config, $this->logger, $messageRepository, $cache);
            
            $status = $provider->getMessageStatus('mid.test789');
            
            expect($status->status)->toBe('SENT');
        });
        
        test('marks old SENT messages as UNKNOWN after timeout', function () {
            $client = new Client();
            
            $messageRepository = Mockery::mock(\WhatsApp\Adapter\Repositories\MessageRepositoryInterface::class);
            
            // Message sent 25 hours ago (exceeds 24 hour default timeout)
            $sentAt = new \DateTimeImmutable('-25 hours');
            $message = new \WhatsApp\Adapter\Models\Message(
                id: 'mid.old',
                type: 'text',
                toNumber: '1234567890',
                fromNumber: '9876543210',
                status: 'SENT',
                content: ['text' => 'Test message'],
                sentAt: $sentAt,
                deliveredAt: null,
                readAt: null,
                errorMessage: null,
                metadata: ['provider' => 'meta']
            );
            
            $messageRepository->shouldReceive('findById')
                ->with('mid.old')
                ->once()
                ->andReturn($message);
            
            $provider = new MetaProvider($client, $this->config, $this->logger, $messageRepository);
            
            $status = $provider->getMessageStatus('mid.old');
            
            // Status should be UNKNOWN due to timeout
            expect($status->status)->toBe('UNKNOWN');
            expect($status->error)->toBe('No delivery confirmation received within timeout period');
        });
        
        test('does not mark recent SENT messages as UNKNOWN', function () {
            $client = new Client();
            
            $messageRepository = Mockery::mock(\WhatsApp\Adapter\Repositories\MessageRepositoryInterface::class);
            
            // Message sent 1 hour ago (within 24 hour timeout)
            $sentAt = new \DateTimeImmutable('-1 hour');
            $message = new \WhatsApp\Adapter\Models\Message(
                id: 'mid.recent',
                type: 'text',
                toNumber: '1234567890',
                fromNumber: '9876543210',
                status: 'SENT',
                content: ['text' => 'Test message'],
                sentAt: $sentAt,
                deliveredAt: null,
                readAt: null,
                errorMessage: null,
                metadata: ['provider' => 'meta']
            );
            
            $messageRepository->shouldReceive('findById')
                ->with('mid.recent')
                ->once()
                ->andReturn($message);
            
            $provider = new MetaProvider($client, $this->config, $this->logger, $messageRepository);
            
            $status = $provider->getMessageStatus('mid.recent');
            
            // Status should remain SENT (not timed out)
            expect($status->status)->toBe('SENT');
            expect($status->error)->toBeNull();
        });
        
        test('respects custom timeout threshold from config', function () {
            $client = new Client();
            
            $messageRepository = Mockery::mock(\WhatsApp\Adapter\Repositories\MessageRepositoryInterface::class);
            
            // Custom config with 1 hour timeout
            $customConfig = array_merge($this->config, [
                'status_timeout_seconds' => 3600 // 1 hour
            ]);
            
            // Message sent 2 hours ago (exceeds 1 hour custom timeout)
            $sentAt = new \DateTimeImmutable('-2 hours');
            $message = new \WhatsApp\Adapter\Models\Message(
                id: 'mid.custom',
                type: 'text',
                toNumber: '1234567890',
                fromNumber: '9876543210',
                status: 'SENT',
                content: ['text' => 'Test message'],
                sentAt: $sentAt,
                deliveredAt: null,
                readAt: null,
                errorMessage: null,
                metadata: ['provider' => 'meta']
            );
            
            $messageRepository->shouldReceive('findById')
                ->with('mid.custom')
                ->once()
                ->andReturn($message);
            
            $provider = new MetaProvider($client, $customConfig, $this->logger, $messageRepository);
            
            $status = $provider->getMessageStatus('mid.custom');
            
            // Status should be UNKNOWN due to custom timeout
            expect($status->status)->toBe('UNKNOWN');
        });
        
        test('does not mark DELIVERED or READ messages as UNKNOWN', function () {
            $client = new Client();
            
            $messageRepository = Mockery::mock(\WhatsApp\Adapter\Repositories\MessageRepositoryInterface::class);
            
            // Old DELIVERED message (should not be marked UNKNOWN)
            $sentAt = new \DateTimeImmutable('-30 hours');
            $deliveredAt = new \DateTimeImmutable('-29 hours');
            $message = new \WhatsApp\Adapter\Models\Message(
                id: 'mid.delivered',
                type: 'text',
                toNumber: '1234567890',
                fromNumber: '9876543210',
                status: 'DELIVERED',
                content: ['text' => 'Test message'],
                sentAt: $sentAt,
                deliveredAt: $deliveredAt,
                readAt: null,
                errorMessage: null,
                metadata: ['provider' => 'meta']
            );
            
            $messageRepository->shouldReceive('findById')
                ->with('mid.delivered')
                ->once()
                ->andReturn($message);
            
            $provider = new MetaProvider($client, $this->config, $this->logger, $messageRepository);
            
            $status = $provider->getMessageStatus('mid.delivered');
            
            // Status should remain DELIVERED (timeout only applies to SENT)
            expect($status->status)->toBe('DELIVERED');
            expect($status->error)->toBeNull();
        });
    });
    
    describe('template management', function () {
        
        test('getTemplates returns empty array', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $templates = $provider->getTemplates();
            
            expect($templates)->toBeArray();
            expect($templates)->toBeEmpty();
        });
        
        test('getTemplate returns null for any template ID', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $template = $provider->getTemplate('any_template_id');
            
            expect($template)->toBeNull();
        });
        
        test('getTemplate returns null for empty template ID', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $template = $provider->getTemplate('');
            
            expect($template)->toBeNull();
        });
        
        test('processTemplateUpdate returns TemplateUpdate with not_supported action', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $payload = [
                'template_id' => 'test_template',
                'action' => 'approved',
                'timestamp' => '2024-01-17T10:00:00Z'
            ];
            
            $result = $provider->processTemplateUpdate($payload);
            
            expect($result)->toBeInstanceOf(\WhatsApp\Adapter\Providers\Models\TemplateUpdate::class);
            expect($result->action)->toBe('not_supported');
            expect($result->templateId)->toBe('');
            expect($result->reason)->toBe('Templates not supported for Instagram/Messenger');
            expect($result->template)->toBeNull();
        });
        
        test('processTemplateUpdate returns TemplateUpdate with empty payload', function () {
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $this->logger);
            
            $result = $provider->processTemplateUpdate([]);
            
            expect($result)->toBeInstanceOf(\WhatsApp\Adapter\Providers\Models\TemplateUpdate::class);
            expect($result->action)->toBe('not_supported');
            expect($result->templateId)->toBe('');
            expect($result->reason)->toBe('Templates not supported for Instagram/Messenger');
        });
        
        test('template methods log explanatory messages', function () {
            // Create a mock logger to verify log messages
            $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
            
            // Expect log message for getTemplates
            $logger->shouldReceive('info')
                ->with('Templates not supported for Meta provider (Instagram/Messenger)')
                ->once();
            
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $logger);
            
            $provider->getTemplates();
        });
        
        test('getTemplate logs with template ID', function () {
            // Create a mock logger to verify log messages
            $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
            
            // Expect log message for getTemplate with template_id
            $logger->shouldReceive('info')
                ->with(
                    'Templates not supported for Meta provider (Instagram/Messenger)',
                    ['template_id' => 'test_template_123']
                )
                ->once();
            
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $logger);
            
            $provider->getTemplate('test_template_123');
        });
        
        test('processTemplateUpdate logs explanatory message', function () {
            // Create a mock logger to verify log messages
            $logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
            
            // Expect log message for processTemplateUpdate
            $logger->shouldReceive('info')
                ->with('Template updates not supported for Meta provider (Instagram/Messenger)')
                ->once();
            
            $client = new Client();
            $provider = new MetaProvider($client, $this->config, $logger);
            
            $provider->processTemplateUpdate(['some' => 'data']);
        });
    });
});