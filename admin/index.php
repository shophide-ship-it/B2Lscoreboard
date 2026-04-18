<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>バスケットボールリーグ管理</title>
    <link rel="stylesheet" href="style.css"> <!-- スタイルシート -->
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
// チーム情報をDBから取得
include 'db.php';

/** @var PDO $pdo */ // ←この1行を追加してください
$stmt = $pdo->query("SELECT * FROM teams");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['name']}</td>
                <td>{$row['division']}</td>
            </tr>";
        }
        ?>
    </table>
</body>
</html>
