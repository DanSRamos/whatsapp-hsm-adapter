<?php

/**
 * Webhook Receiver para Mensagens Recebidas da Infobip
 * 
 * Configure este URL na Infobip:
 * https://seu-dominio.com/webhook_receiver.php
 */

// Log de todas as requisições recebidas
$logFile = __DIR__ . '/../storage/logs/webhook_incoming.log';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Capturar dados recebidos
$rawInput = file_get_contents('php://input');
$headers = getallheaders();
$timestamp = date('Y-m-d H:i:s');

// Log completo
$logEntry = [
    'timestamp' => $timestamp,
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => $headers,
    'raw_input' => $rawInput,
    'parsed_input' => json_decode($rawInput, true)
];

file_put_contents(
    $logFile,
    json_encode($logEntry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n" . str_repeat("=", 80) . "\n\n",
    FILE_APPEND
);

// Processar webhook
header('Content-Type: application/json');

try {
    $payload = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    // Processar mensagens recebidas
    if (isset($payload['results']) && is_array($payload['results'])) {
        foreach ($payload['results'] as $message) {
            $messageId = $message['messageId'] ?? 'N/A';
            $from = $message['from'] ?? 'N/A';
            $to = $message['to'] ?? 'N/A';
            $receivedAt = $message['receivedAt'] ?? 'N/A';
            
            // Extrair conteúdo da mensagem
            $messageType = $message['message']['type'] ?? 'UNKNOWN';
            $messageText = $message['message']['text'] ?? null;
            
            // Salvar em arquivo separado para fácil consulta
            $messagesFile = __DIR__ . '/../storage/logs/received_messages.log';
            $messageEntry = sprintf(
                "[%s] De: %s | Para: %s | Tipo: %s | Texto: %s\n",
                $receivedAt,
                $from,
                $to,
                $messageType,
                $messageText ?? '(sem texto)'
            );
            
            file_put_contents($messagesFile, $messageEntry, FILE_APPEND);
            
            // Aqui você pode processar a mensagem
            // Por exemplo: salvar no banco de dados, enviar notificação, etc.
            
            echo "Mensagem processada: $messageId\n";
        }
    }
    
    // Responder com sucesso
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'processed' => count($payload['results'] ?? []),
        'timestamp' => $timestamp
    ]);
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
