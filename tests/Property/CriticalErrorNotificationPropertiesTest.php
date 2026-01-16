<?php

declare(strict_types=1);

use Monolog\Logger;
use Monolog\Handler\TestHandler;
use WhatsApp\Adapter\Services\CriticalErrorNotifier;

/**
 * Property 22: Critical Error Notification
 * 
 * For any erro crítico que ocorra, o adapter deve notificar os administradores do sistema
 * 
 * Validates: Requirements 12.4
 * 
 * Feature: whatsapp-hsm-adapter, Property 22: Critical Error Notification
 */

beforeEach(function () {
    // Create test handler to capture log records
    $this->testHandler = new TestHandler();
    
    // Create logger with test handler
    $this->logger = new Logger('test');
    $this->logger->pushHandler($this->testHandler);
});

test('Property 22.1: Critical errors are logged at CRITICAL level', function () {
    $notifier = new CriticalErrorNotifier($this->logger);
    
    $errorMessage = 'Database connection failed';
    $context = [
        'error' => 'Connection timeout',
        'host' => 'localhost',
        'port' => 3306
    ];
    
    $notifier->notifyCriticalError($errorMessage, $context);
    
    // Verify critical log was created
    expect($this->testHandler->hasCriticalRecords())->toBeTrue();
    
    // Verify log contains the error message
    $records = $this->testHandler->getRecords();
    $criticalRecords = array_filter($records, fn($r) => $r['level'] === Logger::CRITICAL);
    
    expect(count($criticalRecords))->toBeGreaterThan(0);
    
    // Verify at least one critical record contains our message
    $hasMessage = false;
    foreach ($criticalRecords as $record) {
        if (isset($record['context']['message']) && $record['context']['message'] === $errorMessage) {
            $hasMessage = true;
            break;
        }
    }
    
    expect($hasMessage)->toBeTrue();
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'notifications');

test('Property 22.2: Notification includes error context', function () {
    $notifier = new CriticalErrorNotifier($this->logger);
    
    $errorMessage = 'API rate limit exceeded';
    $context = [
        'provider' => 'infobip',
        'endpoint' => '/whatsapp/1/message/template',
        'rate_limit' => 100,
        'current_count' => 150
    ];
    
    $notifier->notifyCriticalError($errorMessage, $context);
    
    // Verify critical log contains context
    $records = $this->testHandler->getRecords();
    $criticalRecords = array_filter($records, fn($r) => $r['level'] === Logger::CRITICAL);
    
    expect(count($criticalRecords))->toBeGreaterThan(0);
    
    // Verify at least one record has the context
    $hasContext = false;
    foreach ($criticalRecords as $record) {
        if (isset($record['context']['context']) && is_array($record['context']['context'])) {
            $recordContext = $record['context']['context'];
            if (isset($recordContext['provider']) && $recordContext['provider'] === 'infobip') {
                $hasContext = true;
                break;
            }
        }
    }
    
    expect($hasContext)->toBeTrue();
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'notifications');

test('Property 22.3: Notification includes timestamp', function () {
    $notifier = new CriticalErrorNotifier($this->logger);
    
    $errorMessage = 'Webhook validation failed';
    $context = ['ip' => '192.168.1.1'];
    
    $notifier->notifyCriticalError($errorMessage, $context);
    
    // Verify critical log contains timestamp
    $records = $this->testHandler->getRecords();
    $criticalRecords = array_filter($records, fn($r) => $r['level'] === Logger::CRITICAL);
    
    expect(count($criticalRecords))->toBeGreaterThan(0);
    
    // Verify at least one record has timestamp
    $hasTimestamp = false;
    foreach ($criticalRecords as $record) {
        if (isset($record['context']['timestamp'])) {
            $hasTimestamp = true;
            break;
        }
    }
    
    expect($hasTimestamp)->toBeTrue();
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'notifications');

test('Property 22.4: Email notification is attempted when configured', function () {
    $config = [
        'email' => [
            'enabled' => true,
            'to' => ['admin@example.com'],
            'from' => 'noreply@whatsapp-adapter.local'
        ]
    ];
    
    $notifier = new CriticalErrorNotifier($this->logger, $config);
    
    $errorMessage = 'Critical system failure';
    $context = ['component' => 'message_service'];
    
    $result = $notifier->notifyCriticalError($errorMessage, $context);
    
    // Verify notification was attempted (returns true)
    expect($result)->toBeTrue();
    
    // Verify log indicates email would be sent
    $records = $this->testHandler->getRecords();
    $infoRecords = array_filter($records, fn($r) => $r['level'] === Logger::INFO);
    
    $hasEmailLog = false;
    foreach ($infoRecords as $record) {
        if (str_contains($record['message'], 'email notification')) {
            $hasEmailLog = true;
            break;
        }
    }
    
    expect($hasEmailLog)->toBeTrue();
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'notifications');

test('Property 22.5: Slack notification is attempted when configured', function () {
    $config = [
        'slack' => [
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/TEST/WEBHOOK/URL'
        ]
    ];
    
    $notifier = new CriticalErrorNotifier($this->logger, $config);
    
    $errorMessage = 'Provider API down';
    $context = ['provider' => 'infobip', 'status_code' => 503];
    
    $result = $notifier->notifyCriticalError($errorMessage, $context);
    
    // Verify notification was attempted (returns true)
    expect($result)->toBeTrue();
    
    // Verify log indicates Slack would be sent
    $records = $this->testHandler->getRecords();
    $infoRecords = array_filter($records, fn($r) => $r['level'] === Logger::INFO);
    
    $hasSlackLog = false;
    foreach ($infoRecords as $record) {
        if (str_contains($record['message'], 'Slack notification')) {
            $hasSlackLog = true;
            break;
        }
    }
    
    expect($hasSlackLog)->toBeTrue();
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'notifications');

test('Property 22.6: Webhook notification is attempted when configured', function () {
    $config = [
        'webhook' => [
            'enabled' => true,
            'url' => 'https://monitoring.example.com/webhooks/critical'
        ]
    ];
    
    $notifier = new CriticalErrorNotifier($this->logger, $config);
    
    $errorMessage = 'Data corruption detected';
    $context = ['table' => 'messages', 'affected_rows' => 42];
    
    $result = $notifier->notifyCriticalError($errorMessage, $context);
    
    // Verify notification was attempted (returns true)
    expect($result)->toBeTrue();
    
    // Verify log indicates webhook would be sent
    $records = $this->testHandler->getRecords();
    $infoRecords = array_filter($records, fn($r) => $r['level'] === Logger::INFO);
    
    $hasWebhookLog = false;
    foreach ($infoRecords as $record) {
        if (str_contains($record['message'], 'webhook notification')) {
            $hasWebhookLog = true;
            break;
        }
    }
    
    expect($hasWebhookLog)->toBeTrue();
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'notifications');

test('Property 22.7: Multiple notification channels can be enabled simultaneously', function () {
    $config = [
        'email' => [
            'enabled' => true,
            'to' => ['admin@example.com']
        ],
        'slack' => [
            'enabled' => true,
            'webhook_url' => 'https://hooks.slack.com/services/TEST/WEBHOOK/URL'
        ],
        'webhook' => [
            'enabled' => true,
            'url' => 'https://monitoring.example.com/webhooks/critical'
        ]
    ];
    
    $notifier = new CriticalErrorNotifier($this->logger, $config);
    
    $errorMessage = 'Multiple system failures';
    $context = ['severity' => 'high'];
    
    $result = $notifier->notifyCriticalError($errorMessage, $context);
    
    // Verify notification was attempted (returns true)
    expect($result)->toBeTrue();
    
    // Verify logs indicate all three channels would be used
    $records = $this->testHandler->getRecords();
    $infoRecords = array_filter($records, fn($r) => $r['level'] === Logger::INFO);
    
    $hasEmail = false;
    $hasSlack = false;
    $hasWebhook = false;
    
    foreach ($infoRecords as $record) {
        if (str_contains($record['message'], 'email notification')) {
            $hasEmail = true;
        }
        if (str_contains($record['message'], 'Slack notification')) {
            $hasSlack = true;
        }
        if (str_contains($record['message'], 'webhook notification')) {
            $hasWebhook = true;
        }
    }
    
    expect($hasEmail)->toBeTrue();
    expect($hasSlack)->toBeTrue();
    expect($hasWebhook)->toBeTrue();
})->repeat(100)->group('property-tests', 'whatsapp-hsm-adapter', 'notifications');
