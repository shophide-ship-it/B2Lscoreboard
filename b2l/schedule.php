<?php
require_once __DIR__ . '/config.php';
$pdo = getDB();
$division = isset($_GET['division']) ? (int)$_GET['division'] : 0;

$sql = "SELECT g.*, ht.name as home_name, ht.short_name as home_short, ht.logo_color as home_color,
        at.name as away_name, at.short_name as away_short, at.logo_color as away_color
        FROM games g JOIN teams ht ON g.home_team_id=ht.id JOIN teams at ON g.away_team_id=at.id";
if ($division > 0) $sql .= " WHERE g.division=$division";
$sql .= " ORDER BY g.game_date DESC, g.game_time ASC";
$games = $pdo->query($sql)->fetchAll();

$byDate = [];
foreach ($games as $g) $byDate[$g['game_date']][] = $g;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スケジュール - <?= SITE_NAME ?></title>
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
                <a href="<?= url('schedule.php') ?>" class="active">スケジュール</a>
                <a href="<?= url('teams.php') ?>">チーム</a>
                <a href="<?= url('standings.php') ?>">順位表</a>
                <a href="<?= url('leaders.php') ?>">リーダーズ</a>
            </nav>
        </div>
    </header>
    <div class="container content-wrapper">
        <div class="section-header"><h2>スケジュール</h2></div>
        <div class="division-tabs mb-2">
            <a href="<?= url('schedule.php') ?>" class="division-tab <?= $division===0?'active':'' ?>">全部門</a>
            <a href="<?= url('schedule.php?division=1') ?>" class="division-tab <?= $division===1?'active':'' ?>">1部</a>
            <a href="<?= url('schedule.php?division=2') ?>" class="division-tab <?= $division===2?'active':'' ?>">2部</a>
            <a href="<?= url('schedule.php?division=3') ?>" class="division-tab <?= $division===3?'active':'' ?>">3部</a>
        </div>
        <?php if (empty($byDate)): ?>
            <div class="empty-state"><div class="icon">📅</div><p>スケジュールがありません</p></div>
        <?php else: foreach ($byDate as $date => $dayGames): ?>
            <h3 style="color:var(--text-secondary);font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin:24px 0 12px"><?= date('Y年n月j日 (D)', strtotime($date)) ?></h3>
            <div class="games-grid mb-2">
                <?php foreach ($dayGames as $game): ?>
                    <?php $fin=$game['status']==='finished'; $hw=$fin&&$game['home_score']>$game['away_score']; $aw=$fin&&$game['away_score']>$game['home_score']; ?>
                    <div class="game-card">
                        <div class="game-status">
                            <span><?= getDivisionName($game['division']) ?></span>
                            <span class="status-badge status-<?= $game['status'] ?>"><?= $game['status']==='finished'?'終了':($game['status']==='live'?'LIVE':'予定') ?></span>
                        </div>
                        <div class="game-matchup">
                            <div class="game-team"><div class="team-logo-circle" style="background:<?= $game['home_color'] ?>"><?= $game['home_short'] ?></div><span class="team-name"><?= htmlspecialchars($game['home_name']) ?></span></div>
                            <div class="game-score">
                                <?php if ($fin||$game['status']==='live'): ?>
                                    <span class="score <?= $hw?'winner':'loser' ?>"><?= $game['home_score'] ?></span><span class="vs">-</span><span class="score <?= $aw?'winner':'loser' ?>"><?= $game['away_score'] ?></span>
                                <?php else: ?>
                                    <span class="vs"><?= $game['game_time']?date('H:i',strtotime($game['game_time'])):'VS' ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="game-team"><div class="team-logo-circle" style="background:<?= $game['away_color'] ?>"><?= $game['away_short'] ?></div><span class="team-name"><?= htmlspecialchars($game['away_name']) ?></span></div>
                        </div>
                        <?php if ($game['venue']): ?><div class="game-info"><span>📍 <?= htmlspecialchars($game['venue']) ?></span></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <footer class="main-footer"><div class="footer-content"><div class="footer-logo">B2L <span>LEAGUE</span></div><div class="footer-copy">© 2024 B2L League.</div></div></footer>
    <script src="<?= url('js/app.js') ?>"></script>
</body>
</html>
