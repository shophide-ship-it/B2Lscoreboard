<?php include '../config.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>試合管理 | B2L League</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    <main>
        <h2>試合管理</h2>
        <table>
            <tr>
                <th>日時</th>
                <th>チーム1</th>
                <th>チーム2</th>
                <th>操作</th>
            </tr>
            <?php
            $stmt = $pdo->query("SELECT * FROM schedule");
            while ($row = $stmt->fetch()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['date_time']) . '</td>';
                echo '<td>' . htmlspecialchars($row['team1']) . '</td>';
                echo '<td>' . htmlspecialchars($row['team2']) . '</td>';
                echo '<td><a href="edit_game.php?id=' . $row['id'] . '">編集</a> | <a href="delete_game.php?id=' . $row['id'] . '">削除</a></td>';
                echo '</tr>';
            }
            ?>
        </table>
    </main>
    <?php include '../includes/admin_footer.php'; ?>
</body>
</html>
