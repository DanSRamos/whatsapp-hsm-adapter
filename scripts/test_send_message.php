<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Psr\Log\NullLogger;

/**
 * Script de teste para enviar mensagem HSM usando templates da Infobip
 */

// Configuração
$apiKey = '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1';
$sender = '351927587119';
$recipient = '351912345678'; // ⚠️ ALTERE PARA UM NÚMERO VÁLIDO

echo "📱 Teste de Envio de Mensagem HSM\n";
echo str_repeat("━", 60) . "\n\n";

// Criar provider factory
$config = [
    'default_provider' => 'infobip',
    'providers' => [
        'infobip' => [
            'api_key' => $apiKey,
            'base_url' => 'https://api.infobip.com',
            'sender' => $sender,
            'webhook_secret' => 'test_secret'
        ]
    ]
];

$httpClient = new Client();
$providerFactory = new \App\Providers\WhatsAppProviderFactory(
    $config,
    $httpClient,
    new NullLogger()
);

// Exemplos de envio com diferentes templates

echo "1️⃣  Teste Simples (teste2_mds)\n";
echo str_repeat("─", 60) . "\n";

try {
    $provider = $providerFactory->getProvider('infobip');
    
    // Criar payload para Infobip
    $payload = [
        'messages' => [[
            'from' => $sender,
            'to' => $recipient,
            'content' => [
                'templateName' => 'teste2_mds',
                'templateData' => [
                    'body' => [
                        'placeholders' => []
                    ]
                ],
                'language' => 'pt_PT'
            ]
        ]]
    ];
    
    echo "Enviando para: +$recipient\n";
    echo "Template: teste2_mds\n";
    echo "Mensagem: 'Isto é um teste 😜'\n";
    echo "Botões: [Sim, Não]\n\n";
    
    $response = $httpClient->post(
        'https://api.infobip.com/whatsapp/1/message/template',
        [
            'headers' => [
                'Authorization' => 'App ' . $apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => $payload
        ]
    );
    
    $result = json_decode($response->getBody()->getContents(), true);
    
    if (isset($result['messages'][0]['messageId'])) {
        echo "✅ Mensagem enviada com sucesso!\n";
        echo "Message ID: " . $result['messages'][0]['messageId'] . "\n";
        echo "Status: " . ($result['messages'][0]['status']['name'] ?? 'N/A') . "\n";
    } else {
        echo "⚠️  Resposta inesperada:\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("━", 60) . "\n\n";

echo "2️⃣  Teste com Parâmetros (entrega_saiu_mensagem)\n";
echo str_repeat("─", 60) . "\n";

try {
    $payload = [
        'messages' => [[
            'from' => $sender,
            'to' => $recipient,
            'content' => [
                'templateName' => 'entrega_saiu_mensagem',
                'templateData' => [
                    'body' => [
                        'placeholders' => [
                            'João Silva',      // {{1}} - Nome
                            'Livros',          // {{2}} - Produto
                            'ABC123',          // {{3}} - Referência
                            '14:00',           // {{4}} - Hora início
                            '18:00'            // {{5}} - Hora fim
                        ]
                    ]
                ],
                'language' => 'pt_PT'
            ]
        ]]
    ];
    
    echo "Enviando para: +$recipient\n";
    echo "Template: entrega_saiu_mensagem\n";
    echo "Parâmetros:\n";
    echo "  - Nome: João Silva\n";
    echo "  - Produto: Livros\n";
    echo "  - Ref: ABC123\n";
    echo "  - Horário: 14:00 - 18:00\n\n";
    
    $response = $httpClient->post(
        'https://api.infobip.com/whatsapp/1/message/template',
        [
            'headers' => [
                'Authorization' => 'App ' . $apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => $payload
        ]
    );
    
    $result = json_decode($response->getBody()->getContents(), true);
    
    if (isset($result['messages'][0]['messageId'])) {
        echo "✅ Mensagem enviada com sucesso!\n";
        echo "Message ID: " . $result['messages'][0]['messageId'] . "\n";
        echo "Status: " . ($result['messages'][0]['status']['name'] ?? 'N/A') . "\n";
    } else {
        echo "⚠️  Resposta inesperada:\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("━", 60) . "\n\n";

echo "3️⃣  Teste com Código OTP (entrega_saiu_codigo)\n";
echo str_repeat("─", 60) . "\n";

try {
    $otpCode = rand(100000, 999999); // Gerar código de 6 dígitos
    
    $payload = [
        'messages' => [[
            'from' => $sender,
            'to' => $recipient,
            'content' => [
                'templateName' => 'entrega_saiu_codigo',
                'templateData' => [
                    'body' => [
                        'placeholders' => [
                            (string)$otpCode  // {{1}} - Código OTP
                        ]
                    ]
                ],
                'language' => 'pt_PT'
            ]
        ]]
    ];
    
    echo "Enviando para: +$recipient\n";
    echo "Template: entrega_saiu_codigo\n";
    echo "Código OTP: $otpCode\n\n";
    
    $response = $httpClient->post(
        'https://api.infobip.com/whatsapp/1/message/template',
        [
            'headers' => [
                'Authorization' => 'App ' . $apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => $payload
        ]
    );
    
    $result = json_decode($response->getBody()->getContents(), true);
    
    if (isset($result['messages'][0]['messageId'])) {
        echo "✅ Mensagem enviada com sucesso!\n";
        echo "Message ID: " . $result['messages'][0]['messageId'] . "\n";
        echo "Status: " . ($result['messages'][0]['status']['name'] ?? 'N/A') . "\n";
    } else {
        echo "⚠️  Resposta inesperada:\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("━", 60) . "\n";
echo "\n✅ Testes concluídos!\n";
echo "\n💡 Dica: Altere a variável \$recipient para o seu número de teste\n";
