<?php

/**
 * Script alternativo para consultar templates WhatsApp da conta Infobip
 */

// Configuração
$apiKey = '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1';
$baseUrl = 'https://api.infobip.com';

echo "🔍 Consultando informações da conta Infobip...\n\n";

// Primeiro, vamos tentar listar os senders disponíveis
echo "📱 Verificando senders (números WhatsApp)...\n";
$sendersUrl = $baseUrl . '/whatsapp/1/senders';

$ch = curl_init($sendersUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: HTTP $httpCode\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (!empty($data['senders'])) {
        echo "✅ Senders encontrados:\n";
        foreach ($data['senders'] as $sender) {
            echo "  - " . ($sender['sender'] ?? 'N/A') . 
                 " (Status: " . ($sender['status'] ?? 'N/A') . ")\n";
        }
        
        // Tentar buscar templates para cada sender
        foreach ($data['senders'] as $sender) {
            $senderNumber = $sender['sender'] ?? null;
            if ($senderNumber) {
                echo "\n📋 Buscando templates para sender: $senderNumber\n";
                
                $templatesUrl = $baseUrl . '/whatsapp/2/senders/' . urlencode($senderNumber) . '/templates';
                
                $ch = curl_init($templatesUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: App ' . $apiKey,
                    'Accept: application/json'
                ]);
                
                $templatesResponse = curl_exec($ch);
                $templatesHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($templatesHttpCode === 200) {
                    $templatesData = json_decode($templatesResponse, true);
                    if (!empty($templatesData['templates'])) {
                        echo "✅ Templates encontrados: " . count($templatesData['templates']) . "\n";
                        foreach ($templatesData['templates'] as $template) {
                            echo "\n  📄 " . ($template['name'] ?? 'N/A') . "\n";
                            echo "     ID: " . ($template['id'] ?? 'N/A') . "\n";
                            echo "     Idioma: " . ($template['language'] ?? 'N/A') . "\n";
                            echo "     Status: " . ($template['status'] ?? 'N/A') . "\n";
                            echo "     Categoria: " . ($template['category'] ?? 'N/A') . "\n";
                        }
                    } else {
                        echo "ℹ️  Nenhum template encontrado para este sender\n";
                    }
                } else {
                    echo "⚠️  Erro ao buscar templates: HTTP $templatesHttpCode\n";
                    echo "   Resposta: $templatesResponse\n";
                }
            }
        }
    } else {
        echo "ℹ️  Nenhum sender encontrado\n";
    }
} else {
    echo "⚠️  Resposta: $response\n";
}

echo "\n" . str_repeat("━", 60) . "\n";
echo "\n📚 Informações sobre a conta:\n\n";

// Tentar obter informações da conta
$accountUrl = $baseUrl . '/account/1/balance';

$ch = curl_init($accountUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Accept: application/json'
]);

$accountResponse = curl_exec($ch);
$accountHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($accountHttpCode === 200) {
    $accountData = json_decode($accountResponse, true);
    echo "💰 Saldo da conta: " . ($accountData['balance'] ?? 'N/A') . " " . 
         ($accountData['currency'] ?? '') . "\n";
} else {
    echo "⚠️  Não foi possível obter informações da conta (HTTP $accountHttpCode)\n";
}

echo "\n" . str_repeat("━", 60) . "\n";
echo "\n💡 Próximos passos:\n\n";
echo "1. Se não tem senders configurados:\n";
echo "   - Acesse o portal Infobip\n";
echo "   - Configure um número WhatsApp Business\n";
echo "   - Aguarde aprovação do WhatsApp\n\n";
echo "2. Para criar templates:\n";
echo "   - Acesse WhatsApp > Templates no portal\n";
echo "   - Crie templates seguindo as diretrizes do WhatsApp\n";
echo "   - Submeta para aprovação\n\n";
echo "3. Documentação:\n";
echo "   - https://www.infobip.com/docs/whatsapp\n\n";

echo "✅ Consulta concluída!\n";
