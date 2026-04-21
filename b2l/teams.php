<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>チーム | B2L League</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <h2>チーム一覧</h2>
        <table>
            <tr>
                <th>チーム名</th>
                <th>部</th>
            </tr>
            <?php
            $stmt = $pdo->query("SELECT * FROM teams");
            while ($row = $stmt->fetch()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['division']) . '</td>';
                echo '</tr>';
            }
            ?>
        </table>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
