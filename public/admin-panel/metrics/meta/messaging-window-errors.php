<?php
/**
 * Meta Messaging Window Errors Endpoint (Mock)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$period = $_GET['period'] ?? '1h';
$platform = $_GET['platform'] ?? 'all';

$response = [
    'success' => true,
    'data' => [
        'errors' => [],
        'total' => 0,
        'platform' => $platform
    ],
    'message' => 'Mock data - backend not implemented'
];

echo json_encode($response, JSON_PRETTY_PRINT);
