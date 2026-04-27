<?php
require_once __DIR__ . '/config.php';

$pdo = getDB();
$messages = [];

try {
    $queries = [
        "CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            short_name VARCHAR(10) NOT NULL,
            division TINYINT NOT NULL DEFAULT 1,
            logo_color VARCHAR(7) DEFAULT '#1d428a',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_division (division)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS players (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            number INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            height DECIMAL(4,1) DEFAULT NULL,
            position ENUM('PG','SG','SF','PF','C') NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            INDEX idx_team (team_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            division TINYINT NOT NULL,
            home_team_id INT NOT NULL,
            away_team_id INT NOT NULL,
            home_score INT DEFAULT NULL,
            away_score INT DEFAULT NULL,
            game_date DATE NOT NULL,
            game_time TIME DEFAULT NULL,
            venue VARCHAR(200) DEFAULT NULL,
            status ENUM('scheduled','live','finished') DEFAULT 'scheduled',
            season VARCHAR(20) DEFAULT '2024-25',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (home_team_id) REFERENCES teams(id),
            FOREIGN KEY (away_team_id) REFERENCES teams(id),
            INDEX idx_date (game_date),
            INDEX idx_division (division),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS player_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_id INT NOT NULL,
            player_id INT NOT NULL,
            team_id INT NOT NULL,
            minutes_played INT DEFAULT 0,
            pts INT DEFAULT 0,
            reb INT DEFAULT 0,
            ast INT DEFAULT 0,
            stl INT DEFAULT 0,
            blk INT DEFAULT 0,
            fgm INT DEFAULT 0,
            fga INT DEFAULT 0,
            three_pm INT DEFAULT 0,
            three_pa INT DEFAULT 0,
            ftm INT DEFAULT 0,
            fta INT DEFAULT 0,
            oreb INT DEFAULT 0,
            dreb INT DEFAULT 0,
            tov INT DEFAULT 0,
            pf INT DEFAULT 0,
            plus_minus INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
            FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
            FOREIGN KEY (team_id) REFERENCES teams(id),
            UNIQUE KEY unique_game_player (game_id, player_id),
            INDEX idx_player (player_id),
            INDEX idx_game (game_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS standings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            division TINYINT NOT NULL,
            season VARCHAR(20) DEFAULT '2024-25',
            wins INT DEFAULT 0,
            losses INT DEFAULT 0,
            win_pct DECIMAL(4,3) DEFAULT 0.000,
            points_for INT DEFAULT 0,
            points_against INT DEFAULT 0,
            streak VARCHAR(10) DEFAULT '-',
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            UNIQUE KEY unique_team_season (team_id, season),
            INDEX idx_division (division)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($queries as $sql) {
        $pdo->exec($sql);
    }
    $messages[] = ['success', 'テーブルの作成が完了しました。'];

    $stmt = $pdo->query("SELECT COUNT(*) FROM teams");
    if ($stmt->fetchColumn() == 0) {
        $teams = [
            ['スパークス', 'SPK', 1, '#C8102E'],
            ['サンダーズ', 'THD', 1, '#007AC1'],
            ['イーグルス', 'EGL', 1, '#1D428A'],
            ['ブレイズ', 'BLZ', 1, '#E03A3E'],
            ['ウォリアーズ', 'WAR', 1, '#FFC72C'],
            ['パンサーズ', 'PAN', 1, '#552583'],
            ['ファルコンズ', 'FAL', 1, '#006BB6'],
            ['ドラゴンズ', 'DRG', 1, '#CE1141'],
            ['ホーネッツ', 'HOR', 2, '#1D1160'],
            ['グリズリーズ', 'GRZ', 2, '#5D76A9'],
            ['ブルズ', 'BUL', 2, '#CE1141'],
            ['ラプターズ', 'RAP', 2, '#CE1141'],
            ['ウルブズ', 'WLV', 2, '#0C2340'],
            ['ホークス', 'HWK', 2, '#E03A3E'],
            ['ナイツ', 'KNT', 2, '#006BB6'],
            ['タイガース', 'TGR', 2, '#FF6900'],
            ['レオパーズ', 'LEO', 3, '#98002E'],
            ['コブラズ', 'COB', 3, '#00471B'],
            ['シャークス', 'SHK', 3, '#0077C0'],
            ['フェニックス', 'PHX', 3, '#E56020'],
            ['ウィザーズ', 'WIZ', 3, '#002B5C'],
            ['セルティックス', 'CEL', 3, '#007A33'],
            ['キングス', 'KNG', 3, '#5A2D81'],
            ['ロケッツ', 'RKT', 3, '#CE1141'],
        ];

        $stmt = $pdo->prepare("INSERT INTO teams (name, short_name, division, logo_color) VALUES (?, ?, ?, ?)");
        foreach ($teams as $t) {
            $stmt->execute($t);
        }
        $messages[] = ['success', 'サンプルチーム24チームを挿入しました。'];

        $pdo->exec("INSERT INTO standings (team_id, division, season) SELECT id, division, '2024-25' FROM teams");
        $messages[] = ['success', '順位テーブルを初期化しました。'];
    } else {
        $messages[] = ['info', 'チームデータは既に存在します。スキップしました。'];
    }

} catch (PDOException $e) {
    $messages[] = ['error', 'エラー: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>インストール - <?= SITE_NAME ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #0a0a0a; color: #fff; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; }
        .msg { padding: 15px; margin: 10px 0; border-radius: 8px; }
        .msg.success { background: #1b5e20; }
        .msg.error { background: #b71c1c; }
        .msg.info { background: #0d47a1; }
        a { color: #4fc3f7; text-decoration: none; }
        h1 { color: #fff; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🏀 <?= SITE_NAME ?> インストール</h1>
    <?php foreach ($messages as $msg): ?>
        <div class="msg <?= $msg[0] ?>"><?= $msg[1] ?></div>
    <?php endforeach; ?>
    <p style="margin-top:20px;"><a href="<?= url('index.php') ?>">→ サイトトップへ</a></p>
    <p><a href="<?= url('admin/index.php') ?>">→ 管理ページへ</a></p>
</div>
</body>
</html>
