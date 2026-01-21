<?php

$apiKey = '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1';
$sender = '351927587119';
$recipient = '351961725398';

// Test with teste2_mds (no parameters)
echo "Test 1: Template without parameters (teste2_mds)\n";
echo "=================================================\n";

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
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode === 200) {
    echo "✅ Success! Template without parameters works.\n\n";
} else {
    echo "❌ Failed\n\n";
}

// Now test with parameters
echo "Test 2: Template with parameters (contacto_mds_teste)\n";
echo "======================================================\n";

$payload2 = [
    'messages' => [[
        'from' => $sender,
        'to' => $recipient,
        'content' => [
            'templateName' => 'contacto_mds_teste',
            'templateData' => [
                'body' => [
                    'placeholders' => ['João', 'Cartão de cidadão', '123456']
                ]
            ],
            'language' => 'pt_PT'
        ]
    ]]
];

echo "Payload:\n" . json_encode($payload2, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init('https://api.infobip.com/whatsapp/1/message/template');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload2));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode === 200) {
    echo "✅ Success!\n";
} else {
    echo "❌ Failed - This is the error you're seeing\n";
}
