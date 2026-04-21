<?php include '../config.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理ダッシュボード | B2L League</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    <main>
        <h2>管理ダッシュボード</h2>
        <nav>
            <ul>
                <li><a href="teams.php">チーム管理</a></li>
                <li><a href="players.php">選手管理</a></li>
                <li><a href="games.php">試合管理</a></li>
                <li><a href="stats.php">スタッツ管理</a></li>
            </ul>
        </nav>
    </main>
    <?php include '../includes/admin_footer.php'; ?>
</body>
</html>
