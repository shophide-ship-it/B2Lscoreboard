<!-- stats_input.php -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スタッツ入力</title>
</head>
<body>
    <h1>スタッツ入力</h1>
    <form action="submit_stats.php" method="post">
        <label for="player_id">選手ID:</label>
        <input type="number" name="player_id" required><br>
        <label for="game_date">試合日:</label>
        <input type="date" name="game_date" required><br>
        <!-- 他の項目も追加 -->
        <input type="submit" value="スタッツを送信">
    </form>
</body>
</html>
