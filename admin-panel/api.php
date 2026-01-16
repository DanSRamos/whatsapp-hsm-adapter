<?php

/**
 * WhatsApp HSM Admin Panel - Backend API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration
$config = [
    'infobip_api_key' => '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1',
    'infobip_sender' => '351927587119',
    'messages_file' => __DIR__ . '/messages.json'
];

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_templates':
        getTemplates($config);
        break;
    
    case 'send_message':
        sendMessage($config);
        break;
    
    case 'get_messages':
        getMessages($config);
        break;
    
    case 'webhook':
        handleWebhook($config);
        break;
    
    default:
        jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
}

/**
 * Get all templates from Infobip
 */
function getTemplates($config) {
    $ch = curl_init('https://api.infobip.com/whatsapp/2/senders/' . $config['infobip_sender'] . '/templates');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: App ' . $config['infobip_api_key'],
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        jsonResponse(['success' => false, 'error' => 'Failed to fetch templates from Infobip'], 500);
        return;
    }
    
    $data = json_decode($response, true);
    $templates = [];
    
    if (isset($data['templates'])) {
        foreach ($data['templates'] as $template) {
            $body = '';
            $parameters = [];
            $hasButtons = false;
            $hasHeader = false;
            $templateType = 'TEXT';
            
            // Get template type
            if (isset($template['structure']['type'])) {
                $templateType = $template['structure']['type'];
            }
            
            // Check for buttons
            if (isset($template['structure']['buttons']) && !empty($template['structure']['buttons'])) {
                $hasButtons = true;
            }
            
            // Check for header
            if (isset($template['structure']['header'])) {
                $hasHeader = true;
            }
            
            if (isset($template['structure']['body']['text'])) {
                $body = $template['structure']['body']['text'];
                
                // Extract parameters like {{1}}, {{2}}, etc.
                preg_match_all('/\{\{(\d+)\}\}/', $body, $matches);
                if (!empty($matches[1])) {
                    $parameters = array_unique($matches[1]);
                    sort($parameters);
                }
            }
            
            $templates[] = [
                'name' => $template['name'] ?? '',
                'language' => $template['language'] ?? '',
                'status' => $template['status'] ?? '',
                'category' => $template['category'] ?? '',
                'body' => $body,
                'parameters' => $parameters,
                'type' => $templateType,
                'hasButtons' => $hasButtons,
                'hasHeader' => $hasHeader
            ];
        }
    }
    
    jsonResponse(['success' => true, 'templates' => $templates]);
}

/**
 * Send HSM message via Infobip
 */
function sendMessage($config) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['template']) || !isset($input['recipient'])) {
        jsonResponse(['success' => false, 'error' => 'Missing required fields'], 400);
        return;
    }
    
    $template = $input['template'];
    $recipient = $input['recipient'];
    $language = $input['language'] ?? 'pt_PT';
    $parameters = $input['parameters'] ?? [];
    
    // Ensure parameters is an array and filter empty values
    if (!is_array($parameters)) {
        $parameters = [];
    }
    $parameters = array_values(array_filter($parameters, function($val) {
        return $val !== null && $val !== '';
    }));
    
    $payload = [
        'messages' => [[
            'from' => $config['infobip_sender'],
            'to' => $recipient,
            'content' => [
                'templateName' => $template,
                'templateData' => [
                    'body' => [
                        'placeholders' => $parameters
                    ]
                ],
                'language' => $language
            ]
        ]]
    ];
    
    // Log the payload for debugging
    error_log('Sending message with payload: ' . json_encode($payload));
    
    $ch = curl_init('https://api.infobip.com/whatsapp/1/message/template');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: App ' . $config['infobip_api_key'],
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log response for debugging
    error_log('Infobip response (HTTP ' . $httpCode . '): ' . $response);
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        $errorMsg = 'Failed to send message';
        
        // Try to extract detailed error message
        if (isset($error['requestError']['serviceException']['text'])) {
            $errorMsg = $error['requestError']['serviceException']['text'];
        } elseif (isset($error['requestError']['serviceException']['messageId'])) {
            $errorMsg = 'Error: ' . $error['requestError']['serviceException']['messageId'];
        }
        
        // Include validation errors if present
        if (isset($error['requestError']['serviceException']['validationErrors'])) {
            $validationErrors = $error['requestError']['serviceException']['validationErrors'];
            $errorMsg .= ' - ' . json_encode($validationErrors);
        }
        
        jsonResponse(['success' => false, 'error' => $errorMsg, 'details' => $error], 500);
        return;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['messages'][0])) {
        $message = $result['messages'][0];
        jsonResponse([
            'success' => true,
            'messageId' => $message['messageId'] ?? '',
            'status' => $message['status']['name'] ?? '',
            'to' => $message['to'] ?? ''
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Unexpected response from Infobip'], 500);
    }
}

/**
 * Get received messages from storage
 */
function getMessages($config) {
    $messagesFile = $config['messages_file'];
    
    if (!file_exists($messagesFile)) {
        jsonResponse(['success' => true, 'messages' => []]);
        return;
    }
    
    $messages = json_decode(file_get_contents($messagesFile), true) ?? [];
    
    // Sort by timestamp (newest first)
    usort($messages, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Format for display
    $formatted = array_map(function($msg) {
        return [
            'from' => $msg['from'] ?? 'Unknown',
            'text' => $msg['text'] ?? '',
            'time' => date('d/m/Y H:i:s', strtotime($msg['timestamp'] ?? 'now'))
        ];
    }, $messages);
    
    jsonResponse(['success' => true, 'messages' => $formatted]);
}

/**
 * Handle incoming webhook from Infobip
 */
function handleWebhook($config) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        jsonResponse(['success' => false, 'error' => 'Invalid JSON'], 400);
        return;
    }
    
    // Store message
    $messagesFile = $config['messages_file'];
    $messages = [];
    
    if (file_exists($messagesFile)) {
        $messages = json_decode(file_get_contents($messagesFile), true) ?? [];
    }
    
    // Extract message data
    if (isset($data['results'])) {
        foreach ($data['results'] as $result) {
            $messages[] = [
                'from' => $result['from'] ?? '',
                'to' => $result['to'] ?? '',
                'text' => $result['message']['text'] ?? '',
                'messageId' => $result['messageId'] ?? '',
                'timestamp' => $result['receivedAt'] ?? date('c'),
                'raw' => $result
            ];
        }
    }
    
    // Save to file
    file_put_contents($messagesFile, json_encode($messages, JSON_PRETTY_PRINT));
    
    jsonResponse(['success' => true, 'message' => 'Webhook received']);
}

/**
 * Send JSON response
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
