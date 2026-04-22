<?php
// エラー表示
ini_set('display_errors', 1);
error_reporting(E_ALL);

$json = file_get_contents('php://input');
$data = json_decode($json, true);

// ログ記録の開始
$logFile = 'debug.txt';
$now = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "Webhook node is active.";
    exit;
}

// 受信データを記録
file_put_contents($logFile, "[{$now}] Received: {$json}\n", FILE_APPEND);

if (!empty($data['events'])) {
    foreach ($data['events'] as $event) {
        $lineUserId = $event['source']['userId'] ?? null;
        $text = trim($event['message']['text'] ?? '');

        file_put_contents($logFile, "[{$now}] Processing: User={$lineUserId}, Text={$text}\n", FILE_APPEND);

        if ($lineUserId && is_numeric($text)) {
            $teamId = (int)$text;

            // データベース接続
            $db = new mysqli('mysql3114.db.sakura.ne.jp', 'kasugai-sp_b2l-league', 'B2L_db2025secure', 'kasugai-sp_b2l-league');
            if ($db->connect_error) {
                file_put_contents($logFile, "[{$now}] DB Connection Error: {$db->connect_error}\n", FILE_APPEND);
                continue;
            }

            $db->set_charset("utf8mb4");
            $stmt = $db->prepare("UPDATE teams SET line_user_id = ? WHERE id = ?");
            $stmt->bind_param("si", $lineUserId, $teamId);
            
            if ($stmt->execute()) {
                $affected = $db->affected_rows; // 実際に更新された行数
                file_put_contents($logFile, "[{$now}] DB Update Success: Affected Rows={$affected}\n", FILE_APPEND);
            } else {
                file_put_contents($logFile, "[{$now}] DB Update Failed: {$stmt->error}\n", FILE_APPEND);
            }
            $db->close();
        }
    }
}