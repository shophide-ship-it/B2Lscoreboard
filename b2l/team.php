<?php
require_once __DIR__ . '/config.php';
$pdo = getDB();
$teamId = (int)($_GET['id']??0);
if (!$teamId) { header('Location: '.url('teams.php')); exit; }

$team = $pdo->prepare("SELECT t.*, s.wins, s.losses, s.win_pct, s.points_for, s.points_against FROM teams t LEFT JOIN standings s ON t.id=s.team_id AND s.season='2024-25' WHERE t.id=?");
$team->execute([$teamId]); $team = $team->fetch();
if (!$team) { header('Location: '.url('teams.php')); exit; }

$teamStats = $pdo->prepare("
    SELECT p.id, p.number, p.name, p.position, p.height,
           COUNT(ps.id) as gp, ROUND(AVG(ps.pts),1) as ppg, ROUND(AVG(ps.reb),1) as rpg,
           ROUND(AVG(ps.ast),1) as apg, ROUND(AVG(ps.stl),1) as spg, ROUND(AVG(ps.blk),1) as bpg,
           ROUND(SUM(ps.fgm)*100.0/NULLIF(SUM(ps.fga),0),1) as fg_pct,
           ROUND(SUM(ps.three_pm)*100.0/NULLIF(SUM(ps.three_pa),0),1) as three_pct,
           ROUND(SUM(ps.ftm)*100.0/NULLIF(SUM(ps.fta),0),1) as ft_pct
    FROM players p LEFT JOIN player_stats ps ON p.id=ps.player_id
    WHERE p.team_id=? AND p.is_active=1 GROUP BY p.id ORDER BY ppg DESC
");
$teamStats->execute([$teamId]); $teamStats = $teamStats->fetchAll();

$games = $pdo->prepare("
    SELECT g.*, ht.name as home_name, ht.short_name as home_short, ht.logo_color as home_color,
           at.name as away_name, at.short_name as away_short, at.logo_color as away_color
    FROM games g JOIN teams ht ON g.home_team_id=ht.id JOIN teams at ON g.away_team_id=at.id
    WHERE g.home_team_id=? OR g.away_team_id=? ORDER BY g.game_date DESC LIMIT 10
");
$games->execute([$teamId,$teamId]); $games = $games->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($team['name']) ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body>
    <header class="main-header">
        <div class="header-top"><div class="header-top-inner"><a href="<?= url('admin/') ?>">管理ページ</a></div></div>
        <div class="nav-container">
            <a href="<?= url('index.php') ?>" class="logo"><span class="logo-icon">🏀</span><span class="logo-text">B2L <span>LEAGUE</span></span></a>
            <button class="mobile-menu-btn">☰</button>
            <nav class="main-nav">
                <a href="<?= url('index.php') ?>">ホーム</a><a href="<?= url('schedule.php') ?>">スケジュール</a>
                <a href="<?= url('teams.php') ?>" class="active">チーム</a><a href="<?= url('standings.php') ?>">順位表</a><a href="<?= url('leaders.php') ?>">リーダーズ</a>
            </nav>
        </div>
    </header>
    <div class="team-header" style="background:linear-gradient(135deg,<?= $team['logo_color'] ?>22,#1a1a2e)">
        <div class="team-info">
            <div class="team-big-logo" style="background:<?= $team['logo_color'] ?>"><?= $team['short_name'] ?></div>
            <div class="team-details">
                <p style="color:var(--text-muted);font-size:13px;text-transform:uppercase;letter-spacing:1px"><?= getDivisionName($team['division']) ?></p>
                <h1><?= htmlspecialchars($team['name']) ?></h1>
                <div class="team-record"><?= $team['wins']??0 ?>勝 <?= $team['losses']??0 ?>敗 (<?= number_format($team['win_pct']??0,3) ?>)</div>
            </div>
        </div>
    </div>
    <div class="container content-wrapper">
        <section class="mb-3">
            <div class="section-header"><h2>ロスター & スタッツ</h2></div>
            <?php if (empty($teamStats)): ?>
                <div class="empty-state"><p>選手が登録されていません</p></div>
            <?php else: ?>
                <div class="stats-table-wrapper">
                    <table class="stats-table">
                        <thead><tr><th style="text-align:left">#</th><th style="text-align:left">選手名</th><th>POS</th><th>身長</th><th>GP</th><th>PPG</th><th>RPG</th><th>APG</th><th>SPG</th><th>BPG</th><th>FG%</th><th>3P%</th><th>FT%</th></tr></thead>
                        <tbody>
                            <?php foreach ($teamStats as $p): ?>
                            <tr>
                                <td style="text-align:left;color:var(--text-muted);font-weight:600"><?= $p['number'] ?></td>
                                <td style="text-align:left"><a href="<?= url('player.php?id='.$p['id']) ?>" style="font-weight:600"><?= htmlspecialchars($p['name']) ?></a></td>
                                <td><?= $p['position'] ?></td><td><?= $p['height']?$p['height'].'cm':'-' ?></td>
                                <td><?= $p['gp'] ?></td><td class="fw-bold"><?= $p['ppg']??'-' ?></td><td><?= $p['rpg']??'-' ?></td>
                                <td><?= $p['apg']??'-' ?></td><td><?= $p['spg']??'-' ?></td><td><?= $p['bpg']??'-' ?></td>
                                <td><?= $p['fg_pct']!==null?$p['fg_pct'].'%':'-' ?></td>
                                <td><?= $p['three_pct']!==null?$p['three_pct'].'%':'-' ?></td>
                                <td><?= $p['ft_pct']!==null?$p['ft_pct'].'%':'-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <section class="mb-3">
            <div class="section-header"><h2>最近の試合</h2></div>
            <?php if (empty($games)): ?>
                <div class="empty-state"><p>試合データがありません</p></div>
            <?php else: ?>
                <div class="games-grid">
                    <?php foreach ($games as $g): ?>
                        <?php $fin=$g['status']==='finished'; $hw=$fin&&$g['home_score']>$g['away_score']; $aw=$fin&&$g['away_score']>$g['home_score']; ?>
                        <div class="game-card">
                            <div class="game-status"><span><?= date('n/j',strtotime($g['game_date'])) ?></span><span class="status-badge status-<?= $g['status'] ?>"><?= $g['status']==='finished'?'終了':($g['status']==='live'?'LIVE':'予定') ?></span></div>
                            <div class="game-matchup">
                                <div class="game-team"><div class="team-logo-circle" style="background:<?= $g['home_color'] ?>"><?= $g['home_short'] ?></div><span class="team-name"><?= htmlspecialchars($g['home_name']) ?></span></div>
                                <div class="game-score">
                                    <?php if ($fin||$g['status']==='live'): ?><span class="score <?= $hw?'winner':'loser' ?>"><?= $g['home_score'] ?></span><span class="vs">-</span><span class="score <?= $aw?'winner':'loser' ?>"><?= $g['away_score'] ?></span><?php else: ?><span class="vs">VS</span><?php endif; ?>
                                </div>
                                <div class="game-team"><div class="team-logo-circle" style="background:<?= $g['away_color'] ?>"><?= $g['away_short'] ?></div><span class="team-name"><?= htmlspecialchars($g['away_name']) ?></span></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <footer class="main-footer"><div class="footer-content"><div class="footer-logo">B2L <span>LEAGUE</span></div><div class="footer-copy">© 2024 B2L League.</div></div></footer>
    <script src="<?= url('js/app.js') ?>"></script>
</body>
</html>

