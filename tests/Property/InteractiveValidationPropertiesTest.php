<?php

declare(strict_types=1);

use WhatsApp\Adapter\Models\Requests\InteractiveButtonsRequest;
use WhatsApp\Adapter\Models\Requests\InteractiveListRequest;

/**
 * Property 15: Interactive Button Count Validation
 * 
 * For any mensagem com botões interativos, o adapter deve validar que 
 * existem no máximo 3 botões, retornando erro se o limite for excedido
 * 
 * Validates: Requirements 9.1, 9.5
 * Feature: whatsapp-hsm-adapter, Property 15: Interactive Button Count Validation
 */

describe('Property 15: Interactive Button Count Validation', function () {

    test('validates maximum of 3 buttons', function () {
        // Test with 1 button (valid)
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [
                ['id' => '1', 'text' => 'Option 1']
            ]
        ))->not->toThrow(InvalidArgumentException::class);

        // Test with 3 buttons (valid - at limit)
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [
                ['id' => '1', 'text' => 'Option 1'],
                ['id' => '2', 'text' => 'Option 2'],
                ['id' => '3', 'text' => 'Option 3']
            ]
        ))->not->toThrow(InvalidArgumentException::class);

        // Test with 4 buttons (invalid - exceeds limit)
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
    });

    test('rejects empty button array', function () {
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: []
        ))->toThrow(InvalidArgumentException::class, 'must not be empty');
    });
});

/**
 * Property 16: Interactive List Item Count Validation
 * 
 * For any mensagem com lista interativa, o adapter deve validar que 
 * existem no máximo 10 itens no total, retornando erro se o limite for excedido
 * 
 * Validates: Requirements 9.2, 9.5
 * Feature: whatsapp-hsm-adapter, Property 16: Interactive List Item Count Validation
 */

describe('Property 16: Interactive List Item Count Validation', function () {

    test('validates maximum of 10 list items', function () {
        // Test with 5 items (valid)
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [[
                'items' => [
                    ['id' => '1', 'title' => 'Item 1'],
                    ['id' => '2', 'title' => 'Item 2'],
                    ['id' => '3', 'title' => 'Item 3'],
                    ['id' => '4', 'title' => 'Item 4'],
                    ['id' => '5', 'title' => 'Item 5']
                ]
            ]]
        ))->not->toThrow(InvalidArgumentException::class);

        // Test with 10 items (valid - at limit)
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [[
                'items' => array_map(
                    fn($i) => ['id' => (string)$i, 'title' => "Item $i"],
                    range(1, 10)
                )
            ]]
        ))->not->toThrow(InvalidArgumentException::class);

        // Test with 11 items (invalid - exceeds limit)
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
    });

    test('validates total items across multiple sections', function () {
        // Test with 10 items across 2 sections (valid)
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [
                [
                    'items' => [
                        ['id' => '1', 'title' => 'Item 1'],
                        ['id' => '2', 'title' => 'Item 2'],
                        ['id' => '3', 'title' => 'Item 3'],
                        ['id' => '4', 'title' => 'Item 4'],
                        ['id' => '5', 'title' => 'Item 5']
                    ]
                ],
                [
                    'items' => [
                        ['id' => '6', 'title' => 'Item 6'],
                        ['id' => '7', 'title' => 'Item 7'],
                        ['id' => '8', 'title' => 'Item 8'],
                        ['id' => '9', 'title' => 'Item 9'],
                        ['id' => '10', 'title' => 'Item 10']
                    ]
                ]
            ]
        ))->not->toThrow(InvalidArgumentException::class);
    });

    test('rejects empty sections array', function () {
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: []
        ))->toThrow(InvalidArgumentException::class, 'must not be empty');
    });
});

/**
 * Property 17: Interactive Element Uniqueness
 * 
 * For any botão ou item de lista, o adapter deve validar que tem um 
 * ID único e texto descritivo
 * 
 * Validates: Requirements 9.3
 * Feature: whatsapp-hsm-adapter, Property 17: Interactive Element Uniqueness
 */

describe('Property 17: Interactive Element Uniqueness', function () {

    test('validates button ID uniqueness', function () {
        // Test with unique button IDs (valid)
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [
                ['id' => '1', 'text' => 'Option 1'],
                ['id' => '2', 'text' => 'Option 2'],
                ['id' => '3', 'text' => 'Option 3']
            ]
        ))->not->toThrow(InvalidArgumentException::class);

        // Test with duplicate button IDs (invalid)
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [
                ['id' => '1', 'text' => 'Option 1'],
                ['id' => '1', 'text' => 'Option 2']
            ]
        ))->toThrow(InvalidArgumentException::class, 'Duplicate button ID');
    });

    test('validates button has required text field', function () {
        // Test button without text (invalid)
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [
                ['id' => '1']
            ]
        ))->toThrow(InvalidArgumentException::class, 'must have "text"');

        // Test button with empty text (invalid)
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [
                ['id' => '1', 'text' => '']
            ]
        ))->toThrow(InvalidArgumentException::class, 'must have "text"');
    });

    test('validates list item ID uniqueness', function () {
        // Test with unique item IDs (valid)
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [[
                'items' => [
                    ['id' => '1', 'title' => 'Item 1'],
                    ['id' => '2', 'title' => 'Item 2'],
                    ['id' => '3', 'title' => 'Item 3']
                ]
            ]]
        ))->not->toThrow(InvalidArgumentException::class);

        // Test with duplicate item IDs (invalid)
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
    });

    test('validates list item has required title field', function () {
        // Test item without title (invalid)
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [[
                'items' => [
                    ['id' => '1']
                ]
            ]]
        ))->toThrow(InvalidArgumentException::class, 'must have a "title"');

        // Test item with empty title (invalid)
        expect(fn() => new InteractiveListRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttonText: 'Select',
            sections: [[
                'items' => [
                    ['id' => '1', 'title' => '']
                ]
            ]]
        ))->toThrow(InvalidArgumentException::class, 'must have a "title"');
    });
});

/**
 * Property 18: Interactive Button Type Support
 * 
 * For any botão interativo (resposta rápida, URL, chamada telefónica), 
 * o adapter deve suportar o envio através da API da Infobip
 * 
 * Validates: Requirements 9.6
 * Feature: whatsapp-hsm-adapter, Property 18: Interactive Button Type Support
 */

describe('Property 18: Interactive Button Type Support', function () {

    test('allows buttons without explicit type', function () {
        // Buttons without type field should be valid (defaults to reply)
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [
                ['id' => '1', 'text' => 'Option 1'],
                ['id' => '2', 'text' => 'Option 2']
            ]
        ))->not->toThrow(InvalidArgumentException::class);
    });

    test('allows buttons with valid type field', function () {
        // Buttons with type field should be valid
        expect(fn() => new InteractiveButtonsRequest(
            to: '+351912345678',
            bodyText: 'Choose an option',
            buttons: [
                ['id' => '1', 'text' => 'Option 1', 'type' => 'reply']
            ]
        ))->not->toThrow(InvalidArgumentException::class);
    });
});
