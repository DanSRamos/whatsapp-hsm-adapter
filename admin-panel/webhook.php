<?php

/**
 * Webhook endpoint for receiving WhatsApp messages from Infobip
 * Configure this URL in Infobip portal: https://your-domain.com/admin-panel/webhook.php
 */

// Log all incoming requests
$logFile = __DIR__ . '/webhook.log';
$messagesFile = __DIR__ . '/messages.json';

// Get raw input
$input = file_get_contents('php://input');
$timestamp = date('Y-m-d H:i:s');

// Log the webhook
file_put_contents($logFile, "\n\n=== Webhook Received: $timestamp ===\n", FILE_APPEND);
file_put_contents($logFile, "Headers:\n", FILE_APPEND);
foreach (getallheaders() as $name => $value) {
    file_put_contents($logFile, "$name: $value\n", FILE_APPEND);
}
file_put_contents($logFile, "\nBody:\n$input\n", FILE_APPEND);

// Parse JSON
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Load existing messages
$messages = [];
if (file_exists($messagesFile)) {
    $messages = json_decode(file_get_contents($messagesFile), true) ?? [];
}

// Process incoming messages
if (isset($data['results'])) {
    foreach ($data['results'] as $result) {
        $message = [
            'from' => $result['from'] ?? '',
            'to' => $result['to'] ?? '',
            'messageId' => $result['messageId'] ?? '',
            'timestamp' => $result['receivedAt'] ?? date('c'),
            'type' => 'incoming'
        ];
        
        // Extract message content based on type
        if (isset($result['message']['text'])) {
            $message['text'] = $result['message']['text'];
            $message['messageType'] = 'text';
        } elseif (isset($result['message']['button'])) {
            $message['text'] = 'Button: ' . ($result['message']['button']['text'] ?? 'Unknown');
            $message['messageType'] = 'button';
        } elseif (isset($result['message']['image'])) {
            $message['text'] = 'Image: ' . ($result['message']['image']['caption'] ?? 'No caption');
            $message['messageType'] = 'image';
        } else {
            $message['text'] = 'Unknown message type';
            $message['messageType'] = 'unknown';
        }
        
        $message['raw'] = $result;
        $messages[] = $message;
    }
}

// Process delivery reports
if (isset($data['results']) && isset($data['results'][0]['status'])) {
    foreach ($data['results'] as $result) {
        if (isset($result['status'])) {
            $message = [
                'from' => 'SYSTEM',
                'to' => $result['to'] ?? '',
                'messageId' => $result['messageId'] ?? '',
                'timestamp' => date('c'),
                'type' => 'delivery_report',
                'text' => 'Delivery Status: ' . ($result['status']['name'] ?? 'Unknown'),
                'messageType' => 'delivery_report',
                'raw' => $result
            ];
            $messages[] = $message;
        }
    }
}

// Save messages
file_put_contents($messagesFile, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Respond to Infobip
http_response_code(200);
echo json_encode(['status' => 'ok', 'received' => count($data['results'] ?? [])]);
