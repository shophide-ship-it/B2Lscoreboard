<?php
exit("設定ファイルは読み込まれています"); // これを足す
// config.php の接続設定を以下に差し替え
define('DB_HOST', '210.224.185.153'); // ホスト名の代わりにIPを直接指定
define('DB_NAME', 'kasugai-sp_b2l-league');
define('DB_USER', 'kasugai-sp_b2l-league');
define('DB_PASS', 'X_MJJk5CfDwv4nf');                 // ← ★ここを確認

// 以下は変更不要
define('DB_CHARSET', 'utf8mb4');
define('SITE_NAME', 'B2L LEAGUE');

define('SITE_URL', 'https://kasugai-sp.sakura.ne.jp/b2l');
define('BASE_PATH', '/b2l');
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'b2league2024');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // charsetを一旦外して接続だけを確認
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME; 
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // エラーが出た場合、何が原因か詳細を出す
            exit('DB接続エラー詳細: ' . $e->getMessage());
        }
    }
    return $pdo;
}

function getDivisionName($division) {
    $names = [1 => '1部', 2 => '2部', 3 => '3部'];
    return $names[$division] ?? '';
}

function getPositionName($pos) {
    $positions = [
        'PG' => 'ポイントガード', 'SG' => 'シューティングガード',
        'SF' => 'スモールフォワード', 'PF' => 'パワーフォワード', 'C' => 'センター'
    ];
    return $positions[$pos] ?? $pos;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
