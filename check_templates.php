<?php

/**
 * Script para consultar templates WhatsApp da conta Infobip
 */

// Configuração
$apiKey = '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1';
$baseUrl = 'https://api.infobip.com';

// Endpoint para listar templates
$url = $baseUrl . '/whatsapp/2/senders/management/templates';

// Fazer requisição
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Accept: application/json'
]);

echo "🔍 Consultando templates da conta Infobip...\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erro na requisição: $error\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "❌ Erro HTTP $httpCode\n";
    echo "Resposta: $response\n";
    exit(1);
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

// Exibir resultados
if (empty($data['templates'])) {
    echo "ℹ️  Nenhum template encontrado na conta.\n";
    echo "\n📝 Para criar templates:\n";
    echo "1. Acesse o portal Infobip\n";
    echo "2. Vá para WhatsApp > Templates\n";
    echo "3. Crie e submeta templates para aprovação do WhatsApp\n";
} else {
    $templates = $data['templates'];
    echo "✅ Encontrados " . count($templates) . " template(s):\n\n";
    
    foreach ($templates as $index => $template) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📋 Template #" . ($index + 1) . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "ID:         " . ($template['id'] ?? 'N/A') . "\n";
        echo "Nome:       " . ($template['name'] ?? 'N/A') . "\n";
        echo "Idioma:     " . ($template['language'] ?? 'N/A') . "\n";
        echo "Status:     " . ($template['status'] ?? 'N/A') . "\n";
        echo "Categoria:  " . ($template['category'] ?? 'N/A') . "\n";
        
        if (isset($template['structure'])) {
            echo "\n📝 Estrutura:\n";
            
            if (isset($template['structure']['header'])) {
                echo "  Header: " . ($template['structure']['header']['format'] ?? 'N/A') . "\n";
            }
            
            if (isset($template['structure']['body'])) {
                echo "  Body: " . ($template['structure']['body']['text'] ?? 'N/A') . "\n";
            }
            
            if (isset($template['structure']['footer'])) {
                echo "  Footer: " . ($template['structure']['footer']['text'] ?? 'N/A') . "\n";
            }
            
            if (isset($template['structure']['buttons'])) {
                echo "  Botões: " . count($template['structure']['buttons']) . "\n";
                foreach ($template['structure']['buttons'] as $btnIndex => $button) {
                    echo "    " . ($btnIndex + 1) . ". " . ($button['text'] ?? 'N/A') . 
                         " (" . ($button['type'] ?? 'N/A') . ")\n";
                }
            }
        }
        
        echo "\n";
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n💡 Dica: Use estes templates para enviar mensagens HSM\n";
}

// Exibir JSON completo se necessário
if (isset($argv[1]) && $argv[1] === '--json') {
    echo "\n📄 JSON Completo:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n✅ Consulta concluída!\n";
