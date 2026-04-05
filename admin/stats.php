<?php
require_once 'auth.php';
requireLogin();
$pdo = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_stats') {
        $gameId = (int)$_POST['game_id'];
        $playerId = (int)$_POST['player_id'];

        // チームID取得
        $player = $pdo->prepare("SELECT team_id FROM players WHERE id=?");
        $player->execute([$playerId]);
        $teamId = (int)$player->fetchColumn();

        $oreb = (int)$_POST['oreb'];
        $dreb = (int)$_POST['dreb'];
        $reb = $oreb + $dreb;

        $fgm = (int)$_POST['fgm'];
        $three_pm = (int)$_POST['three_pm'];
        $ftm = (int)$_POST['ftm'];
        $pts = ($fgm * 2) + $three_pm + $ftm;

        $stmt = $pdo->prepare("
            INSERT INTO player_stats (game_id, player_id, team_id, pts, reb, ast, stl, blk, fgm, fga, three_pm, three_pa, ftm, fta, oreb, dreb, tov, pf, plus_minus)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            pts=VALUES(pts), reb=VALUES(reb), ast=VALUES(ast), stl=VALUES(stl), blk=VALUES(blk),
            fgm=VALUES(fgm), fga=VALUES(fga), three_pm=VALUES(three_pm), three_pa=VALUES(three_pa),
            ftm=VALUES(ftm), fta=VALUES(fta), oreb=VALUES(oreb), dreb=VALUES(dreb),
            tov=VALUES(tov), pf=VALUES(pf), plus_minus=VALUES(plus_minus)
        ");
        $stmt->execute([
            $gameId, $playerId, $teamId, $pts, $reb,
            (int)$_POST['ast'], (int)$_POST['stl'], (int)$_POST['blk'],
            $fgm, (int)$_POST['fga'], $three_pm, (int)$_POST['three_pa'],
            $ftm, (int)$_POST['fta'], $oreb, $dreb,
            (int)$_POST['tov'], (int)$_POST['pf'], (int)$_POST['plus_minus']
        ]);
        $message = 'スタッツを保存しました。(PTS: ' . $pts . ', REB: ' . $reb . ')';
    }

    if ($action === 'delete_stat') {
        $pdo->prepare("DELETE FROM player_stats WHERE id=?")->execute([(int)$_POST['stat_id']]);
        $message = 'スタッツを削除しました。';
    }
}

// 試合選択
$selectedGame = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;

$games = $pdo->query("
    SELECT g.id, g.game_date, g.status, g.division,
           ht.name as home_name, ht.short_name as home_short,
           at.name as away_name, at.short_name as away_short
    FROM games g
    JOIN teams ht ON g.home_team_id = ht.id
    JOIN teams at ON g.away_team_id = at.id
    ORDER BY g.game_date DESC
    LIMIT 50
")->fetchAll();

$gameInfo = null;
$gamePlayers = [];
$existingStats = [];

if ($selectedGame) {
    $stmt = $pdo->prepare("
        SELECT g.*, ht.name as home_name, at.name as away_name
        FROM games g
        JOIN teams ht ON g.home_team_id = ht.id
        JOIN teams at ON g.away_team_id = at.id
        WHERE g.id = ?
    ");
    $stmt->execute([$selectedGame]);
    $gameInfo = $stmt->fetch();

    if ($gameInfo) {
        // 両チームの選手
        $stmt = $pdo->prepare("
            SELECT p.*, t.name as team_name, t.short_name
            FROM players p
            JOIN teams t ON p.team_id = t.id
            WHERE p.team_id IN (?, ?) AND p.is_active = 1
            ORDER BY p.team_id, p.number
        ");
        $stmt->execute([$gameInfo['home_team_id'], $gameInfo['away_team_id']]);
        $gamePlayers = $stmt->fetchAll();

        // 既存のスタッツ
        $stmt = $pdo->prepare("
            SELECT ps.*, p.name as player_name, p.number, t.short_name
            FROM player_stats ps
            JOIN players p ON ps.player_id = p.id
            JOIN teams t ON ps.team_id = t.id
            WHERE ps.game_id = ?
            ORDER BY t.name, p.number
        ");
        $stmt->execute([$selectedGame]);
        $existingStats = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スタッツ入力 -
        <?= SITE_NAME?>
    </title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-logo">
                <h2>B2L <span>LEAGUE</span></h2>
                <p>管理パネル</p>
            </div>
            <nav class="admin-nav">
                <a href="index.php"><span class="icon">📊</span> ダッシュボード</a>
                <a href="teams.php"><span class="icon">🏀</span> チーム管理</a>
                <a href="players.php"><span class="icon">👤</span> 選手管理</a>
                <a href="games.php"><span class="icon">📅</span> 試合管理</a>
                <a href="stats.php" class="active"><span class="icon">📈</span> スタッツ入力</a>
                <a href="../index.php"><span class="icon">🌐</span> サイト表示</a>
                <a href="index.php?action=logout"><span class="icon">🚪</span> ログアウト</a>
            </nav>
        </aside>
        <main class="admin-main">
            <div class="admin-header">
                <h1>スタッツ入力</h1>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-success">✅
                <?= $message?>
            </div>
            <?php
endif; ?>

            <div class="alert alert-info">
                💡 FGM/3PM/FTMとOREBDREBを入力すると、PTS(=FGM×2+3PM+FTM)とREB(=OREB+DREB)が自動計算されます。
            </div>

            <!-- Game Selection -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3>試合選択</h3>
                </div>
                <div class="card-body">
                    <form method="GET">
                        <div class="form-group">
                            <label>試合</label>
                            <select name="game_id" class="form-control" onchange="this.form.submit()">
                                <option value="">試合を選択...</option>
                                <?php foreach ($games as $g): ?>
                                <option value="<?= $g['id']?>" <?=$selectedGame==$g['id'] ? 'selected' : ''?>>
                                    <?= date('n/j', strtotime($g['game_date']))?>
                                    [
                                    <?= getDivisionName($g['division'])?>]
                                    <?= $g['home_short']?> vs
                                    <?= $g['away_short']?>
                                    (
                                    <?= $g['status'] === 'finished' ? '終了' : ($g['status'] === 'live' ? 'LIVE' : '予定')?>)
                                </option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($gameInfo): ?>
            <!-- Stats Input Form -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3>スタッツ入力:
                        <?= htmlspecialchars($gameInfo['home_name'])?> vs
                        <?= htmlspecialchars($gameInfo['away_name'])?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" id="stats-form">
                        <input type="hidden" name="action" value="add_stats">
                        <input type="hidden" name="game_id" value="<?= $selectedGame?>">

                        <div class="form-group">
                            <label>選手</label>
                            <select name="player_id" class="form-control" required>
                                <option value="">選手を選択...</option>
                                <?php
    $currentTeam = '';
    foreach ($gamePlayers as $p):
        if ($currentTeam !== $p['team_name']):
            if ($currentTeam !== '')
                echo '</optgroup>';
            echo '<optgroup label="' . htmlspecialchars($p['team_name']) . '">';
            $currentTeam = $p['team_name'];
        endif;
?>
                                <option value="<?= $p['id']?>">#
                                    <?= $p['number']?>
                                    <?= htmlspecialchars($p['name'])?> (
                                    <?= $p['position']?>)
                                </option>
                                <?php
    endforeach; ?>
                                <?php if ($currentTeam !== '')
        echo '</optgroup>'; ?>
                            </select>
                        </div>

                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;">
                            <div class="form-group">
                                <label>FGM</label>
                                <input type="number" name="fgm" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>FGA</label>
                                <input type="number" name="fga" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>3PM</label>
                                <input type="number" name="three_pm" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>3PA</label>
                                <input type="number" name="three_pa" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>FTM</label>
                                <input type="number" name="ftm" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>FTA</label>
                                <input type="number" name="fta" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>OREB</label>
                                <input type="number" name="oreb" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>DREB</label>
                                <input type="number" name="dreb" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>REB (自動)</label>
                                <input type="number" name="reb" class="form-control" min="0" value="0" readonly
                                    style="background:#222;">
                            </div>
                            <div class="form-group">
                                <label>PTS (自動)</label>
                                <input type="number" name="pts" class="form-control" min="0" value="0" readonly
                                    style="background:#222;">
                            </div>
                            <div class="form-group">
                                <label>AST</label>
                                <input type="number" name="ast" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>STL</label>
                                <input type="number" name="stl" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>BLK</label>
                                <input type="number" name="blk" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>TOV</label>
                                <input type="number" name="tov" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>PF</label>
                                <input type="number" name="pf" class="form-control" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>+/-</label>
                                <input type="number" name="plus_minus" class="form-control" value="0">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg mt-2">スタッツを保存</button>
                    </form>
                </div>
            </div>

            <!-- Existing Stats -->
            <?php if (!empty($existingStats)): ?>
            <div class="card">
                <div class="card-header">
                    <h3>入力済みスタッツ</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <div class="stats-table-wrapper">
                        <table class="stats-table">
                            <thead>
                                <tr>
                                    <th style="text-align:left">選手</th>
                                    <th>TEAM</th>
                                    <th>PTS</th>
                                    <th>REB</th>
                                    <th>AST</th>
                                    <th>STL</th>
                                    <th>BLK</th>
                                    <th>FGM</th>
                                    <th>FGA</th>
                                    <th>3PM</th>
                                    <th>3PA</th>
                                    <th>FTM</th>
                                    <th>FTA</th>
                                    <th>OREB</th>
                                    <th>DREB</th>
                                    <th>TOV</th>
                                    <th>PF</th>
                                    <th>+/-</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($existingStats as $s): ?>
                                <tr>
                                    <td style="text-align:left;font-weight:600;">#
                                        <?= $s['number']?>
                                        <?= htmlspecialchars($s['player_name'])?>
                                    </td>
                                    <td>
                                        <?= $s['short_name']?>
                                    </td>
                                    <td class="fw-bold">
                                        <?= $s['pts']?>
                                    </td>
                                    <td>
                                        <?= $s['reb']?>
                                    </td>
                                    <td>
                                        <?= $s['ast']?>
                                    </td>
                                    <td>
                                        <?= $s['stl']?>
                                    </td>
                                    <td>
                                        <?= $s['blk']?>
                                    </td>
                                    <td>
                                        <?= $s['fgm']?>
                                    </td>
                                    <td>
                                        <?= $s['fga']?>
                                    </td>
                                    <td>
                                        <?= $s['three_pm']?>
                                    </td>
                                    <td>
                                        <?= $s['three_pa']?>
                                    </td>
                                    <td>
                                        <?= $s['ftm']?>
                                    </td>
                                    <td>
                                        <?= $s['fta']?>
                                    </td>
                                    <td>
                                        <?= $s['oreb']?>
                                    </td>
                                    <td>
                                        <?= $s['dreb']?>
                                    </td>
                                    <td>
                                        <?= $s['tov']?>
                                    </td>
                                    <td>
                                        <?= $s['pf']?>
                                    </td>
                                    <td>
                                        <?= $s['plus_minus'] > 0 ? '+' . $s['plus_minus'] : $s['plus_minus']?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_stat">
                                            <input type="hidden" name="stat_id" value="<?= $s['id']?>">
                                            <input type="hidden" name="game_id" value="<?= $selectedGame?>">
                                            <button type="submit" class="btn btn-sm btn-danger btn-delete">削除</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php
        endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php
    endif; ?>
            <?php
else: ?>
            <div class="empty-state">
                <div class="icon">📈</div>
                <p>試合を選択してスタッツを入力してください</p>
            </div>
            <?php
endif; ?>
        </main>
    </div>
    <script src="../js/app.js"></script>
</body>

</html>