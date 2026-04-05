<?php
require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json');

$pdo = getDB();

$stmt = $pdo->query("
    SELECT t.*, 
           (SELECT COUNT(*) FROM players WHERE team_id = t.id) as player_count
    FROM teams t 
    ORDER BY t.created_at DESC
");
$teams = $stmt->fetchAll();

foreach ($teams as &$team) {
    $stmt = $pdo->prepare("
        SELECT number, name, position, height 
        FROM players 
        WHERE team_id = ? 
        ORDER BY number ASC
    ");
    $stmt->execute([$team['id']]);
    $team['players'] = $stmt->fetchAll();
}

echo json_encode($teams);
