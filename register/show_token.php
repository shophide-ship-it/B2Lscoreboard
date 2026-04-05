<?php
// /b2l/register/show_token.php
require_once __DIR__ . '/../config.php';

$pdo = getDB();
$stmt = $pdo->query("SELECT id, name, token FROM teams ORDER BY id DESC LIMIT 5");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Team Tokens</h2>";
foreach ($teams as $t) {
    $url = "https://kasugai-sp.sakura.ne.jp/b2l/register/players.php?token=" . $t['token'];
    echo "<p><strong>{$t['name']}</strong> (ID: {$t['id']})<br>";
    echo "Token: <code>{$t['token']}</code><br>";
    echo "URL: <a href='{$url}'>{$url}</a></p>";
    echo "<hr>";
}
?>
