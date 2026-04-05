<?php
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/config.php';

$pdo = getDB();

echo "=== team_registrations テーブル構造 ===\n";
$stmt = $pdo->query("SHOW COLUMNS FROM team_registrations");
$cols = $stmt->fetchAll();
foreach ($cols as $c) {
    echo "  {$c['Field']} ({$c['Type']}) {$c['Null']} {$c['Key']} Default:{$c['Default']}\n";
}

echo "\n=== player_registrations テーブル構造 ===\n";
$stmt = $pdo->query("SHOW COLUMNS FROM player_registrations");
$cols = $stmt->fetchAll();
foreach ($cols as $c) {
    echo "  {$c['Field']} ({$c['Type']}) {$c['Null']} {$c['Key']} Default:{$c['Default']}\n";
}

echo "\n=== game_schedule テーブル構造 ===\n";
$stmt = $pdo->query("SHOW COLUMNS FROM game_schedule");
$cols = $stmt->fetchAll();
foreach ($cols as $c) {
    echo "  {$c['Field']} ({$c['Type']}) {$c['Null']} {$c['Key']} Default:{$c['Default']}\n";
}

echo "\n=== team_registrations データ件数 ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM team_registrations");
echo "  " . $stmt->fetch()['cnt'] . " 件\n";

echo "\n=== player_registrations データ件数 ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM player_registrations");
echo "  " . $stmt->fetch()['cnt'] . " 件\n";

echo "\n=== teams データ件数 ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM teams");
echo "  " . $stmt->fetch()['cnt'] . " 件\n";

echo "\n=== players データ件数 ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM players");
echo "  " . $stmt->fetch()['cnt'] . " 件\n";

echo "\n=== teams データ一覧 ===\n";
$stmt = $pdo->query("SELECT id, name, division, registration_id FROM teams ORDER BY id");
$rows = $stmt->fetchAll();
foreach ($rows as $r) {
    echo "  ID:{$r['id']} {$r['name']} Div:{$r['division']} RegID:{$r['registration_id']}\n";
}

echo "\n完了\n";
?>
