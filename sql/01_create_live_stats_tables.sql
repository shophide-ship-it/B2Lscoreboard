-- B2L League ライブスタッツシステム - DB テーブル作成スクリプト
-- 実行方法: mysql> SOURCE /path/to/this/file;

-- ==========================================
-- 1. ライブスタッツセッション管理テーブル
-- ==========================================
CREATE TABLE IF NOT EXISTS live_stat_sessions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='試合中のユーザーセッション管理 (複数ユーザーの同時入力対応)';

-- ==========================================
-- 2. 試合イベント時系列記録テーブル
-- ==========================================
CREATE TABLE IF NOT EXISTS game_events (
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
    
    event_data JSON COMMENT '{"points": 2}や{"foul_type": "personal"}等',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='試合イベント時系列記録 (得点, ファウル, 交代等)';

-- ==========================================
-- 3. ライブスコア状態キャッシュテーブル
-- ==========================================
CREATE TABLE IF NOT EXISTS live_score_state (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='リアルタイムスコア状態 (公開表示用キャッシュ)';

-- ==========================================
-- 4. 選手別ファウル履歴テーブル
-- ==========================================
CREATE TABLE IF NOT EXISTS player_foul_log (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='選手別ファウル履歴 (ファウル退場管理)';

-- ==========================================
-- サンプルデータ: ライブスコア初期化
-- ==========================================
-- INSERT IGNORE INTO live_score_state (game_id) 
-- SELECT id FROM games LIMIT 10;
