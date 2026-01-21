<?php

declare(strict_types=1);

use WhatsApp\Adapter\Providers\Meta\MetaMessageFormatter;

beforeEach(function () {
    $this->formatter = new MetaMessageFormatter();
});

describe('MetaMessageFormatter', function () {
    
    describe('formatTextMessage', function () {
        
        test('formats text message correctly', function () {
            $recipientId = '1234567890';
            $text = 'Hello from Meta!';
            
            $result = $this->formatter->formatTextMessage($recipientId, $text);
            
            expect($result)->toHaveKey('recipient');
            expect($result)->toHaveKey('message');
            expect($result['recipient']['id'])->toBe($recipientId);
            expect($result['message']['text'])->toBe($text);
        });
        
        test('formats text message with special characters', function () {
            $recipientId = '9876543210';
            $text = 'Hello! 👋 How are you? 😊';
            
            $result = $this->formatter->formatTextMessage($recipientId, $text);
            
            expect($result['message']['text'])->toBe($text);
        });
        
        test('formats text message with line breaks', function () {
            $recipientId = '1234567890';
            $text = "Line 1\nLine 2\nLine 3";
            
            $result = $this->formatter->formatTextMessage($recipientId, $text);
            
            expect($result['message']['text'])->toBe($text);
        });
        
        test('formats text message with URLs', function () {
            $recipientId = '1234567890';
            $text = 'Check this out: https://example.com';
            
            $result = $this->formatter->formatTextMessage($recipientId, $text);
            
            expect($result['message']['text'])->toBe($text);
        });
    });
    
    describe('formatMediaMessage', function () {
        
        test('formats image message correctly', function () {
            $recipientId = '1234567890';
            $type = 'image';
            $url = 'https://example.com/image.jpg';
            
            $result = $this->formatter->formatMediaMessage($recipientId, $type, $url);
            
            expect($result)->toHaveKey('recipient');
            expect($result)->toHaveKey('message');
            expect($result['recipient']['id'])->toBe($recipientId);
            expect($result['message']['attachment']['type'])->toBe($type);
            expect($result['message']['attachment']['payload']['url'])->toBe($url);
            expect($result['message']['attachment']['payload']['is_reusable'])->toBeTrue();
        });
        
        test('formats video message correctly', function () {
            $recipientId = '1234567890';
            $type = 'video';
            $url = 'https://example.com/video.mp4';
            
            $result = $this->formatter->formatMediaMessage($recipientId, $type, $url);
            
            expect($result['message']['attachment']['type'])->toBe($type);
            expect($result['message']['attachment']['payload']['url'])->toBe($url);
        });
        
        test('formats audio message correctly', function () {
            $recipientId = '1234567890';
            $type = 'audio';
            $url = 'https://example.com/audio.mp3';
            
            $result = $this->formatter->formatMediaMessage($recipientId, $type, $url);
            
            expect($result['message']['attachment']['type'])->toBe($type);
            expect($result['message']['attachment']['payload']['url'])->toBe($url);
        });
        
        test('formats file message correctly', function () {
            $recipientId = '1234567890';
            $type = 'file';
            $url = 'https://example.com/document.pdf';
            
            $result = $this->formatter->formatMediaMessage($recipientId, $type, $url);
            
            expect($result['message']['attachment']['type'])->toBe($type);
            expect($result['message']['attachment']['payload']['url'])->toBe($url);
        });
        
        test('includes is_reusable flag for all media types', function () {
            $recipientId = '1234567890';
            $types = ['image', 'video', 'audio', 'file'];
            
            foreach ($types as $type) {
                $result = $this->formatter->formatMediaMessage(
                    $recipientId,
                    $type,
                    'https://example.com/media'
                );
                
                expect($result['message']['attachment']['payload']['is_reusable'])->toBeTrue();
            }
        });
    });
    
    describe('formatMultipleImages', function () {
        
        test('formats single image correctly', function () {
            $recipientId = '1234567890';
            $urls = ['https://example.com/image1.jpg'];
            
            $result = $this->formatter->formatMultipleImages($recipientId, $urls);
            
            expect($result)->toHaveKey('recipient');
            expect($result)->toHaveKey('message');
            expect($result['recipient']['id'])->toBe($recipientId);
            expect($result['message']['attachments'])->toHaveCount(1);
            expect($result['message']['attachments'][0]['type'])->toBe('image');
            expect($result['message']['attachments'][0]['payload']['url'])->toBe($urls[0]);
        });
        
        test('formats multiple images correctly', function () {
            $recipientId = '1234567890';
            $urls = [
                'https://example.com/image1.jpg',
                'https://example.com/image2.png',
                'https://example.com/image3.jpg'
            ];
            
            $result = $this->formatter->formatMultipleImages($recipientId, $urls);
            
            expect($result['message']['attachments'])->toHaveCount(3);
            
            foreach ($urls as $index => $url) {
                expect($result['message']['attachments'][$index]['type'])->toBe('image');
                expect($result['message']['attachments'][$index]['payload']['url'])->toBe($url);
                expect($result['message']['attachments'][$index]['payload']['is_reusable'])->toBeTrue();
            }
        });
        
        test('formats maximum 10 images for Instagram', function () {
            $recipientId = '1234567890';
            $urls = array_fill(0, 10, 'https://example.com/image.jpg');
            
            $result = $this->formatter->formatMultipleImages($recipientId, $urls);
            
            expect($result['message']['attachments'])->toHaveCount(10);
        });
        
        test('preserves image order', function () {
            $recipientId = '1234567890';
            $urls = [
                'https://example.com/first.jpg',
                'https://example.com/second.jpg',
                'https://example.com/third.jpg'
            ];
            
            $result = $this->formatter->formatMultipleImages($recipientId, $urls);
            
            expect($result['message']['attachments'][0]['payload']['url'])->toBe($urls[0]);
            expect($result['message']['attachments'][1]['payload']['url'])->toBe($urls[1]);
            expect($result['message']['attachments'][2]['payload']['url'])->toBe($urls[2]);
        });
    });
    
    describe('formatQuickReplies', function () {
        
        test('formats quick replies correctly for Instagram', function () {
            $recipientId = '123456789012345'; // Instagram ID
            $text = 'Choose an option:';
            $buttons = [
                ['title' => 'Option 1', 'id' => 'opt1'],
                ['title' => 'Option 2', 'id' => 'opt2'],
                ['title' => 'Option 3', 'id' => 'opt3']
            ];
            
            $result = $this->formatter->formatQuickReplies($recipientId, $text, $buttons);
            
            expect($result)->toHaveKey('recipient');
            expect($result)->toHaveKey('message');
            expect($result['recipient']['id'])->toBe($recipientId);
            expect($result['message']['text'])->toBe($text);
            expect($result['message']['quick_replies'])->toHaveCount(3);
            
            foreach ($buttons as $index => $button) {
                expect($result['message']['quick_replies'][$index]['content_type'])->toBe('text');
                expect($result['message']['quick_replies'][$index]['title'])->toBe($button['title']);
                expect($result['message']['quick_replies'][$index]['payload'])->toBe($button['id']);
            }
        });
        
        test('formats quick replies correctly for Messenger', function () {
            $recipientId = '12345678901'; // Messenger ID (shorter)
            $text = 'What would you like to do?';
            $buttons = [
                ['title' => 'Buy', 'id' => 'buy'],
                ['title' => 'Learn More', 'id' => 'learn']
            ];
            
            $result = $this->formatter->formatQuickReplies($recipientId, $text, $buttons);
            
            expect($result['message']['quick_replies'])->toHaveCount(2);
            expect($result['message']['quick_replies'][0]['title'])->toBe('Buy');
            expect($result['message']['quick_replies'][1]['title'])->toBe('Learn More');
        });
        
        test('formats single quick reply', function () {
            $recipientId = '1234567890';
            $text = 'Do you agree?';
            $buttons = [
                ['title' => 'Yes', 'id' => 'yes']
            ];
            
            $result = $this->formatter->formatQuickReplies($recipientId, $text, $buttons);
            
            expect($result['message']['quick_replies'])->toHaveCount(1);
            expect($result['message']['quick_replies'][0]['title'])->toBe('Yes');
        });
        
        test('formats maximum 13 quick replies', function () {
            $recipientId = '1234567890';
            $text = 'Choose a number:';
            $buttons = [];
            
            for ($i = 1; $i <= 13; $i++) {
                $buttons[] = ['title' => "Option $i", 'id' => "opt$i"];
            }
            
            $result = $this->formatter->formatQuickReplies($recipientId, $text, $buttons);
            
            expect($result['message']['quick_replies'])->toHaveCount(13);
        });
        
        test('preserves button order', function () {
            $recipientId = '1234567890';
            $text = 'Select:';
            $buttons = [
                ['title' => 'First', 'id' => '1'],
                ['title' => 'Second', 'id' => '2'],
                ['title' => 'Third', 'id' => '3']
            ];
            
            $result = $this->formatter->formatQuickReplies($recipientId, $text, $buttons);
            
            expect($result['message']['quick_replies'][0]['title'])->toBe('First');
            expect($result['message']['quick_replies'][1]['title'])->toBe('Second');
            expect($result['message']['quick_replies'][2]['title'])->toBe('Third');
        });
    });
    
    describe('formatGenericTemplate', function () {
        
        test('formats generic template correctly', function () {
            $recipientId = '1234567890';
            $elements = [
                [
                    'title' => 'Product 1',
                    'subtitle' => 'Description 1',
                    'image_url' => 'https://example.com/product1.jpg',
                    'buttons' => [
                        ['type' => 'web_url', 'title' => 'View', 'url' => 'https://example.com/p1']
                    ]
                ]
            ];
            
            $result = $this->formatter->formatGenericTemplate($recipientId, $elements);
            
            expect($result)->toHaveKey('recipient');
            expect($result)->toHaveKey('message');
            expect($result['recipient']['id'])->toBe($recipientId);
            expect($result['message']['attachment']['type'])->toBe('template');
            expect($result['message']['attachment']['payload']['template_type'])->toBe('generic');
            expect($result['message']['attachment']['payload']['elements'])->toBe($elements);
        });
        
        test('formats generic template with multiple cards', function () {
            $recipientId = '1234567890';
            $elements = [
                [
                    'title' => 'Product 1',
                    'subtitle' => 'Description 1',
                    'image_url' => 'https://example.com/product1.jpg'
                ],
                [
                    'title' => 'Product 2',
                    'subtitle' => 'Description 2',
                    'image_url' => 'https://example.com/product2.jpg'
                ],
                [
                    'title' => 'Product 3',
                    'subtitle' => 'Description 3',
                    'image_url' => 'https://example.com/product3.jpg'
                ]
            ];
            
            $result = $this->formatter->formatGenericTemplate($recipientId, $elements);
            
            expect($result['message']['attachment']['payload']['elements'])->toHaveCount(3);
        });
        
        test('formats generic template with buttons', function () {
            $recipientId = '1234567890';
            $elements = [
                [
                    'title' => 'Product',
                    'buttons' => [
                        ['type' => 'web_url', 'title' => 'View', 'url' => 'https://example.com'],
                        ['type' => 'postback', 'title' => 'Buy', 'payload' => 'buy_123']
                    ]
                ]
            ];
            
            $result = $this->formatter->formatGenericTemplate($recipientId, $elements);
            
            expect($result['message']['attachment']['payload']['elements'][0]['buttons'])->toHaveCount(2);
        });
    });
    
    describe('formatButtonTemplate', function () {
        
        test('formats button template correctly for Messenger', function () {
            $recipientId = '12345678901'; // Messenger PSID
            $text = 'What would you like to do?';
            $buttons = [
                ['type' => 'web_url', 'title' => 'Visit Website', 'url' => 'https://example.com'],
                ['type' => 'postback', 'title' => 'Start Chat', 'payload' => 'start_chat']
            ];
            
            $result = $this->formatter->formatButtonTemplate($recipientId, $text, $buttons);
            
            expect($result)->toHaveKey('recipient');
            expect($result)->toHaveKey('message');
            expect($result['recipient']['id'])->toBe($recipientId);
            expect($result['message']['attachment']['type'])->toBe('template');
            expect($result['message']['attachment']['payload']['template_type'])->toBe('button');
            expect($result['message']['attachment']['payload']['text'])->toBe($text);
            expect($result['message']['attachment']['payload']['buttons'])->toBe($buttons);
        });
        
        test('formats button template with single button', function () {
            $recipientId = '12345678901';
            $text = 'Click to continue:';
            $buttons = [
                ['type' => 'web_url', 'title' => 'Continue', 'url' => 'https://example.com/continue']
            ];
            
            $result = $this->formatter->formatButtonTemplate($recipientId, $text, $buttons);
            
            expect($result['message']['attachment']['payload']['buttons'])->toHaveCount(1);
        });
        
        test('formats button template with maximum 3 buttons', function () {
            $recipientId = '12345678901';
            $text = 'Choose an action:';
            $buttons = [
                ['type' => 'web_url', 'title' => 'Option 1', 'url' => 'https://example.com/1'],
                ['type' => 'web_url', 'title' => 'Option 2', 'url' => 'https://example.com/2'],
                ['type' => 'postback', 'title' => 'Option 3', 'payload' => 'opt3']
            ];
            
            $result = $this->formatter->formatButtonTemplate($recipientId, $text, $buttons);
            
            expect($result['message']['attachment']['payload']['buttons'])->toHaveCount(3);
        });
        
        test('formats button template with call button', function () {
            $recipientId = '12345678901';
            $text = 'Need help?';
            $buttons = [
                ['type' => 'phone_number', 'title' => 'Call Us', 'payload' => '+1234567890']
            ];
            
            $result = $this->formatter->formatButtonTemplate($recipientId, $text, $buttons);
            
            expect($result['message']['attachment']['payload']['buttons'][0]['type'])->toBe('phone_number');
        });
    });
    
    describe('convertTemplateToText', function () {
        
        test('converts template with single placeholder', function () {
            $templateText = 'Hello {{1}}!';
            $parameters = ['John'];
            
            $result = $this->formatter->convertTemplateToText($templateText, $parameters);
            
            expect($result)->toBe('Hello John!');
        });
        
        test('converts template with multiple placeholders', function () {
            $templateText = 'Hello {{1}}, your order {{2}} is ready!';
            $parameters = ['Alice', '#12345'];
            
            $result = $this->formatter->convertTemplateToText($templateText, $parameters);
            
            expect($result)->toBe('Hello Alice, your order #12345 is ready!');
        });
        
        test('converts template with placeholders in different order', function () {
            $templateText = 'Order {{2}} for {{1}} is confirmed.';
            $parameters = ['Bob', '#67890'];
            
            $result = $this->formatter->convertTemplateToText($templateText, $parameters);
            
            expect($result)->toBe('Order #67890 for Bob is confirmed.');
        });
        
        test('converts template with repeated placeholders', function () {
            $templateText = 'Hello {{1}}! Welcome {{1}}!';
            $parameters = ['Charlie'];
            
            $result = $this->formatter->convertTemplateToText($templateText, $parameters);
            
            expect($result)->toBe('Hello Charlie! Welcome Charlie!');
        });
        
        test('converts template with no placeholders', function () {
            $templateText = 'This is a plain text message.';
            $parameters = [];
            
            $result = $this->formatter->convertTemplateToText($templateText, $parameters);
            
            expect($result)->toBe('This is a plain text message.');
        });
        
        test('converts template with more parameters than placeholders', function () {
            $templateText = 'Hello {{1}}!';
            $parameters = ['David', 'Extra', 'Parameters'];
            
            $result = $this->formatter->convertTemplateToText($templateText, $parameters);
            
            expect($result)->toBe('Hello David!');
        });
        
        test('converts template with special characters in parameters', function () {
            $templateText = 'Message: {{1}}';
            $parameters = ['Hello! 👋 How are you?'];
            
            $result = $this->formatter->convertTemplateToText($templateText, $parameters);
            
            expect($result)->toBe('Message: Hello! 👋 How are you?');
        });
        
        test('converts template with numeric parameters', function () {
            $templateText = 'Your balance is {{1}} and you have {{2}} points.';
            $parameters = ['$100.50', '250'];
            
            $result = $this->formatter->convertTemplateToText($templateText, $parameters);
            
            expect($result)->toBe('Your balance is $100.50 and you have 250 points.');
        });
        
        test('converts template with line breaks', function () {
            $templateText = "Hello {{1}}!\nYour order {{2}} is ready.";
            $parameters = ['Emma', '#99999'];
            
            $result = $this->formatter->convertTemplateToText($templateText, $parameters);
            
            expect($result)->toBe("Hello Emma!\nYour order #99999 is ready.");
        });
        
        test('converts template with URLs in parameters', function () {
            $templateText = 'Check your order at {{1}}';
            $parameters = ['https://example.com/order/12345'];
            
            $result = $this->formatter->convertTemplateToText($templateText, $parameters);
            
            expect($result)->toBe('Check your order at https://example.com/order/12345');
        });
    });
    
    describe('validateRecipientId', function () {
        
        test('validates numeric Instagram ID', function () {
            $result = $this->formatter->validateRecipientId('123456789012345');
            
            expect($result)->toBeTrue();
        });
        
        test('validates numeric Messenger ID', function () {
            $result = $this->formatter->validateRecipientId('12345678901');
            
            expect($result)->toBeTrue();
        });
        
        test('rejects empty recipient ID', function () {
            $result = $this->formatter->validateRecipientId('');
            
            expect($result)->toBeFalse();
        });
        
        test('rejects non-numeric recipient ID', function () {
            $result = $this->formatter->validateRecipientId('abc123');
            
            expect($result)->toBeFalse();
        });
        
        test('rejects recipient ID with special characters', function () {
            $result = $this->formatter->validateRecipientId('123-456-789');
            
            expect($result)->toBeFalse();
        });
    });
    
    describe('validateMediaType', function () {
        
        test('validates image type', function () {
            $result = $this->formatter->validateMediaType('image');
            
            expect($result)->toBeTrue();
        });
        
        test('validates video type', function () {
            $result = $this->formatter->validateMediaType('video');
            
            expect($result)->toBeTrue();
        });
        
        test('validates audio type', function () {
            $result = $this->formatter->validateMediaType('audio');
            
            expect($result)->toBeTrue();
        });
        
        test('validates file type', function () {
            $result = $this->formatter->validateMediaType('file');
            
            expect($result)->toBeTrue();
        });
        
        test('rejects invalid media type', function () {
            $result = $this->formatter->validateMediaType('document');
            
            expect($result)->toBeFalse();
        });
        
        test('rejects empty media type', function () {
            $result = $this->formatter->validateMediaType('');
            
            expect($result)->toBeFalse();
        });
        
        test('is case-sensitive', function () {
            $result = $this->formatter->validateMediaType('IMAGE');
            
            expect($result)->toBeFalse();
        });
    });
});

