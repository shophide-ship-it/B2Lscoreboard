<?php
// /b2l/register/debug_runtime.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// players.php のコードを直接実行して実行時エラーを見る
require_once __DIR__ . '/../config.php';

$token = $_GET['token'] ?? '';
$pdo = getDB();

// teams テーブルの全カラム確認
echo "<h3>teams テーブル構造</h3>";
$stmt = $pdo->query("DESCRIBE teams");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] === 'YES' ? 'NULL可' : 'NOT NULL') . "<br>";
}

// player_registrations テーブルの全カラム確認  
echo "<h3>player_registrations テーブル構造</h3>";
$stmt = $pdo->query("DESCRIBE player_registrations");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ") " . ($row['Null'] === 'YES' ? 'NULL可' : 'NOT NULL') . "<br>";
}

// 他のテーブルも確認
echo "<h3>全テーブル一覧</h3>";
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo $row[0] . "<br>";
}

echo "<hr><h3>players.php 実行テスト</h3>";

// players.php を include して実行時エラーを表示
try {
    include __DIR__ . '/players.php';
} catch (Throwable $e) {
    echo "<p style='color:red;'><strong>エラー:</strong> " . $e->getMessage() . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
