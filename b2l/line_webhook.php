<?php
file_put_contents('debug.txt', "--- START ---\n", FILE_APPEND);
$logFile = 'debug.txt';
$now = date('Y-m-d H:i:s');

// 届いた生データを取得
$json = file_get_contents('php://input');

// 通信情報（ヘッダー）を記録
$headers = getallheaders();
$signature = $headers['X-Line-Signature'] ?? 'No Signature';

// ログに書き込み
file_put_contents($logFile, "[{$now}] --- New Access ---\n", FILE_APPEND);
file_put_contents($logFile, "[{$now}] Signature: {$signature}\n", FILE_APPEND);
file_put_contents($logFile, "[{$now}] Body: {$json}\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "Webhook node is active.";
    exit;
}

$data = json_decode($json, true);
if (!empty($data['events'])) {
    foreach ($data['events'] as $event) {
        $type = $event['type'] ?? 'unknown';
        file_put_contents($logFile, "[{$now}] Event Type: {$type}\n", FILE_APPEND);
        
        // メッセージ処理（前回のコードと同じ）
        if ($type === 'message') {
            $lineUserId = $event['source']['userId'] ?? null;
            $text = trim($event['message']['text'] ?? '');
            
            if ($lineUserId && is_numeric($text)) {
                $db = new mysqli('mysql3114.db.sakura.ne.jp', 'kasugai-sp_b2l-league', 'B2L_db2025secure', 'kasugai-sp_b2l-league');
                $db->set_charset("utf8mb4");
                $stmt = $db->prepare("UPDATE teams SET line_user_id = ? WHERE id = ?");
                $stmt->bind_param("si", $lineUserId, $text);
                $stmt->execute();
                $db->close();
                file_put_contents($logFile, "[{$now}] DB Updated for Team {$text}\n", FILE_APPEND);
            }
        }
    }
}