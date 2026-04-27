<?php
/**
 * B2L League - ライブスタッツシステム DB初期化スクリプト
 * 
 * 用途: 試合中の複数ユーザーリアルタイム同時入力機能に対応
 * 実行方法: ブラウザで http://dev-stats.local/setup_live_stats.php にアクセス
 * または: php setup_live_stats.php from CLI
 */

require_once __DIR__ . '/config.php';

$pdo = getDB();
$messages = [];

try {
    $queries = [
        // ==========================================
        // 1. ライブスタッツセッション管理テーブル
        // ==========================================
        "CREATE TABLE IF NOT EXISTS live_stat_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id INT NOT NULL,
            user_id INT DEFAULT NULL,
            session_token VARCHAR(64) UNIQUE NOT NULL,
            team_id INT NOT NULL,
            role ENUM('score', 'foul', 'stat', 'operator') NOT NULL DEFAULT 'stat',
            
            ip_address VARCHAR(45),
            device_type VARCHAR(20),
            login_status ENUM('active', 'standby', 'disconnected') DEFAULT 'active',
            last_active_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
            FOREIGN KEY (team_id) REFERENCES teams(id),
            INDEX idx_game (game_id),
            INDEX idx_token (session_token),
            INDEX idx_status (login_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // ==========================================
        // 2. 試合イベント時系列記録テーブル
        // ==========================================
        "CREATE TABLE IF NOT EXISTS game_events (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            game_id INT NOT NULL,
            event_type ENUM(
                'score',
                'foul',
                'substitution',
                'rebound',
                'assist',
                'steal',
                'block',
                'turnover',
                'timeout'
            ) NOT NULL,
            
            player_id INT,
            team_id INT NOT NULL,
            recorded_by_session_id INT NOT NULL,
            
            quarter TINYINT DEFAULT 1,
            minute_in_quarter INT DEFAULT 0,
            
            event_data JSON,
            confirmed TINYINT(1) DEFAULT 1,
            confirmed_by_session_id INT,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            event_timestamp TIMESTAMP NULL,
            
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE SET NULL,
            FOREIGN KEY (team_id) REFERENCES teams(id),
            FOREIGN KEY (recorded_by_session_id) REFERENCES live_stat_sessions(id),
            FOREIGN KEY (confirmed_by_session_id) REFERENCES live_stat_sessions(id) ON DELETE SET NULL,
            
            INDEX idx_game (game_id),
            INDEX idx_type (event_type),
            INDEX idx_player (player_id),
            INDEX idx_timestamp (event_timestamp),
            INDEX idx_quarter (game_id, quarter)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // ==========================================
        // 3. ライブスコア状態キャッシュテーブル
        // ==========================================
        "CREATE TABLE IF NOT EXISTS live_score_state (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id INT NOT NULL UNIQUE,
            
            home_score INT DEFAULT 0,
            away_score INT DEFAULT 0,
            
            quarter TINYINT DEFAULT 1,
            minute_in_quarter INT DEFAULT 0,
            possession_team_id INT,
            
            home_team_fouls INT DEFAULT 0,
            away_team_fouls INT DEFAULT 0,
            
            last_event_id BIGINT,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
            FOREIGN KEY (possession_team_id) REFERENCES teams(id),
            FOREIGN KEY (last_event_id) REFERENCES game_events(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // ==========================================
        // 4. 選手別ファウル履歴テーブル
        // ==========================================
        "CREATE TABLE IF NOT EXISTS player_foul_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id INT NOT NULL,
            player_id INT NOT NULL,
            team_id INT NOT NULL,
            
            foul_type ENUM('personal', 'technical', 'flagrant') DEFAULT 'personal',
            foul_number TINYINT,
            fouled_out TINYINT(1) DEFAULT 0,
            
            recorded_by_session_id INT,
            event_id BIGINT,
            
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
            FOREIGN KEY (team_id) REFERENCES teams(id),
            FOREIGN KEY (recorded_by_session_id) REFERENCES live_stat_sessions(id),
            FOREIGN KEY (event_id) REFERENCES game_events(id) ON DELETE SET NULL,
            
            INDEX idx_player_game (player_id, game_id),
            INDEX idx_team_game (team_id, game_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    // テーブル作成
    foreach ($queries as $i => $sql) {
        $pdo->exec($sql);
        $messages[] = "✅ テーブル " . ($i + 1) . " を作成しました";
    }

    // サンプルデータ: ライブスコアキャッシュの初期化
    $games = $pdo->query("SELECT id FROM games LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($games as $game_id) {
        $pdo->prepare("INSERT IGNORE INTO live_score_state (game_id) VALUES (?)")
            ->execute([$game_id]);
    }
    $messages[] = "✅ ライブスコア初期データを作成しました";

} catch (PDOException $e) {
    $messages[] = "❌ エラー: " . $e->getMessage();
    http_response_code(500);
}

// JSON レスポンスを返す
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => empty($errors ?? []),
    'messages' => $messages,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
