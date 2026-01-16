<?php

declare(strict_types=1);

use WhatsApp\Adapter\Models\Template;
use WhatsApp\Adapter\Models\IncomingMessage;
use WhatsApp\Adapter\Repositories\TemplateRepository;
use WhatsApp\Adapter\Repositories\MessageRepository;

/**
 * Property 3: Template Update Persistence
 * 
 * For any notificação válida de alteração ou remoção de template, 
 * o adapter deve registar a mudança na base de dados
 * 
 * Validates: Requirements 2.2, 2.3
 * Feature: whatsapp-hsm-adapter, Property 3: Template Update Persistence
 */

/**
 * Property 11: Incoming Message Persistence
 * 
 * For any mensagem válida recebida (resposta de cliente ou mensagem normal), 
 * o adapter deve armazenar ou encaminhar para o sistema de gestão de conversas
 * 
 * Validates: Requirements 5.4, 8.5, 10.5
 * Feature: whatsapp-hsm-adapter, Property 11: Incoming Message Persistence
 */

describe('Property 3: Template Update Persistence', function () {
    
    beforeEach(function () {
        // Setup in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create templates table
        $this->pdo->exec('
            CREATE TABLE templates (
                id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                language VARCHAR(10) NOT NULL,
                status VARCHAR(50) NOT NULL,
                category VARCHAR(50) NOT NULL,
                components TEXT NOT NULL,
                rejection_reason TEXT NULL,
                last_synced_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        $this->repository = new TemplateRepository($this->pdo);
    });
    
    test('saving a template persists it to the database', function () {
        $template = new Template(
            id: 'tpl_' . uniqid(),
            name: 'welcome_message',
            language: 'pt',
            status: 'approved',
            category: 'MARKETING',
            components: [
                ['type' => 'BODY', 'text' => 'Welcome {{1}}!']
            ]
        );
        
        // Save template
        $this->repository->save($template);
        
        // Retrieve template
        $retrieved = $this->repository->findById($template->id);
        
        // Verify persistence
        expect($retrieved)->not->toBeNull();
        expect($retrieved->id)->toBe($template->id);
        expect($retrieved->name)->toBe($template->name);
        expect($retrieved->language)->toBe($template->language);
        expect($retrieved->status)->toBe($template->status);
        expect($retrieved->category)->toBe($template->category);
        expect($retrieved->components)->toBe($template->components);
    })->repeat(10);
    
    test('updating a template persists the changes', function () {
        $templateId = 'tpl_' . uniqid();
        
        // Save initial template
        $template1 = new Template(
            id: $templateId,
            name: 'welcome_message',
            language: 'pt',
            status: 'pending',
            category: 'MARKETING',
            components: [['type' => 'BODY', 'text' => 'Welcome!']]
        );
        $this->repository->save($template1);
        
        // Update template
        $template2 = new Template(
            id: $templateId,
            name: 'welcome_message',
            language: 'pt',
            status: 'approved',
            category: 'MARKETING',
            components: [['type' => 'BODY', 'text' => 'Welcome {{1}}!']]
        );
        $this->repository->save($template2);
        
        // Retrieve template
        $retrieved = $this->repository->findById($templateId);
        
        // Verify update was persisted
        expect($retrieved)->not->toBeNull();
        expect($retrieved->status)->toBe('approved');
        expect($retrieved->components)->toBe($template2->components);
    })->repeat(10);
    
    test('deleting a template removes it from the database', function () {
        $template = new Template(
            id: 'tpl_' . uniqid(),
            name: 'test_template',
            language: 'en',
            status: 'approved',
            category: 'UTILITY',
            components: []
        );
        
        // Save template
        $this->repository->save($template);
        
        // Verify it exists
        expect($this->repository->findById($template->id))->not->toBeNull();
        
        // Delete template
        $this->repository->delete($template->id);
        
        // Verify it was deleted
        expect($this->repository->findById($template->id))->toBeNull();
    })->repeat(10);
});

describe('Property 11: Incoming Message Persistence', function () {
    
    beforeEach(function () {
        // Setup in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create messages table (simplified for incoming messages test)
        $this->pdo->exec('
            CREATE TABLE messages (
                id VARCHAR(255) PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                to_number VARCHAR(20) NOT NULL,
                from_number VARCHAR(20) NOT NULL,
                status VARCHAR(50) NOT NULL,
                content TEXT NOT NULL,
                sent_at TIMESTAMP NOT NULL,
                delivered_at TIMESTAMP NULL,
                read_at TIMESTAMP NULL,
                error_message TEXT NULL,
                metadata TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        $this->repository = new MessageRepository($this->pdo);
    });
    
    test('saving an incoming message persists it to the database', function () {
        $message = new \WhatsApp\Adapter\Models\Message(
            id: 'msg_' . uniqid(),
            type: 'text',
            toNumber: '+351912345678',
            fromNumber: '+351987654321',
            status: 'received',
            content: ['text' => 'Hello, this is a test message'],
            sentAt: new DateTimeImmutable()
        );
        
        // Save message
        $this->repository->save($message);
        
        // Retrieve message
        $retrieved = $this->repository->findById($message->id);
        
        // Verify persistence
        expect($retrieved)->not->toBeNull();
        expect($retrieved->id)->toBe($message->id);
        expect($retrieved->type)->toBe($message->type);
        expect($retrieved->toNumber)->toBe($message->toNumber);
        expect($retrieved->fromNumber)->toBe($message->fromNumber);
        expect($retrieved->status)->toBe($message->status);
        expect($retrieved->content)->toBe($message->content);
    })->repeat(10);
    
    test('updating message status persists the change', function () {
        $messageId = 'msg_' . uniqid();
        
        // Save initial message
        $message = new \WhatsApp\Adapter\Models\Message(
            id: $messageId,
            type: 'text',
            toNumber: '+351912345678',
            fromNumber: '+351987654321',
            status: 'pending',
            content: ['text' => 'Test'],
            sentAt: new DateTimeImmutable()
        );
        $this->repository->save($message);
        
        // Update status
        $this->repository->updateStatus($messageId, 'delivered', ['delivered_at' => time()]);
        
        // Retrieve message
        $retrieved = $this->repository->findById($messageId);
        
        // Verify status was updated
        expect($retrieved)->not->toBeNull();
        expect($retrieved->status)->toBe('delivered');
    })->repeat(10);
});
