<?php
header('Content-Type: application/json');

echo json_encode([
    'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT SET',
    'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'NOT SET',
    'getallheaders' => function_exists('getallheaders') ? getallheaders() : 'NOT AVAILABLE',
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
]);
