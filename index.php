<?php
require_once 'config.php';
$pdo = getDB();

// 直近の試合
$recentGames = $pdo->query("
    SELECT g.*, 
           ht.name as home_name, ht.short_name as home_short, ht.logo_color as home_color,
           at.name as away_name, at.short_name as away_short, at.logo_color as away_color
    FROM games g
    JOIN teams ht ON g.home_team_id = ht.id
    JOIN teams at ON g.away_team_id = at.id
    ORDER BY g.game_date DESC, g.game_time DESC
    LIMIT 6
")->fetchAll();

// 各部門の順位（上位4チーム）
$standingsData = [];
for ($d = 1; $d <= 3; $d++) {
    $standingsData[$d] = $pdo->query("
        SELECT s.*, t.name, t.short_name, t.logo_color
        FROM standings s
        JOIN teams t ON s.team_id = t.id
        WHERE s.division = $d
        ORDER BY s.win_pct DESC, s.wins DESC
        LIMIT 4
    ")->fetchAll();
}

// 得点リーダー
$scoringLeaders = $pdo->query("
    SELECT p.name, p.number, t.short_name, t.logo_color,
           ROUND(AVG(ps.pts), 1) as avg_pts,
           COUNT(ps.id) as games_played
    FROM player_stats ps
    JOIN players p ON ps.player_id = p.id
    JOIN teams t ON p.team_id = t.id
    GROUP BY ps.player_id
    HAVING games_played >= 1
    ORDER BY avg_pts DESC
    LIMIT 5
")->fetchAll();

$assistLeaders = $pdo->query("
    SELECT p.name, p.number, t.short_name, t.logo_color,
           ROUND(AVG(ps.ast), 1) as avg_ast,
           COUNT(ps.id) as games_played
    FROM player_stats ps
    JOIN players p ON ps.player_id = p.id
    JOIN teams t ON p.team_id = t.id
    GROUP BY ps.player_id
    HAVING games_played >= 1
    ORDER BY avg_ast DESC
    LIMIT 5
")->fetchAll();

$reboundLeaders = $pdo->query("
    SELECT p.name, p.number, t.short_name, t.logo_color,
           ROUND(AVG(ps.reb), 1) as avg_reb,
           COUNT(ps.id) as games_played
    FROM player_stats ps
    JOIN players p ON ps.player_id = p.id
    JOIN teams t ON p.team_id = t.id
    GROUP BY ps.player_id
    HAVING games_played >= 1
    ORDER BY avg_reb DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= SITE_NAME?> - バスケットボールリーグ
    </title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-top">
            <div class="header-top-inner">
                <a href="admin/index.php">管理ページ</a>
            </div>
        </div>
        <div class="nav-container">
            <a href="index.php" class="logo">
                <span class="logo-icon">🏀</span>
                <span class="logo-text">B2L <span>LEAGUE</span></span>
            </a>
            <button class="mobile-menu-btn">☰</button>
            <nav class="main-nav">
                <a href="index.php" class="active">ホーム</a>
                <a href="schedule.php">スケジュール</a>
                <a href="teams.php">チーム</a>
                <a href="standings.php">順位表</a>
                <a href="leaders.php">リーダーズ</a>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero">
        <h1><span>B2L LEAGUE</span></h1>
        <p>2024-25 シーズン</p>
    </section>

    <div class="container content-wrapper">
        <!-- Recent Games -->
        <section class="mb-3">
            <div class="section-header">
                <h2>最近の試合</h2>
                <a href="schedule.php" class="view-all">すべて見る →</a>
            </div>
            <?php if (empty($recentGames)): ?>
            <div class="empty-state">
                <div class="icon">📅</div>
                <p>まだ試合が登録されていません</p>
            </div>
            <?php
else: ?>
            <div class="games-grid">
                <?php foreach ($recentGames as $game): ?>
                <?php
        $isFinished = $game['status'] === 'finished';
        $homeWin = $isFinished && $game['home_score'] > $game['away_score'];
        $awayWin = $isFinished && $game['away_score'] > $game['home_score'];
?>
                <div class="game-card">
                    <div class="game-status">
                        <span>
                            <?= date('n/j (D)', strtotime($game['game_date']))?>
                        </span>
                        <span class="status-badge status-<?= $game['status']?>">
                            <?php
        switch ($game['status']) {
            case 'scheduled':
                echo '予定';
                break;
            case 'live':
                echo 'LIVE';
                break;
            case 'finished':
                echo '終了';
                break;
        }
?>
                        </span>
                    </div>
                    <div class="game-matchup">
                        <div class="game-team">
                            <div class="team-logo-circle" style="background:<?= $game['home_color']?>">
                                <?= $game['home_short']?>
                            </div>
                            <span class="team-name">
                                <?= htmlspecialchars($game['home_name'])?>
                            </span>
                        </div>
                        <div class="game-score">
                            <?php if ($isFinished || $game['status'] === 'live'): ?>
                            <span class="score <?= $homeWin ? 'winner' : 'loser'?>">
                                <?= $game['home_score']?>
                            </span>
                            <span class="vs">-</span>
                            <span class="score <?= $awayWin ? 'winner' : 'loser'?>">
                                <?= $game['away_score']?>
                            </span>
                            <?php
        else: ?>
                            <span class="vs">
                                <?= $game['game_time'] ? date('H:i', strtotime($game['game_time'])) : 'VS'?>
                            </span>
                            <?php
        endif; ?>
                        </div>
                        <div class="game-team">
                            <div class="team-logo-circle" style="background:<?= $game['away_color']?>">
                                <?= $game['away_short']?>
                            </div>
                            <span class="team-name">
                                <?= htmlspecialchars($game['away_name'])?>
                            </span>
                        </div>
                    </div>
                    <?php if ($game['venue']): ?>
                    <div class="game-info">
                        <span>📍
                            <?= htmlspecialchars($game['venue'])?>
                        </span>
                    </div>
                    <?php
        endif; ?>
                </div>
                <?php
    endforeach; ?>
            </div>
            <?php
endif; ?>
        </section>

        <!-- Standings -->
        <section class="mb-3">
            <div class="section-header">
                <h2>順位表</h2>
                <a href="standings.php" class="view-all">すべて見る →</a>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($standingsData[$d])): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted" style="padding:20px;">データなし</td>
                                </tr>
                                <?php
    else: ?>
                                <?php foreach ($standingsData[$d] as $i => $row): ?>
                                <tr>
                                    <td class="rank <?= $i < 3 ? 'top' : ''?>">
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
                                    <td class="text-center">
                                        <?= $row['wins']?>
                                    </td>
                                    <td class="text-center">
                                        <?= $row['losses']?>
                                    </td>
                                    <td class="text-center win-pct">
                                        <?= number_format($row['win_pct'], 3)?>
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
        </section>

        <!-- Leaders -->
        <section class="mb-3">
            <div class="section-header">
                <h2>スタッツリーダー</h2>
                <a href="leaders.php" class="view-all">すべて見る →</a>
            </div>
            <?php if (empty($scoringLeaders) && empty($assistLeaders) && empty($reboundLeaders)): ?>
            <div class="empty-state">
                <div class="icon">📊</div>
                <p>まだスタッツデータがありません</p>
            </div>
            <?php
else: ?>
            <div class="leaders-grid">
                <!-- Scoring -->
                <div class="leader-card">
                    <div class="leader-card-header">得点 (PPG)</div>
                    <?php foreach ($scoringLeaders as $i => $leader): ?>
                    <div class="leader-item">
                        <span class="leader-rank <?= $i < 3 ? 'top-' . ($i + 1) : ''?>">
                            <?= $i + 1?>
                        </span>
                        <div class="team-mini-logo"
                            style="background:<?= $leader['logo_color']?>;width:32px;height:32px;font-size:9px;">
                            <?= $leader['short_name']?>
                        </div>
                        <div class="leader-info">
                            <div class="name">
                                <?= htmlspecialchars($leader['name'])?>
                            </div>
                            <div class="team">
                                <?= $leader['short_name']?>
                            </div>
                        </div>
                        <div class="leader-stat">
                            <?= $leader['avg_pts']?>
                        </div>
                    </div>
                    <?php
    endforeach; ?>
                </div>

                <!-- Assists -->
                <div class="leader-card">
                    <div class="leader-card-header">アシスト (APG)</div>
                    <?php foreach ($assistLeaders as $i => $leader): ?>
                    <div class="leader-item">
                        <span class="leader-rank <?= $i < 3 ? 'top-' . ($i + 1) : ''?>">
                            <?= $i + 1?>
                        </span>
                        <div class="team-mini-logo"
                            style="background:<?= $leader['logo_color']?>;width:32px;height:32px;font-size:9px;">
                            <?= $leader['short_name']?>
                        </div>
                        <div class="leader-info">
                            <div class="name">
                                <?= htmlspecialchars($leader['name'])?>
                            </div>
                            <div class="team">
                                <?= $leader['short_name']?>
                            </div>
                        </div>
                        <div class="leader-stat">
                            <?= $leader['avg_ast']?>
                        </div>
                    </div>
                    <?php
    endforeach; ?>
                </div>

                <!-- Rebounds -->
                <div class="leader-card">
                    <div class="leader-card-header">リバウンド (RPG)</div>
                    <?php foreach ($reboundLeaders as $i => $leader): ?>
                    <div class="leader-item">
                        <span class="leader-rank <?= $i < 3 ? 'top-' . ($i + 1) : ''?>">
                            <?= $i + 1?>
                        </span>
                        <div class="team-mini-logo"
                            style="background:<?= $leader['logo_color']?>;width:32px;height:32px;font-size:9px;">
                            <?= $leader['short_name']?>
                        </div>
                        <div class="leader-info">
                            <div class="name">
                                <?= htmlspecialchars($leader['name'])?>
                            </div>
                            <div class="team">
                                <?= $leader['short_name']?>
                            </div>
                        </div>
                        <div class="leader-stat">
                            <?= $leader['avg_reb']?>
                        </div>
                    </div>
                    <?php
    endforeach; ?>
                </div>
            </div>
            <?php
endif; ?>
<?php
// b2l/index.php のライブスコア表示部分に追加
$liveGames = $pdo->query("
    SELECT lg.*, g.game_date, g.game_time,
           ht.name AS home_name, at.name AS away_name
    FROM live_games lg
    JOIN games g ON lg.game_id = g.id
    JOIN teams ht ON g.home_team_id = ht.id
    JOIN teams at ON g.away_team_id = at.id
    WHERE lg.status NOT IN ('ready', 'finished')
    ORDER BY lg.updated_at DESC
")->fetchAll();
?>

<?php if (!empty($liveGames)): ?>
<section class="live-section">
    <h2>🔴 ライブスコア</h2>
    <?php foreach ($liveGames as $lg): ?>
    <div class="live-card">
        <span><?= htmlspecialchars($lg['home_name']) ?></span>
        <strong><?= $lg['home_score'] ?> - <?= $lg['away_score'] ?></strong>
        <span><?= htmlspecialchars($lg['away_name']) ?></span>
        <small><?= strtoupper($lg['status']) ?></small>
    </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-content">
            <div class="footer-logo">B2L <span>LEAGUE</span></div>
            <div class="footer-links">
                <a href="schedule.php">スケジュール</a>
                <a href="teams.php">チーム</a>
                <a href="standings.php">順位表</a>
                <a href="leaders.php">リーダーズ</a>
                <a href="admin/index.php">管理</a>
            </div>
            <div class="footer-copy">© 2024 B2L League. All rights reserved.</div>
        </div>
    </footer>
    <script src="js/app.js"></script>
</body>

</html>