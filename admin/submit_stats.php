<?php
include 'db.php';

// スタッツをストレージ
$stmt = $pdo->prepare("INSERT INTO player_stats (game_id, player_id, pts) VALUES (:game_id, :player_id, :pts)");
$stmt->execute(['game_id' => '1', 'player_id' => $_POST['player'], 'pts' => $_POST['pts']]);

// ランキングアップデート
// (更新ロジックを記述)
?>
