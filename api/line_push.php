<?php
require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (!$message) {
    echo json_encode(['error' => 'メッセージが空です']);
    exit;
}

// LINE Broadcast API
$url = 'https://api.line.me/v2/bot/message/broadcast';
$data = [
    'messages' => [
        [
            'type' => 'text',
            'text' => $message
        ]
    ]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . LINE_ACCESS_TOKEN
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo json_encode(['success' => true, 'message' => '配信しました']);
} else {
    $res = json_decode($response, true);
    echo json_encode([
        'error' => 'LINE API エラー: ' . ($res['message'] ?? 'HTTP ' . $httpCode)
    ]);
}
