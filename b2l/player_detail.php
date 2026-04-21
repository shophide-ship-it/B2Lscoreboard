<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>選手詳細 | B2L League</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main>
        <h2>選手詳細</h2>
        <?php
        $player_id = $_GET['id'] ?? 1; // デフォルト値を設定
        $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
        $stmt->execute([$player_id]);
        $player = $stmt->fetch();

        echo '<h3>' . htmlspecialchars($player['name']) . '</h3>';
        echo '<p>番号: ' . htmlspecialchars($player['number']) . '</p>';
        echo '<p>身長: ' . htmlspecialchars($player['height']) . '</p>';
        echo '<p>ポジション: ' . htmlspecialchars($player['position']) . '</p>';
        ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
