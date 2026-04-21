<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>リーダーズ | B2L League</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <h2>スタッツリーダー</h2>
        <table>
            <tr>
                <th>選手名</th>
                <th>PTS</th>
            </tr>
            <?php
            $stmt = $pdo->query("SELECT players.name, SUM(stats.PTS) as total_pts FROM players JOIN stats ON players.id = stats.player_id GROUP BY players.id ORDER BY total_pts DESC LIMIT 10");
            while ($row = $stmt->fetch()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['total_pts']) . '</td>';
                echo '</tr>';
            }
            ?>
        </table>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
