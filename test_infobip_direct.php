<?php

/**
 * Test Infobip API directly
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['INFOBIP_API_KEY'] ?? '';
$sender = $_ENV['INFOBIP_SENDER'] ?? '';

echo "Testing Infobip API\n";
echo "===================\n\n";

echo "API Key: " . substr($apiKey, 0, 20) . "...\n";
echo "Sender: $sender\n\n";

// Test 1: Get senders
echo "Test 1: Get WhatsApp Senders\n";
echo "-----------------------------\n";

$ch = curl_init('https://api.infobip.com/whatsapp/1/senders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Try to send WhatsApp message
echo "Test 2: Send WhatsApp Message\n";
echo "------------------------------\n";

$payload = [
    'messages' => [[
        'from' => $sender,
        'to' => '351927587119',
        'content' => [
            'text' => 'Test message from Infobip'
        ]
    ]]
];

$ch = curl_init('https://api.infobip.com/whatsapp/1/message/text');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Payload sent:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n";
echo "Response:\n";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Check RCS availability
echo "Test 3: Check RCS Endpoints\n";
echo "----------------------------\n";

$rcsEndpoints = [
    '/rcs/1/message',
    '/rcs/1/messages',
    '/rcs/2/message',
    '/rcs/2/messages',
];

foreach ($rcsEndpoints as $endpoint) {
    $ch = curl_init('https://api.infobip.com' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: App ' . $apiKey,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Endpoint: $endpoint - HTTP $httpCode\n";
    if ($httpCode !== 404) {
        echo "  Response: " . substr($response, 0, 200) . "\n";
    }
}

echo "\n";
