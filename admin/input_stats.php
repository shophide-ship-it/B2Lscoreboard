<?php
include 'db.php'; // データベース接続ファイルをインクルード

// スタッツが送信された場合の処理
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // フォームから送信されたデータを取得
    $game_id = $_POST['game_id'];
    $player_id = $_POST['player_id'];
    $pts = $_POST['pts'];
    $reb = $_POST['reb'];
    $ast = $_POST['ast'];
    // その他のスタッツを追加...

    // データベースにスタッツを挿入
    $stmt = $conn->prepare("INSERT INTO player_stats (game_id, player_id, pts, reb, ast) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiii", $game_id, $player_id, $pts, $reb, $ast);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スタッツ入力</title>
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: Arial, sans-serif;
        }
        form {
            margin: 20px 0;
        }
        input {
            margin: 5px;
            padding: 10px;
            width: 200px;
        }
        button {
            padding: 10px 20px;
            background-color: #ffcc00;
            border: none;
            color: #121212;
            cursor: pointer;
        }
    </style>
</head>
<body>

<h1>スタッツ入力</h1>
<form action="input_stats.php" method="post">
    <label for="game_id">ゲームID:</label><br>
    <input type="number" id="game_id" name="game_id" required><br>
    <label for="player_id">選手ID:</label><br>
    <input type="number" id="player_id" name="player_id" required><br>
    <label for="pts">ポイント:</label><br>
    <input type="number" id="pts" name="pts" required><br>
    <label for="reb">リバウンド:</label><br>
    <input type="number" id="reb" name="reb" required><br>
    <label for="ast">アシスト:</label><br>
    <input type="number" id="ast" name="ast" required><br>
    <!-- その他のスタッツのフィールドを追加 -->
    <button type="submit">スタッツを送信</button>
</form>

</body>
</html>

<?php
$conn->close(); // データベース接続を閉じる
?>
