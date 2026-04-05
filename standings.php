<?php
require_once 'config.php';
$pdo = getDB();

$standingsData = [];
for ($d = 1; $d <= 3; $d++) {
    $standingsData[$d] = $pdo->query("
        SELECT s.*, t.name, t.short_name, t.logo_color
        FROM standings s
        JOIN teams t ON s.team_id = t.id
        WHERE s.division = $d
        ORDER BY s.win_pct DESC, s.wins DESC
    ")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>順位表 -
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
                <a href="standings.php" class="active">順位表</a>
                <a href="leaders.php">リーダーズ</a>
            </nav>
        </div>
    </header>

    <div class="container content-wrapper">
        <div class="section-header">
            <h2>順位表</h2>
        </div>

        <div class="division-section">
            <div class="division-tabs">
                <button class="division-tab active" data-division="1">1部</button>
                <button class="division-tab" data-division="2">2部</button>
                <button class="division-tab" data-division="3">3部</button>
            </div>

            <?php for ($d = 1; $d <= 3; $d++): ?>
            <div class="division-content" data-division="<?= $d?>" style="<?= $d > 1 ? 'display:none' : ''?>">
                <div class="standings-table-wrapper">
                    <table class="standings-table">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>チーム</th>
                                <th class="text-center">勝</th>
                                <th class="text-center">敗</th>
                                <th class="text-center">勝率</th>
                                <th class="text-center">得点</th>
                                <th class="text-center">失点</th>
                                <th class="text-center">得失点差</th>
                                <th class="text-center">連勝/連敗</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($standingsData[$d])): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted" style="padding:30px;">データなし</td>
                            </tr>
                            <?php
    else: ?>
                            <?php foreach ($standingsData[$d] as $i => $row): ?>
                            <?php $diff = $row['points_for'] - $row['points_against']; ?>
                            <tr>
                                <td class="rank <?= $i < 4 ? 'top' : ''?>">
                                    <?= $i + 1?>
                                </td>
                                <td>
                                    <div class="team-cell">
                                        <div class="team-mini-logo" style="background:<?= $row['logo_color']?>">
                                            <?= $row['short_name']?>
                                        </div>
                                        <a href="team.php?id=<?= $row['team_id']?>" class="team-name">
                                            <?= htmlspecialchars($row['name'])?>
                                        </a>
                                    </div>
                                </td>
                                <td class="text-center fw-bold">
                                    <?= $row['wins']?>
                                </td>
                                <td class="text-center">
                                    <?= $row['losses']?>
                                </td>
                                <td class="text-center win-pct">
                                    <?= number_format($row['win_pct'], 3)?>
                                </td>
                                <td class="text-center">
                                    <?= $row['points_for']?>
                                </td>
                                <td class="text-center">
                                    <?= $row['points_against']?>
                                </td>
                                <td
                                    class="text-center <?= $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : '')?>">
                                    <?= $diff > 0 ? '+' . $diff : $diff?>
                                </td>
                                <td
                                    class="text-center <?= strpos($row['streak'], 'W') === 0 ? 'streak-w' : (strpos($row['streak'], 'L') === 0 ? 'streak-l' : '')?>">
                                    <?= $row['streak']?>
                                </td>
                            </tr>
                            <?php
        endforeach; ?>
                            <?php
    endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php
endfor; ?>
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