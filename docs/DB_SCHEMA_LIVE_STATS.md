# B2L League - ライブスタッツシステム DBスキーマ設計

## 📋 新規テーブル構成（4つ追加）

### 1. `live_stat_sessions` - 試合中の入力者セッション管理

```sql
CREATE TABLE IF NOT EXISTS live_stat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    session_token VARCHAR(64) UNIQUE,
    team_id INT NOT NULL,
    role ENUM('score', 'foul', 'stat', 'operator') NOT NULL,
    -- role説明:
    -- score: 得点入力担当
    -- foul: ファウル・交代入力担当
    -- stat: リバウンド・アシスト等の詳細スタッツ担当
    -- operator: 試合進行管理（時間、クォーター等）
    
    ip_address VARCHAR(45),
    device_type VARCHAR(20),
    login_status ENUM('active', 'standby', 'disconnected') DEFAULT 'active',
    last_active_at TIMESTAMP,
    
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id),
    INDEX idx_game_session (game_id, user_id),
    INDEX idx_token (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**用途**: 
- 試合中に複数ユーザーがログイン・ロール分担
- WebSocketやPollingで接続状態を監視
- 同時入力時の競合を防ぐ

---

### 2. `game_events` - 試合イベント時系列記録（コアテーブル）

```sql
CREATE TABLE IF NOT EXISTS game_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    event_type ENUM(
        'score',          -- 得点
        'foul',           -- ファウル
        'substitution',   -- 交代
        'rebound',        -- リバウンド
        'assist',         -- アシスト
        'steal',          -- スティール
        'block',          -- ブロック
        'turnover'        -- ターンオーバー
    ) NOT NULL,
    
    player_id INT NOT NULL,
    team_id INT NOT NULL,
    recorded_by_session_id INT NOT NULL,
    
    quarter TINYINT DEFAULT 1,
    minute_in_quarter INT,
    
    -- イベント詳細（JSON）
    event_data JSON,
    -- 例: score: {"points": 2, "foul_committed": false}
    -- 例: foul: {"foul_type": "personal", "player_fouled_out": false}
    -- 例: substitution: {"player_out": 5, "player_in": 10}
    
    confirmed TINYINT(1) DEFAULT 0,
    confirmed_by_session_id INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    event_timestamp TIMESTAMP,  -- 実際の試合中の時刻
    
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (recorded_by_session_id) REFERENCES live_stat_sessions(id),
    FOREIGN KEY (confirmed_by_session_id) REFERENCES live_stat_sessions(id),
    
    INDEX idx_game_event (game_id, event_type),
    INDEX idx_player (player_id),
    INDEX idx_timestamp (event_timestamp),
    INDEX idx_quarter (game_id, quarter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**用途**: 
- すべての試合イベントを時系列で記録
- イベントの監査ログ（誰が入力したか）
- 統計情報の元データ

---

### 3. `live_score_state` - リアルタイムスコア状態（キャッシュ）

```sql
CREATE TABLE IF NOT EXISTS live_score_state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL UNIQUE,
    
    -- スコア情報
    home_score INT DEFAULT 0,
    away_score INT DEFAULT 0,
    
    -- 試合進行情報
    quarter TINYINT DEFAULT 1,
    minute_in_quarter INT DEFAULT 0,
    possession_team_id INT,  -- ボール保持チーム
    
    -- ファウル情報
    home_team_fouls INT DEFAULT 0,
    away_team_fouls INT DEFAULT 0,
    
    -- 選手別ファウル数
    -- (別テーブルで管理する方が良いため、ここではカウントのみ)
    
    last_event_id BIGINT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (possession_team_id) REFERENCES teams(id),
    FOREIGN KEY (last_event_id) REFERENCES game_events(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**用途**:
- 公開サイトで表示するスコア情報の高速読み取り
- リアルタイムスコアボードのキャッシュ
- イベント発生後に自動更新

---

### 4. `player_foul_log` - 選手別ファウル履歴

```sql
CREATE TABLE IF NOT EXISTS player_foul_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    player_id INT NOT NULL,
    team_id INT NOT NULL,
    
    foul_type ENUM('personal', 'technical', 'flagrant') DEFAULT 'personal',
    foul_number TINYINT,  -- 1st, 2nd, 3rd... (5ファウル退場)
    fouled_out TINYINT(1) DEFAULT 0,  -- 退場したか
    
    recorded_by_session_id INT,
    event_id BIGINT,  -- game_events テーブルへの参照
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (recorded_by_session_id) REFERENCES live_stat_sessions(id),
    FOREIGN KEY (event_id) REFERENCES game_events(id),
    
    INDEX idx_player_game (player_id, game_id),
    INDEX idx_team_game (team_id, game_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**用途**:
- 選手別のファウル記録・管理
- 5ファウル退場ロジック
- 統計情報の集計

---

## 🔄 データフロー

```
[入力端末] 
   ↓
[ボタンタップ] → AJAX リクエスト
   ↓
[API エンドポイント (api/record_event.php)]
   ↓
INSERT INTO game_events
   ↓
自動トリガー / アプリケーションロジック:
   ├─ UPDATE live_score_state (スコア更新)
   ├─ UPDATE player_foul_log (ファウル数更新)
   └─ WebSocket ブロードキャスト (他のクライアントに通知)
   ↓
[公開スコアボード (index.php / scoreboard.php)]
   ↓
SELECT FROM live_score_state (リアルタイム表示)
```

---

## 📊 複数ユーザー同時入力時の競合対策

### 1. イベント重複防止
- `game_events` テーブルに **タイムスタンプ + セッションID** で一意性を確保
- 同一時刻の同一選手による同一イベントは2回記録されない

### 2. 入力確認フロー
```
[複数ユーザーが同時入力]
      ↓
[game_events に INSERT (confirmed=0)]
      ↓
[他のユーザーに通知 - 確認ダイアログ表示]
      ↓
[確認ユーザーが「承認」クリック]
      ↓
[UPDATE game_events SET confirmed=1]
      ↓
[live_score_state を更新]
```

### 3. セッションタイムアウト
- `last_active_at` で接続監視
- 一定時間無活動 → ステータスを `disconnected` に
- 復帰時に差分を同期

---

## 🎮 実装フェーズ

### Phase 1: DB構築（今回）
- 4テーブルを新規作成
- 既存テーブル (`games`, `players`, `teams`) との連携確認

### Phase 2: APIエンドポイント作成
- `api/record_event.php` - イベント記録
- `api/get_live_state.php` - リアルタイム状態取得
- `api/confirm_event.php` - イベント確認
- `api/session_keepalive.php` - ハートビート

### Phase 3: 入力UI (admin/live-stats.php)
- ボタンベースのUIコンポーネント
- リアルタイム同期機能
- セッション管理

### Phase 4: 公開スコアボード (scoreboard.php)
- リアルタイム表示
- 得点・ファウル・交代の自動更新
- WebSocket 対応（オプション）

---

## 💾 既存テーブルとの関係図

```
teams ←─┬─ players ─┐
        ↓           ↓
      games ←─ player_stats (既存、試合後スタッツ用)
        ↑
        ├─ game_events (NEW: ライブ記録)
        ├─ live_stat_sessions (NEW: ユーザー管理)
        ├─ live_score_state (NEW: キャッシュ)
        └─ player_foul_log (NEW: ファウル管理)
```

