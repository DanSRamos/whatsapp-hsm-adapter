<?php
/**
 * Teste completo do webhook Meta
 * Baseado no exemplo oficial do Meta Dashboard
 */

echo "🧪 TESTE COMPLETO DO WEBHOOK META\n";
echo "=====================================\n\n";

// Configuração
$webhookUrl = 'http://localhost:8081/webhooks/meta';
$appSecret = '8a7d5669cc9a004f5c3a9360d59d4fac'; // Do .env
$verifyToken = 'd0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5'; // Do .env

// ============================================
// TESTE 1: Verificação do Webhook (GET)
// ============================================
echo "📋 TESTE 1: Verificação do Webhook (GET)\n";
echo "-------------------------------------------\n";

$challenge = 'test_challenge_' . uniqid();
$verifyUrl = $webhookUrl . '?' . http_build_query([
    'hub.mode' => 'subscribe',
    'hub.verify_token' => $verifyToken,
    'hub.challenge' => $challenge
]);

echo "URL: $verifyUrl\n";

$ch = curl_init($verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200 && $response === $challenge) {
    echo "✅ TESTE 1 PASSOU: Verificação funcionou!\n\n";
} else {
    echo "❌ TESTE 1 FALHOU: Verificação não funcionou!\n";
    echo "   Esperado: $challenge\n";
    echo "   Recebido: $response\n\n";
    exit(1);
}

// ============================================
// TESTE 2: Mensagem de Texto (POST)
// ============================================
echo "📋 TESTE 2: Mensagem de Texto (POST)\n";
echo "-------------------------------------------\n";

$payload = [
    'object' => 'page',
    'entry' => [
        [
            'id' => '118491818174527', // Teu Page ID
            'time' => time() * 1000,
            'messaging' => [
                [
                    'sender' => [
                        'id' => '123456789' // ID do remetente
                    ],
                    'recipient' => [
                        'id' => '118491818174527' // Teu Page ID
                    ],
                    'timestamp' => time() * 1000,
                    'message' => [
                        'mid' => 'test_message_' . uniqid(),
                        'text' => 'Olá! Esta é uma mensagem de teste.'
                    ]
                ]
            ]
        ]
    ]
];

$jsonPayload = json_encode($payload);
$signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $appSecret);

echo "Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";
echo "Signature: $signature\n\n";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Hub-Signature-256: ' . $signature
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200) {
    echo "✅ TESTE 2 PASSOU: Mensagem de texto aceite!\n\n";
} else {
    echo "❌ TESTE 2 FALHOU: Mensagem de texto rejeitada!\n\n";
    exit(1);
}

// ============================================
// TESTE 3: Mensagem com Quick Reply (POST)
// ============================================
echo "📋 TESTE 3: Mensagem com Quick Reply (POST)\n";
echo "-------------------------------------------\n";

$payload = [
    'object' => 'page',
    'entry' => [
        [
            'id' => '118491818174527',
            'time' => time() * 1000,
            'messaging' => [
                [
                    'sender' => [
                        'id' => '123456789'
                    ],
                    'recipient' => [
                        'id' => '118491818174527'
                    ],
                    'timestamp' => time() * 1000,
                    'message' => [
                        'mid' => 'test_message_' . uniqid(),
                        'text' => 'Sim',
                        'quick_reply' => [
                            'payload' => 'OPTION_YES'
                        ]
                    ]
                ]
            ]
        ]
    ]
];

$jsonPayload = json_encode($payload);
$signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $appSecret);

echo "Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Hub-Signature-256: ' . $signature
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200) {
    echo "✅ TESTE 3 PASSOU: Quick Reply aceite!\n\n";
} else {
    echo "❌ TESTE 3 FALHOU: Quick Reply rejeitado!\n\n";
    exit(1);
}

// ============================================
// TESTE 4: Postback (Botão clicado)
// ============================================
echo "📋 TESTE 4: Postback (Botão clicado)\n";
echo "-------------------------------------------\n";

$payload = [
    'object' => 'page',
    'entry' => [
        [
            'id' => '118491818174527',
            'time' => time() * 1000,
            'messaging' => [
                [
                    'sender' => [
                        'id' => '123456789'
                    ],
                    'recipient' => [
                        'id' => '118491818174527'
                    ],
                    'timestamp' => time() * 1000,
                    'postback' => [
                        'title' => 'Get Started',
                        'payload' => 'GET_STARTED_PAYLOAD'
                    ]
                ]
            ]
        ]
    ]
];

$jsonPayload = json_encode($payload);
$signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $appSecret);

echo "Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Hub-Signature-256: ' . $signature
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200) {
    echo "✅ TESTE 4 PASSOU: Postback aceite!\n\n";
} else {
    echo "❌ TESTE 4 FALHOU: Postback rejeitado!\n\n";
    exit(1);
}

// ============================================
// TESTE 5: Delivery Report
// ============================================
echo "📋 TESTE 5: Delivery Report\n";
echo "-------------------------------------------\n";

$payload = [
    'object' => 'page',
    'entry' => [
        [
            'id' => '118491818174527',
            'time' => time() * 1000,
            'messaging' => [
                [
                    'sender' => [
                        'id' => '123456789'
                    ],
                    'recipient' => [
                        'id' => '118491818174527'
                    ],
                    'timestamp' => time() * 1000,
                    'delivery' => [
                        'mids' => [
                            'test_message_123',
                            'test_message_456'
                        ],
                        'watermark' => time() * 1000
                    ]
                ]
            ]
        ]
    ]
];

$jsonPayload = json_encode($payload);
$signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $appSecret);

echo "Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Hub-Signature-256: ' . $signature
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200) {
    echo "✅ TESTE 5 PASSOU: Delivery Report aceite!\n\n";
} else {
    echo "❌ TESTE 5 FALHOU: Delivery Report rejeitado!\n\n";
    exit(1);
}

// ============================================
// TESTE 6: Read Receipt
// ============================================
echo "📋 TESTE 6: Read Receipt\n";
echo "-------------------------------------------\n";

$payload = [
    'object' => 'page',
    'entry' => [
        [
            'id' => '118491818174527',
            'time' => time() * 1000,
            'messaging' => [
                [
                    'sender' => [
                        'id' => '123456789'
                    ],
                    'recipient' => [
                        'id' => '118491818174527'
                    ],
                    'timestamp' => time() * 1000,
                    'read' => [
                        'watermark' => time() * 1000
                    ]
                ]
            ]
        ]
    ]
];

$jsonPayload = json_encode($payload);
$signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $appSecret);

echo "Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Hub-Signature-256: ' . $signature
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200) {
    echo "✅ TESTE 6 PASSOU: Read Receipt aceite!\n\n";
} else {
    echo "❌ TESTE 6 FALHOU: Read Receipt rejeitado!\n\n";
    exit(1);
}

// ============================================
// RESUMO FINAL
// ============================================
echo "\n";
echo "=====================================\n";
echo "✅ TODOS OS TESTES PASSARAM!\n";
echo "=====================================\n\n";

echo "O webhook está a funcionar corretamente e aceita:\n";
echo "  ✅ Verificação (GET)\n";
echo "  ✅ Mensagens de texto\n";
echo "  ✅ Quick Replies\n";
echo "  ✅ Postbacks (botões)\n";
echo "  ✅ Delivery Reports\n";
echo "  ✅ Read Receipts\n\n";

echo "🔍 Próximo passo:\n";
echo "   Adiciona a tua conta como Tester no Meta Dashboard\n";
echo "   para que o Meta envie webhooks reais!\n\n";

echo "📊 Ver requisições no ngrok:\n";
echo "   http://127.0.0.1:4040\n\n";
