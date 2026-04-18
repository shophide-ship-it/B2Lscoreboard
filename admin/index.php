<?php
include 'db.php'; // データベース接続ファイルをインクルード

// チーム情報を取得するクエリ
$query = "SELECT id, name, division FROM teams";
$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>バスケットボールリーグ管理</title>
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ffcc00;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #ffcc00;
            color: #121212;
        }
    </style>
</head>
<body>

<h1>バスケットボールリーグ管理</h1>
<h2>チーム一覧</h2>
<table>
    <tr>
        <th>ID</th>
        <th>チーム名</th>
        <th>部</th>
    </tr>

    <?php
    // データが存在する場合、テーブルに表示
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['division']}</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='3'>チームが見つかりません。</td></tr>";
    }
    ?>

</table>

</body>
</html>

<?php
$conn->close(); // データベース接続を閉じる
?>
