<?php

declare(strict_types=1);

use WhatsApp\Adapter\Providers\Meta\MetaPlatformDetector;

beforeEach(function () {
    $this->detector = new MetaPlatformDetector();
});

describe('MetaPlatformDetector', function () {
    
    describe('detectFromWebhook', function () {
        
        test('detects Instagram from webhook with instagram object type', function () {
            $payload = [
                'object' => 'instagram',
                'entry' => [
                    [
                        'id' => '123456789',
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'message' => ['text' => 'Hello']
                            ]
                        ]
                    ]
                ]
            ];
            
            $platform = $this->detector->detectFromWebhook($payload);
            
            expect($platform)->toBe('instagram');
        });
        
        test('detects Instagram from webhook with instagram field in entry', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'instagram' => true,
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'],
                                'message' => ['text' => 'Hello']
                            ]
                        ]
                    ]
                ]
            ];
            
            $platform = $this->detector->detectFromWebhook($payload);
            
            expect($platform)->toBe('instagram');
        });
        
        test('detects Instagram from long sender ID (15+ digits)', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'messaging' => [
                            [
                                'sender' => ['id' => '123456789012345'], // 15 digits
                                'message' => [
                                    'text' => 'Hello',
                                    'is_echo' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $platform = $this->detector->detectFromWebhook($payload);
            
            expect($platform)->toBe('instagram');
        });
        
        test('detects Messenger from webhook with page object type', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890'], // 10 digits
                                'message' => ['text' => 'Hello']
                            ]
                        ]
                    ]
                ]
            ];
            
            $platform = $this->detector->detectFromWebhook($payload);
            
            expect($platform)->toBe('messenger');
        });
        
        test('detects Messenger from short sender ID (10-14 digits)', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'messaging' => [
                            [
                                'sender' => ['id' => '12345678901234'], // 14 digits
                                'message' => [
                                    'text' => 'Hello',
                                    'is_echo' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $platform = $this->detector->detectFromWebhook($payload);
            
            expect($platform)->toBe('messenger');
        });
        
        test('defaults to Messenger when no Instagram indicators found', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'messaging' => [
                            [
                                'sender' => ['id' => '1234567890'],
                                'message' => ['text' => 'Hello']
                            ]
                        ]
                    ]
                ]
            ];
            
            $platform = $this->detector->detectFromWebhook($payload);
            
            expect($platform)->toBe('messenger');
        });
        
        test('throws exception for invalid webhook payload without entry field', function () {
            $payload = [
                'object' => 'page'
            ];
            
            expect(fn() => $this->detector->detectFromWebhook($payload))
                ->toThrow(\InvalidArgumentException::class, 'Invalid webhook payload: missing entry field');
        });
        
        test('throws exception for webhook payload with non-array entry', function () {
            $payload = [
                'object' => 'page',
                'entry' => 'not_an_array'
            ];
            
            expect(fn() => $this->detector->detectFromWebhook($payload))
                ->toThrow(\InvalidArgumentException::class, 'Invalid webhook payload: missing entry field');
        });
        
        test('handles empty entry array', function () {
            $payload = [
                'object' => 'page',
                'entry' => []
            ];
            
            $platform = $this->detector->detectFromWebhook($payload);
            
            // Should default to messenger
            expect($platform)->toBe('messenger');
        });
        
        test('handles entry without messaging field', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'time' => 1234567890
                    ]
                ]
            ];
            
            $platform = $this->detector->detectFromWebhook($payload);
            
            // Should default to messenger
            expect($platform)->toBe('messenger');
        });
        
        test('detects Instagram from very long ID (17+ digits)', function () {
            $payload = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => '123456789',
                        'messaging' => [
                            [
                                'sender' => ['id' => '12345678901234567'], // 17 digits
                                'message' => [
                                    'text' => 'Hello',
                                    'is_echo' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $platform = $this->detector->detectFromWebhook($payload);
            
            expect($platform)->toBe('instagram');
        });
    });
    
    describe('detectFromId', function () {
        
        test('detects Instagram from long numeric ID (15+ digits)', function () {
            $id = '123456789012345'; // 15 digits
            
            $platform = $this->detector->detectFromId($id);
            
            expect($platform)->toBe('instagram');
        });
        
        test('detects Instagram from very long numeric ID (20 digits)', function () {
            $id = '12345678901234567890'; // 20 digits
            
            $platform = $this->detector->detectFromId($id);
            
            expect($platform)->toBe('instagram');
        });
        
        test('detects Messenger from short numeric ID (10 digits)', function () {
            $id = '1234567890'; // 10 digits
            
            $platform = $this->detector->detectFromId($id);
            
            expect($platform)->toBe('messenger');
        });
        
        test('detects Messenger from medium numeric ID (14 digits)', function () {
            $id = '12345678901234'; // 14 digits
            
            $platform = $this->detector->detectFromId($id);
            
            expect($platform)->toBe('messenger');
        });
        
        test('detects Messenger from non-numeric ID', function () {
            $id = 'abc123def456';
            
            $platform = $this->detector->detectFromId($id);
            
            expect($platform)->toBe('messenger');
        });
        
        test('detects Messenger from empty ID', function () {
            $id = '';
            
            $platform = $this->detector->detectFromId($id);
            
            expect($platform)->toBe('messenger');
        });
        
        test('detects Messenger from ID with special characters', function () {
            $id = '123-456-7890';
            
            $platform = $this->detector->detectFromId($id);
            
            expect($platform)->toBe('messenger');
        });
        
        test('boundary case: exactly 15 digits is Instagram', function () {
            $id = '123456789012345'; // Exactly 15 digits
            
            $platform = $this->detector->detectFromId($id);
            
            expect($platform)->toBe('instagram');
        });
        
        test('boundary case: exactly 14 digits is Messenger', function () {
            $id = '12345678901234'; // Exactly 14 digits
            
            $platform = $this->detector->detectFromId($id);
            
            expect($platform)->toBe('messenger');
        });
    });
    
    describe('getPlatformLimits', function () {
        
        test('returns Instagram limits for instagram platform', function () {
            $limits = $this->detector->getPlatformLimits('instagram');
            
            expect($limits)->toHaveKey('max_images_per_message');
            expect($limits['max_images_per_message'])->toBe(10);
            expect($limits['max_image_size'])->toBe(8 * 1024 * 1024); // 8MB
            expect($limits['max_video_size'])->toBe(25 * 1024 * 1024); // 25MB
            expect($limits['max_audio_size'])->toBe(25 * 1024 * 1024); // 25MB
            expect($limits['max_file_size'])->toBe(25 * 1024 * 1024); // 25MB
            expect($limits['max_quick_replies'])->toBe(13);
            expect($limits['messaging_window_hours'])->toBe(24);
            expect($limits['supports_button_template'])->toBeFalse();
            expect($limits['supports_generic_template'])->toBeTrue();
        });
        
        test('returns Messenger limits for messenger platform', function () {
            $limits = $this->detector->getPlatformLimits('messenger');
            
            expect($limits)->toHaveKey('max_images_per_message');
            expect($limits['max_images_per_message'])->toBe(1);
            expect($limits['max_image_size'])->toBe(25 * 1024 * 1024); // 25MB
            expect($limits['max_video_size'])->toBe(25 * 1024 * 1024); // 25MB
            expect($limits['max_audio_size'])->toBe(25 * 1024 * 1024); // 25MB
            expect($limits['max_file_size'])->toBe(25 * 1024 * 1024); // 25MB
            expect($limits['max_quick_replies'])->toBe(13);
            expect($limits['messaging_window_hours'])->toBe(24);
            expect($limits['supports_button_template'])->toBeTrue();
            expect($limits['supports_generic_template'])->toBeTrue();
        });
        
        test('returns Messenger limits for unknown platform', function () {
            $limits = $this->detector->getPlatformLimits('unknown');
            
            // Should default to Messenger limits
            expect($limits['max_images_per_message'])->toBe(1);
            expect($limits['supports_button_template'])->toBeTrue();
        });
        
        test('Instagram has higher image limit than Messenger', function () {
            $instagramLimits = $this->detector->getPlatformLimits('instagram');
            $messengerLimits = $this->detector->getPlatformLimits('messenger');
            
            expect($instagramLimits['max_images_per_message'])
                ->toBeGreaterThan($messengerLimits['max_images_per_message']);
        });
        
        test('Instagram has smaller image size limit than Messenger', function () {
            $instagramLimits = $this->detector->getPlatformLimits('instagram');
            $messengerLimits = $this->detector->getPlatformLimits('messenger');
            
            expect($instagramLimits['max_image_size'])
                ->toBeLessThan($messengerLimits['max_image_size']);
        });
        
        test('both platforms have same quick reply limit', function () {
            $instagramLimits = $this->detector->getPlatformLimits('instagram');
            $messengerLimits = $this->detector->getPlatformLimits('messenger');
            
            expect($instagramLimits['max_quick_replies'])
                ->toBe($messengerLimits['max_quick_replies']);
        });
        
        test('both platforms have same messaging window', function () {
            $instagramLimits = $this->detector->getPlatformLimits('instagram');
            $messengerLimits = $this->detector->getPlatformLimits('messenger');
            
            expect($instagramLimits['messaging_window_hours'])
                ->toBe($messengerLimits['messaging_window_hours']);
        });
        
        test('only Messenger supports button template', function () {
            $instagramLimits = $this->detector->getPlatformLimits('instagram');
            $messengerLimits = $this->detector->getPlatformLimits('messenger');
            
            expect($instagramLimits['supports_button_template'])->toBeFalse();
            expect($messengerLimits['supports_button_template'])->toBeTrue();
        });
        
        test('both platforms support generic template', function () {
            $instagramLimits = $this->detector->getPlatformLimits('instagram');
            $messengerLimits = $this->detector->getPlatformLimits('messenger');
            
            expect($instagramLimits['supports_generic_template'])->toBeTrue();
            expect($messengerLimits['supports_generic_template'])->toBeTrue();
        });
    });
    
    describe('getPlatformName', function () {
        
        test('returns Instagram for instagram platform', function () {
            $name = $this->detector->getPlatformName('instagram');
            
            expect($name)->toBe('Instagram');
        });
        
        test('returns Facebook Messenger for messenger platform', function () {
            $name = $this->detector->getPlatformName('messenger');
            
            expect($name)->toBe('Facebook Messenger');
        });
        
        test('returns Unknown Platform for invalid platform', function () {
            $name = $this->detector->getPlatformName('invalid');
            
            expect($name)->toBe('Unknown Platform');
        });
        
        test('returns Unknown Platform for empty string', function () {
            $name = $this->detector->getPlatformName('');
            
            expect($name)->toBe('Unknown Platform');
        });
    });
    
    describe('isValidPlatform', function () {
        
        test('returns true for instagram', function () {
            $isValid = $this->detector->isValidPlatform('instagram');
            
            expect($isValid)->toBeTrue();
        });
        
        test('returns true for messenger', function () {
            $isValid = $this->detector->isValidPlatform('messenger');
            
            expect($isValid)->toBeTrue();
        });
        
        test('returns false for invalid platform', function () {
            $isValid = $this->detector->isValidPlatform('invalid');
            
            expect($isValid)->toBeFalse();
        });
        
        test('returns false for empty string', function () {
            $isValid = $this->detector->isValidPlatform('');
            
            expect($isValid)->toBeFalse();
        });
        
        test('returns false for whatsapp', function () {
            $isValid = $this->detector->isValidPlatform('whatsapp');
            
            expect($isValid)->toBeFalse();
        });
        
        test('is case sensitive', function () {
            expect($this->detector->isValidPlatform('Instagram'))->toBeFalse();
            expect($this->detector->isValidPlatform('INSTAGRAM'))->toBeFalse();
            expect($this->detector->isValidPlatform('Messenger'))->toBeFalse();
            expect($this->detector->isValidPlatform('MESSENGER'))->toBeFalse();
        });
    });
});
