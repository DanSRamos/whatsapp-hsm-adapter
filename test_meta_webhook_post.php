<?php
/**
 * Test script to simulate a Meta webhook POST request
 * This simulates what Meta would send when a message is received
 */

// Simulate a message from Meta
$payload = [
    'object' => 'page',
    'entry' => [
        [
            'id' => '118491818174527', // Your Page ID
            'time' => time() * 1000, // Current timestamp in milliseconds
            'messaging' => [
                [
                    'sender' => [
                        'id' => '123456789' // Simulated sender ID
                    ],
                    'recipient' => [
                        'id' => '118491818174527' // Your Page ID
                    ],
                    'timestamp' => time() * 1000,
                    'message' => [
                        'mid' => 'test_message_' . uniqid(),
                        'text' => 'Teste de mensagem do Meta'
                    ]
                ]
            ]
        ]
    ]
];

$jsonPayload = json_encode($payload);

// Calculate signature (Meta uses HMAC SHA256)
$appSecret = '8a7d5669cc9a004f5c3a9360d59d4fac'; // Your app secret from .env
$signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $appSecret);

echo "🧪 TESTE DE WEBHOOK META - POST REQUEST\n";
echo "========================================\n\n";

echo "📦 Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

echo "🔐 Signature: $signature\n\n";

echo "📡 Enviando para http://localhost:8081/webhooks/meta ...\n\n";

// Send POST request
$ch = curl_init('http://localhost:8081/webhooks/meta');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Hub-Signature-256: ' . $signature
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📥 RESPOSTA:\n";
echo "HTTP Status: $httpCode\n";

if ($error) {
    echo "❌ Erro: $error\n";
} else {
    echo "✅ Resposta:\n";
    echo $response . "\n";
}

echo "\n";
echo "🔍 Verifica também:\n";
echo "- ngrok: http://127.0.0.1:4040\n";
echo "- Logs: tail -f storage/logs/whatsapp-adapter.log\n";
