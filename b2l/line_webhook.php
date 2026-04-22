<?php
// 1. エラー表示設定（デバッグ中のみ）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. ブラウザで直接開いた時のチェック用
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "<h1>Webhook node is active.</h1>";
    if (file_put_contents('test_status.txt', 'Checking...' . date('Y-m-d H:i:s'))) {
        echo "<p style='color:green;'>書き込み権限OK: test_status.txt を作成しました。</p>";
    } else {
        echo "<p style='color:red;'>書き込み権限NG: フォルダのパーミッションを確認してください。</p>";
    }
    exit;
}

// 3. LINEからのデータを取得
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// 届いたデータをログに保存
file_put_contents('debug.txt', "Received: " . $json . "\n", FILE_APPEND);

if (empty($data['events'])) exit;

$event = $data['events'][0];
$lineUserId = $event['source']['userId'];

// 4. メッセージ処理
if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
    $text = trim($event['message']['text']);
    
    if (is_numeric($text)) {
        $teamId = (int)$text;

        // データベース接続設定
        $host = 'mysql3114.db.sakura.ne.jp';
        $user = 'kasugai-sp_b2l-league';
        $pass = 'B2L_db2025secure'; // ←後で変更を推奨
        $dbname = 'kasugai-sp_b2l-league';

        $db = new mysqli($host, $user, $pass, $dbname);
        
        if ($db->connect_error) {
            file_put_contents('debug.txt', "DB接続エラー: " . $db->connect_error . "\n", FILE_APPEND);
            exit;
        }

        $db->set_charset("utf8mb4");

        // プリペアドステートメントで安全に更新
        $stmt = $db->prepare("UPDATE teams SET line_user_id = ? WHERE id = ?");
        $stmt->bind_param("si", $lineUserId, $teamId);
        
        if ($stmt->execute()) {
            file_put_contents('debug.txt', "更新成功: Team {$teamId} to {$lineUserId}\n", FILE_APPEND);
            
            // 成功したことを代表者に返信する処理をここに入れることも可能です
        } else {
            file_put_contents('debug.txt', "更新失敗: " . $stmt->error . "\n", FILE_APPEND);
        }
        $db->close();
    }
}