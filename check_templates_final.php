<?php

/**
 * Script para consultar templates WhatsApp da conta Infobip
 * Sender: +351927587119
 */

// Configuração
$apiKey = '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1';
$baseUrl = 'https://api.infobip.com';
$sender = '351927587119'; // Sem o + para a API

echo "🔍 Consultando templates da conta Infobip\n";
echo "📱 Sender: +351927587119\n\n";

// Endpoint para listar templates do sender
$url = $baseUrl . '/whatsapp/2/senders/' . $sender . '/templates';

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

if ($httpCode !== 200) {
    echo "❌ Erro HTTP $httpCode\n";
    echo "Resposta: $response\n\n";
    
    // Tentar com formato alternativo
    echo "🔄 Tentando formato alternativo do número...\n";
    $sender = '+351927587119';
    $url = $baseUrl . '/whatsapp/2/senders/' . urlencode($sender) . '/templates';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: App ' . $apiKey,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "❌ Erro HTTP $httpCode\n";
        echo "Resposta: $response\n";
        exit(1);
    }
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

// Exibir resultados
if (empty($data['templates'])) {
    echo "ℹ️  Nenhum template encontrado para este sender.\n\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📝 Para criar templates:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "1. Acesse o portal Infobip: https://portal.infobip.com\n";
    echo "2. Navegue para: Channels > WhatsApp > Templates\n";
    echo "3. Clique em 'Create Template'\n";
    echo "4. Preencha os campos:\n";
    echo "   - Template Name (ex: welcome_message)\n";
    echo "   - Category (MARKETING, UTILITY, AUTHENTICATION)\n";
    echo "   - Language (pt, en, etc.)\n";
    echo "   - Header (opcional)\n";
    echo "   - Body (texto da mensagem com {{1}}, {{2}} para parâmetros)\n";
    echo "   - Footer (opcional)\n";
    echo "   - Buttons (opcional)\n";
    echo "5. Submeta para aprovação do WhatsApp\n";
    echo "6. Aguarde aprovação (pode levar até 24h)\n\n";
    echo "💡 Exemplo de template:\n";
    echo "   Nome: welcome_message\n";
    echo "   Categoria: MARKETING\n";
    echo "   Idioma: pt\n";
    echo "   Body: Olá {{1}}! Bem-vindo à nossa plataforma.\n\n";
} else {
    $templates = $data['templates'];
    echo "✅ Encontrados " . count($templates) . " template(s):\n\n";
    
    foreach ($templates as $index => $template) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "📋 Template #" . ($index + 1) . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        $templateId = $template['id'] ?? 'N/A';
        $templateName = $template['name'] ?? 'N/A';
        $language = $template['language'] ?? 'N/A';
        $status = $template['status'] ?? 'N/A';
        $category = $template['category'] ?? 'N/A';
        
        // Status com emoji
        $statusEmoji = match($status) {
            'APPROVED' => '✅',
            'PENDING' => '⏳',
            'REJECTED' => '❌',
            'PAUSED' => '⏸️',
            default => 'ℹ️'
        };
        
        echo "ID:         $templateId\n";
        echo "Nome:       $templateName\n";
        echo "Idioma:     $language\n";
        echo "Status:     $statusEmoji $status\n";
        echo "Categoria:  $category\n";
        
        // Estrutura do template
        if (isset($template['structure'])) {
            echo "\n📝 Estrutura:\n";
            echo str_repeat("─", 50) . "\n";
            
            // Header
            if (isset($template['structure']['header'])) {
                $header = $template['structure']['header'];
                echo "\n🔹 HEADER:\n";
                echo "  Formato: " . ($header['format'] ?? 'N/A') . "\n";
                if (isset($header['text'])) {
                    echo "  Texto: " . $header['text'] . "\n";
                }
            }
            
            // Body
            if (isset($template['structure']['body'])) {
                $body = $template['structure']['body'];
                echo "\n🔹 BODY:\n";
                $bodyText = $body['text'] ?? 'N/A';
                echo "  Texto: $bodyText\n";
                
                // Contar parâmetros
                preg_match_all('/\{\{(\d+)\}\}/', $bodyText, $matches);
                if (!empty($matches[1])) {
                    echo "  Parâmetros: " . count($matches[1]) . " ({{" . implode('}}, {{', $matches[1]) . "}})\n";
                }
            }
            
            // Footer
            if (isset($template['structure']['footer'])) {
                $footer = $template['structure']['footer'];
                echo "\n🔹 FOOTER:\n";
                echo "  Texto: " . ($footer['text'] ?? 'N/A') . "\n";
            }
            
            // Buttons
            if (isset($template['structure']['buttons']) && !empty($template['structure']['buttons'])) {
                echo "\n🔹 BOTÕES: (" . count($template['structure']['buttons']) . ")\n";
                foreach ($template['structure']['buttons'] as $btnIndex => $button) {
                    $btnType = $button['type'] ?? 'N/A';
                    $btnText = $button['text'] ?? 'N/A';
                    echo "  " . ($btnIndex + 1) . ". $btnText";
                    
                    if ($btnType === 'URL' && isset($button['url'])) {
                        echo " → " . $button['url'];
                    } elseif ($btnType === 'PHONE_NUMBER' && isset($button['phoneNumber'])) {
                        echo " → " . $button['phoneNumber'];
                    }
                    
                    echo " ($btnType)\n";
                }
            }
            
            echo str_repeat("─", 50) . "\n";
        }
        
        // Exemplo de uso
        if ($status === 'APPROVED') {
            echo "\n💡 Exemplo de uso (PHP):\n";
            echo "```php\n";
            echo "\$request = new HSMRequest(\n";
            echo "    to: '351912345678',\n";
            echo "    templateName: '$templateName',\n";
            echo "    templateLanguage: '$language',\n";
            
            // Gerar exemplo de parâmetros
            if (isset($template['structure']['body']['text'])) {
                preg_match_all('/\{\{(\d+)\}\}/', $template['structure']['body']['text'], $matches);
                if (!empty($matches[1])) {
                    echo "    parameters: [";
                    $params = [];
                    foreach ($matches[1] as $paramNum) {
                        $params[] = "'Valor$paramNum'";
                    }
                    echo implode(', ', $params);
                    echo "]\n";
                }
            }
            
            echo ");\n";
            echo "\$result = \$messageService->sendHSM(\$request);\n";
            echo "```\n";
        }
        
        echo "\n";
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
}

// Estatísticas
if (!empty($data['templates'])) {
    echo "\n📊 Estatísticas:\n";
    echo str_repeat("─", 50) . "\n";
    
    $stats = [
        'total' => count($data['templates']),
        'approved' => 0,
        'pending' => 0,
        'rejected' => 0,
        'paused' => 0
    ];
    
    foreach ($data['templates'] as $template) {
        $status = strtolower($template['status'] ?? '');
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
    }
    
    echo "Total:     {$stats['total']}\n";
    echo "✅ Aprovados: {$stats['approved']}\n";
    echo "⏳ Pendentes: {$stats['pending']}\n";
    echo "❌ Rejeitados: {$stats['rejected']}\n";
    echo "⏸️  Pausados:  {$stats['paused']}\n";
}

echo "\n✅ Consulta concluída!\n";

// Opção para ver JSON completo
if (isset($argv[1]) && $argv[1] === '--json') {
    echo "\n📄 JSON Completo:\n";
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}
