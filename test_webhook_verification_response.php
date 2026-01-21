<?php
/**
 * Teste específico da resposta de verificação do webhook
 * Para garantir que estamos a retornar exatamente o que o Meta espera
 */

echo "🔍 TESTE DA RESPOSTA DE VERIFICAÇÃO DO WEBHOOK\n";
echo "================================================\n\n";

// Configuração
$webhookUrl = 'http://localhost:8081/webhooks/meta';
$verifyToken = 'd0f9a5d7806bc7b8cef7e8cfdca0c43a4bfd2d172938b3cf94d8c53bc02b20e5';

// Gerar um challenge único
$challenge = 'test_challenge_' . bin2hex(random_bytes(16));

echo "📋 Informação do Teste:\n";
echo "   Webhook URL: $webhookUrl\n";
echo "   Verify Token: " . substr($verifyToken, 0, 20) . "...\n";
echo "   Challenge: $challenge\n\n";

// Construir URL de verificação
$verifyUrl = $webhookUrl . '?' . http_build_query([
    'hub.mode' => 'subscribe',
    'hub.verify_token' => $verifyToken,
    'hub.challenge' => $challenge
]);

echo "🌐 URL Completo:\n";
echo "   $verifyUrl\n\n";

echo "📤 A enviar pedido GET...\n\n";

// Fazer pedido GET
$ch = curl_init($verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

// Separar headers e body
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "📥 RESPOSTA RECEBIDA:\n";
echo "=====================\n\n";

echo "HTTP Status Code: $httpCode\n\n";

echo "Headers:\n";
echo "--------\n";
echo $headers . "\n";

echo "Body:\n";
echo "-----\n";
echo "Length: " . strlen($body) . " bytes\n";
echo "Content: '$body'\n\n";

echo "Challenge Original:\n";
echo "-------------------\n";
echo "Length: " . strlen($challenge) . " bytes\n";
echo "Content: '$challenge'\n\n";

// Validação
echo "✅ VALIDAÇÃO:\n";
echo "=============\n\n";

$checks = [
    'HTTP 200' => $httpCode === 200,
    'Body não vazio' => !empty($body),
    'Body = Challenge' => $body === $challenge,
    'Body length correto' => strlen($body) === strlen($challenge),
    'Sem espaços extra' => trim($body) === $body,
    'Sem newlines' => strpos($body, "\n") === false && strpos($body, "\r") === false
];

$allPassed = true;
foreach ($checks as $check => $passed) {
    $icon = $passed ? '✅' : '❌';
    echo "$icon $check\n";
    if (!$passed) {
        $allPassed = false;
    }
}

echo "\n";

if ($allPassed) {
    echo "🎉 PERFEITO! A resposta está correta!\n";
    echo "   O webhook está a retornar exatamente o que o Meta espera.\n\n";
    
    echo "📊 Análise Detalhada:\n";
    echo "   - HTTP Status: 200 OK ✅\n";
    echo "   - Content-Type: text/plain (verificar nos headers acima)\n";
    echo "   - Body: Challenge string exata sem modificações ✅\n";
    echo "   - Sem espaços ou newlines extra ✅\n\n";
    
    echo "🔍 Próximos Passos para Debugging:\n";
    echo "   1. Verificar se o ngrok está a funcionar:\n";
    echo "      curl https://dramaturgic-rushingly-raphael.ngrok-free.dev/webhooks/meta?hub.mode=subscribe&hub.verify_token=$verifyToken&hub.challenge=test123\n\n";
    
    echo "   2. Verificar logs do ngrok:\n";
    echo "      http://127.0.0.1:4040\n\n";
    
    echo "   3. Verificar no Meta Dashboard:\n";
    echo "      - App Dashboard > Messenger > Settings > Webhooks\n";
    echo "      - Verificar se o webhook está marcado como 'Verified'\n\n";
    
    echo "   4. Verificar privilégios da conta:\n";
    echo "      - App Dashboard > Roles\n";
    echo "      - Adicionar a tua conta como 'Tester' se ainda não estiver\n\n";
    
} else {
    echo "❌ PROBLEMA ENCONTRADO!\n";
    echo "   A resposta não está no formato correto.\n\n";
    
    if ($httpCode !== 200) {
        echo "   ⚠️  HTTP Status incorreto: $httpCode (esperado: 200)\n";
    }
    
    if (empty($body)) {
        echo "   ⚠️  Body vazio! O webhook não está a retornar o challenge.\n";
    } elseif ($body !== $challenge) {
        echo "   ⚠️  Body diferente do challenge!\n";
        echo "       Esperado: '$challenge'\n";
        echo "       Recebido: '$body'\n";
        
        // Análise byte-a-byte
        echo "\n   🔬 Análise byte-a-byte:\n";
        $maxLen = max(strlen($challenge), strlen($body));
        for ($i = 0; $i < min($maxLen, 50); $i++) {
            $expectedChar = $i < strlen($challenge) ? $challenge[$i] : '(fim)';
            $actualChar = $i < strlen($body) ? $body[$i] : '(fim)';
            $expectedOrd = $i < strlen($challenge) ? ord($challenge[$i]) : 0;
            $actualOrd = $i < strlen($body) ? ord($body[$i]) : 0;
            
            if ($expectedChar !== $actualChar) {
                echo "      Posição $i: esperado '$expectedChar' (ASCII $expectedOrd), recebido '$actualChar' (ASCII $actualOrd)\n";
            }
        }
    }
    
    if (trim($body) !== $body) {
        echo "   ⚠️  Body tem espaços extra no início ou fim!\n";
    }
    
    if (strpos($body, "\n") !== false || strpos($body, "\r") !== false) {
        echo "   ⚠️  Body contém newlines!\n";
    }
}

echo "\n";
echo "================================================\n";
echo "Teste concluído.\n";
