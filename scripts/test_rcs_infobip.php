<?php

/**
 * Test Script for RCS Infobip Integration
 * 
 * This script tests the RCS endpoints using Infobip credentials
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Helper function for env() if not available
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false) {
            return $default;
        }
        return $value;
    }
}

// Colors for terminal output
class Colors {
    public static $GREEN = "\033[0;32m";
    public static $RED = "\033[0;31m";
    public static $YELLOW = "\033[1;33m";
    public static $BLUE = "\033[0;34m";
    public static $NC = "\033[0m"; // No Color
}

function printHeader($text) {
    echo "\n" . Colors::$BLUE . "========================================" . Colors::$NC . "\n";
    echo Colors::$BLUE . $text . Colors::$NC . "\n";
    echo Colors::$BLUE . "========================================" . Colors::$NC . "\n\n";
}

function printSuccess($text) {
    echo Colors::$GREEN . "✓ " . $text . Colors::$NC . "\n";
}

function printError($text) {
    echo Colors::$RED . "✗ " . $text . Colors::$NC . "\n";
}

function printWarning($text) {
    echo Colors::$YELLOW . "⚠ " . $text . Colors::$NC . "\n";
}

function printInfo($text) {
    echo Colors::$BLUE . "ℹ " . $text . Colors::$NC . "\n";
}

// Test 1: Check environment variables
printHeader("TEST 1: Verificar Credenciais Infobip");

$requiredVars = [
    'INFOBIP_API_KEY',
    'INFOBIP_BASE_URL',
    'INFOBIP_SENDER'
];

$allPresent = true;
foreach ($requiredVars as $var) {
    $value = $_ENV[$var] ?? '';
    if (empty($value) || $value === 'your_infobip_api_key_here' || $value === 'your_sender_number') {
        printError("$var não configurado ou com valor placeholder");
        $allPresent = false;
    } else {
        // Mask sensitive data
        $masked = substr($value, 0, 10) . '...' . substr($value, -4);
        printSuccess("$var: $masked");
    }
}

if (!$allPresent) {
    printWarning("\nPor favor, configura as credenciais no ficheiro .env:");
    echo "\nINFOBIP_API_KEY=your_actual_api_key\n";
    echo "INFOBIP_BASE_URL=https://api.infobip.com\n";
    echo "INFOBIP_SENDER=your_whatsapp_number\n\n";
    exit(1);
}

// Test 2: Check provider configuration
printHeader("TEST 2: Verificar Configuração do Provider");

$config = require __DIR__ . '/config/providers.php';

if (!isset($config['providers']['infobip-rcs'])) {
    printError("Provider 'infobip-rcs' não encontrado na configuração");
    exit(1);
}

$rcsConfig = $config['providers']['infobip-rcs'];
printSuccess("Provider 'infobip-rcs' encontrado");
printInfo("Tipo: " . $rcsConfig['type']);
printInfo("Enabled: " . ($rcsConfig['enabled'] ? 'Sim' : 'Não'));

// Verify config values
$providerConfig = $rcsConfig['config'];
if (empty($providerConfig['api_key'])) {
    printError("API Key não carregada na configuração");
} else {
    printSuccess("API Key carregada");
}

if (empty($providerConfig['sender'])) {
    printError("Sender não carregado na configuração");
} else {
    printSuccess("Sender carregado: " . $providerConfig['sender']);
}

// Test 3: Test API connectivity
printHeader("TEST 3: Testar Conectividade com API");

$baseUrl = $_ENV['INFOBIP_BASE_URL'] ?? 'https://api.infobip.com';
$apiKey = $_ENV['INFOBIP_API_KEY'] ?? '';

printInfo("Base URL: $baseUrl");
printInfo("Testando conectividade...");

$ch = curl_init($baseUrl . '/whatsapp/1/senders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    printError("Erro de conexão: $error");
} elseif ($httpCode === 401) {
    printError("Autenticação falhou - API Key inválida");
} elseif ($httpCode === 200) {
    printSuccess("Conectividade OK - API Key válida");
    $data = json_decode($response, true);
    if (isset($data['senders']) && is_array($data['senders'])) {
        printInfo("Senders disponíveis: " . count($data['senders']));
        foreach ($data['senders'] as $sender) {
            printInfo("  - " . ($sender['name'] ?? $sender['sender'] ?? 'N/A'));
        }
    }
} else {
    printWarning("Resposta inesperada: HTTP $httpCode");
    printInfo("Response: " . substr($response, 0, 200));
}

// Test 4: Test RCS endpoint (local)
printHeader("TEST 4: Testar Endpoint RCS Local");

$testNumber = readline("\nDigita um número de teste (formato: +351912345678) ou ENTER para skip: ");

if (empty($testNumber)) {
    printWarning("Teste de envio skipped");
} else {
    printInfo("Enviando mensagem de teste para: $testNumber");
    
    $payload = [
        'to' => $testNumber,
        'text' => 'Teste RCS via Infobip - ' . date('Y-m-d H:i:s')
    ];
    
    $ch = curl_init('http://localhost:8081/api/rcs/text');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "\nResposta do servidor:\n";
    echo "HTTP Code: $httpCode\n";
    
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
        
        if ($httpCode === 200 && ($responseData['success'] ?? false)) {
            printSuccess("Mensagem enviada com sucesso!");
            if (isset($responseData['data']['message_id'])) {
                printInfo("Message ID: " . $responseData['data']['message_id']);
            }
        } else {
            printError("Falha ao enviar mensagem");
            if (isset($responseData['error']['message'])) {
                printError("Erro: " . $responseData['error']['message']);
            }
        }
    } else {
        printError("Resposta inválida do servidor");
        echo $response . "\n";
    }
}

// Test 5: Test RCS Card endpoint
printHeader("TEST 5: Testar Endpoint RCS Card");

$testCard = readline("\nTestar envio de Rich Card? (y/n): ");

if (strtolower($testCard) === 'y') {
    $testNumber = readline("Número de destino (formato: +351912345678): ");
    
    if (!empty($testNumber)) {
        printInfo("Enviando Rich Card para: $testNumber");
        
        $payload = [
            'to' => $testNumber,
            'title' => 'Teste RCS Card',
            'description' => 'Este é um teste de Rich Card via Infobip',
            'mediaUrl' => 'https://via.placeholder.com/600x400',
            'suggestions' => [
                [
                    'text' => 'Ver Mais',
                    'postbackData' => 'view_more'
                ],
                [
                    'text' => 'Contactar',
                    'postbackData' => 'contact'
                ]
            ]
        ];
        
        $ch = curl_init('http://localhost:8081/api/rcs/card');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "\nResposta do servidor:\n";
        echo "HTTP Code: $httpCode\n";
        
        $responseData = json_decode($response, true);
        if ($responseData) {
            echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
            
            if ($httpCode === 200 && ($responseData['success'] ?? false)) {
                printSuccess("Rich Card enviado com sucesso!");
            } else {
                printError("Falha ao enviar Rich Card");
            }
        }
    }
}

// Summary
printHeader("RESUMO DOS TESTES");

echo "\n";
printInfo("Servidor PHP: http://localhost:8081");
printInfo("Admin Panel: http://localhost:8081/admin-panel/rcs.html");
printInfo("API Docs: http://localhost:8081/admin-panel/api-docs.html");

echo "\n";
printSuccess("Testes concluídos!");
echo "\n";

// Show next steps
printHeader("PRÓXIMOS PASSOS");

echo "\n1. Abre o Admin Panel: http://localhost:8081/admin-panel/rcs.html\n";
echo "2. Testa os diferentes tipos de mensagens RCS\n";
echo "3. Verifica os logs em: storage/logs/whatsapp-adapter.log\n";
echo "4. Monitoriza no portal Infobip: https://portal.infobip.com/\n\n";
