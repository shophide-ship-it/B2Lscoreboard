<?php
// 1. 設定情報の定義
$host = 'mysql3114.db.sakura.ne.jp';
$dbname = 'kasugai-sp_b2l-league';
$username = 'kasugai-sp_b2l-league';
$password = 'B2L_db2025secure';

// 2. データベースに接続する（この処理が終わると $pdo が使えるようになります）
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // 接続に失敗した場合はエラーを表示して止める
    die("Could not connect to the database {$dbname} :" . $e->getMessage());
}

// 注意：ここでは SELECT 文（データの取得）は書かなくてOKです。
// データの取得は、表示を行う index.php 側でやるのが一般的です。