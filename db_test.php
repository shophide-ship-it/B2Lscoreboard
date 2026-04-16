<?php
$host = 'mysql3114.db.sakura.ne.jp';
$db   = 'kasugai-sp_b2l-league';
$user = 'kasugai-sp_b2l-league';
$pass = 'パスワード'; // 実際のパスワードに置き換えてください。

// DSN（データソース名）を作成
$dsn = "mysql:host=$host;dbname=$db;charset=utf8";

try {
    $pdo = new PDO($dsn, $user, $pass);
    echo "接続成功";
} catch (PDOException $e) {
    echo "接続失敗: " . $e->getMessage();
}
?>

