<?php
/**
 * ライブスタッツ入力画面
 * 
 * 機能:
 * - 試合選択
 * - セッション作成（ロール選択）
 * - 選手ごとのボタンタップ入力
 * - リアルタイムスコア表示
 * - 複数ユーザー同時入力対応
 */

require_once __DIR__ . '/auth.php';
requireLogin();

$pdo = getDB();

// 進行中・予定中の試合を取得
$games = $pdo->query("
    SELECT g.id, g.game_date, g.game_time, g.status, g.division,
           ht.id as home_id, ht.name as home_name, ht.short_name as home_short,
           at.id as away_id, at.name as away_name, at.short_name as away_short
    FROM games g
    JOIN teams ht ON g.home_team_id = ht.id
    JOIN teams at ON g.away_team_id = at.id
    WHERE g.status IN ('scheduled', 'live')
    ORDER BY g.game_date DESC, g.game_time DESC
    LIMIT 20
")->fetchAll();

// 試合が選択されている場合、その試合の情報を取得
$selectedGameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
$gameInfo = null;
$homePlayers = [];
$awayPlayers = [];
$liveScore = null;

if ($selectedGameId > 0) {
    $gameInfo = $pdo->query("
        SELECT g.*, 
               ht.id as home_id, ht.name as home_name, ht.short_name as home_short,
               at.id as away_id, at.name as away_name, at.short_name as away_short
        FROM games g
        JOIN teams ht ON g.home_team_id = ht.id
        JOIN teams at ON g.away_team_id = at.id
        WHERE g.id = $selectedGameId
    ")->fetch();

    if ($gameInfo) {
        // ホーム側選手
        $homePlayers = $pdo->query("
            SELECT * FROM players 
            WHERE team_id = {$gameInfo['home_id']} AND is_active = 1
            ORDER BY number
        ")->fetchAll();

        // アウェイ側選手
        $awayPlayers = $pdo->query("
            SELECT * FROM players 
            WHERE team_id = {$gameInfo['away_id']} AND is_active = 1
            ORDER BY number
        ")->fetchAll();

        // ライブスコア状態を取得（なければ初期化）
        $liveScore = $pdo->query("SELECT * FROM live_score_state WHERE game_id = $selectedGameId")->fetch();
        if (!$liveScore) {
            $pdo->prepare("INSERT INTO live_score_state (game_id) VALUES (?)")->execute([$selectedGameId]);
            $liveScore = $pdo->query("SELECT * FROM live_score_state WHERE game_id = $selectedGameId")->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ライブスタッツ入力 - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
    <style>
        .live-stats-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .scoreboard {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 30px;
            align-items: center;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        }

        .scoreboard-team {
            text-align: center;
        }

        .scoreboard-team-name {
            font-size: 16px;
            color: #aaa;
            margin-bottom: 8px;
        }

        .scoreboard-score {
            font-size: 72px;
            font-weight: 900;
            margin-bottom: 12px;
        }

        .scoreboard-fouls {
            font-size: 14px;
            color: #ff6b6b;
        }

        .scoreboard-time {
            text-align: center;
        }

        .scoreboard-quarter {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .scoreboard-clock {
            font-size: 48px;
            font-weight: bold;
            font-family: 'Monaco', 'Courier New', monospace;
        }

        .team-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            border: 2px solid #e0e0e0;
        }

        .team-section h3 {
            margin-bottom: 20px;
            font-size: 18px;
            color: #333;
            border-bottom: 2px solid #1976d2;
            padding-bottom: 12px;
        }

        .players-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 12px;
        }

        .player-button-wrapper {
            position: relative;
        }

        .player-button {
            width: 100%;
            padding: 16px;
            background: #f5f5f5;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            font-family: inherit;
            font-size: 12px;
        }

        .player-button:hover {
            border-color: #1976d2;
            background: #e3f2fd;
        }

        .player-button:active {
            transform: scale(0.95);
        }

        .player-number {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
            display: block;
            margin-bottom: 4px;
        }

        .player-name {
            font-size: 11px;
            color: #666;
            display: block;
            margin-bottom: 6px;
            word-break: break-word;
        }

        .player-buttons-group {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }

        .btn-stat {
            flex: 1;
            padding: 6px;
            font-size: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }

        .btn-score {
            background: #4caf50;
            color: white;
        }

        .btn-score:hover {
            background: #45a049;
        }

        .btn-foul {
            background: #f44336;
            color: white;
        }

        .btn-foul:hover {
            background: #da190b;
        }

        .session-info {
            grid-column: 1 / -1;
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 16px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .session-info.disconnected {
            background: #ffebee;
            border-left-color: #f44336;
        }

        .event-log {
            grid-column: 1 / -1;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
        }

        .event-log-title {
            font-weight: bold;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .event-item {
            padding: 10px;
            margin-bottom: 8px;
            background: #f9f9f9;
            border-left: 4px solid #999;
            border-radius: 3px;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
        }

        .event-item.score {
            border-left-color: #4caf50;
        }

        .event-item.foul {
            border-left-color: #f44336;
        }

        .event-time {
            color: #999;
            font-size: 11px;
        }

        .alert {
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert-info {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            color: #0d47a1;
        }

        .close-alert {
            cursor: pointer;
            font-size: 20px;
            color: inherit;
        }

        .game-select {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #1976d2;
            box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
        }

        .btn-login {
            background: #1976d2;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            width: 100%;
            transition: all 0.2s;
        }

        .btn-login:hover {
            background: #1565c0;
        }

        .btn-logout {
            background: #f44336;
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }

        .btn-logout:hover {
            background: #da190b;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 768px) {
            .live-stats-container {
                grid-template-columns: 1fr;
            }

            .scoreboard {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .scoreboard-clock {
                font-size: 36px;
            }

            .players-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-logo"><h2>B2L <span>LEAGUE</span></h2><p>管理パネル</p></div>
        <nav class="admin-nav">
            <a href="<?= url('admin/index.php') ?>"><span class="icon">📊</span> ダッシュボード</a>
            <a href="<?= url('admin/teams.php') ?>"><span class="icon">🏀</span> チーム管理</a>
            <a href="<?= url('admin/players.php') ?>"><span class="icon">👤</span> 選手管理</a>
            <a href="<?= url('admin/games.php') ?>"><span class="icon">📅</span> 試合管理</a>
            <a href="<?= url('admin/stats.php') ?>"><span class="icon">📈</span> スタッツ入力</a>
            <a href="<?= url('admin/live-stats.php') ?>" class="active"><span class="icon">🔴</span> ライブ入力</a>
            <a href="<?= url('index.php') ?>"><span class="icon">🌐</span> サイト表示</a>
            <a href="<?= url('admin/index.php?action=logout') ?>"><span class="icon">🚪</span> ログアウト</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>🔴 ライブスタッツ入力</h1>
        </div>

        <div class="alert alert-info">
            <div>
                💡 <strong>試合中に複数ユーザーがボタンタップして同時に得点やファウルを入力できます。</strong>
                ホーム側2-3名、アウェイ側2-3名で役割分担して記録してください。
            </div>
            <span class="close-alert" onclick="this.parentElement.style.display='none';">✕</span>
        </div>

        <!-- 試合選択フォーム -->
        <div class="card mb-3 game-select">
            <div class="card-header"><h3>試合選択</h3></div>
            <div class="card-body">
                <form method="GET" id="game-select-form">
                    <div class="form-group">
                        <label>試合を選択</label>
                        <select name="game_id" id="game-id-select" onchange="document.getElementById('game-select-form').submit()">
                            <option value="">--- 試合を選択してください ---</option>
                            <?php foreach ($games as $g): ?>
                                <option value="<?= $g['id'] ?>" <?= $selectedGameId === $g['id'] ? 'selected' : '' ?>>
                                    [<?= getDivisionName($g['division']) ?>] 
                                    <?= date('n/j H:i', strtotime($g['game_date'] . ' ' . ($g['game_time'] ?? '00:00'))) ?>
                                    <?= $g['home_short'] ?> vs <?= $g['away_short'] ?>
                                    (<?= $g['status'] === 'live' ? '🔴 LIVE' : '📅 予定' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($gameInfo && $liveScore): ?>

        <!-- セッション情報＆ログイン -->
        <div id="session-control" class="card mb-3">
            <div class="card-body">
                <div class="form-group">
                    <label>ロール選択</label>
                    <select id="role-select">
                        <option value="score">得点担当</option>
                        <option value="foul">ファウル・交代担当</option>
                        <option value="stat">詳細スタッツ担当</option>
                        <option value="operator">試合進行管理</option>
                    </select>
                </div>
                <button class="btn-login" id="btn-login">ログイン</button>
                <button class="btn-logout hidden" id="btn-logout">ログアウト</button>
            </div>
        </div>

        <!-- セッション情報表示 -->
        <div id="session-info" class="session-info hidden">
            <div>
                <strong id="role-display"></strong> でログイン中
            </div>
            <span id="session-status">● アクティブ</span>
        </div>

        <!-- スコアボード -->
        <div class="live-stats-container" id="live-stats-container">
            <div class="scoreboard">
                <div class="scoreboard-team">
                    <div class="scoreboard-team-name"><?= htmlspecialchars($gameInfo['home_name']) ?></div>
                    <div class="scoreboard-score" id="home-score"><?= $liveScore['home_score'] ?></div>
                    <div class="scoreboard-fouls">FOULS: <span id="home-fouls">0</span></div>
                </div>

                <div class="scoreboard-time">
                    <div class="scoreboard-quarter">Q<span id="quarter"><?= $liveScore['quarter'] ?></span></div>
                    <div class="scoreboard-clock"><span id="minute">00</span>:<span id="second">00</span></div>
                </div>

                <div class="scoreboard-team">
                    <div class="scoreboard-team-name"><?= htmlspecialchars($gameInfo['away_name']) ?></div>
                    <div class="scoreboard-score" id="away-score"><?= $liveScore['away_score'] ?></div>
                    <div class="scoreboard-fouls">FOULS: <span id="away-fouls">0</span></div>
                </div>
            </div>

            <!-- ホーム側選手 -->
            <div class="team-section">
                <h3><?= htmlspecialchars($gameInfo['home_name']) ?> 選手</h3>
                <div class="players-grid" id="home-players">
                    <?php foreach ($homePlayers as $p): ?>
                        <div class="player-button-wrapper">
                            <button class="player-button" data-player-id="<?= $p['id'] ?>" data-player-name="<?= htmlspecialchars($p['name']) ?>">
                                <span class="player-number">#<?= $p['number'] ?></span>
                                <span class="player-name"><?= htmlspecialchars($p['name']) ?></span>
                                <span class="player-name" style="font-size: 10px; color: #999;"><?= $p['position'] ?></span>
                            </button>
                            <div class="player-buttons-group">
                                <button class="btn-stat btn-score" data-action="score" data-player-id="<?= $p['id'] ?>">+2</button>
                                <button class="btn-stat btn-foul" data-action="foul" data-player-id="<?= $p['id'] ?>">F</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- アウェイ側選手 -->
            <div class="team-section">
                <h3><?= htmlspecialchars($gameInfo['away_name']) ?> 選手</h3>
                <div class="players-grid" id="away-players">
                    <?php foreach ($awayPlayers as $p): ?>
                        <div class="player-button-wrapper">
                            <button class="player-button" data-player-id="<?= $p['id'] ?>" data-player-name="<?= htmlspecialchars($p['name']) ?>">
                                <span class="player-number">#<?= $p['number'] ?></span>
                                <span class="player-name"><?= htmlspecialchars($p['name']) ?></span>
                                <span class="player-name" style="font-size: 10px; color: #999;"><?= $p['position'] ?></span>
                            </button>
                            <div class="player-buttons-group">
                                <button class="btn-stat btn-score" data-action="score" data-player-id="<?= $p['id'] ?>">+2</button>
                                <button class="btn-stat btn-foul" data-action="foul" data-player-id="<?= $p['id'] ?>">F</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- イベントログ -->
            <div class="event-log">
                <div class="event-log-title">📋 イベント履歴</div>
                <div id="event-log-items"></div>
            </div>
        </div>

        <?php else: ?>

        <div class="alert alert-info">
            試合を選択してください
        </div>

        <?php endif; ?>

    </main>
</div>

<script src="<?= url('js/live-stats-ui.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const gameId = <?= $selectedGameId ?>;
    const teamId = <?= $gameInfo ? $gameInfo['home_id'] : 'null' ?>;
    
    let liveStats = null;

    // ログインボタン
    document.getElementById('btn-login')?.addEventListener('click', async function() {
        if (!gameId) {
            alert('試合を選択してください');
            return;
        }

        const role = document.getElementById('role-select').value;

        try {
            const response = await fetch('/dev-stats/api/session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create',
                    game_id: gameId,
                    team_id: teamId,
                    role: role
                })
            });

            const data = await response.json();
            if (data.success) {
                liveStats = new LiveStatsUI('live-stats-container', data.session_token);
                liveStats.gameId = gameId;
                liveStats.teamId = teamId;
                
                document.getElementById('session-control').classList.add('hidden');
                document.getElementById('session-info').classList.remove('hidden');
                document.getElementById('role-display').textContent = role === 'score' ? '得点担当' : 
                                                                       role === 'foul' ? 'ファウル担当' : 
                                                                       role === 'stat' ? 'スタッツ担当' : '試合進行管理';
                
                document.getElementById('btn-logout').classList.remove('hidden');

                // イベントボタンのリスナー
                attachPlayerButtonListeners(liveStats);
            } else {
                alert('エラー: ' + data.error);
            }
        } catch (error) {
            console.error('セッション作成失敗:', error);
            alert('セッション作成に失敗しました');
        }
    });

    // ログアウトボタン
    document.getElementById('btn-logout')?.addEventListener('click', async function() {
        if (liveStats) {
            await liveStats.logout();
            location.reload();
        }
    });

    function attachPlayerButtonListeners(liveStats) {
        // 得点ボタン
        document.querySelectorAll('.btn-score').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const playerId = parseInt(btn.dataset.playerId);
                liveStats.recordScore(playerId, 2);
            });
        });

        // ファウルボタン
        document.querySelectorAll('.btn-foul').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const playerId = parseInt(btn.dataset.playerId);
                liveStats.recordFoul(playerId);
            });
        });
    }
});
</script>
</body>
</html>
