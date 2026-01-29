<?php
/**
 * Meta Alerts Endpoint (Mock)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$hours = $_GET['hours'] ?? 1;

$response = [
    'success' => true,
    'data' => [
        'alerts' => [],
        'total' => 0,
        'bySeverity' => [
            'critical' => 0,
            'warning' => 0,
            'info' => 0
        ]
    ],
    'message' => 'Mock data - backend not implemented'
];

echo json_encode($response, JSON_PRETTY_PRINT);
