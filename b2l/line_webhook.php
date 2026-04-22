<?php
// ブラウザで開いた時に強制的にファイルを作るテスト
file_put_contents('test_write.txt', 'write test ok: ' . date('Y-m-d H:i:s'));
echo "Test file created.";
exit;
// LINEからのデータを取得
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// 届いたデータをそのままファイルに保存して中身を確認する（デバッグ用）
file_put_contents('debug.txt', $json);

if (empty($data['events'])) exit;

$event = $data['events'][0];
$lineUserId = $event['source']['userId'];

if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
    $text = trim($event['message']['text']);
    
    if (is_numeric($text)) {
        $teamId = (int)$text;

        // --- データベース接続設定を確認してください ---
        // さくらインターネットの場合、localhostで動かないことがあります
        $host = 'mysqlXXX.db.sakura.ne.jp'; // さくらの管理画面で確認できる「データベースサーバ」
        $user = 'ユーザー名';
        $pass = 'パスワード';
        $dbname = 'kasugai-sp_b2l-league';

        $db = new mysqli($host, $user, $pass, $dbname);
        
        if ($db->connect_error) {
            file_put_contents('debug.txt', "DB接続エラー: " . $db->connect_error, FILE_APPEND);
            exit;
        }

        // 文字コード設定（文字化け防止）
        $db->set_charset("utf8mb4");

        $stmt = $db->prepare("UPDATE teams SET line_user_id = ? WHERE id = ?");
        $stmt->bind_param("si", $lineUserId, $teamId);
        
        if ($stmt->execute()) {
            file_put_contents('debug.txt', "\n更新成功: Team {$teamId} to {$lineUserId}", FILE_APPEND);
        } else {
            file_put_contents('debug.txt', "\n更新失敗: " . $stmt->error, FILE_APPEND);
        }
        $db->close();
    }
}
<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ファイル書き込みテスト
if (file_put_contents('test_status.txt', 'Checking...')) {
    echo "書き込み権限OK";
} else {
    echo "書き込み権限NG：フォルダのパーミッションを確認してください";
}
exit;