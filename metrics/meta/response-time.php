<?php
/**
 * Meta Response Time Metrics Endpoint (Mock)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = [
    'success' => true,
    'data' => [
        'average' => 0,
        'p50' => 0,
        'p95' => 0,
        'p99' => 0,
        'samples' => []
    ],
    'message' => 'Mock data - backend not implemented'
];

echo json_encode($response, JSON_PRETTY_PRINT);
