<?php
// /b2l/register/check_schema.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config.php';

$pdo = getDB();

echo "<h3>テーブル一覧</h3>";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "- $t<br>";
}

echo "<h3>game_schedule 構造</h3>";
try {
    $cols = $pdo->query("DESCRIBE game_schedule")->fetchAll();
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($cols as $c) {
        echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td><td>{$c['Key']}</td><td>{$c['Default']}</td></tr>";
    }
    echo "</table>";
} catch (Throwable $e) {
    echo "game_schedule テーブルが存在しません: " . $e->getMessage();
}
?>
