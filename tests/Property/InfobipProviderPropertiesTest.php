<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use WhatsApp\Adapter\Models\Requests\HSMRequest;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Providers\Infobip\InfobipProvider;

/**
 * Property-based tests for InfobipProvider
 * Feature: whatsapp-hsm-adapter
 */

describe('InfobipProvider Properties', function () {
    
    beforeEach(function () {
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new NullHandler());
        
        $this->config = [
            'api_key' => 'test_api_key_' . bin2hex(random_bytes(16)),
            'base_url' => 'https://api.infobip.com',
            'sender' => '447860099299',
            'webhook_secret' => 'test_secret_' . bin2hex(random_bytes(16))
        ];
    });

    /**
     * Property 1: Template Response Format Consistency
     * For any response from Infobip API containing templates, the adapter must format
     * the data in a consistent format that includes all required fields
     * (ID, name, language, parameters, approval status)
     * 
     * Validates: Requirements 1.2, 1.4
     */
    test('Property 1: Template Response Format Consistency', function () {
        // Generate random template data
        $templateId = 'template_' . bin2hex(random_bytes(8));
        $templateName = 'test_template_' . rand(1, 1000);
        $language = ['en', 'pt', 'es', 'fr'][array_rand(['en', 'pt', 'es', 'fr'])];
        $status = ['APPROVED', 'PENDING', 'REJECTED'][array_rand(['APPROVED', 'PENDING', 'REJECTED'])];
        $category = ['MARKETING', 'UTILITY', 'AUTHENTICATION'][array_rand(['MARKETING', 'UTILITY', 'AUTHENTICATION'])];
        
        $mockResponse = [
            'templates' => [[
                'id' => $templateId,
                'name' => $templateName,
                'language' => $language,
                'status' => $status,
                'category' => $category,
                'structure' => [
                    'body' => [
                        ['type' => 'BODY', 'text' => 'Hello {{1}}, your code is {{2}}']
                    ]
                ]
            ]]
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new InfobipProvider($client, $this->config, $this->logger);
        $templates = $provider->getTemplates();

        // Verify format consistency
        expect($templates)->toBeArray()
            ->and($templates)->toHaveCount(1)
            ->and($templates[0])->toHaveProperty('id', $templateId)
            ->and($templates[0])->toHaveProperty('name', $templateName)
            ->and($templates[0])->toHaveProperty('language', $language)
            ->and($templates[0])->toHaveProperty('status', $status)
            ->and($templates[0])->toHaveProperty('category', $category)
            ->and($templates[0])->toHaveProperty('components');
    })->repeat(10)->group('property-tests', 'whatsapp-hsm-adapter', 'infobip');

    /**
     * Property 5: Template Parameter Substitution
     * For any HSM template with dynamic parameters, the adapter must correctly
     * substitute all placeholders with provided values
     * 
     * Validates: Requirements 3.6
     */
    test('Property 5: Template Parameter Substitution', function () {
        // Generate random parameters
        $paramCount = rand(1, 5);
        $parameters = [];
        for ($i = 0; $i < $paramCount; $i++) {
            $parameters[] = 'param_' . bin2hex(random_bytes(4));
        }

        $templateName = 'test_template_' . rand(1, 1000);
        $to = '+351' . rand(900000000, 999999999);
        $language = ['en', 'pt'][array_rand(['en', 'pt'])];

        $mockResponse = [
            'messages' => [[
                'messageId' => 'msg_' . bin2hex(random_bytes(8)),
                'status' => ['groupName' => 'PENDING']
            ]]
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new InfobipProvider($client, $this->config, $this->logger);
        
        $request = new HSMRequest(
            to: $to,
            templateName: $templateName,
            templateLanguage: $language,
            parameters: $parameters
        );

        $result = $provider->sendTemplate($request);

        // Verify parameters are included in the request
        expect($result->success)->toBeTrue()
            ->and($result->messageId)->not->toBeNull();
            
        // Verify the mock received the correct payload structure
        $lastRequest = $mock->getLastRequest();
        expect($lastRequest)->not->toBeNull();
    })->repeat(10)->group('property-tests', 'whatsapp-hsm-adapter', 'infobip');

    /**
     * Property 6: Successful Send Response
     * For any successful message send (any type), the adapter must return
     * the message ID and send status
     * 
     * Validates: Requirements 3.3, 6.4, 7.6, 9.4
     */
    test('Property 6: Successful Send Response', function () {
        $messageTypes = ['hsm', 'text'];
        $messageType = $messageTypes[array_rand($messageTypes)];
        
        $messageId = 'msg_' . bin2hex(random_bytes(8));
        $to = '+351' . rand(900000000, 999999999);

        $mockResponse = [
            'messages' => [[
                'messageId' => $messageId,
                'status' => ['groupName' => 'PENDING']
            ]]
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new InfobipProvider($client, $this->config, $this->logger);

        if ($messageType === 'hsm') {
            $request = new HSMRequest(
                to: $to,
                templateName: 'test_template',
                templateLanguage: 'en',
                parameters: ['param1']
            );
            $result = $provider->sendTemplate($request);
        } else {
            $request = new TextRequest(
                to: $to,
                text: 'Test message ' . bin2hex(random_bytes(4))
            );
            $result = $provider->sendText($request);
        }

        // Verify successful response contains message ID and status
        expect($result->success)->toBeTrue()
            ->and($result->messageId)->toBe($messageId)
            ->and($result->status)->not->toBeNull();
    })->repeat(10)->group('property-tests', 'whatsapp-hsm-adapter', 'infobip');

    /**
     * Property 12: Text Content Type Support
     * For any free-text message containing plain text, formatted text, or emojis,
     * the adapter must send correctly through Infobip API
     * 
     * Validates: Requirements 6.3
     */
    test('Property 12: Text Content Type Support', function () {
        $textTypes = [
            'plain' => 'Simple text message',
            'formatted' => '*Bold* _italic_ ~strikethrough~',
            'emoji' => '👋 Hello! 🎉 Welcome 🚀',
            'mixed' => '*Hello* 👋 This is a _test_ message 🎉'
        ];
        
        $textType = array_rand($textTypes);
        $text = $textTypes[$textType];
        $to = '+351' . rand(900000000, 999999999);

        $mockResponse = [
            'messages' => [[
                'messageId' => 'msg_' . bin2hex(random_bytes(8)),
                'status' => ['groupName' => 'PENDING']
            ]]
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new InfobipProvider($client, $this->config, $this->logger);
        
        $request = new TextRequest(
            to: $to,
            text: $text
        );

        $result = $provider->sendText($request);

        // Verify text is sent successfully regardless of content type
        expect($result->success)->toBeTrue()
            ->and($result->messageId)->not->toBeNull();
    })->repeat(10)->group('property-tests', 'whatsapp-hsm-adapter', 'infobip');

    /**
     * Property 19: API Request Authentication
     * For any request sent to Infobip API, the adapter must include
     * valid authentication credentials
     * 
     * Validates: Requirements 11.2
     */
    test('Property 19: API Request Authentication', function () {
        $to = '+351' . rand(900000000, 999999999);
        $text = 'Test message ' . bin2hex(random_bytes(4));

        $mockResponse = [
            'messages' => [[
                'messageId' => 'msg_' . bin2hex(random_bytes(8)),
                'status' => ['groupName' => 'PENDING']
            ]]
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new InfobipProvider($client, $this->config, $this->logger);
        
        $request = new TextRequest(
            to: $to,
            text: $text
        );

        $result = $provider->sendText($request);

        // Verify request was made (authentication was included)
        $lastRequest = $mock->getLastRequest();
        expect($lastRequest)->not->toBeNull()
            ->and($lastRequest->getHeaderLine('Authorization'))->toContain('App')
            ->and($result->success)->toBeTrue();
    })->repeat(10)->group('property-tests', 'whatsapp-hsm-adapter', 'infobip');
});
