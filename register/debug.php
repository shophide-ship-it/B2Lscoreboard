<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: PHP OK\n";

require_once __DIR__ . '/../config.php';
echo "Step 2: Config OK\n";

$db = getDB();
echo "Step 3: DB OK\n";

$token = $_GET['token'] ?? 'none';
echo "Step 4: Token = " . $token . "\n";

$stmt = $db->prepare("SELECT * FROM teams WHERE token = ?");
$stmt->execute([$token]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);

if ($team) {
    echo "Step 5: Team found = " . $team['name'] . "\n";
} else {
    echo "Step 5: Team NOT found\n";
}

echo "Step 6: All OK\n";
