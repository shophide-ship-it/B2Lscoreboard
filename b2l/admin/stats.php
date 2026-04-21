<?php include '../config.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スタッツ管理 | B2L League</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    <main>
        <h2>スタッツ管理</h2>
        <table>
            <tr>
                <th>選手名</th>
                <th>PTS</th>
                <th>REB</th>
                <th>AST</th>
                <th>操作</th>
            </tr>
            <?php
            $stmt = $pdo->query("SELECT players.name, stats.PTS, stats.REB, stats.AST FROM stats JOIN players ON stats.player_id = players.id");
            while ($row = $stmt->fetch()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['PTS']) . '</td>';
                echo '<td>' . htmlspecialchars($row['REB']) . '</td>';
                echo '<td>' . htmlspecialchars($row['AST']) . '</td>';
                echo '<td><a href="edit_stats.php?id=' . $row['player_id'] . '">編集</a> | <a href="delete_stats.php?id=' . $row['player_id'] . '">削除</a></td>';
                echo '</tr>';
            }
            ?>
        </table>
    </main>
    <?php include '../includes/admin_footer.php'; ?>
</body>
</html>
