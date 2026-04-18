<?php
// データベース接続
require_once '../db/db.php';

// スケジュール入力処理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $home_team_id = $_POST['home_team_id'];
    $away_team_id = $_POST['away_team_id'];
    $game_date = $_POST['game_date'];

    // スケジュールをgamesテーブルに挿入
    $stmt = $pdo->prepare("INSERT INTO games (home_team_id, away_team_id, game_date) VALUES (?, ?, ?)");
    $stmt->execute([$home_team_id, $away_team_id, $game_date]);

    echo "スケジュールが追加されました。";
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スケジュール入力</title>
</head>
<body>
    <h1>スケジュール入力</h1>
    <form method="POST" action="">
        <label for="home_team_id">ホームチーム:</label>
        <input type="number" name="home_team_id" required><br>
        <label for="away_team_id">アウェイチーム:</label>
        <input type="number" name="away_team_id" required><br>
        <label for="game_date">試合日時:</label>
        <input type="datetime-local" name="game_date" required><br>
        <button type="submit">追加</button>
    </form>
</body>
</html>
