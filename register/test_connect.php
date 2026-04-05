<?php
$host = 'mysql3114.db.sakura.ne.jp';
$dbname = 'kasugai-sp_b2l-league';
$user = 'kasugai-sp_b2l-league';
$pass = 'X_MJJk5CfDwv4nf';

echo "<h2>接続テスト</h2>";
echo "<p>HOST: {$host}</p>";
echo "<p>DB: {$dbname}</p>";
echo "<p>USER: {$user}</p>";
echo "<hr>";

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<h3 style='color:green'>✅ 接続成功！</h3>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>テーブル数: " . count($tables) . "</p>";
    echo "<ul>";
    foreach ($tables as $t) {
        echo "<li>{$t}</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<h3 style='color:red'>❌ 失敗: " . $e->getMessage() . "</h3>";
}
