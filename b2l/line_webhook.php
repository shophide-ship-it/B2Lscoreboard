<?php
// LINEからのアクセスであることの検証（簡易版）
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// イベントがない場合は終了
if (empty($data['events'])) exit;

$event = $data['events'][0];
$lineUserId = $event['source']['userId']; // 送信者のLINE User ID

// メッセージイベントの場合
if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
    $text = trim($event['message']['text']); // 送られてきたテキスト
    
    // 数字だけが送られてきたかチェック（例：「1」や「24」）
    if (is_numeric($text)) {
        $teamId = (int)$text;

        // 1〜24の範囲内かチェック
        if ($teamId >= 1 && $teamId <= 24) {
            
            // --- データベース接続 (ご自身の環境に合わせて修正してください) ---
            $db = new mysqli('localhost', 'kasugai-sp_b2l-league', 'B2L_db2025secure', 'kasugai-sp_b2l-league');
            
            // line_user_id を更新
            $stmt = $db->prepare("UPDATE teams SET line_user_id = ? WHERE id = ?");
            $stmt->bind_param("si", $lineUserId, $teamId);
            
            if ($stmt->execute()) {
                $replyText = "連携完了！\nチームID: " . $teamId . " の代表者として登録しました。";
            } else {
                $replyText = "エラーが発生しました。時間を置いて再度お試しください。";
            }
            $db->close();
            
        } else {
            $replyText = "チームIDは1〜24の間で入力してください。";
        }
        
        // --- 応答メッセージを送る (Messaging API) ---
        sendReply($event['replyToken'], $replyText);
    }
}

// 応答用関数
function sendReply($replyToken, $text) {
    $accessToken = 'あなたのチャネルアクセストークン';
    $url = 'https://api.line.me/v2/bot/message/reply';
    $postData = [
        'replyToken' => $replyToken,
        'messages' => [['type' => 'text', 'text' => $text]]
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_exec($ch);
    curl_close($ch);
}