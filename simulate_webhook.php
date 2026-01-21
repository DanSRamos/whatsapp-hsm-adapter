<?php

/**
 * Simular webhook de mensagem recebida
 * Para testar o processamento local
 */

echo "🧪 Simulando Webhook de Mensagem Recebida\n";
echo str_repeat("━", 60) . "\n\n";

// Exemplo de payload que a Infobip enviaria
$webhookPayload = [
    'results' => [
        [
            'messageId' => 'ABEGkYaYVDPXAgo-sKD87r8vwSL_',
            'from' => '351961725398',
            'to' => '351927587119',
            'receivedAt' => date('Y-m-d\TH:i:s.000\Z'),
            'message' => [
                'type' => 'TEXT',
                'text' => 'Sim'  // Exemplo de resposta
            ],
            'contact' => [
                'name' => 'Daniel Ramos'
            ],
            'price' => [
                'pricePerMessage' => 0.0042,
                'currency' => 'EUR'
            ]
        ]
    ]
];

echo "📝 Payload simulado:\n";
echo json_encode($webhookPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Salvar em arquivo de log
$logFile = __DIR__ . '/storage/logs/received_messages.log';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$message = $webhookPayload['results'][0];
$logEntry = sprintf(
    "[%s] De: %s | Para: %s | Tipo: %s | Texto: %s\n",
    $message['receivedAt'],
    $message['from'],
    $message['to'],
    $message['message']['type'],
    $message['message']['text']
);

file_put_contents($logFile, $logEntry, FILE_APPEND);

echo "✅ Mensagem simulada salva em: $logFile\n\n";

// Mostrar conteúdo do arquivo
if (file_exists($logFile)) {
    echo "📄 Mensagens recebidas:\n";
    echo str_repeat("─", 60) . "\n";
    echo file_get_contents($logFile);
    echo str_repeat("─", 60) . "\n";
}

echo "\n💡 Para receber mensagens reais:\n";
echo "1. Hospede o webhook_receiver.php num servidor público\n";
echo "2. Configure o URL na Infobip\n";
echo "3. As mensagens aparecerão automaticamente no log\n";

echo "\n✅ Simulação concluída!\n";
