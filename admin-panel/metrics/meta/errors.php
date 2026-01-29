<?php
/**
 * Meta Errors Endpoint (Mock)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$period = $_GET['period'] ?? '1h';
$limit = $_GET['limit'] ?? 20;

$response = [
    'success' => true,
    'data' => [
        'errors' => [],
        'total' => 0,
        'byCode' => []
    ],
    'message' => 'Mock data - backend not implemented'
];

echo json_encode($response, JSON_PRETTY_PRINT);
