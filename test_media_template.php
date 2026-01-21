<?php

$apiKey = '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1';
$sender = '351927587119';
$recipient = '351961725398';

// Try sending with TEXT type structure (current)
$payload1 = [
    'messages' => [[
        'from' => $sender,
        'to' => $recipient,
        'content' => [
            'templateName' => 'contacto_mds_teste',
            'templateData' => [
                'body' => [
                    'placeholders' => ['Daniel', 'Documento123', 'Processo456']
                ]
            ],
            'language' => 'pt_PT'
        ]
    ]]
];

echo "Testing TEXT structure:\n";
echo json_encode($payload1, JSON_PRETTY_PRINT) . "\n\n";

// According to Infobip docs, MEDIA templates might need different structure
// Let's check the documentation format

echo "For MEDIA templates, we might need to specify the media type.\n";
echo "Checking if template needs header/media specification...\n";
