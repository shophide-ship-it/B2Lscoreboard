<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
$pdo = getDB();
$pdo->exec("SET NAMES utf8mb4");

echo "<h2>Japanese Insert Test</h2>";

try {
    $pdo->exec("DELETE FROM players WHERE number IN (98, 99)");
    
    $stmt = $pdo->prepare("INSERT INTO players (team_id, number, name, position, is_active) VALUES (1, 98, ?, 'PG', 1)");
    $stmt->execute(['TestJP']);
    echo "<p>Insert 1 OK</p>";
    
    $r = $pdo->query("SELECT name FROM players WHERE number = 98")->fetchColumn();
    echo "<p>Read back: " . $r . "</p>";
    
    // Now try actual Japanese
    $pdo->exec("DELETE FROM players WHERE number = 97");
    $jp = "\xe3\x83\x86\xe3\x82\xb9\xe3\x83\x88"; // ¥Æ¥¹¥È in UTF-8 bytes
    $stmt2 = $pdo->prepare("INSERT INTO players (team_id, number, name, position, is_active) VALUES (1, 97, ?, 'PG', 1)");
    $stmt2->execute([$jp]);
    echo "<p>Insert JP OK</p>";
    
    $r2 = $pdo->query("SELECT name FROM players WHERE number = 97")->fetchColumn();
    echo "<p>JP Read: " . $r2 . "</p>";
    echo "<p>JP Hex: " . bin2hex($r2) . "</p>";
    
    // Clean
    $pdo->exec("DELETE FROM players WHERE number IN (97, 98)");
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Now check existing players
echo "<h2>Existing Players</h2>";
$rows = $pdo->query("SELECT id, number, name, hex(name) as hexname FROM players ORDER BY id LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1'><tr><th>ID</th><th>#</th><th>Name</th><th>Hex</th></tr>";
foreach ($rows as $row) {
    echo "<tr><td>{$row['id']}</td><td>{$row['number']}</td><td>" . htmlspecialchars((string)$row['name']) . "</td><td>{$row['hexname']}</td></tr>";
}
echo "</table>";

// Re-register with clean data
echo "<h2>Re-register Players</h2>";
$game = $pdo->query("SELECT home_team_id, away_team_id FROM games WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
$hid = $game['home_team_id'];
$aid = $game['away_team_id'];

$pdo->exec("DELETE FROM players WHERE team_id IN ($hid, $aid)");

$ins = $pdo->prepare("INSERT INTO players (team_id, number, name, position, is_active) VALUES (?, ?, ?, 'SF', 1)");

$home = [[4,'Tanaka'],[5,'Suzuki'],[6,'Sato'],[7,'Yamada'],[8,'Takahashi'],[9,'Matsuse'],[10,'Nakamura'],[11,'Kobayashi'],[12,'Kato'],[13,'Watanabe'],[14,'Ito'],[15,'Yoshida']];
$away = [[1,'Ishikawa'],[2,'Kimura'],[3,'Maeda'],[4,'Fujita'],[5,'Okada'],[6,'Matsumoto'],[7,'Inoue'],[8,'Mori'],[9,'Shimizu'],[10,'Hasegawa'],[11,'Kondo'],[12,'Sakamoto']];

foreach ($home as $p) { $ins->execute([$hid, $p[0], $p[1]]); }
foreach ($away as $p) { $ins->execute([$aid, $p[0], $p[1]]); }

echo "<p style='color:green; font-size:20px'>Done! Home:" . count($home) . " Away:" . count($away) . "</p>";
echo "<p><a href='https://kasugai-sp.sakura.ne.jp/b2l/scorer/' style='font-size:24px'>Open Scorer</a></p>";
?>
