<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>チーム詳細 | B2L League</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <h2>チーム詳細</h2>
        <?php
        $team_id = $_GET['id'] ?? 1; // デフォルト値を設定
        $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
        $stmt->execute([$team_id]);
        $team = $stmt->fetch();

        echo '<h3>' . htmlspecialchars($team['name']) . '</h3>';
        echo '<p>部: ' . htmlspecialchars($team['division']) . '</p>';

        echo '<h4>選手:</h4>';
        $stmt = $pdo->prepare("SELECT * FROM players WHERE team_id = ?");
        $stmt->execute([$team_id]);
        echo '<ul>';
        while ($player = $stmt->fetch()) {
            echo '<li>' . htmlspecialchars($player['name']) . ' (' . htmlspecialchars($player['number']) . ')</li>';
        }
        echo '</ul>';
        ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
