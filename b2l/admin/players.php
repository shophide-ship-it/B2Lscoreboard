<?php include '../config.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>選手管理 | B2L League</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    <main>
        <h2>選手管理</h2>
        <table>
            <tr>
                <th>名前</th>
                <th>番号</th>
                <th>身長</th>
                <th>ポジション</th>
                <th>操作</th>
            </tr>
            <?php
            $stmt = $pdo->query("SELECT * FROM players");
            while ($row = $stmt->fetch()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['number']) . '</td>';
                echo '<td>' . htmlspecialchars($row['height']) . '</td>';
                echo '<td>' . htmlspecialchars($row['position']) . '</td>';
                echo '<td><a href="edit_player.php?id=' . $row['id'] . '">編集</a> | <a href="delete_player.php?id=' . $row['id'] . '">削除</a></td>';
                echo '</tr>';
            }
            ?>
        </table>
    </main>
    <?php include '../includes/admin_footer.php'; ?>
</body>
</html>
