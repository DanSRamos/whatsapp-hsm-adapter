<?php

/**
 * Consultar mensagens recebidas (incoming messages) da Infobip
 */

// Configuração
$apiKey = '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1';
$sender = '351927587119';

echo "📥 Consultando mensagens recebidas\n";
echo str_repeat("━", 60) . "\n\n";

// Endpoint para consultar mensagens recebidas
// Nota: A Infobip envia mensagens recebidas via webhook, mas também podemos consultar
$url = 'https://api.infobip.com/whatsapp/1/reports';

echo "🔍 Buscando relatórios de mensagens...\n\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Accept: application/json'
]);

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
    
    if (isset($result['results']) && !empty($result['results'])) {
        echo "✅ Encontrados " . count($result['results']) . " relatório(s):\n\n";
        
        foreach ($result['results'] as $index => $report) {
            echo str_repeat("─", 60) . "\n";
            echo "📊 Relatório #" . ($index + 1) . "\n";
            echo str_repeat("─", 60) . "\n";
            
            echo "Message ID:  " . ($report['messageId'] ?? 'N/A') . "\n";
            echo "To:          " . ($report['to'] ?? 'N/A') . "\n";
            echo "From:        " . ($report['from'] ?? 'N/A') . "\n";
            echo "Status:      " . ($report['status']['name'] ?? 'N/A') . "\n";
            echo "Description: " . ($report['status']['description'] ?? 'N/A') . "\n";
            
            if (isset($report['sentAt'])) {
                echo "Sent At:     " . $report['sentAt'] . "\n";
            }
            if (isset($report['doneAt'])) {
                echo "Done At:     " . $report['doneAt'] . "\n";
            }
            
            echo "\n";
        }
    } else {
        echo "ℹ️  Nenhum relatório encontrado\n";
    }
    
    echo "\n📄 Resposta completa:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
} else {
    echo "❌ Erro ao consultar relatórios\n";
    echo "Resposta: $response\n";
}

echo "\n" . str_repeat("━", 60) . "\n\n";

// Tentar endpoint alternativo para mensagens recebidas
echo "🔄 Tentando endpoint de mensagens recebidas...\n\n";

// Para receber mensagens, normalmente você precisa configurar um webhook
// Mas vamos tentar consultar se há algum endpoint disponível

$inboundUrl = 'https://api.infobip.com/whatsapp/1/inbox/reports';

$ch = curl_init($inboundUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    
    if (isset($result['results']) && !empty($result['results'])) {
        echo "✅ Mensagens recebidas encontradas:\n\n";
        
        foreach ($result['results'] as $index => $message) {
            echo str_repeat("─", 60) . "\n";
            echo "💬 Mensagem #" . ($index + 1) . "\n";
            echo str_repeat("─", 60) . "\n";
            
            echo "Message ID:  " . ($message['messageId'] ?? 'N/A') . "\n";
            echo "From:        " . ($message['from'] ?? 'N/A') . "\n";
            echo "To:          " . ($message['to'] ?? 'N/A') . "\n";
            echo "Received At: " . ($message['receivedAt'] ?? 'N/A') . "\n";
            
            if (isset($message['message'])) {
                echo "\n📝 Conteúdo:\n";
                if (isset($message['message']['text'])) {
                    echo "Texto: " . $message['message']['text'] . "\n";
                }
                if (isset($message['message']['type'])) {
                    echo "Tipo: " . $message['message']['type'] . "\n";
                }
            }
            
            echo "\n";
        }
        
        echo "\n📄 Resposta completa:\n";
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
    } else {
        echo "ℹ️  Nenhuma mensagem recebida encontrada\n";
        echo "Resposta: $response\n";
    }
} else {
    echo "⚠️  Endpoint não disponível ou sem mensagens (HTTP $httpCode)\n";
    echo "Resposta: $response\n";
}

echo "\n" . str_repeat("━", 60) . "\n";
echo "\n💡 Nota Importante:\n";
echo str_repeat("─", 60) . "\n";
echo "Para receber mensagens do WhatsApp, você precisa:\n\n";
echo "1. Configurar um Webhook na Infobip:\n";
echo "   - Acesse o portal Infobip\n";
echo "   - Vá para WhatsApp > Settings > Webhooks\n";
echo "   - Configure o URL do webhook para receber mensagens\n";
echo "   - Exemplo: https://seu-dominio.com/webhooks/incoming-messages\n\n";
echo "2. O webhook receberá notificações em tempo real quando:\n";
echo "   - Um cliente responde à sua mensagem\n";
echo "   - Um cliente envia uma mensagem nova\n\n";
echo "3. Estrutura do webhook payload:\n";
echo "   {\n";
echo "     \"results\": [{\n";
echo "       \"messageId\": \"...\",\n";
echo "       \"from\": \"351961725398\",\n";
echo "       \"to\": \"351927587119\",\n";
echo "       \"receivedAt\": \"2026-01-16T...\",\n";
echo "       \"message\": {\n";
echo "         \"type\": \"TEXT\",\n";
echo "         \"text\": \"Sim\"\n";
echo "       }\n";
echo "     }]\n";
echo "   }\n\n";

echo "✅ Consulta concluída!\n";
