<?php
include 'config.php';

// テーブル作成スクリプト
$queries = [
    "CREATE TABLE IF NOT EXISTS teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        division VARCHAR(10) NOT NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS players (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT,
        number INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        height FLOAT,
        position VARCHAR(50),
        FOREIGN KEY(team_id) REFERENCES teams(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date_time DATETIME NOT NULL,
        team1 INT,
        team2 INT,
        FOREIGN KEY(team1) REFERENCES teams(id),
        FOREIGN KEY(team2) REFERENCES teams(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_id INT,
        PTS INT,
        REB INT,
        AST INT,
        STL INT,
        BLK INT,
        FOREIGN KEY(player_id) REFERENCES players(id)
    )"
];

foreach ($queries as $query) {
    $pdo->exec($query);
}

echo "Database setup complete.";
?>
