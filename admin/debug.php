<?php
// /b2l/admin/debug.php - 設定確認用

// config.phpを読み込まず、直接テスト
echo "<h2>1. config.php の場所確認</h2>";
echo "現在のファイル: " . __FILE__ . "<br>";
echo "現在のディレクトリ: " . __DIR__ . "<br>";

$config_path = __DIR__ . '/config.php';
echo "config.phpのパス: " . $config_path . "<br>";
echo "config.php存在: " . (file_exists($config_path) ? 'YES' : 'NO') . "<br>";

if (file_exists($config_path)) {
    echo "<h2>2. config.php の中身（最初の500文字）</h2>";
    echo "<pre>" . htmlspecialchars(substr(file_get_contents($config_path), 0, 500)) . "</pre>";
}

echo "<h2>3. DB直接接続テスト</h2>";
try {
    $pdo = new PDO(
        'mysql:host=mysql3114.db.sakura.ne.jp;dbname=kasugai-sp_b2l-league;charset=utf8mb4',
        'kasugai-sp_b2l-league',
        'X_MJJk5CfDwv4nf',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "? 直接接続: 成功<br>";
    $pdo = null;
} catch (PDOException $e) {
    echo "? 直接接続: " . $e->getMessage() . "<br>";
}

echo "<h2>4. config.php読み込みテスト</h2>";
require_once $config_path;
echo "DB_USER = " . (defined('DB_USER') ? DB_USER : '未定義') . "<br>";
echo "DB_HOST = " . (defined('DB_HOST') ? DB_HOST : '未定義') . "<br>";
echo "DB_NAME = " . (defined('DB_NAME') ? DB_NAME : '未定義') . "<br>";
