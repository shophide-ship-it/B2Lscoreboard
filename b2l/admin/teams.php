<?php include '../config.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>チーム管理 | B2L League</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    <main>
        <h2>チーム管理</h2>
        <table>
            <tr>
                <th>チーム名</th>
                <th>部</th>
                <th>操作</th>
            </tr>
            <?php
            $stmt = $pdo->query("SELECT * FROM teams");
            while ($row = $stmt->fetch()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['division']) . '</td>';
                echo '<td><a href="edit_team.php?id=' . $row['id'] . '">編集</a> | <a href="delete_team.php?id=' . $row['id'] . '">削除</a></td>';
                echo '</tr>';
            }
            ?>
        </table>
    </main>
    <?php include '../includes/admin_footer.php'; ?>
</body>
</html>
