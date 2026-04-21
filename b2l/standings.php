<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>順位 | B2L League</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <h2>順位表</h2>
        <table>
            <tr>
                <th>順位</th>
                <th>チーム名</th>
                <th>勝利数</th>
                <th>敗北数</th>
            </tr>
            <?php
            $stmt = $pdo->query("SELECT teams.name, COUNT(games.winner) as wins FROM teams LEFT JOIN games ON teams.id = games.winner GROUP BY teams.id ORDER BY wins DESC");
            $rank = 1;
            while ($row = $stmt->fetch()) {
                echo '<tr>';
                echo '<td>' . $rank++ . '</td>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['wins']) . '</td>';
                echo '<td>' . htmlspecialchars($row['losses']) . '</td>';
                echo '</tr>';
            }
            ?>
        </table>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
