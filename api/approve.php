<?php
// /b2l/api/approve.php
require_once('../db/Database.php');

// 認証チェック（例）
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['message' => '認証に失敗しました。']);
    exit();
}

// データベース接続
$db = new Database();
$conn = $db->getConnection();

// GETパラメータからIDを取得
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['message' => '無効なリクエストです。']);
    exit();
}

$registrationId = intval($_GET['id']);

try {
    // トランザクション開始
    $conn->beginTransaction();

    // 申請を承認（statusを更新）
    $stmt = $conn->prepare("UPDATE player_registrations SET status = 'approved' WHERE id = ?");
    $stmt->execute([$registrationId]);

    // 承認された選手をplayersテーブルに追加
    $stmt = $conn->prepare("
        INSERT INTO players (name, age) 
        SELECT name, age FROM player_registrations WHERE id = ? 
    ");
    $stmt->execute([$registrationId]);

    // トランザクションをコミット
    $conn->commit();

    // レスポンスの送信
    echo json_encode(['message' => '選手登録が承認されました。']);
    
} catch (Exception $e) {
    // エラーが発生した場合はロールバック
    $conn->rollBack();
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['message' => 'エラーが発生しました。']);
}
