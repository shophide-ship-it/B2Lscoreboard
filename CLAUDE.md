Markdown
# B2L League Management System Project

24チーム（3部制・各8チーム）のバスケットボールリーグ運営サイト。
現在、選手登録申請および管理者承認機能の実装中。

## プロジェクト概要
- **創設:** 14年目
- **構成:** 3部リーグ構成、各部8チーム（計24チーム）
- **主要機能:** チーム表示、選手詳細、スケジュール、成績管理、リーダーボード
- **現在のタスク:** 代表者による選手登録フォームの実装と、管理者による承認フローの完成。

## 技術スタック
- **Backend:** PHP (Native / Procedural or Lightweight OOP)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla JS or jQuery)
- **Directory Structure:**
  - `/admin/`: 管理者用承認画面・データ管理
  - `/api/players/`: 選手登録・取得・承認用エンドポイント
  - `/register/`: 代表者用選手登録フォーム（フロントエンド）
  - `/db/`: データベース接続クラス・設定
- **Database Structure:**
  - `players`: 確定した選手情報。
  - `player_registrations`: 代表者がフォームから送信した一時データ。管理者が承認後、`players` へ移行または `status` を更新。
  - `teams` / `team_registrations`: チーム情報の管理。
  - `games` / `game_schedule` / `standings`: リーグ運営の核となる試合・順位データ。
  - `live_*` 系テーブル: 試合中のリアルタイム更新用。

## 実装ルール
- **命名規則:** 変数・関数名はキャメルケース (`$playerData`)、DBカラム名はスネークケース (`player_id`)。
- **承認フロー:** 1. 代表者が `/register/` から選手情報を送信。
  2. `players` テーブルの `status` カラムを `pending` (または 0) で保存。
  3. 管理者が `/admin/` で確認し、承認すると `approved` (または 1) に更新。
- **セキュリティ:** - SQLインジェクション対策として `PDO` または `mysqli_prepare` を使用。
  - 管理者画面・登録画面には適切な認証チェックを入れる。

/b2l/register/
├── scorer/                // フォルダ：スコアラー関連
├── check_db.php           // DB接続確認用
├── check_encoding.php     // 文字コード確認用
├── check_schema.php       // スキーマ確認用
├── debug.php              // デバッグ用
├── debug2.php             // デバッグ用
├── debug_check_config.php // 設定確認用デバッグ
├── debug_players.php      // 選手データ確認用デバッグ
├── debug_runtime.php      // 実行時デバッグ
├── debug_runtime2.php     // 実行時デバッグ
├── index.php              // 登録システムのメインエントリ
├── players.php            // 選手登録処理用
├── show_config.php        // 設定表示用
├── show_token.php         // トークン表示用
└── test_connect.php       // 接続テスト用

/b2l/api/
├── players/            // フォルダ：選手関連API
├── .htaccess           // ルーティング・アクセス制限
├── approve.php         // 【重要】承認処理用エンドポイント
├── auth.php            // 認証処理
├── check.php           // ステータスチェック用
├── debug_table.php     // テーブル構造確認用デバッグ
├── games.php           // 試合データAPI
├── line_push.php       // LINE通知連携用
├── live-stats (2026-03-29 8-44-30).php // バックアップ/旧版
├── live-stats (2026-03-30 7-45-22).php // バックアップ/旧版
├── live-stats.php      // ライブ統計API
├── register.php        // 登録処理用
├── registrations.php   // 申請一覧取得用
├── reject.php          // 【重要】却下処理用エンドポイント
├── schedule.php        // スケジュールAPI
├── teams_list.php      // チーム一覧取得
└── test-auth.php       // 認証テスト用

/b2l/admin/
├── auth.php            // 管理者認証
├── check_config.php    // 設定確認用
├── check_reg_tables.php // 登録テーブル確認用
├── config.php          // 管理画面用設定
├── debug.php           // デバッグ用
├── games.php           // 試合管理画面
├── index.php           // 管理画面トップ（ダッシュボード）
├── player_approvals.php // 【最重要】選手承認管理UI画面
├── players.php         // 選手一覧・編集画面
├── setup_phase1.php    // セットアップ用
├── stats.php           // 統計管理
├── teams.php           // チーム管理
├── test.html           // テスト用表示
└── test.php            // テスト用スクリプト

## 解決すべき現在のエラー / 未完了タスク
- [ ] 選手登録フォームのバリデーション実装
- [ ] 管理者画面での「未承認リスト」の表示
- [ ] 承認ボタン押下時の `status` 更新処理の実装

## 主要なワークフロー
- **選手登録:** `/b2l/register/index.php` → `/b2l/api/register.php` → `player_registrations`テーブルへ。
- **承認フロー:** `/b2l/admin/player_approvals.php` (UI) → `/b2l/api/approve.php` (Logic) → `players`テーブルへ移行。
- **通知:** 必要に応じて `line_push.php` を呼び出し。

## 注意事項
- `live-stats` 等に日付入りのバックアップファイルがあるため、これらを編集せず、最新の `.php` ファイルを修正すること。
- `api/approve.php` と `api/reject.php` が承認ロジックの核となる。
