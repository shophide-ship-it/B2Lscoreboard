<?php
// /b2l/register/debug_players.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Step 1: Start<br>";

echo "Step 2: Loading config...<br>";
require_once __DIR__ . '/../config.php';
echo "Step 3: Config loaded<br>";

$token = $_GET['token'] ?? '';
echo "Step 4: Token = " . htmlspecialchars($token) . "<br>";

try {
    $pdo = getDB();
    echo "Step 5: DB connected<br>";
    
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE token = ?");
    $stmt->execute([$token]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($team) {
        echo "Step 6: Team found - " . htmlspecialchars($team['name']) . "<br>";
        echo "Step 7: Division = " . htmlspecialchars($team['division']) . "<br>";
        echo "Step 8: rep_name = " . htmlspecialchars($team['rep_name'] ?? '(empty)') . "<br>";
    } else {
        echo "Step 6: Team NOT found<br>";
    }
    
    // player_registrations テーブル存在確認
    echo "Step 9: Checking player_registrations table...<br>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'player_registrations'");
    $exists = $stmt->fetch();
    echo "Step 10: player_registrations exists = " . ($exists ? 'YES' : 'NO') . "<br>";
    
    if ($exists) {
        $stmt = $pdo->query("DESCRIBE player_registrations");
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Step 11: Columns = " . implode(', ', $cols) . "<br>";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<br>Step 12: Now trying to include players.php logic...<br>";

// players.php の中身を読み込んでシンタックスチェック
$file = __DIR__ . '/players.php';
if (file_exists($file)) {
    echo "File size: " . filesize($file) . " bytes<br>";
    echo "First 3 bytes (hex): " . bin2hex(substr(file_get_contents($file), 0, 3)) . "<br>";
    
    // シンタックスチェック
    $output = [];
    $ret = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $ret);
    echo "Syntax check: " . implode('<br>', $output) . "<br>";
} else {
    echo "players.php NOT FOUND<br>";
}
?>
