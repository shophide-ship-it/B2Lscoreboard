# ライブスタッツシステム - セットアップサマリー

## 📦 提供ファイル一覧

### Phase 1: DBスキーマ ✅ 完成
```
docs/DB_SCHEMA_LIVE_STATS.md     - 4テーブルの詳細設計書
sql/01_create_live_stats_tables.sql - MySQL DDL
setup_live_stats.php              - PHPセットアップスクリプト
```

### Phase 2: APIエンドポイント ✅ 完成
```
api/session.php                   - セッション管理 API
api/record_event.php              - イベント記録 API  
api/get_live_score.php            - スコア取得 API（公開用）
```

### Phase 3: フロントエンド ✅ 部分完成
```
js/live-stats-ui.js               - UIコンポーネント（完成）
admin/live-stats.php              - 入力画面（スケルトン）
scoreboard.php                    - 公開スコアボード（スケルトン）
```

### ドキュメント ✅ 完成
```
docs/LIVE_STATS_IMPLEMENTATION.md - 完全な実装ガイド
docs/DB_SCHEMA_LIVE_STATS.md     - スキーマ解説
```

---

## 🚀 すぐに実行できる作業

### 1. DBテーブルを作成する
```bash
# リモートサーバーのMySQLで実行
SOURCE sql/01_create_live_stats_tables.sql;
```

### 2. APIをテストする
```bash
# セッション作成
curl -X POST http://your-server/api/session.php \
  -H "Content-Type: application/json" \
  -d '{"action":"create","game_id":1,"team_id":1,"role":"score"}'

# スコア取得
curl http://your-server/api/get_live_score.php?game_id=1
```

### 3. 実装ガイドに従って完成させる
[docs/LIVE_STATS_IMPLEMENTATION.md](docs/LIVE_STATS_IMPLEMENTATION.md) を参照

---

## 📊 システムアーキテクチャ

```
複数入力端末 (ホーム側2-3人 + アウェイ側2-3人)
        ↓
    API 層 (タップ入力処理)
        ├─ api/session.php (認証)
        ├─ api/record_event.php (イベント記録)
        └─ api/get_live_score.php (スコア取得)
        ↓
    DB 層 (データ永続化)
        ├─ game_events (監査ログ)
        ├─ live_score_state (キャッシュ)
        ├─ live_stat_sessions (セッション)
        └─ player_foul_log (ファウル管理)
        ↓
公開スコアボード (観客表示)
```

---

## 🎯 データフロー例

### シナリオ: ホームチームが得点
```
1. 入力者 (得点担当) が選手ボタンをタップ
   ↓
2. api/record_event.php に POST
   {
     "session_token": "...",
     "event_type": "score",
     "player_id": 15,
     "team_id": 1,
     "event_data": {"points": 2}
   }
   ↓
3. game_events テーブルに INSERT
   ↓
4. live_score_state を UPDATE (home_score + 2)
   ↓
5. 公開スコアボード (api/get_live_score.php) が自動取得
   ↓
6. 観客が見る画面で +2 点が表示される ✨
```

---

## ✅ 動作チェックリスト

- [ ] DBテーブル作成完了
- [ ] api/session.php でセッション作成可能
- [ ] api/record_event.php でイベント記録可能
- [ ] api/get_live_score.php でスコア取得可能
- [ ] live-stats-ui.js が正常に動作
- [ ] admin/live-stats.php UI実装完了
- [ ] scoreboard.php 公開表示実装完了
- [ ] WebSocket 対応検討（オプション）

---

## 🔧 トラブルシューティング

**Q: テーブルが作成されない**  
A: `setup_live_stats.php` または SQL ファイルを直接実行して、エラーメッセージを確認してください。

**Q: APIが 404 エラー**  
A: ファイルパスが正しいか確認。`api/` フォルダが存在するか確認してください。

**Q: スコアが更新されない**  
A: ブラウザコンソール見て JavaScript エラーがないか確認。API レスポンスが 200 OK か確認。

---

## 📞 技術サポート

詳細は `docs/LIVE_STATS_IMPLEMENTATION.md` を参照してください。

