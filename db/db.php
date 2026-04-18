<?php
// db.php
try {
    $host = 'mysql3114.db.sakura.ne.jp'; // データベースホスト
    $dbname = 'kasugai-sp_b2l-league'; // データベース名
    $username = 'kasugai-sp_b2l-league'; // ユーザー名
    $password = 'B2L_db2025secure'; // パスワード

    // PDOを使用してデータベースに接続
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "データベースに接続成功"; // 接続に成功した場合のメッセージ
} catch (PDOException $e) {
    echo "接続失敗: " . $e->getMessage();
    exit(); // 接続失敗時は処理を終了
}
?>
