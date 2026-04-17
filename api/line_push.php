<?php
// /b2l/line_push.php

function line_push($message) {
    $access_token = 'YOUR_LINE_ACCESS_TOKEN'; // 実際のアクセストークンを設定
    $url = 'https://api.line.me/v2/bot/message/push';

    $post_data = [
        'to' => 'YOUR_LINE_USER_ID', // 受信者のLINE IDを設定
        'messages' => [[
            'type' => 'text',
            'text' => $message,
        ]],
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result; // 成功/失敗を返す
}
