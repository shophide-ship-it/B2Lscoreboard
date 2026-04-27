# B2L League ライブスタッツシステム - 実装ガイド

## 📋 概要

複数ユーザーによるリアルタイム同時入力に対応した試合中のスタッツ入力システムです。

- ✅ **複数ユーザー同時入力**: ホーム側2-3人、アウェイ側2-3人で役割分担
- ✅ **リアルタイム更新**: 入力即座に公開スコアボードが自動更新
- ✅ **ボタンタップ入力**: 得点、ファウル、交代などをタップで記録
- ✅ **監査ログ**: すべてのイベントを時系列で記録

---

## 🗂️ ファイル構成

### DBテーブル定義
```
docs/DB_SCHEMA_LIVE_STATS.md    - スキーマ設計書
sql/01_create_live_stats_tables.sql - SQL定義ファイル
setup_live_stats.php             - セットアップスクリプト (PHP)
```

### API エンドポイント
```
api/session.php              - セッション管理 (作成/キープアライブ/ログアウト)
api/record_event.php         - イベント記録 (得点/ファウル/交代など)
api/get_live_score.php       - スコア取得 (公開表示用)
```

### フロントエンド
```
js/live-stats-ui.js          - UIコンポーネント (ボタン/リアルタイム更新)
admin/live-stats.php         - 入力UI (未作成 - 実装予定)
scoreboard.php               - 公開スコアボード (未作成 - 実装予定)
```

---

## 🚀 実装手順

### **ステップ 1: DBテーブル作成**

#### 方法A: SQLスクリプトを直接実行（推奨）
```bash
# リモートサーバーにSSH接続後
mysql -h mysql3114.db.sakura.ne.jp -u kasugai-sp_div-stats -p kasugai-sp_div-stats < sql/01_create_live_stats_tables.sql
```

#### 方法B: PHPセットアップスクリプト
```
ブラウザで以下にアクセス:
https://kasugai-sp.sakura.ne.jp/dev-stats/setup_live_stats.php
```

#### 方法C: MySQL管理画面から実行
```
phpmyadmin で SQL を直接実行
```

---

### **ステップ 2: API エンドポイントの動作確認**

#### セッション作成のテスト
```bash
curl -X POST http://localhost/dev-stats/api/session.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "create",
    "game_id": 1,
    "team_id": 1,
    "role": "score"
  }'
```

**レスポンス:**
```json
{
  "success": true,
  "session_token": "abc123xyz...",
  "message": "セッションを作成しました",
  "expires_in": 3600
}
```

#### イベント記録のテスト
```bash
curl -X POST http://localhost/dev-stats/api/record_event.php \
  -H "Content-Type: application/json" \
  -d '{
    "session_token": "abc123xyz...",
    "event_type": "score",
    "player_id": 15,
    "team_id": 1,
    "quarter": 1,
    "minute_in_quarter": 5,
    "event_data": {"points": 2}
  }'
```

#### リアルタイムスコア取得のテスト
```bash
curl http://localhost/dev-stats/api/get_live_score.php?game_id=1
```

---

### **ステップ 3: 入力UI ページの作成 (admin/live-stats.php)**

以下の構成で作成：

```php
<?php
require_once __DIR__ . '/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ライブスタッツ入力</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body>
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <!-- 既存の管理画面サイドバー -->
        </aside>
        <main class="admin-main">
            <h1>ライブスタッツ入力</h1>
            
            <!-- セッション情報 -->
            <div id="session-info">
                試合選択: <select id="game-select">
                    <option value="">選択...</option>
                </select>
                
                ロール選択: <select id="role-select">
                    <option value="score">得点担当</option>
                    <option value="foul">ファウル・交代担当</option>
                    <option value="stat">詳細スタッツ担当</option>
                </select>
                
                <button id="btn-login">ログイン</button>
            </div>

            <!-- スコアボード表示エリア -->
            <div id="live-stats-container">
                <div class="scoreboard-area"></div>
                
                <!-- ホーム側選手ボタン -->
                <div class="team-section home-team">
                    <h3>ホームチーム</h3>
                    <div id="home-players" class="players-grid">
                        <!-- JSで動的に生成 -->
                    </div>
                </div>
                
                <!-- アウェイ側選手ボタン -->
                <div class="team-section away-team">
                    <h3>アウェイチーム</h3>
                    <div id="away-players" class="players-grid">
                        <!-- JSで動的に生成 -->
                    </div>
                </div>

                <!-- イベントログ -->
                <div class="event-log"></div>
            </div>

            <button id="btn-logout">ログアウト</button>
        </main>
    </div>

    <script src="<?= url('js/live-stats-ui.js') ?>"></script>
    <script>
        let liveStats;

        document.addEventListener('DOMContentLoaded', function() {
            // セッションログイン処理
            document.getElementById('btn-login').addEventListener('click', async function() {
                const gameId = parseInt(document.getElementById('game-select').value);
                const role = document.getElementById('role-select').value;
                
                if (!gameId) {
                    alert('試合を選択してください');
                    return;
                }

                // チーム情報を取得（現在のユーザーが属するチーム）
                // TODO: ユーザー認証情報からチームを取得

                try {
                    const response = await fetch('/api/session.php?action=create', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            game_id: gameId,
                            team_id: 1,  // TODO: 実際のチームID
                            role: role
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        liveStats = new LiveStatsUI('live-stats-container', data.session_token);
                        liveStats.gameId = gameId;
                        liveStats.teamId = 1;
                        loadPlayerButtons(gameId);
                    } else {
                        alert('エラー: ' + data.error);
                    }
                } catch (error) {
                    console.error('セッション作成失敗:', error);
                }
            });

            // ログアウト
            document.getElementById('btn-logout').addEventListener('click', async function() {
                if (liveStats) {
                    await liveStats.logout();
                    alert('ログアウトしました');
                }
            });

            // 試合一覧を取得・表示
            fetchGames();
        });

        async function fetchGames() {
            try {
                const response = await fetch('/admin/api/get_games.php');  // 既存API使用
                const games = await response.json();
                
                const select = document.getElementById('game-select');
                games.forEach(game => {
                    const option = document.createElement('option');
                    option.value = game.id;
                    option.textContent = `${game.game_date} ${game.home_short} vs ${game.away_short}`;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('試合一覧取得失敗:', error);
            }
        }

        async function loadPlayerButtons(gameId) {
            // TODO: 試合に参加している選手を取得して、ボタンを動的に生成
            // ボタンクリック時に liveStats.recordScore() 等を実行
        }
    </script>
</body>
</html>
```

---

### **ステップ 4: スタイル設定 (css/live-stats.css)**

```css
/* ライブスタッツUIのスタイル */

.live-scoreboard {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #222;
    color: white;
    border-radius: 8px;
    margin-bottom: 30px;
}

.live-scoreboard .team {
    flex: 1;
    text-align: center;
}

.live-scoreboard .score {
    font-size: 72px;
    font-weight: bold;
}

.live-scoreboard .fouls {
    font-size: 14px;
    color: #aaa;
    margin-top: 8px;
}

.live-scoreboard .game-time {
    text-align: center;
}

.live-scoreboard .quarter {
    font-size: 24px;
    font-weight: bold;
}

.live-scoreboard .time {
    font-size: 48px;
    font-weight: bold;
}

/* 選手ボタングリッド */
.players-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
    margin: 20px 0;
}

.player-button {
    padding: 16px;
    background: #333;
    border: 2px solid #555;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.player-button:hover {
    border-color: var(--accent-blue);
    background: #444;
}

.player-button.active {
    border-color: var(--accent-green);
    background: rgba(76, 175, 80, 0.2);
}

.player-button .number {
    font-size: 24px;
    font-weight: bold;
    display: block;
}

.player-button .name {
    font-size: 12px;
    margin-top: 4px;
}

/* イベントログ */
.event-log {
    background: #f5f5f5;
    border-radius: 8px;
    padding: 16px;
    max-height: 300px;
    overflow-y: auto;
}

.event-item {
    padding: 8px;
    margin-bottom: 4px;
    background: white;
    border-left: 4px solid #999;
    border-radius: 4px;
    display: flex;
    gap: 12px;
    font-size: 13px;
}

.event-item.event-score {
    border-left-color: #4CAF50;
}

.event-item.event-foul {
    border-left-color: #f44336;
}

.event-item.event-rebound {
    border-left-color: #2196F3;
}
```

---

## 🔄 データフロー図

```
┌─────────────┐
│ 入力端末    │ (複数ユーザー)
└─────┬───────┘
      │ ボタンタップ
      ↓
┌──────────────────────┐
│ api/record_event.php │ ← POST リクエスト
│ - session検証        │
│ - イベント記録       │
└────────┬─────────────┘
         │
         ↓
    ┌────────────────────────┐
    │ game_events テーブル   │
    │ (監査ログ)             │
    └────────┬───────────────┘
             │
             ├─→ UPDATE live_score_state
             │   (スコア自動更新)
             │
             └─→ UPDATE player_foul_log
                 (ファウル数更新)
                      ↓
            ┌─────────────────────┐
            │ 公開スコアボード    │
            │ (観客が見る画面)    │
            │ /api/get_live_score │
            └─────────────────────┘
                      ↓
            スコア自動更新 ✨
```

---

## 🛠️ トラブルシューティング

### Q: セッション作成時に「既に入力者がいます」エラーが出る
**A:** 前回のセッションが残っている可能性があります。
```sql
UPDATE live_stat_sessions SET login_status = 'disconnected' 
WHERE game_id = ? AND login_status = 'active' AND game_id != CURRENT_TIMESTAMP - INTERVAL 1 HOUR;
```

### Q: スコアボードが更新されない
**A:** 以下を確認してください：
1. APIレスポンスで `"success": true` が返されているか
2. `live_score_state` テーブルに該当ゲームのレコードがあるか
3. ブラウザのコンソールでエラーがないか

### Q: 複数ユーザーが同時に入力するとデータが重複する
**A:** `game_events` テーブルのUNIQUEインデックスを確認してください。タイムスタンプとセッションIDの組み合わせで重複を防いでいます。

---

## 📝 次のステップ

- [ ] admin/live-stats.php のUI完成
- [ ] scoreboard.php の公開スコアボード作成
- [ ] WebSocket対応 (リアルタイム性向上)
- [ ] モバイル最適化
- [ ] 試合後の統計情報自動計算

