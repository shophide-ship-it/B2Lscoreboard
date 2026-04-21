function getDB() {
    $host = '$host = '127.0.0.1';
    $db   = 'kasugai-sp_b2l-league';
    $user = 'kasugai-sp_b2l-league';
    $pass = 'B2L_db2025secure';

    try {
        // オプションを追加して接続を強化
        $dsn = "mysql:host=$host;dbname=$db;
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5 // 5秒でタイムアウト
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // 500エラーにならず、エラー内容を強制的に表示して停止する
        echo "<div style='background:#fee;color:#c00;padding:20px;border:2px solid #c00;'>";
        echo "<h2>DB接続テスト失敗</h2>";
        echo "原因: " . htmlspecialchars($e->getMessage());
        echo "</div>";
        exit;
    }
}