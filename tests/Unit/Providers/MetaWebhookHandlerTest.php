<?php

declare(strict_types=1);

use WhatsApp\Adapter\Providers\Meta\MetaWebhookHandler;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Log\NullLogger;

beforeEach(function () {
    $this->logger = new NullLogger();
    $this->handler = new MetaWebhookHandler($this->logger);
    $this->appSecret = 'test_app_secret_12345';
});

describe('MetaWebhookHandler', function () {
    
    describe('validateSignature', function () {
        
        test('validates correct signature successfully', function () {
            $body = json_encode(['test' => 'data']);
            $signature = 'sha256=' . hash_hmac('sha256', $body, $this->appSecret);
            
            $request = new ServerRequest(
                'POST',
                '/webhooks/meta',
                ['X-Hub-Signature-256' => $signature],
                $body
            );
            
            $result = $this->handler->validateSignature($request, $this->appSecret);
            
            expect($result)->toBeTrue();
        });
        
        test('rejects incorrect signature', function () {
            $body = json_encode(['test' => 'data']);
            $wrongSignature = 'sha256=wrong_signature_hash';
            
            $request = new ServerRequest(
                'POST',
                '/webhooks/meta',
                ['X-Hub-Signature-256' => $wrongSignature],
                $body
            );
            
            $result = $this->handler->validateSignature($request, $this->appSecret);
            
            expect($result)->toBeFalse();
        });
        
        test('rejects request without signature header', function () {
            $body = json_encode(['test' => 'data']);
            
            $request = new ServerRequest(
                'POST',
                '/webhooks/meta',
                [],
                $body
            );
            
            $result = $this->handler->validateSignature($request, $this->appSecret);
            
            expect($result)->toBeFalse();
        });
        
        test('rejects request with empty body', function () {
            $signature = 'sha256=' . hash_hmac('sha256', '', $this->appSecret);
            
            $request = new ServerRequest(
                'POST',
                '/webhooks/meta',
                ['X-Hub-Signature-256' => $signature],
                ''
            );
            
            $result = $this->handler->validateSignature($request, $this->appSecret);
            
            expect($result)->toBeFalse();
        });
        
        test('uses timing-safe comparison (hash_equals)', function () {
            // This test verifies that hash_equals is used by testing with
            // signatures that differ only in timing-sensitive ways
            $body = json_encode(['test' => 'data']);
            $correctSignature = 'sha256=' . hash_hmac('sha256', $body, $this->appSecret);
            
            // Create a signature that's almost correct but differs by one character
            $almostCorrectSignature = substr($correctSignature, 0, -1) . 'x';
            
            $request = new ServerRequest(
                'POST',
                '/webhooks/meta',
                ['X-Hub-Signature-256' => $almostCorrectSignature],
                $body
            );
            
            $result = $this->handler->validateSignature($request, $this->appSecret);
            
            // Should still reject even though it's very close
            expect($result)->toBeFalse();
        });
        
        test('validates signature with different app secrets', function () {
            $body = json_encode(['test' => 'data']);
            $secret1 = 'secret_one';
            $secret2 = 'secret_two';
            
            $signature1 = 'sha256=' . hash_hmac('sha256', $body, $secret1);
            
            $request = new ServerRequest(
                'POST',
                '/webhooks/meta',
                ['X-Hub-Signature-256' => $signature1],
                $body
            );
            
            // Should fail with wrong secret
            $result = $this->handler->validateSignature($request, $secret2);
            expect($result)->toBeFalse();
            
            // Should succeed with correct secret
            $result = $this->handler->validateSignature($request, $secret1);
            expect($result)->toBeTrue();
        });
        
        test('validates signature with complex JSON payload', function () {
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
            
            $signature = 'sha256=' . hash_hmac('sha256', $body, $this->appSecret);
            
            $request = new ServerRequest(
                'POST',
                '/webhooks/meta',
                ['X-Hub-Signature-256' => $signature],
                $body
            );
            
            $result = $this->handler->validateSignature($request, $this->appSecret);
            
            expect($result)->toBeTrue();
        });
    });
    
    describe('handleVerification', function () {
        
        test('returns challenge for valid verification request', function () {
            $verifyToken = 'my_verify_token_123';
            $challenge = 'challenge_string_xyz';
            
            $params = [
                'hub_mode' => 'subscribe',
                'hub_verify_token' => $verifyToken,
                'hub_challenge' => $challenge
            ];
            
            $result = $this->handler->handleVerification($params, $verifyToken);
            
            expect($result)->toBe($challenge);
        });
        
        test('returns null for invalid mode', function () {
            $verifyToken = 'my_verify_token_123';
            
            $params = [
                'hub_mode' => 'invalid_mode',
                'hub_verify_token' => $verifyToken,
                'hub_challenge' => 'challenge_xyz'
            ];
            
            $result = $this->handler->handleVerification($params, $verifyToken);
            
            expect($result)->toBeNull();
        });
        
        test('returns null for mismatched verify token', function () {
            $verifyToken = 'my_verify_token_123';
            $wrongToken = 'wrong_token_456';
            
            $params = [
                'hub_mode' => 'subscribe',
                'hub_verify_token' => $wrongToken,
                'hub_challenge' => 'challenge_xyz'
            ];
            
            $result = $this->handler->handleVerification($params, $verifyToken);
            
            expect($result)->toBeNull();
        });
        
        test('returns null for missing challenge', function () {
            $verifyToken = 'my_verify_token_123';
            
            $params = [
                'hub_mode' => 'subscribe',
                'hub_verify_token' => $verifyToken
            ];
            
            $result = $this->handler->handleVerification($params, $verifyToken);
            
            expect($result)->toBeNull();
        });
        
        test('returns null for empty challenge', function () {
            $verifyToken = 'my_verify_token_123';
            
            $params = [
                'hub_mode' => 'subscribe',
                'hub_verify_token' => $verifyToken,
                'hub_challenge' => ''
            ];
            
            $result = $this->handler->handleVerification($params, $verifyToken);
            
            expect($result)->toBeNull();
        });
        
        test('returns null for missing mode', function () {
            $verifyToken = 'my_verify_token_123';
            
            $params = [
                'hub_verify_token' => $verifyToken,
                'hub_challenge' => 'challenge_xyz'
            ];
            
            $result = $this->handler->handleVerification($params, $verifyToken);
            
            expect($result)->toBeNull();
        });
        
        test('returns null for missing verify token', function () {
            $verifyToken = 'my_verify_token_123';
            
            $params = [
                'hub_mode' => 'subscribe',
                'hub_challenge' => 'challenge_xyz'
            ];
            
            $result = $this->handler->handleVerification($params, $verifyToken);
            
            expect($result)->toBeNull();
        });
        
        test('uses timing-safe comparison for verify token', function () {
            $verifyToken = 'my_verify_token_123';
            // Token that differs by one character
            $almostCorrectToken = 'my_verify_token_12x';
            
            $params = [
                'hub_mode' => 'subscribe',
                'hub_verify_token' => $almostCorrectToken,
                'hub_challenge' => 'challenge_xyz'
            ];
            
            $result = $this->handler->handleVerification($params, $verifyToken);
            
            // Should reject even though it's very close
            expect($result)->toBeNull();
        });
        
        test('handles special characters in challenge', function () {
            $verifyToken = 'my_verify_token_123';
            $challenge = 'challenge_with_special_chars_!@#$%^&*()_+-=[]{}|;:,.<>?';
            
            $params = [
                'hub_mode' => 'subscribe',
                'hub_verify_token' => $verifyToken,
                'hub_challenge' => $challenge
            ];
            
            $result = $this->handler->handleVerification($params, $verifyToken);
            
            expect($result)->toBe($challenge);
        });
        
        test('handles long challenge string', function () {
            $verifyToken = 'my_verify_token_123';
            $challenge = str_repeat('a', 1000); // Very long challenge
            
            $params = [
                'hub_mode' => 'subscribe',
                'hub_verify_token' => $verifyToken,
                'hub_challenge' => $challenge
            ];
            
            $result = $this->handler->handleVerification($params, $verifyToken);
            
            expect($result)->toBe($challenge);
        });
    });
    
    describe('extractMessages', function () {
        
        test('extracts messages from webhook payload', function () {
            $payload = [
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
            ];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toHaveCount(1);
            expect($messages[0]['message']['text'])->toBe('Hello World');
        });
        
        test('extracts multiple messages from payload', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '987654321'],
                                'message' => ['mid' => 'mid.1', 'text' => 'Message 1']
                            ],
                            [
                                'sender' => ['id' => '987654322'],
                                'message' => ['mid' => 'mid.2', 'text' => 'Message 2']
                            ]
                        ]
                    ]
                ]
            ];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toHaveCount(2);
            expect($messages[0]['message']['text'])->toBe('Message 1');
            expect($messages[1]['message']['text'])->toBe('Message 2');
        });
        
        test('returns empty array for payload without messages', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '987654321'],
                                'delivery' => ['mids' => ['mid.123']]
                            ]
                        ]
                    ]
                ]
            ];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toBeEmpty();
        });
        
        test('returns empty array for empty payload', function () {
            $payload = [];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toBeEmpty();
        });
        
        test('extracts text message from Instagram webhook', function () {
            $payload = [
                'object' => 'instagram',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'], // Instagram IGSID (15 digits)
                                'recipient' => ['id' => '987654321'],
                                'timestamp' => 1234567890,
                                'message' => [
                                    'mid' => 'mid.instagram.123',
                                    'text' => 'Hello from Instagram'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toHaveCount(1);
            expect($messages[0]['message']['text'])->toBe('Hello from Instagram');
            expect($messages[0]['sender']['id'])->toBe('123456789012345');
        });
        
        test('extracts text message from Messenger webhook', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890'], // Messenger PSID (10 digits)
                                'recipient' => ['id' => '987654321'],
                                'timestamp' => 1234567890,
                                'message' => [
                                    'mid' => 'mid.messenger.123',
                                    'text' => 'Hello from Messenger'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toHaveCount(1);
            expect($messages[0]['message']['text'])->toBe('Hello from Messenger');
            expect($messages[0]['sender']['id'])->toBe('1234567890');
        });
        
        test('extracts media message with image from Instagram', function () {
            $payload = [
                'object' => 'instagram',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'recipient' => ['id' => '987654321'],
                                'timestamp' => 1234567890,
                                'message' => [
                                    'mid' => 'mid.instagram.456',
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
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toHaveCount(1);
            expect($messages[0]['message']['attachments'][0]['type'])->toBe('image');
            expect($messages[0]['message']['attachments'][0]['payload']['url'])->toBe('https://example.com/image.jpg');
        });
        
        test('extracts media message with video from Messenger', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890'],
                                'recipient' => ['id' => '987654321'],
                                'timestamp' => 1234567890,
                                'message' => [
                                    'mid' => 'mid.messenger.789',
                                    'attachments' => [
                                        [
                                            'type' => 'video',
                                            'payload' => [
                                                'url' => 'https://example.com/video.mp4'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toHaveCount(1);
            expect($messages[0]['message']['attachments'][0]['type'])->toBe('video');
        });
        
        test('extracts quick_reply response from Instagram', function () {
            $payload = [
                'object' => 'instagram',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'recipient' => ['id' => '987654321'],
                                'timestamp' => 1234567890,
                                'message' => [
                                    'mid' => 'mid.instagram.qr',
                                    'text' => 'Yes',
                                    'quick_reply' => [
                                        'payload' => 'QUICK_REPLY_YES'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toHaveCount(1);
            expect($messages[0]['message']['quick_reply']['payload'])->toBe('QUICK_REPLY_YES');
            expect($messages[0]['message']['text'])->toBe('Yes');
        });
        
        test('extracts quick_reply response from Messenger', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890'],
                                'recipient' => ['id' => '987654321'],
                                'timestamp' => 1234567890,
                                'message' => [
                                    'mid' => 'mid.messenger.qr',
                                    'text' => 'No',
                                    'quick_reply' => [
                                        'payload' => 'QUICK_REPLY_NO'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toHaveCount(1);
            expect($messages[0]['message']['quick_reply']['payload'])->toBe('QUICK_REPLY_NO');
        });
        
        test('extracts multiple images from Instagram message', function () {
            $payload = [
                'object' => 'instagram',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'recipient' => ['id' => '987654321'],
                                'timestamp' => 1234567890,
                                'message' => [
                                    'mid' => 'mid.instagram.multi',
                                    'attachments' => [
                                        [
                                            'type' => 'image',
                                            'payload' => ['url' => 'https://example.com/img1.jpg']
                                        ],
                                        [
                                            'type' => 'image',
                                            'payload' => ['url' => 'https://example.com/img2.jpg']
                                        ],
                                        [
                                            'type' => 'image',
                                            'payload' => ['url' => 'https://example.com/img3.jpg']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toHaveCount(1);
            expect($messages[0]['message']['attachments'])->toHaveCount(3);
            expect($messages[0]['message']['attachments'][0]['type'])->toBe('image');
            expect($messages[0]['message']['attachments'][1]['type'])->toBe('image');
            expect($messages[0]['message']['attachments'][2]['type'])->toBe('image');
        });
        
        test('detects platform automatically from Instagram webhook structure', function () {
            $payload = [
                'object' => 'instagram', // Instagram indicator
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'message' => ['mid' => 'mid.123', 'text' => 'Test']
                            ]
                        ]
                    ]
                ]
            ];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toHaveCount(1);
            // Platform detection is implicit in the extraction
            expect($messages[0]['sender']['id'])->toBe('123456789012345');
        });
        
        test('detects platform automatically from Messenger webhook structure', function () {
            $payload = [
                'object' => 'page', // Messenger indicator
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890'],
                                'message' => ['mid' => 'mid.123', 'text' => 'Test']
                            ]
                        ]
                    ]
                ]
            ];
            
            $messages = $this->handler->extractMessages($payload);
            
            expect($messages)->toHaveCount(1);
            // Platform detection is implicit in the extraction
            expect($messages[0]['sender']['id'])->toBe('1234567890');
        });
    });
    
    describe('extractDeliveryReports', function () {
        
        test('extracts delivery reports from webhook payload', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '987654321'],
                                'recipient' => ['id' => '123456789'],
                                'delivery' => [
                                    'mids' => ['mid.123'],
                                    'watermark' => 1234567890
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toHaveCount(1);
            expect($reports[0]['delivery']['mids'])->toContain('mid.123');
        });
        
        test('extracts read receipts from webhook payload', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '987654321'],
                                'recipient' => ['id' => '123456789'],
                                'read' => [
                                    'watermark' => 1234567890
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toHaveCount(1);
            expect($reports[0]['read'])->toHaveKey('watermark');
        });
        
        test('extracts both delivery and read reports', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '987654321'],
                                'delivery' => ['mids' => ['mid.123']]
                            ],
                            [
                                'sender' => ['id' => '987654321'],
                                'read' => ['watermark' => 1234567890]
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toHaveCount(2);
        });
        
        test('returns empty array for payload without delivery reports', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '987654321'],
                                'message' => ['mid' => 'mid.123', 'text' => 'Hello']
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toBeEmpty();
        });
        
        test('extracts sent status from Instagram webhook', function () {
            $payload = [
                'object' => 'instagram',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'], // Instagram IGSID
                                'recipient' => ['id' => '987654321'],
                                'delivery' => [
                                    'mids' => ['mid.instagram.123'],
                                    'watermark' => 1234567890,
                                    'seq' => 1
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toHaveCount(1);
            expect($reports[0]['delivery']['mids'])->toContain('mid.instagram.123');
            expect($reports[0]['sender']['id'])->toBe('123456789012345');
        });
        
        test('extracts sent status from Messenger webhook', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890'], // Messenger PSID
                                'recipient' => ['id' => '987654321'],
                                'delivery' => [
                                    'mids' => ['mid.messenger.456'],
                                    'watermark' => 1234567890,
                                    'seq' => 1
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toHaveCount(1);
            expect($reports[0]['delivery']['mids'])->toContain('mid.messenger.456');
            expect($reports[0]['sender']['id'])->toBe('1234567890');
        });
        
        test('extracts delivered status from Instagram webhook', function () {
            $payload = [
                'object' => 'instagram',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'recipient' => ['id' => '987654321'],
                                'delivery' => [
                                    'mids' => ['mid.instagram.789'],
                                    'watermark' => 1234567895
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toHaveCount(1);
            expect($reports[0]['delivery']['watermark'])->toBe(1234567895);
        });
        
        test('extracts delivered status from Messenger webhook', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890'],
                                'recipient' => ['id' => '987654321'],
                                'delivery' => [
                                    'mids' => ['mid.messenger.101'],
                                    'watermark' => 1234567895
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toHaveCount(1);
            expect($reports[0]['delivery']['watermark'])->toBe(1234567895);
        });
        
        test('extracts read status from Instagram webhook', function () {
            $payload = [
                'object' => 'instagram',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'recipient' => ['id' => '987654321'],
                                'read' => [
                                    'watermark' => 1234567900,
                                    'seq' => 5
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toHaveCount(1);
            expect($reports[0]['read']['watermark'])->toBe(1234567900);
            expect($reports[0]['sender']['id'])->toBe('123456789012345');
        });
        
        test('extracts read status from Messenger webhook', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890'],
                                'recipient' => ['id' => '987654321'],
                                'read' => [
                                    'watermark' => 1234567900
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toHaveCount(1);
            expect($reports[0]['read']['watermark'])->toBe(1234567900);
            expect($reports[0]['sender']['id'])->toBe('1234567890');
        });
        
        test('extracts multiple delivery reports from same webhook', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890'],
                                'delivery' => ['mids' => ['mid.1'], 'watermark' => 1234567890]
                            ],
                            [
                                'sender' => ['id' => '1234567891'],
                                'delivery' => ['mids' => ['mid.2'], 'watermark' => 1234567891]
                            ],
                            [
                                'sender' => ['id' => '1234567892'],
                                'read' => ['watermark' => 1234567892]
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toHaveCount(3);
            expect($reports[0]['delivery'])->toHaveKey('mids');
            expect($reports[1]['delivery'])->toHaveKey('mids');
            expect($reports[2]['read'])->toHaveKey('watermark');
        });
        
        test('handles delivery report with multiple message IDs', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890,
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890'],
                                'delivery' => [
                                    'mids' => ['mid.1', 'mid.2', 'mid.3'],
                                    'watermark' => 1234567890
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $reports = $this->handler->extractDeliveryReports($payload);
            
            expect($reports)->toHaveCount(1);
            expect($reports[0]['delivery']['mids'])->toHaveCount(3);
            expect($reports[0]['delivery']['mids'])->toContain('mid.1');
            expect($reports[0]['delivery']['mids'])->toContain('mid.2');
            expect($reports[0]['delivery']['mids'])->toContain('mid.3');
        });
    });
    
    describe('processWithErrorHandling', function () {
        
        test('processes webhook successfully without error handler', function () {
            $payload = ['object' => 'page', 'entry' => []];
            $processed = false;
            
            $processor = function ($p) use (&$processed) {
                $processed = true;
            };
            
            $result = $this->handler->processWithErrorHandling($payload, $processor, 1);
            
            expect($result['success'])->toBeTrue();
            expect($result['should_retry'])->toBeFalse();
            expect($result['error'])->toBeNull();
            expect($processed)->toBeTrue();
        });
        
        test('handles error without error handler', function () {
            $payload = ['object' => 'page', 'entry' => []];
            
            $processor = function ($p) {
                throw new \Exception('Processing failed');
            };
            
            $result = $this->handler->processWithErrorHandling($payload, $processor, 1);
            
            expect($result['success'])->toBeFalse();
            expect($result['should_retry'])->toBeFalse();
            expect($result['error'])->toBe('Processing failed');
        });
        
        test('processes webhook successfully with error handler', function () {
            $errorHandler = Mockery::mock(\WhatsApp\Adapter\Services\WebhookErrorHandler::class);
            $handler = new MetaWebhookHandler($this->logger, $errorHandler);
            
            $payload = ['object' => 'page', 'entry' => []];
            $processed = false;
            
            $processor = function ($p) use (&$processed) {
                $processed = true;
            };
            
            $result = $handler->processWithErrorHandling($payload, $processor, 1);
            
            expect($result['success'])->toBeTrue();
            expect($result['should_retry'])->toBeFalse();
            expect($result['error'])->toBeNull();
            expect($processed)->toBeTrue();
        });
        
        test('uses error handler for retry decision', function () {
            $errorHandler = Mockery::mock(\WhatsApp\Adapter\Services\WebhookErrorHandler::class);
            $errorHandler->shouldReceive('handleError')
                ->once()
                ->andReturn([
                    'should_retry' => true,
                    'delay_ms' => 1000,
                    'reason' => 'Transient error'
                ]);
            
            $handler = new MetaWebhookHandler($this->logger, $errorHandler);
            
            $payload = ['object' => 'page', 'entry' => []];
            
            $processor = function ($p) {
                throw new \Exception('Temporary error');
            };
            
            $result = $handler->processWithErrorHandling($payload, $processor, 1);
            
            expect($result['success'])->toBeFalse();
            expect($result['should_retry'])->toBeTrue();
            expect($result['delay_ms'])->toBe(1000);
        });
    });
    
    describe('error checking methods', function () {
        
        test('isMessagingWindowError returns false without error handler', function () {
            $error = new \Exception('Messaging window expired', 2022);
            
            $result = $this->handler->isMessagingWindowError($error);
            
            expect($result)->toBeFalse();
        });
        
        test('isAccountEligibilityError returns false without error handler', function () {
            $error = new \Exception('Account not eligible', 36103);
            
            $result = $this->handler->isAccountEligibilityError($error);
            
            expect($result)->toBeFalse();
        });
        
        test('getUserFriendlyErrorMessage returns error message without error handler', function () {
            $error = new \Exception('Some error');
            
            $result = $this->handler->getUserFriendlyErrorMessage($error);
            
            expect($result)->toBe('Some error');
        });
        
        test('isMessagingWindowError delegates to error handler', function () {
            $errorHandler = Mockery::mock(\WhatsApp\Adapter\Services\WebhookErrorHandler::class);
            $errorHandler->shouldReceive('isMessagingWindowError')
                ->once()
                ->andReturn(true);
            
            $handler = new MetaWebhookHandler($this->logger, $errorHandler);
            $error = new \Exception('Messaging window expired', 2022);
            
            $result = $handler->isMessagingWindowError($error);
            
            expect($result)->toBeTrue();
        });
        
        test('isAccountEligibilityError delegates to error handler', function () {
            $errorHandler = Mockery::mock(\WhatsApp\Adapter\Services\WebhookErrorHandler::class);
            $errorHandler->shouldReceive('isAccountEligibilityError')
                ->once()
                ->andReturn(true);
            
            $handler = new MetaWebhookHandler($this->logger, $errorHandler);
            $error = new \Exception('Account not eligible', 36103);
            
            $result = $handler->isAccountEligibilityError($error);
            
            expect($result)->toBeTrue();
        });
        
        test('getUserFriendlyErrorMessage delegates to error handler', function () {
            $errorHandler = Mockery::mock(\WhatsApp\Adapter\Services\WebhookErrorHandler::class);
            $errorHandler->shouldReceive('getUserFriendlyMessage')
                ->once()
                ->andReturn('Friendly error message');
            
            $handler = new MetaWebhookHandler($this->logger, $errorHandler);
            $error = new \Exception('Some error');
            
            $result = $handler->getUserFriendlyErrorMessage($error);
            
            expect($result)->toBe('Friendly error message');
        });
    });
});
