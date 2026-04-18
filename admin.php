<?php
session_start();
if (!(isset($_SESSION['authenticated']) && $_SESSION['authenticated'])) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    die("Not authorized");
}

include 'db.php';

// DBからチームデータを取得
$stmt = $pdo->query("SELECT * FROM teams");
$teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>バスケットボールリーグ管理</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>バスケットボールリーグ管理</h1>
    </header>
    <main>
        <h2>チーム一覧</h2>
        <ul>
            <?php foreach ($teams as $team): ?>
                <li><?php echo htmlspecialchars($team['name']); ?> (<?php echo htmlspecialchars($team['division']); ?>)</li>
            <?php endforeach; ?>
        </ul>
        <!-- スタッツ登録フォームなど追加 -->
    </main>
</body>
</html>
