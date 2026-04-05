<?php
// /b2l/register/debug2.php - トークンとDB照合チェック
require_once __DIR__ . '/../config.php';

echo "<h2>DB Token Debug</h2>";

try {
    $pdo = getDB();
    echo "DB接続: OK<br><br>";
    
    // 全チーム一覧
    $stmt = $pdo->query("SELECT id, name, division, token, LEFT(token, 30) as token_prefix, LENGTH(token) as token_len FROM teams ORDER BY id DESC LIMIT 10");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>登録済みチーム一覧</h3>";
    if (empty($teams)) {
        echo "<p style='color:red;'>チームが1件も登録されていません！</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Division</th><th>Token (先頭30文字)</th><th>Token長</th></tr>";
        foreach ($teams as $t) {
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>{$t['name']}</td>";
            echo "<td>{$t['division']}</td>";
            echo "<td><code>{$t['token_prefix']}...</code></td>";
            echo "<td>{$t['token_len']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // URLのトークンと比較
    $url_token = $_GET['token'] ?? '(なし)';
    echo "<br><h3>URLのトークン</h3>";
    echo "値: <code>" . htmlspecialchars($url_token) . "</code><br>";
    echo "長さ: " . strlen($url_token) . "<br><br>";
    
    if ($url_token !== '(なし)') {
        // 完全一致検索
        $stmt = $pdo->prepare("SELECT id, name FROM teams WHERE token = ?");
        $stmt->execute([$url_token]);
        $found = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($found) {
            echo "<p style='color:green;'>? 完全一致: チーム「{$found['name']}」(ID: {$found['id']})</p>";
        } else {
            echo "<p style='color:red;'>? 完全一致なし</p>";
            
            // LIKE検索で部分一致を試す
            $prefix = substr($url_token, 0, 20);
            $stmt = $pdo->prepare("SELECT id, name, token FROM teams WHERE token LIKE ?");
            $stmt->execute([$prefix . '%']);
            $partial = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($partial) {
                echo "<p style='color:orange;'>部分一致あり:</p>";
                foreach ($partial as $p) {
                    echo "ID: {$p['id']}, Name: {$p['name']}<br>";
                    echo "DB token: <code>{$p['token']}</code><br>";
                    echo "URL token: <code>{$url_token}</code><br>";
                    // バイト比較
                    echo "DB長: " . strlen($p['token']) . " / URL長: " . strlen($url_token) . "<br>";
                }
            } else {
                echo "<p style='color:red;'>部分一致もなし。チームが未登録か、トークンが異なります。</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage();
}
?>
