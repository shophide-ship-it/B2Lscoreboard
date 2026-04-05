<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../config.php';

echo "<html><head><meta charset='utf-8'><title>チーム一覧</title></head><body>";
echo "<h2>チーム一覧と登録URL</h2>";

try {
    $db = getDB();
    
    $stmt = $db->query("SELECT id, name, short_name, division, token, rep_name FROM teams ORDER BY division, name");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>チーム数: " . count($teams) . "</p>";
    
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr style='background:#333;color:#fff'>";
    echo "<th>ID</th><th>チーム名</th><th>略称</th><th>Division</th><th>代表者</th><th>トークン</th><th>登録URL</th>";
    echo "</tr>";
    
    $base = "https://kasugai-sp.sakura.ne.jp/b2l/register/players.php";
    
    foreach ($teams as $t) {
        $token = $t['token'] ?: '<span style="color:red">未設定</span>';
        $url = $t['token'] 
            ? "<a href='{$base}?token={$t['token']}' target='_blank'>開く</a>" 
            : '-';
        
        echo "<tr>";
        echo "<td>{$t['id']}</td>";
        echo "<td>" . htmlspecialchars($t['name']) . "</td>";
        echo "<td>" . htmlspecialchars($t['short_name']) . "</td>";
        echo "<td>{$t['division']}</td>";
        echo "<td>" . htmlspecialchars($t['rep_name']) . "</td>";
        echo "<td style='font-size:11px'>" . htmlspecialchars($token) . "</td>";
        echo "<td>{$url}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>選手登録状況</h2>";
    $stmt2 = $db->query("
        SELECT t.name, COUNT(pr.id) as player_count 
        FROM teams t 
        LEFT JOIN player_registrations pr ON t.id = pr.team_id 
        GROUP BY t.id, t.name 
        ORDER BY t.name
    ");
    $regs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($regs as $r) {
        $color = $r['player_count'] > 0 ? 'green' : 'gray';
        echo "<p><span style='color:{$color}'>●</span> " . htmlspecialchars($r['name']) . ": {$r['player_count']}名</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
