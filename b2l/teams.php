<?php
require_once __DIR__ . '/config.php';
$pdo = getDB();
$teamsData = [];
for ($d=1;$d<=3;$d++) {
    $teamsData[$d] = $pdo->query("SELECT t.*, s.wins, s.losses FROM teams t LEFT JOIN standings s ON t.id=s.team_id AND s.season='2024-25' WHERE t.division=$d ORDER BY t.name")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>チーム - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body>
    <header class="main-header">
        <div class="header-top"><div class="header-top-inner"><a href="<?= url('admin/') ?>">管理ページ</a></div></div>
        <div class="nav-container">
            <a href="<?= url('index.php') ?>" class="logo"><span class="logo-icon">🏀</span><span class="logo-text">B2L <span>LEAGUE</span></span></a>
            <button class="mobile-menu-btn">☰</button>
            <nav class="main-nav">
                <a href="<?= url('index.php') ?>">ホーム</a>
                <a href="<?= url('schedule.php') ?>">スケジュール</a>
                <a href="<?= url('teams.php') ?>" class="active">チーム</a>
                <a href="<?= url('standings.php') ?>">順位表</a>
                <a href="<?= url('leaders.php') ?>">リーダーズ</a>
            </nav>
        </div>
    </header>
    <div class="container content-wrapper">
        <div class="section-header"><h2>チーム</h2></div>
        <?php for ($d=1;$d<=3;$d++): ?>
            <h3 style="color:var(--text-secondary);font-size:16px;font-weight:700;margin:30px 0 16px;padding-bottom:8px;border-bottom:2px solid var(--dark-border)"><?= getDivisionName($d) ?></h3>
            <div class="teams-grid mb-3">
                <?php foreach ($teamsData[$d] as $t): ?>
                    <a href="<?= url('team.php?id='.$t['id']) ?>" class="team-card">
                        <div class="team-logo" style="background:<?= $t['logo_color'] ?>"><?= $t['short_name'] ?></div>
                        <div class="team-name"><?= htmlspecialchars($t['name']) ?></div>
                        <div class="team-division"><?= ($t['wins']??0) ?>勝 <?= ($t['losses']??0) ?>敗</div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
    </div>
    <footer class="main-footer"><div class="footer-content"><div class="footer-logo">B2L <span>LEAGUE</span></div><div class="footer-copy">© 2024 B2L League.</div></div></footer>
    <script src="<?= url('js/app.js') ?>"></script>
</body>
</html>

