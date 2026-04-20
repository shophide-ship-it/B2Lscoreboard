<?php
require_once __DIR__ . '/config.php';
$pdo = getDB();
$div = isset($_GET['division'])?(int)$_GET['division']:0;
$dw = $div>0?"AND t.division=$div":"";

$cats = [
    ['pts','得点 (PPG)','AVG(ps.pts)',1,''],
    ['reb','リバウンド (RPG)','AVG(ps.reb)',1,''],
    ['ast','アシスト (APG)','AVG(ps.ast)',1,''],
    ['stl','スティール (SPG)','AVG(ps.stl)',1,''],
    ['blk','ブロック (BPG)','AVG(ps.blk)',1,''],
    ['fg','FG%','SUM(ps.fgm)*100.0/NULLIF(SUM(ps.fga),0)',1,'AND AVG(ps.fga)>=2'],
    ['3p','3P%','SUM(ps.three_pm)*100.0/NULLIF(SUM(ps.three_pa),0)',1,'AND AVG(ps.three_pa)>=1'],
    ['ft','FT%','SUM(ps.ftm)*100.0/NULLIF(SUM(ps.fta),0)',1,'AND AVG(ps.fta)>=1'],
];
$data = [];
foreach ($cats as $c) {
    $data[$c[0]] = $pdo->query("
        SELECT p.id,p.name,t.short_name,t.logo_color,ROUND({$c[2]},{$c[3]}) as sv,COUNT(ps.id) as gp
        FROM player_stats ps JOIN players p ON ps.player_id=p.id JOIN teams t ON p.team_id=t.id
        WHERE 1=1 $dw GROUP BY ps.player_id HAVING gp>=1 {$c[4]} AND sv IS NOT NULL ORDER BY sv DESC LIMIT 10
    ")->fetchAll();
}
$isPct = ['fg'=>1,'3p'=>1,'ft'=>1];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>リーダーズ - <?= SITE_NAME ?></title>
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
                <a href="<?= url('teams.php') ?>">チーム</a><a href="<?= url('standings.php') ?>">順位表</a><a href="<?= url('leaders.php') ?>" class="active">リーダーズ</a>
            </nav>
        </div>
    </header>
    <div class="container content-wrapper">
        <div class="section-header"><h2>スタッツリーダー</h2></div>
        <div class="division-tabs mb-2">
            <a href="<?= url('leaders.php') ?>" class="division-tab <?= $div===0?'active':'' ?>">全部門</a>
            <a href="<?= url('leaders.php?division=1') ?>" class="division-tab <?= $div===1?'active':'' ?>">1部</a>
            <a href="<?= url('leaders.php?division=2') ?>" class="division-tab <?= $div===2?'active':'' ?>">2部</a>
            <a href="<?= url('leaders.php?division=3') ?>" class="division-tab <?= $div===3?'active':'' ?>">3部</a>
        </div>
        <div class="leaders-grid">
            <?php foreach ($cats as $c): ?>
            <div class="leader-card">
                <div class="leader-card-header"><?= $c[1] ?></div>
                <?php if (empty($data[$c[0]])): ?><div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px">データなし</div>
                <?php else: foreach ($data[$c[0]] as $i=>$l): ?>
                <div class="leader-item">
                    <span class="leader-rank <?= $i<3?'top-'.($i+1):'' ?>"><?= $i+1 ?></span>
                    <div class="team-mini-logo" style="background:<?= $l['logo_color'] ?>;width:32px;height:32px;font-size:9px"><?= $l['short_name'] ?></div>
                    <div class="leader-info"><div class="name"><a href="<?= url('player.php?id='.$l['id']) ?>"><?= htmlspecialchars($l['name']) ?></a></div><div class="team"><?= $l['short_name'] ?> | <?= $l['gp'] ?> GP</div></div>
                    <div class="leader-stat"><?= $l['sv'] ?><?= isset($isPct[$c[0]])?'%':'' ?></div>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <footer class="main-footer"><div class="footer-content"><div class="footer-logo">B2L <span>LEAGUE</span></div><div class="footer-copy">© 2024 B2L League.</div></div></footer>
    <script src="<?= url('js/app.js') ?>"></script>
</body>
</html>

