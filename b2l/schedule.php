<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スケジュール | B2L League</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <h2>試合スケジュール</h2>
        <table>
            <tr>
                <th>日時</th>
                <th>チーム1</th>
                <th>チーム2</th>
            </tr>
            <?php
            $stmt = $pdo->query("SELECT * FROM schedule");
            while ($row = $stmt->fetch()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['date_time']) . '</td>';
                echo '<td>' . htmlspecialchars($row['team1']) . '</td>';
                echo '<td>' . htmlspecialchars($row['team2']) . '</td>';
                echo '</tr>';
            }
            ?>
        </table>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
