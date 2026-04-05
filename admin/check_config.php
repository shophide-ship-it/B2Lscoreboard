<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config.php';

echo "=== DB接続テスト ===\n\n";

try {
    $pdo = getDB();
    echo "? DB接続成功！\n\n";
    
    // テーブル一覧
    echo "=== テーブル一覧 ===\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        echo "- $t\n";
    }
    
    // teamsテーブル確認
    echo "\n=== teams テーブル構造 ===\n";
    if (in_array('teams', $tables)) {
        $stmt = $pdo->query("SHOW COLUMNS FROM teams");
        $cols = $stmt->fetchAll();
        foreach ($cols as $c) {
            echo "  {$c['Field']} ({$c['Type']})\n";
        }
    } else {
        echo "  teamsテーブルなし\n";
    }
    
    // playersテーブル確認
    echo "\n=== players テーブル構造 ===\n";
    if (in_array('players', $tables)) {
        $stmt = $pdo->query("SHOW COLUMNS FROM players");
        $cols = $stmt->fetchAll();
        foreach ($cols as $c) {
            echo "  {$c['Field']} ({$c['Type']})\n";
        }
    } else {
        echo "  playersテーブルなし\n";
    }
    
} catch (Exception $e) {
    echo "? エラー: " . $e->getMessage() . "\n";
}
?>
