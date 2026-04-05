<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../config.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "=== DB接続OK ===\n\n";
}
catch (PDOException $e) {
    echo "DB接続エラー: " . $e->getMessage() . "\n";
    exit;
}

// api_tokensテーブルの構造を確認
echo "=== api_tokens テーブル構造 ===\n";
try {
    $stmt = $pdo->query("DESCRIBE api_tokens");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . " | " . $col['Key'] . "\n";
    }
}
catch (PDOException $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}

echo "\n=== api_tokens データ ===\n";
try {
    $stmt = $pdo->query("SELECT * FROM api_tokens");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "レコード数: " . count($rows) . "\n\n";
    foreach ($rows as $row) {
        foreach ($row as $key => $val) {
            echo "  $key: $val\n";
        }
        echo "---\n";
    }
}
catch (PDOException $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}