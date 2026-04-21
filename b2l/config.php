<?php
// データベース設定
define('DB_HOST', 'mysql80.db.sakura.ne.jp');
define('DB_NAME', 'kasugai-sp_b2l-league');
define('DB_USER', 'kasugai-sp_b2l-league');
define('DB_PASS', 'B2L_db2025secure');
define('DB_CHARSET', 'utf8mb4');

// サイト設定
define('SITE_NAME', 'B2L LEAGUE');
define('SITE_URL', 'https://kasugai-sp.sakura.ne.jp/b2l');
define('BASE_PATH', '/b2l');
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'b2league2024');

// DB接続
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die('DB接続エラー: ' . $e->getMessage());
        }
    }
    return $pdo;
}

// 部門名取得
function getDivisionName($division) {
    $names = [1 => '1部', 2 => '2部', 3 => '3部'];
    return $names[$division] ?? '';
}

// ポジション名
function getPositionName($pos) {
    $positions = [
        'PG' => 'ポイントガード',
        'SG' => 'シューティングガード',
        'SF' => 'スモールフォワード',
        'PF' => 'パワーフォワード',
        'C' => 'センター'
    ];
    return $positions[$pos] ?? $pos;
}

// ベースURL取得
function url($path = '') {
    return BASE_PATH . '/' . ltrim($path, '/');
}

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
