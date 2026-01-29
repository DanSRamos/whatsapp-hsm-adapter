<?php
/**
 * Meta Metrics Endpoint (Mock)
 * This is a mock endpoint that returns sample metrics data
 * In production, this should connect to your actual metrics backend
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$period = $_GET['period'] ?? '1h';
$timeRange = $_GET['timeRange'] ?? '1h';

// Mock data - replace with actual metrics from your backend
$response = [
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
                'remaining' => 200,
                'resetAt' => date('c', strtotime('+1 hour'))
            ],
            'daily' => [
                'limit' => 1000,
                'used' => 0,
                'remaining' => 1000,
                'resetAt' => date('c', strtotime('tomorrow'))
            ]
        ],
        'circuitBreaker' => [
            'state' => 'closed',
            'failures' => 0,
            'successRate' => 100,
            'lastFailure' => null
        ],
        'alerts' => [],
        'period' => $period,
        'timestamp' => date('c')
    ],
    'message' => 'Mock metrics data - backend not implemented yet'
];

http_response_code(200);
echo json_encode($response, JSON_PRETTY_PRINT);
