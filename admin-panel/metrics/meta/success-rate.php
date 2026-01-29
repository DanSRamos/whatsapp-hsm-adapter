<?php
/**
 * Meta Success Rate Endpoint (Mock)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$period = $_GET['period'] ?? '1h';

$response = [
    'success' => true,
    'data' => [
        'successRate' => 100,
        'totalMessages' => 0,
        'successfulMessages' => 0,
        'failedMessages' => 0
    ],
    'message' => 'Mock data - backend not implemented'
];

echo json_encode($response, JSON_PRETTY_PRINT);
