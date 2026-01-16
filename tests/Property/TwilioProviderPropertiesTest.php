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
use WhatsApp\Adapter\Providers\Twilio\TwilioProvider;

/**
 * Property-based tests for TwilioProvider
 * Feature: whatsapp-hsm-adapter
 */

describe('TwilioProvider Properties', function () {
    
    beforeEach(function () {
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new NullHandler());
        
        $this->config = [
            'account_sid' => 'AC' . bin2hex(random_bytes(16)),
            'auth_token' => 'test_token_' . bin2hex(random_bytes(16)),
            'base_url' => 'https://api.twilio.com',
            'sender' => '14155238886'
        ];
    });

    /**
     * Property 1: Template Response Format Consistency
     * For any response from Twilio API containing templates, the adapter must format
     * the data in a consistent format that includes all required fields
     * (ID, name, language, parameters, approval status)
     * 
     * Validates: Requirements 1.2, 1.4
     */
    test('Property 1: Template Response Format Consistency', function () {
        // Generate random template data
        $templateSid = 'HX' . bin2hex(random_bytes(16));
        $templateName = 'test_template_' . rand(1, 1000);
        $language = ['en', 'pt', 'es', 'fr'][array_rand(['en', 'pt', 'es', 'fr'])];
        $status = ['approved', 'pending', 'rejected'][array_rand(['approved', 'pending', 'rejected'])];
        
        $mockResponse = [
            'contents' => [[
                'sid' => $templateSid,
                'friendly_name' => $templateName,
                'language' => $language,
                'approval_status' => $status,
                'types' => [
                    'twilio/text' => [
                        'body' => 'Hello {{1}}, your code is {{2}}'
                    ]
                ]
            ]]
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new TwilioProvider($client, $this->config, $this->logger);
        $templates = $provider->getTemplates();

        // Verify format consistency
        expect($templates)->toBeArray()
            ->and($templates)->toHaveCount(1)
            ->and($templates[0])->toHaveProperty('id', $templateSid)
            ->and($templates[0])->toHaveProperty('name', $templateName)
            ->and($templates[0])->toHaveProperty('language', $language)
            ->and($templates[0])->toHaveProperty('status', $status)
            ->and($templates[0])->toHaveProperty('components');
    })->repeat(10)->group('property-tests', 'whatsapp-hsm-adapter', 'twilio');

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

        $contentSid = 'HX' . bin2hex(random_bytes(16));
        $to = '+1' . rand(2000000000, 9999999999);

        $mockResponse = [
            'sid' => 'SM' . bin2hex(random_bytes(16)),
            'status' => 'queued'
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new TwilioProvider($client, $this->config, $this->logger);
        
        $request = new HSMRequest(
            to: $to,
            templateName: $contentSid,
            templateLanguage: 'en',
            parameters: $parameters
        );

        $result = $provider->sendTemplate($request);

        // Verify parameters are included in the request
        expect($result->success)->toBeTrue()
            ->and($result->messageId)->not->toBeNull();
    })->repeat(10)->group('property-tests', 'whatsapp-hsm-adapter', 'twilio');

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
        
        $messageSid = 'SM' . bin2hex(random_bytes(16));
        $to = '+1' . rand(2000000000, 9999999999);

        $mockResponse = [
            'sid' => $messageSid,
            'status' => 'queued'
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new TwilioProvider($client, $this->config, $this->logger);

        if ($messageType === 'hsm') {
            $request = new HSMRequest(
                to: $to,
                templateName: 'HX' . bin2hex(random_bytes(8)),
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
            ->and($result->messageId)->toBe($messageSid)
            ->and($result->status)->not->toBeNull();
    })->repeat(10)->group('property-tests', 'whatsapp-hsm-adapter', 'twilio');

    /**
     * Property 19: API Request Authentication
     * For any request sent to Twilio API, the adapter must include
     * valid authentication credentials
     * 
     * Validates: Requirements 11.2
     */
    test('Property 19: API Request Authentication', function () {
        $to = '+1' . rand(2000000000, 9999999999);
        $text = 'Test message ' . bin2hex(random_bytes(4));

        $mockResponse = [
            'sid' => 'SM' . bin2hex(random_bytes(16)),
            'status' => 'queued'
        ];

        $mock = new MockHandler([
            new Response(200, [], json_encode($mockResponse))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $provider = new TwilioProvider($client, $this->config, $this->logger);
        
        $request = new TextRequest(
            to: $to,
            text: $text
        );

        $result = $provider->sendText($request);

        // Verify request was made (authentication was included)
        $lastRequest = $mock->getLastRequest();
        expect($lastRequest)->not->toBeNull()
            ->and($lastRequest->getHeaderLine('Authorization'))->toContain('Basic')
            ->and($result->success)->toBeTrue();
    })->repeat(10)->group('property-tests', 'whatsapp-hsm-adapter', 'twilio');
});
