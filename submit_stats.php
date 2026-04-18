<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $player_id = $_POST['player_id'];
    $game_date = $_POST['game_date'];
    // 他のスタッツフィールドも取得

    $stmt = $pdo->prepare("INSERT INTO stats (player_id, game_date, pts, reb, ast) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$player_id, $game_date, $pts, $reb, $ast]);

    // ランキング更新ロジックなども追加

    header('Location: index.php');
    exit;
}
