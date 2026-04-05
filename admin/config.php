<?php
// /b2l/admin/config.php

// DB設定
define('DB_HOST', 'mysql3114.db.sakura.ne.jp');
define('DB_NAME', 'kasugai-sp_b2l-league');
define('DB_USER', 'kasugai-sp_b2l-league');
define('DB_PASS', 'X_MJJk5CfDwv4nf');

// 管理者ログイン
define('ADMIN_USER', 'b2ladmin');
define('ADMIN_PASS', 'B2L2025!admin');

// LINE設定
define('LINE_CHANNEL_ID', '2009653133');
define('LINE_CHANNEL_SECRET', 'd46f6d27b226634f98f650e218b25fc6');

// アプリ設定
define('APP_NAME', 'B2L LEAGUE');
define('BASE_URL', 'https://kasugai-sp.sakura.ne.jp/b2l');
define('API_BASE', '/b2l/api');

function getDB() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        return $pdo;
    } catch (PDOException $e) {
        die('DB接続エラー: ' . $e->getMessage());
    }
}

session_start();

