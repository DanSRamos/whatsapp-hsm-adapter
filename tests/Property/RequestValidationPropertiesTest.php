<?php

declare(strict_types=1);

use WhatsApp\Adapter\Models\Requests\HSMRequest;
use WhatsApp\Adapter\Models\Requests\TextRequest;
use WhatsApp\Adapter\Models\Requests\MediaRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;

/**
 * Property 4: Request Parameter Validation
 * 
 * For any pedido de envio de mensagem (HSM, texto, media, interativa), 
 * o adapter deve validar todos os parâmetros obrigatórios e retornar 
 * erro de validação específico se algum estiver em falta ou inválido
 * 
 * Validates: Requirements 3.1, 3.4, 6.1, 7.7, 9.5
 * Feature: whatsapp-hsm-adapter, Property 4: Request Parameter Validation
 */

describe('Property 4: Request Parameter Validation', function () {
    
    test('HSMRequest validates required parameters', function () {
        // Test missing 'to' parameter
        expect(fn() => new HSMRequest(
            to: '',
            templateName: 'welcome',
            templateLanguage: 'pt'
        ))->toThrow(InvalidArgumentException::class, 'Field "to" is required');

        // Test missing 'templateName' parameter
        expect(fn() => new HSMRequest(
            to: '+351912345678',
            templateName: '',
            templateLanguage: 'pt'
        ))->toThrow(InvalidArgumentException::class, 'Field "templateName" is required');

        // Test missing 'templateLanguage' parameter
        expect(fn() => new HSMRequest(
            to: '+351912345678',
            templateName: 'welcome',
            templateLanguage: ''
        ))->toThrow(InvalidArgumentException::class, 'Field "templateLanguage" is required');

        // Test valid request does not throw
        expect(fn() => new HSMRequest(
            to: '+351912345678',
            templateName: 'welcome',
            templateLanguage: 'pt'
        ))->not->toThrow(InvalidArgumentException::class);
    })->repeat(10);

    test('TextRequest validates required parameters', function () {
        // Test missing 'to' parameter
        expect(fn() => new TextRequest(
            to: '',
            text: 'Hello World'
        ))->toThrow(InvalidArgumentException::class, 'Field "to" is required');

        // Test missing 'text' parameter
        expect(fn() => new TextRequest(
            to: '+351912345678',
            text: ''
        ))->toThrow(InvalidArgumentException::class, 'Field "text" is required');

        // Test valid request does not throw
        expect(fn() => new TextRequest(
            to: '+351912345678',
            text: 'Hello World'
        ))->not->toThrow(InvalidArgumentException::class);
    })->repeat(10);

    test('MediaRequest validates required parameters and media type', function () {
        // Test missing 'to' parameter
        expect(fn() => new MediaRequest(
            to: '',
            mediaType: 'image',
            mediaUrl: 'https://example.com/image.jpg'
        ))->toThrow(InvalidArgumentException::class, 'Field "to" is required');

        // Test missing 'mediaType' parameter
        expect(fn() => new MediaRequest(
            to: '+351912345678',
            mediaType: '',
            mediaUrl: 'https://example.com/image.jpg'
        ))->toThrow(InvalidArgumentException::class, 'Field "mediaType" is required');

        // Test missing 'mediaUrl' parameter
        expect(fn() => new MediaRequest(
            to: '+351912345678',
            mediaType: 'image',
            mediaUrl: ''
        ))->toThrow(InvalidArgumentException::class, 'Field "mediaUrl" is required');

        // Test invalid media type
        expect(fn() => new MediaRequest(
            to: '+351912345678',
            mediaType: 'invalid',
            mediaUrl: 'https://example.com/file.txt'
        ))->toThrow(InvalidArgumentException::class, 'Invalid media type');

        // Test valid request does not throw
        expect(fn() => new MediaRequest(
            to: '+351912345678',
            mediaType: 'image',
            mediaUrl: 'https://example.com/image.jpg'
        ))->not->toThrow(InvalidArgumentException::class);
    })->repeat(10);

    test('InteractiveButtonsRequest validates required parameters and button constraints', function () {
        // Test missing 'to' parameter
        expect(fn() => new InteractiveButtonsRequest(
            to: '',
            bodyText: 'Choose an option',
            buttons: [['id' => '1', 'text' => 'Option 1']]
        ))->toThrow(InvalidArgumentException::class, 'Field "to" is required');

        // Test missing 'bodyText' parameter
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: '',
            buttons: [['id' => '1', 'text' => 'Option 1']]
        ))->toThrow(InvalidArgumentException::class, 'Field "bodyText" is required');

        // Test empty buttons array
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: []
        ))->toThrow(InvalidArgumentException::class, 'Field "buttons" is required and must not be empty');

        // Test too many buttons (more than 3)
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [
                ['id' => '1', 'text' => 'Option 1'],
                ['id' => '2', 'text' => 'Option 2'],
                ['id' => '3', 'text' => 'Option 3'],
                ['id' => '4', 'text' => 'Option 4']
            ]
        ))->toThrow(InvalidArgumentException::class, 'Maximum 3 buttons allowed');

        // Test button without id
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [['text' => 'Option 1']]
        ))->toThrow(InvalidArgumentException::class, 'must have an "id"');

        // Test button without text
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [['id' => '1']]
        ))->toThrow(InvalidArgumentException::class, 'must have "text"');

        // Test duplicate button IDs
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [
                ['id' => '1', 'text' => 'Option 1'],
                ['id' => '1', 'text' => 'Option 2']
            ]
        ))->toThrow(InvalidArgumentException::class, 'Duplicate button ID');

        // Test valid request does not throw
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [
                ['id' => '1', 'text' => 'Option 1'],
                ['id' => '2', 'text' => 'Option 2']
            ]
        ))->not->toThrow(InvalidArgumentException::class);
    })->repeat(10);

    test('InteractiveListRequest validates required parameters and item constraints', function () {
        // Test missing 'to' parameter
        expect(fn() => new InteractiveListRequest(
            to: '',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [['items' => [['id' => '1', 'title' => 'Item 1']]]]
        ))->toThrow(InvalidArgumentException::class, 'Field "to" is required');

        // Test missing 'bodyText' parameter
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: '',
            buttonText: 'Select',
            sections: [['items' => [['id' => '1', 'title' => 'Item 1']]]]
        ))->toThrow(InvalidArgumentException::class, 'Field "bodyText" is required');

        // Test missing 'buttonText' parameter
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: '',
            sections: [['items' => [['id' => '1', 'title' => 'Item 1']]]]
        ))->toThrow(InvalidArgumentException::class, 'Field "buttonText" is required');

        // Test empty sections array
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: []
        ))->toThrow(InvalidArgumentException::class, 'Field "sections" is required and must not be empty');

        // Test too many items (more than 10)
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [[
                'items' => array_map(
                    fn($i) => ['id' => (string)$i, 'title' => "Item $i"],
                    range(1, 11)
                )
            ]]
        ))->toThrow(InvalidArgumentException::class, 'Maximum 10 items allowed');

        // Test item without id
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [['items' => [['title' => 'Item 1']]]]
        ))->toThrow(InvalidArgumentException::class, 'must have an "id"');

        // Test item without title
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [['items' => [['id' => '1']]]]
        ))->toThrow(InvalidArgumentException::class, 'must have a "title"');

        // Test duplicate item IDs
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [[
                'items' => [
                    ['id' => '1', 'title' => 'Item 1'],
                    ['id' => '1', 'title' => 'Item 2']
                ]
            ]]
        ))->toThrow(InvalidArgumentException::class, 'Duplicate item ID');

        // Test valid request does not throw
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [[
                'items' => [
                    ['id' => '1', 'title' => 'Item 1'],
                    ['id' => '2', 'title' => 'Item 2']
                ]
            ]]
        ))->not->toThrow(InvalidArgumentException::class);
    })->repeat(10);
});
