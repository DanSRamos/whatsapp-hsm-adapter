<?php

$apiKey = '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1';
$sender = '351927587119';

$ch = curl_init("https://api.infobip.com/whatsapp/2/senders/$sender/templates");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: App ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

// Find contacto_mds_teste template
foreach ($data['templates'] as $template) {
    if ($template['name'] === 'contacto_mds_teste') {
        echo "Template: contacto_mds_teste\n";
        echo "=================================\n\n";
        echo json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
    }
}
