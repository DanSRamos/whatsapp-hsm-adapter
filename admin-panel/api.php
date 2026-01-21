<?php

/**
 * WhatsApp HSM Admin Panel - Backend API
 */

// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to browser
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/api_errors.log');

// Catch any errors and convert to JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ]);
    exit;
});

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Fatal error: ' . $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
});

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration
$config = [
    'infobip_api_key' => '1de5f9151fbe499385186a3327ea1b27-a6a496a1-03bd-4c7b-99c6-6351972188f1',
    'infobip_sender' => '351927587119',
    'messages_file' => __DIR__ . '/messages.json',
    
    // Meta (Instagram + Messenger) configuration
    'meta_page_access_token' => getenv('META_PAGE_ACCESS_TOKEN') ?: '',
    'meta_page_id' => getenv('META_PAGE_ID') ?: '',
    'meta_api_version' => 'v21.0',
];

// Handle OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? '';

// Handle metrics endpoint (mock data for now)
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/metrics/meta') !== false) {
    $period = $_GET['period'] ?? '1h';
    jsonResponse([
        'success' => true,
        'data' => [
            'summary' => [
                'totalMessages' => 0,
                'successRate' => 0,
                'avgResponseTime' => 0,
                'activeUsers' => 0
            ],
            'byPlatform' => [
                'instagram' => [
                    'sent' => 0,
                    'delivered' => 0,
                    'read' => 0,
                    'failed' => 0
                ],
                'messenger' => [
                    'sent' => 0,
                    'delivered' => 0,
                    'read' => 0,
                    'failed' => 0
                ]
            ],
            'rateLimits' => [
                'hourly' => [
                    'limit' => 200,
                    'used' => 0,
                    'remaining' => 200
                ],
                'daily' => [
                    'limit' => 1000,
                    'used' => 0,
                    'remaining' => 1000
                ]
            ],
            'circuitBreaker' => [
                'state' => 'closed',
                'failures' => 0,
                'successRate' => 100
            ],
            'alerts' => []
        ],
        'message' => 'Metrics endpoint - mock data (backend not implemented yet)'
    ]);
}

switch ($action) {
    case 'health':
        jsonResponse([
            'success' => true,
            'status' => 'ok',
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
            'infobip_configured' => !empty($config['infobip_api_key']),
            'meta_configured' => !empty($config['meta_page_access_token'])
        ]);
        break;
    
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
    // Check if credentials are configured
    if (empty($config['infobip_api_key']) || empty($config['infobip_sender'])) {
        jsonResponse([
            'success' => false, 
            'error' => 'Infobip credentials not configured. Please set INFOBIP_API_KEY and INFOBIP_SENDER in your environment.',
            'templates' => []
        ], 200); // Return 200 so frontend can handle gracefully
        return;
    }
    
    $ch = curl_init('https://api.infobip.com/whatsapp/2/senders/' . $config['infobip_sender'] . '/templates');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: App ' . $config['infobip_api_key'],
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        jsonResponse([
            'success' => false, 
            'error' => 'Connection error: ' . $curlError,
            'templates' => []
        ], 200);
        return;
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = 'Failed to fetch templates from Infobip (HTTP ' . $httpCode . ')';
        
        if (isset($errorData['requestError']['serviceException']['text'])) {
            $errorMsg = $errorData['requestError']['serviceException']['text'];
        }
        
        jsonResponse([
            'success' => false, 
            'error' => $errorMsg,
            'templates' => []
        ], 200);
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
 * Send HSM message via Infobip or Meta message
 */
function sendMessage($config) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $provider = $input['provider'] ?? 'whatsapp';
    
    // Validate provider
    if (!in_array($provider, ['whatsapp', 'instagram', 'messenger'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid provider. Supported: whatsapp, instagram, messenger'], 400);
        return;
    }
    
    // Route to appropriate provider
    if ($provider === 'whatsapp') {
        sendWhatsAppMessage($config, $input);
    } elseif ($provider === 'instagram') {
        sendMetaMessage($config, $input, 'instagram');
    } elseif ($provider === 'messenger') {
        sendMetaMessage($config, $input, 'messenger');
    }
}

/**
 * Send WhatsApp message via Infobip
 */
function sendWhatsAppMessage($config, $input) {
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
    error_log('Sending WhatsApp message with payload: ' . json_encode($payload));
    
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
 * Send Instagram or Messenger message via Meta Graph API
 */
function sendMetaMessage($config, $input, $platform) {
    if (!isset($input['recipient'])) {
        jsonResponse(['success' => false, 'error' => 'Missing recipient ID'], 400);
        return;
    }
    
    if (empty($config['meta_page_access_token']) || empty($config['meta_page_id'])) {
        jsonResponse(['success' => false, 'error' => 'Meta credentials not configured'], 500);
        return;
    }
    
    $recipientId = $input['recipient'];
    $messageType = $input['messageType'] ?? 'text';
    
    $payload = [
        'recipient' => ['id' => $recipientId],
        'messaging_type' => 'RESPONSE'
    ];
    
    // Build message based on type
    if ($messageType === 'text') {
        if (!isset($input['text']) || empty($input['text'])) {
            jsonResponse(['success' => false, 'error' => 'Missing text content'], 400);
            return;
        }
        $payload['message'] = ['text' => $input['text']];
    } elseif ($messageType === 'media') {
        if (!isset($input['mediaType']) || !isset($input['mediaUrl'])) {
            jsonResponse(['success' => false, 'error' => 'Missing media type or URL'], 400);
            return;
        }
        $payload['message'] = [
            'attachment' => [
                'type' => $input['mediaType'],
                'payload' => ['url' => $input['mediaUrl']]
            ]
        ];
    } elseif ($messageType === 'multiple-images' && $platform === 'instagram') {
        if (!isset($input['imageUrls']) || !is_array($input['imageUrls'])) {
            jsonResponse(['success' => false, 'error' => 'Missing image URLs'], 400);
            return;
        }
        
        if (count($input['imageUrls']) > 10) {
            jsonResponse(['success' => false, 'error' => 'Instagram allows maximum 10 images per message'], 400);
            return;
        }
        
        $attachments = array_map(function($url) {
            return [
                'type' => 'image',
                'payload' => ['url' => $url]
            ];
        }, $input['imageUrls']);
        
        $payload['message'] = ['attachments' => $attachments];
    } else {
        jsonResponse(['success' => false, 'error' => 'Invalid message type'], 400);
        return;
    }
    
    // Log the payload for debugging
    error_log('Sending ' . ucfirst($platform) . ' message with payload: ' . json_encode($payload));
    
    $url = 'https://graph.facebook.com/' . $config['meta_api_version'] . '/' . $config['meta_page_id'] . '/messages';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['meta_page_access_token'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log response for debugging
    error_log('Meta API response (HTTP ' . $httpCode . '): ' . $response);
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        $errorMsg = 'Failed to send ' . $platform . ' message';
        
        if (isset($error['error']['message'])) {
            $errorMsg = $error['error']['message'];
        }
        
        jsonResponse(['success' => false, 'error' => $errorMsg, 'details' => $error], 500);
        return;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['message_id'])) {
        jsonResponse([
            'success' => true,
            'messageId' => $result['message_id'],
            'status' => 'sent',
            'to' => $recipientId,
            'platform' => $platform
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Unexpected response from Meta API'], 500);
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
        $provider = $msg['provider'] ?? 'whatsapp';
        $result = [
            'from' => $msg['from'] ?? 'Unknown',
            'text' => $msg['text'] ?? '',
            'time' => date('d/m/Y H:i:s', strtotime($msg['timestamp'] ?? 'now')),
            'provider' => $provider,
            'messageType' => $msg['messageType'] ?? 'text'
        ];
        
        // Add platform-specific IDs
        if ($provider === 'instagram' && isset($msg['igsid'])) {
            $result['igsid'] = $msg['igsid'];
        } elseif ($provider === 'messenger' && isset($msg['psid'])) {
            $result['psid'] = $msg['psid'];
        }
        
        return $result;
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
