<?php
require_once __DIR__ . '/config.php';
$pdo = getDB();
$playerId = (int)($_GET['id']??0);
if (!$playerId) { header('Location: '.url('teams.php')); exit; }

$player = $pdo->prepare("SELECT p.*, t.name as team_name, t.short_name, t.logo_color, t.division FROM players p JOIN teams t ON p.team_id=t.id WHERE p.id=?");
$player->execute([$playerId]); $player = $player->fetch();
if (!$player) { header('Location: '.url('teams.php')); exit; }

$avg = $pdo->prepare("
    SELECT COUNT(*) as gp, ROUND(AVG(pts),1) as ppg, ROUND(AVG(reb),1) as rpg, ROUND(AVG(ast),1) as apg,
           ROUND(AVG(stl),1) as spg, ROUND(AVG(blk),1) as bpg, ROUND(AVG(tov),1) as topg, ROUND(AVG(pf),1) as fpg,
           ROUND(AVG(plus_minus),1) as avg_pm,
           ROUND(SUM(fgm)*100.0/NULLIF(SUM(fga),0),1) as fg_pct,
           ROUND(SUM(three_pm)*100.0/NULLIF(SUM(three_pa),0),1) as three_pct,
           ROUND(SUM(ftm)*100.0/NULLIF(SUM(fta),0),1) as ft_pct,
           ROUND(AVG(oreb),1) as orpg, ROUND(AVG(dreb),1) as drpg
    FROM player_stats WHERE player_id=?
");
$avg->execute([$playerId]); $avg = $avg->fetch();

$log = $pdo->prepare("
    SELECT ps.*, g.game_date, g.home_team_id, g.away_team_id,
           ht.short_name as home_short, at.short_name as away_short
    FROM player_stats ps JOIN games g ON ps.game_id=g.id
    JOIN teams ht ON g.home_team_id=ht.id JOIN teams at ON g.away_team_id=at.id
    WHERE ps.player_id=? ORDER BY g.game_date DESC
");
$log->execute([$playerId]); $log = $log->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($player['name']) ?> - <?= SITE_NAME ?></title>
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
    <div class="player-profile-header" style="background:linear-gradient(135deg,<?= $player['logo_color'] ?>22,#0d1b3e)">
        <div class="player-profile-info">
            <div class="player-avatar" style="background:<?= $player['logo_color'] ?>">#<?= $player['number'] ?></div>
            <div class="player-details">
                <h1><?= htmlspecialchars($player['name']) ?></h1>
                <div class="player-meta">
                    <div class="player-meta-item">チーム: <span><a href="<?= url('team.php?id='.$player['team_id']) ?>"><?= htmlspecialchars($player['team_name']) ?></a></span></div>
                    <div class="player-meta-item">背番号: <span>#<?= $player['number'] ?></span></div>
                    <div class="player-meta-item">POS: <span><?= $player['position'] ?></span></div>
                    <?php if ($player['height']): ?><div class="player-meta-item">身長: <span><?= $player['height'] ?>cm</span></div><?php endif; ?>
                    <div class="player-meta-item">部門: <span><?= getDivisionName($player['division']) ?></span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="container content-wrapper">
        <section class="mb-3">
            <div class="section-header"><h2>シーズン平均</h2></div>
            <?php if ($avg['gp']>0): ?>
            <div class="stats-table-wrapper">
                <table class="stats-table">
                    <thead><tr><th>GP</th><th>PPG</th><th>RPG</th><th>APG</th><th>SPG</th><th>BPG</th><th>FG%</th><th>3P%</th><th>FT%</th><th>OREB</th><th>DREB</th><th>TOV</th><th>PF</th><th>+/-</th></tr></thead>
                    <tbody><tr>
                        <td class="fw-bold"><?= $avg['gp'] ?></td><td class="fw-bold"><?= $avg['ppg'] ?></td>
                        <td><?= $avg['rpg'] ?></td><td><?= $avg['apg'] ?></td><td><?= $avg['spg'] ?></td><td><?= $avg['bpg'] ?></td>
                        <td><?= $avg['fg_pct']!==null?$avg['fg_pct'].'%':'-' ?></td>
                        <td><?= $avg['three_pct']!==null?$avg['three_pct'].'%':'-' ?></td>
                        <td><?= $avg['ft_pct']!==null?$avg['ft_pct'].'%':'-' ?></td>
                        <td><?= $avg['orpg'] ?></td><td><?= $avg['drpg'] ?></td><td><?= $avg['topg'] ?></td><td><?= $avg['fpg'] ?></td>
                        <td><?= $avg['avg_pm']>0?'+'.$avg['avg_pm']:$avg['avg_pm'] ?></td>
                    </tr></tbody>
                </table>
            </div>
            <?php else: ?><div class="empty-state"><p>スタッツデータがありません</p></div><?php endif; ?>
        </section>
        <section class="mb-3">
            <div class="section-header"><h2>ゲームログ</h2></div>
            <?php if (empty($log)): ?>
                <div class="empty-state"><p>ゲームログがありません</p></div>
            <?php else: ?>
            <div class="stats-table-wrapper">
                <table class="stats-table">
                    <thead><tr><th style="text-align:left">日付</th><th>対戦</th><th>PTS</th><th>REB</th><th>AST</th><th>STL</th><th>BLK</th><th>FGM</th><th>FGA</th><th>FG%</th><th>3PM</th><th>3PA</th><th>3P%</th><th>FTM</th><th>FTA</th><th>FT%</th><th>OREB</th><th>DREB</th><th>TOV</th><th>PF</th><th>+/-</th></tr></thead>
                    <tbody>
                        <?php foreach ($log as $g): ?>
                            <?php
                            $isH = $g['team_id']==$g['home_team_id']; $opp = $isH?$g['away_short']:$g['home_short']; $pfx = $isH?'vs':'@';
                            $fgp = $g['fga']>0?round($g['fgm']*100/$g['fga'],1):0;
                            $tp = $g['three_pa']>0?round($g['three_pm']*100/$g['three_pa'],1):0;
                            $ftp = $g['fta']>0?round($g['ftm']*100/$g['fta'],1):0;
                            ?>
                            <tr>
                                <td style="text-align:left"><?= date('n/j',strtotime($g['game_date'])) ?></td>
                                <td><?= $pfx ?> <?= $opp ?></td>
                                <td class="fw-bold"><?= $g['pts'] ?></td><td><?= $g['reb'] ?></td><td><?= $g['ast'] ?></td>
                                <td><?= $g['stl'] ?></td><td><?= $g['blk'] ?></td>
                                <td><?= $g['fgm'] ?></td><td><?= $g['fga'] ?></td><td><?= $fgp ?>%</td>
                                <td><?= $g['three_pm'] ?></td><td><?= $g['three_pa'] ?></td><td><?= $tp ?>%</td>
                                <td><?= $g['ftm'] ?></td><td><?= $g['fta'] ?></td><td><?= $ftp ?>%</td>
                                <td><?= $g['oreb'] ?></td><td><?= $g['dreb'] ?></td><td><?= $g['tov'] ?></td><td><?= $g['pf'] ?></td>
                                <td><?= $g['plus_minus']>0?'+'.$g['plus_minus']:$g['plus_minus'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>
    </div>
    <footer class="main-footer"><div class="footer-content"><div class="footer-logo">B2L <span>LEAGUE</span></div><div class="footer-copy">© 2024 B2L League.</div></div></footer>
    <script src="<?= url('js/app.js') ?>"></script>
</body>
</html>

