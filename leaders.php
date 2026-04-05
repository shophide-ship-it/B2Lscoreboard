<?php
require_once 'config.php';
$pdo = getDB();

$division = isset($_GET['division']) ? (int)$_GET['division'] : 0;
$divisionWhere = $division > 0 ? "AND t.division = $division" : "";

$categories = [
    ['key' => 'pts', 'label' => '得点 (PPG)', 'sql' => 'AVG(ps.pts)', 'format' => 1],
    ['key' => 'reb', 'label' => 'リバウンド (RPG)', 'sql' => 'AVG(ps.reb)', 'format' => 1],
    ['key' => 'ast', 'label' => 'アシスト (APG)', 'sql' => 'AVG(ps.ast)', 'format' => 1],
    ['key' => 'stl', 'label' => 'スティール (SPG)', 'sql' => 'AVG(ps.stl)', 'format' => 1],
    ['key' => 'blk', 'label' => 'ブロック (BPG)', 'sql' => 'AVG(ps.blk)', 'format' => 1],
    ['key' => 'fg_pct', 'label' => 'FG% (2+ FGA/G)', 'sql' => 'SUM(ps.fgm)*100.0/NULLIF(SUM(ps.fga),0)', 'format' => 1, 'having_extra' => 'AND AVG(ps.fga) >= 2'],
    ['key' => 'three_pct', 'label' => '3P% (1+ 3PA/G)', 'sql' => 'SUM(ps.three_pm)*100.0/NULLIF(SUM(ps.three_pa),0)', 'format' => 1, 'having_extra' => 'AND AVG(ps.three_pa) >= 1'],
    ['key' => 'ft_pct', 'label' => 'FT% (1+ FTA/G)', 'sql' => 'SUM(ps.ftm)*100.0/NULLIF(SUM(ps.fta),0)', 'format' => 1, 'having_extra' => 'AND AVG(ps.fta) >= 1'],
];

$leadersData = [];
foreach ($categories as $cat) {
    $havingExtra = $cat['having_extra'] ?? '';
    $sql = "
        SELECT p.id, p.name, p.number, t.short_name, t.logo_color, t.id as team_id,
               ROUND({$cat['sql']}, {$cat['format']}) as stat_value,
               COUNT(ps.id) as gp
        FROM player_stats ps
        JOIN players p ON ps.player_id = p.id
        JOIN teams t ON p.team_id = t.id
        WHERE 1=1 $divisionWhere
        GROUP BY ps.player_id
        HAVING gp >= 1 $havingExtra AND stat_value IS NOT NULL
        ORDER BY stat_value DESC
        LIMIT 10
    ";
    $leadersData[$cat['key']] = $pdo->query($sql)->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>リーダーズ -
        <?= SITE_NAME?>
    </title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="main-header">
        <div class="header-top">
            <div class="header-top-inner"><a href="admin/index.php">管理ページ</a></div>
        </div>
        <div class="nav-container">
            <a href="index.php" class="logo"><span class="logo-icon">🏀</span><span class="logo-text">B2L
                    <span>LEAGUE</span></span></a>
            <button class="mobile-menu-btn">☰</button>
            <nav class="main-nav">
                <a href="index.php">ホーム</a>
                <a href="schedule.php">スケジュール</a>
                <a href="teams.php">チーム</a>
                <a href="standings.php">順位表</a>
                <a href="leaders.php" class="active">リーダーズ</a>
            </nav>
        </div>
    </header>

    <div class="container content-wrapper">
        <div class="section-header">
            <h2>スタッツリーダー</h2>
        </div>

        <div class="division-tabs mb-2">
            <a href="leaders.php" class="division-tab <?= $division === 0 ? 'active' : ''?>">全部門</a>
            <a href="leaders.php?division=1" class="division-tab <?= $division === 1 ? 'active' : ''?>">1部</a>
            <a href="leaders.php?division=2" class="division-tab <?= $division === 2 ? 'active' : ''?>">2部</a>
            <a href="leaders.php?division=3" class="division-tab <?= $division === 3 ? 'active' : ''?>">3部</a>
        </div>

        <div class="leaders-grid">
            <?php foreach ($categories as $cat): ?>
            <div class="leader-card">
                <div class="leader-card-header">
                    <?= $cat['label']?>
                </div>
                <?php if (empty($leadersData[$cat['key']])): ?>
                <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">データなし</div>
                <?php
    else: ?>
                <?php foreach ($leadersData[$cat['key']] as $i => $leader): ?>
                <div class="leader-item">
                    <span class="leader-rank <?= $i < 3 ? 'top-' . ($i + 1) : ''?>">
                        <?= $i + 1?>
                    </span>
                    <div class="team-mini-logo"
                        style="background:<?= $leader['logo_color']?>;width:32px;height:32px;font-size:9px;">
                        <?= $leader['short_name']?>
                    </div>
                    <div class="leader-info">
                        <div class="name"><a href="player.php?id=<?= $leader['id']?>">
                                <?= htmlspecialchars($leader['name'])?>
                            </a></div>
                        <div class="team">
                            <?= $leader['short_name']?> |
                            <?= $leader['gp']?> GP
                        </div>
                    </div>
                    <div class="leader-stat">
                        <?= $leader['stat_value']?>
                        <?= in_array($cat['key'], ['fg_pct', 'three_pct', 'ft_pct']) ? '%' : ''?>
                    </div>
                </div>
                <?php
        endforeach; ?>
                <?php
    endif; ?>
            </div>
            <?php
endforeach; ?>
        </div>
    </div>

    <footer class="main-footer">
        <div class="footer-content">
            <div class="footer-logo">B2L <span>LEAGUE</span></div>
            <div class="footer-copy">© 2024 B2L League. All rights reserved.</div>
        </div>
    </footer>
    <script src="js/app.js"></script>
</body>

</html>