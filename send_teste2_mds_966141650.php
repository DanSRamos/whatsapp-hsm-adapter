<?php

/**
 * Enviar mensagem HSM usando template "teste2_mds" para +351966141650
 */

// Configuração
$apiKey = '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1';
$sender = '351927587119';
$recipient = '351966141650';

echo "📱 Enviando mensagem HSM - Template: teste2_mds\n";
echo str_repeat("━", 60) . "\n\n";

// Template: teste2_mds (pt_PT)
// Mensagem: "Isto é um teste 😜"
// Botões: [Sim, Não]

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

echo "De:         +$sender\n";
echo "Para:       +$recipient\n";
echo "Template:   teste2_mds\n";
echo "Idioma:     pt_PT\n";
echo "Categoria:  MARKETING\n\n";

echo "📝 Mensagem:\n";
echo "\"Isto é um teste 😜\"\n\n";

echo "🔘 Botões:\n";
echo "  1. Sim\n";
echo "  2. Não\n\n";

echo "🔄 Enviando...\n\n";

// Fazer requisição
$ch = curl_init('https://api.infobip.com/whatsapp/1/message/template');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erro na requisição: $error\n";
    exit(1);
}

echo "HTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    
    if (isset($result['messages'][0])) {
        $message = $result['messages'][0];
        
        echo "✅ Mensagem enviada com sucesso!\n\n";
        echo str_repeat("─", 60) . "\n";
        echo "Message ID:  " . ($message['messageId'] ?? 'N/A') . "\n";
        echo "Status:      " . ($message['status']['name'] ?? 'N/A') . "\n";
        echo "Description: " . ($message['status']['description'] ?? 'N/A') . "\n";
        echo "To:          " . ($message['to'] ?? 'N/A') . "\n";
        echo str_repeat("─", 60) . "\n\n";
        
        echo "📱 Verifique o WhatsApp (+351966141650)\n";
        echo "💬 A mensagem deve chegar em alguns segundos\n";
        echo "🔘 Clique em um dos botões para responder\n\n";
        
        // Mostrar JSON completo
        echo "📄 Resposta completa:\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        
    } else {
        echo "⚠️  Resposta inesperada:\n";
        echo $response . "\n";
    }
} else {
    echo "❌ Erro ao enviar mensagem\n";
    echo "Resposta: $response\n";
    
    $result = json_decode($response, true);
    if (isset($result['requestError'])) {
        echo "\n🔍 Detalhes do erro:\n";
        echo "Message ID: " . ($result['requestError']['serviceException']['messageId'] ?? 'N/A') . "\n";
        echo "Text: " . ($result['requestError']['serviceException']['text'] ?? 'N/A') . "\n";
    }
}

echo "\n✅ Script concluído!\n";
