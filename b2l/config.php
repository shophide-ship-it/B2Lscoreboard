function getDB() {
    $host = '127.0.0.1'; // ← '$host = ' が重なっていたのを修正
    $db   = 'kasugai-sp_b2l-league';
    $user = 'kasugai-sp_b2l-league';
    $pass = 'B2L_db2025secure';

    try {
        // DSNの文字列を正しく閉じ、オプションを設定
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4"; 
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // エラー内容を表示
        echo "<div style='color:red; background:#fff; padding:20px; border:2px solid red;'>";
        echo "<h3>DB接続テスト失敗</h3>";
        echo "原因: " . htmlspecialchars($e->getMessage());
        echo "</div>";
        exit;
    }
}