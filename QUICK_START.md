# ライブスタッツシステム - セットアップ実行手順

## ✅ 完成したファイル

### Phase 1: 設定・スキーマ
- [config.php](config.php) - DB接続先を `kasugai-sp_div-stats` に更新 ✅
- [sql/01_create_live_stats_tables.sql](sql/01_create_live_stats_tables.sql) - 4テーブルのDDL ✅
- [db_setup.php](db_setup.php) - PHPベースのセットアップツール ✅

### Phase 2: APIエンドポイント
- [api/session.php](api/session.php) - セッション管理 (create/keepalive/logout) ✅
- [api/record_event.php](api/record_event.php) - イベント記録 ✅
- [api/get_live_score.php](api/get_live_score.php) - スコア取得（公開用） ✅

### Phase 3: フロントエンド
- [js/live-stats-ui.js](js/live-stats-ui.js) - UIコンポーネント ✅
- [admin/live-stats.php](admin/live-stats.php) - 入力画面（完全実装） ✅
- [ナビゲーション更新](admin/index.php) - 全admin ページに「ライブ入力」リンク追加 ✅

---

## 🚀 実行ステップ

### ステップ 1️⃣ : DBテーブルを作成（最重要）

#### 方法A: PHPセットアップツール（推奨・最簡単）
```
ブラウザでアクセス:
https://kasugai-sp.sakura.ne.jp/dev-stats/db_setup.php
```

**期待される画面:**
```
✅ セットアップ成功
✅ テーブル作成成功: live_stat_sessions
✅ テーブル作成成功: game_events
✅ テーブル作成成功: live_score_state
✅ テーブル作成成功: player_foul_log
✅ DBセットアップ完了！
```

#### 方法B: SQLファイルを直接実行
```bash
# リモートサーバーで実行（コマンドライン）
mysql -h mysql3114.db.sakura.ne.jp \
  -u kasugai-sp_div-stats \
  -p kasugai-sp_div-stats \
  < /Users/h1de1234/Library/CloudStorage/MountainDuck3-kasugai-sp.sakura.ne.jp–SFTP/www/dev-stats/sql/01_create_live_stats_tables.sql
```

#### 方法C: PHPMyAdmin で直接実行
1. PHPMyAdmin にログイン
2. `kasugai-sp_div-stats` データベースを選択
3. **SQL** タブをクリック
4. [sql/01_create_live_stats_tables.sql](sql/01_create_live_stats_tables.sql) の内容をコピペして実行

---

### ステップ 2️⃣ : APIをテスト

#### A. セッション作成テスト
```bash
curl -X POST "https://kasugai-sp.sakura.ne.jp/dev-stats/api/session.php" \
  -H "Content-Type: application/json" \
  -d '{
    "action": "create",
    "game_id": 1,
    "team_id": 1,
    "role": "score"
  }'
```

**期待されるレスポンス:**
```json
{
  "success": true,
  "session_token": "abc123xyz...",
  "message": "セッションを作成しました",
  "expires_in": 3600
}
```

#### B. イベント記録テスト
```bash
curl -X POST "https://kasugai-sp.sakura.ne.jp/dev-stats/api/record_event.php" \
  -H "Content-Type: application/json" \
  -d '{
    "session_token": "YOUR_TOKEN_HERE",
    "event_type": "score",
    "player_id": 1,
    "team_id": 1,
    "quarter": 1,
    "minute_in_quarter": 5,
    "event_data": {"points": 2}
  }'
```

#### C. スコア取得テスト
```bash
curl "https://kasugai-sp.sakura.ne.jp/dev-stats/api/get_live_score.php?game_id=1"
```

---

### ステップ 3️⃣ : 管理画面でライブ入力を試す

1. 管理画面にログイン: `https://kasugai-sp.sakura.ne.jp/dev-stats/admin/index.php`

2. ナビゲーションから **🔴 ライブ入力** をクリック

3. 試合が進行中または予定中であることを確認

4. 試合を選択：
   - プルダウンから試合を選択
   - ホームチーム・アウェイチームの選手が表示される

5. ロール選択＆ログイン：
   - 「得点担当」「ファウル・交代担当」などを選択
   - **ログイン** ボタンをクリック

6. ボタンをタップして入力：
   - 選手ボタンの **+2** (得点) を押す
   - 選手ボタンの **F** (ファウル) を押す
   - リアルタイムでスコアボードが更新される

---

## 📊 構成確認

```
dev-stats/
├── config.php (更新済み: DB接続先変更)
├── db_setup.php (新規: セットアップツール)
├── setup_live_stats.php (既存)
│
├── sql/
│   └── 01_create_live_stats_tables.sql (新規: DDL)
│
├── api/
│   ├── session.php (新規)
│   ├── record_event.php (新規)
│   └── get_live_score.php (新規)
│
├── js/
│   └── live-stats-ui.js (新規)
│
├── admin/
│   ├── index.php (更新: ナビゲーション)
│   ├── teams.php (更新: ナビゲーション)
│   ├── players.php (更新: ナビゲーション)
│   ├── games.php (更新: ナビゲーション)
│   ├── stats.php (更新: ナビゲーション)
│   └── live-stats.php (新規: 入力画面)
│
└── docs/
    ├── DB_SCHEMA_LIVE_STATS.md
    ├── LIVE_STATS_IMPLEMENTATION.md
    └── ...
```

---

## ✨ 利用フロー

### 試合開始時

1. 試合が「進行中」になったら、管理側が複数デバイスでログイン
   - ホーム側: 得点担当、ファウル担当（2-3名）
   - アウェイ側: 得点担当、ファウル担当（2-3名）

2. 各担当者が選手ボタンをタップ
   - **得点**: 得点ボタン (+2, +3など) をタップ
   - **ファウル**: Fボタンをタップ
   - **交代**: 交代ボタンをタップ

3. リアルタイムスコアボード
   - 公開サイト: `https://kasugai-sp.sakura.ne.jp/dev-stats/index.php` で自動更新
   - 観客が見る画面に得点・ファウル・時間が表示

---

## ⚙️ トラブルシューティング

### ❌「テーブルが既に存在します」エラー
**対処**: 再度実行しても大丈夫です（IF NOT EXISTS で保護されています）

### ❌ APIが 404 エラー
**確認事項**:
- `api/` フォルダが存在するか確認
- ファイルパスが正しいか確認
- `.htaccess` で `api/` がブロックされていないか確認

### ❌ セッション作成に失敗
**原因**:
- 指定した game_id が存在しない
- 同じゲーム・チーム・ロール で既に入力者がいる

### ❌ スコアが更新されない
**確認**:
- ブラウザコンソールでエラーがないか確認
- network タブで API レスポンスが 200 OK か確認
- DBに `live_score_state` レコードが存在するか確認

---

## 📞 次のステップ

✅ DBテーブル作成  
✅ API実装  
✅ 入力画面実装  
⬜ **公開スコアボード実装** (scoreboard.php)  
⬜ **統計情報自動計算** (試合後の stats 集計)  
⬜ **WebSocket対応** (さらにリアルタイム性向上)

