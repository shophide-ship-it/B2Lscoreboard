<?php
// b2l/install_scorer.php
require_once __DIR__ . '/config.php';

try {
    $pdo = getDB();

    // ライブスタッツ用テーブル
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS live_games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id INT NOT NULL,
            status ENUM('ready','q1','q2','q3','q4','ot','finished') DEFAULT 'ready',
            current_quarter TINYINT DEFAULT 1,
            quarter_time VARCHAR(10) DEFAULT '10:00',
            home_score INT DEFAULT 0,
            away_score INT DEFAULT 0,
            home_fouls INT DEFAULT 0,
            away_fouls INT DEFAULT 0,
            home_timeouts INT DEFAULT 3,
            away_timeouts INT DEFAULT 3,
            started_at DATETIME NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // プレイバイプレイ
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS play_by_play (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id INT NOT NULL,
            quarter TINYINT NOT NULL,
            game_time VARCHAR(10) NOT NULL,
            team_id INT NOT NULL,
            player_id INT NULL,
            action_type ENUM(
                'fgm','fga','3pm','3pa','ftm','fta',
                'oreb','dreb','ast','stl','blk',
                'turnover','foul','timeout',
                'sub_in','sub_out'
            ) NOT NULL,
            points INT DEFAULT 0,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
            INDEX idx_game_quarter (game_id, quarter)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ライブ個人スタッツ（リアルタイム集計用）
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS live_player_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id INT NOT NULL,
            player_id INT NOT NULL,
            team_id INT NOT NULL,
            minutes VARCHAR(10) DEFAULT '0:00',
            fgm INT DEFAULT 0, fga INT DEFAULT 0,
            three_pm INT DEFAULT 0, three_pa INT DEFAULT 0,
            ftm INT DEFAULT 0, fta INT DEFAULT 0,
            oreb INT DEFAULT 0, dreb INT DEFAULT 0,
            ast INT DEFAULT 0, stl INT DEFAULT 0,
            blk INT DEFAULT 0, turnovers INT DEFAULT 0,
            fouls INT DEFAULT 0, points INT DEFAULT 0,
            is_oncourt TINYINT(1) DEFAULT 0,
            entered_at DATETIME NULL,
            UNIQUE KEY unique_game_player (game_id, player_id),
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // APIトークン管理
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS api_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            user_name VARCHAR(50) NOT NULL,
            role ENUM('scorer','admin') DEFAULT 'scorer',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // デフォルトのスコアラートークンを作成
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("INSERT IGNORE INTO api_tokens (token, user_name, role) VALUES (?, 'default_scorer', 'scorer')");
    $stmt->execute([$token]);

    echo "<h2>✅ スコアラーアプリ用テーブル作成完了</h2>";
    echo "<p><strong>APIトークン:</strong> <code>{$token}</code></p>";
    echo "<p>このトークンをアプリのログインに使用してください。</p>";
    echo "<p><a href='scorer/'>スコアラーアプリを開く</a></p>";

}
catch (PDOException $e) {
    die("エラー: " . $e->getMessage());
}
?>