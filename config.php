<?php
// B2L League - Configuration
// Save this file as UTF-8 WITHOUT BOM

// Database
define('DB_HOST', 'mysql3114.db.sakura.ne.jp');
define('DB_NAME', 'kasugai-sp_b2l-league');
define('DB_USER', 'kasugai-sp_b2l-league');
define('DB_PASS', 'X_MJJk5CfDwv4nf');

// Admin
define('ADMIN_USER', 'b2ladmin');
define('ADMIN_PASS', 'B2L2025!admin');

// LINE
define('LINE_CHANNEL_ID', '2009653133');
define('LINE_CHANNEL_SECRET', 'd46f6d27b226634f98f650e218b25fc6');
define('LINE_ACCESS_TOKEN', 'kbZCHXeFaL7WyqEPU/MW45EnWweTNjTDkKkMXlT+Cf2qzyrDkG3v9EG2+lFPY0Xc9uJZznCnMd6ERm/gLZRBy7Oq8M15DP66qRt/B2K1IPKFjZgGb2S9TogAJM/rlNMkNcX0C1i8f2Cqsvi4z6UydQdB04t89/1O/w1cDnyilFU=');

// Base URL
define('BASE_URL', '/b2l');
define('API_BASE', '/b2l/api');

// Player registration deadline
define('PLAYER_REGISTRATION_DEADLINE', '2026-05-01');

// Player registration limits
define('PLAYER_NUMBER_MIN', 0);
define('PLAYER_NUMBER_MAX', 99);
define('PLAYER_MAX_PER_TEAM', 30);

function getDB() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log('B2L DB Error: ' . $e->getMessage());
        die('Database connection error');
    }
}

// Division name mapping
function getDivisionName($div) {
    $names = [
        1 => '1部',
        2 => '2部',
        3 => '3部'
    ];
    return isset($names[$div]) ? $names[$div] : $div . '部';
}

// Position name mapping
function getPositionName($pos) {
    $positions = ['PG', 'SG', 'SF', 'PF', 'C'];
    return in_array($pos, $positions) ? $pos : $pos;
}

function getDeadline() {
    return PLAYER_REGISTRATION_DEADLINE;
}

function isRegistrationOpen() {
    return time() < strtotime(PLAYER_REGISTRATION_DEADLINE);
}

function formatDeadline() {
    $d = new DateTime(PLAYER_REGISTRATION_DEADLINE);
    return $d->format('Y年n月j日');
}
