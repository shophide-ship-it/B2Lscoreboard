<?php
require_once 'config.php';

$pdo = getDB();
$messages = [];

try {
    // テーブル作成
    $queries = [
        // チームテーブル
        "CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            short_name VARCHAR(10) NOT NULL,
            division TINYINT NOT NULL DEFAULT 1 COMMENT '1=1部, 2=2部, 3=3部',
            logo_color VARCHAR(7) DEFAULT '#1d428a',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_division (division)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 選手テーブル
        "CREATE TABLE IF NOT EXISTS players (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            number INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            height DECIMAL(4,1) DEFAULT NULL COMMENT '身長(cm)',
            position ENUM('PG','SG','SF','PF','C') NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
            INDEX idx_team (team_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 試合テーブル
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

        // 選手スタッツテーブル
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

        // 順位テーブル（キャッシュ用）
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

    // サンプルチームデータ挿入
    $stmt = $pdo->query("SELECT COUNT(*) FROM teams");
    if ($stmt->fetchColumn() == 0) {
        $teams = [
            // 1部
            ['name' => 'スパークス', 'short_name' => 'SPK', 'division' => 1, 'logo_color' => '#C8102E'],
            ['name' => 'サンダーズ', 'short_name' => 'THD', 'division' => 1, 'logo_color' => '#007AC1'],
            ['name' => 'イーグルス', 'short_name' => 'EGL', 'division' => 1, 'logo_color' => '#1D428A'],
            ['name' => 'ブレイズ', 'short_name' => 'BLZ', 'division' => 1, 'logo_color' => '#E03A3E'],
            ['name' => 'ウォリアーズ', 'short_name' => 'WAR', 'division' => 1, 'logo_color' => '#FFC72C'],
            ['name' => 'パンサーズ', 'short_name' => 'PAN', 'division' => 1, 'logo_color' => '#552583'],
            ['name' => 'ファルコンズ', 'short_name' => 'FAL', 'division' => 1, 'logo_color' => '#006BB6'],
            ['name' => 'ドラゴンズ', 'short_name' => 'DRG', 'division' => 1, 'logo_color' => '#CE1141'],
            // 2部
            ['name' => 'ホーネッツ', 'short_name' => 'HOR', 'division' => 2, 'logo_color' => '#1D1160'],
            ['name' => 'グリズリーズ', 'short_name' => 'GRZ', 'division' => 2, 'logo_color' => '#5D76A9'],
            ['name' => 'ブルズ', 'short_name' => 'BUL', 'division' => 2, 'logo_color' => '#CE1141'],
            ['name' => 'ラプターズ', 'short_name' => 'RAP', 'division' => 2, 'logo_color' => '#CE1141'],
            ['name' => 'ウルブズ', 'short_name' => 'WLV', 'division' => 2, 'logo_color' => '#0C2340'],
            ['name' => 'ホークス', 'short_name' => 'HWK', 'division' => 2, 'logo_color' => '#E03A3E'],
            ['name' => 'ナイツ', 'short_name' => 'KNT', 'division' => 2, 'logo_color' => '#006BB6'],
            ['name' => 'タイガース', 'short_name' => 'TGR', 'division' => 2, 'logo_color' => '#FF6900'],
            // 3部
            ['name' => 'レオパーズ', 'short_name' => 'LEO', 'division' => 3, 'logo_color' => '#98002E'],
            ['name' => 'コブラズ', 'short_name' => 'COB', 'division' => 3, 'logo_color' => '#00471B'],
            ['name' => 'シャークス', 'short_name' => 'SHK', 'division' => 3, 'logo_color' => '#0077C0'],
            ['name' => 'フェニックス', 'short_name' => 'PHX', 'division' => 3, 'logo_color' => '#E56020'],
            ['name' => 'ウィザーズ', 'short_name' => 'WIZ', 'division' => 3, 'logo_color' => '#002B5C'],
            ['name' => 'セルティックス', 'short_name' => 'CEL', 'division' => 3, 'logo_color' => '#007A33'],
            ['name' => 'キングス', 'short_name' => 'KNG', 'division' => 3, 'logo_color' => '#5A2D81'],
            ['name' => 'ロケッツ', 'short_name' => 'RKT', 'division' => 3, 'logo_color' => '#CE1141'],
        ];

        $stmt = $pdo->prepare("INSERT INTO teams (name, short_name, division, logo_color) VALUES (?, ?, ?, ?)");
        foreach ($teams as $team) {
            $stmt->execute([$team['name'], $team['short_name'], $team['division'], $team['logo_color']]);
        }
        $messages[] = ['success', 'サンプルチームデータを挿入しました。'];

        // 各チームにstandingsレコード挿入
        $pdo->exec("INSERT INTO standings (team_id, division, season) SELECT id, division, '2024-25' FROM teams");
        $messages[] = ['success', '順位テーブルを初期化しました。'];
    }

}
catch (PDOException $e) {
    $messages[] = ['error', 'エラー: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>インストール -
        <?= SITE_NAME?>
    </title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0a0a0a;
            color: #fff;
            padding: 40px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .msg {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
        }

        .msg.success {
            background: #1b5e20;
        }

        .msg.error {
            background: #b71c1c;
        }

        a {
            color: #4fc3f7;
            text-decoration: none;
        }

        h1 {
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🏀
            <?= SITE_NAME?> インストール
        </h1>
        <?php foreach ($messages as $msg): ?>
        <div class="msg <?= $msg[0]?>">
            <?= $msg[1]?>
        </div>
        <?php
endforeach; ?>
        <p><a href="index.php">→ サイトトップへ</a></p>
        <p><a href="admin/index.php">→ 管理ページへ</a></p>
    </div>
</body>

</html>