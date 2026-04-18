<?php
try {
    $host = 'mysql3114.db.sakura.ne.jp';
    $dbname = 'kasugai-sp_b2l-league';
    $username = 'kasugai-sp_b2l-league';
    $password = 'B2L_db2025secure';

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // クエリの例
    $stmt = $conn->query("SELECT * FROM your_table");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $num_rows = count($results); // 行数を取得
   
    echo "$num_rows 行のデータを取得しました。";
} catch (PDOException $e) {
    echo "接続失敗: " . $e->getMessage();
}
?>
