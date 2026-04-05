<?php
/**
 * B2L Phase1 セットアップ
 * - ダミーデータ削除
 * - テーブル構造更新
 * 実行後は必ず削除してください
 */

require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $results = [];
    
    // === 1. ダミーデータ削除 ===
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 関連テーブルも先にクリア
    $pdo->exec("DELETE FROM player_registrations");
    $results[] = "? player_registrations クリア";
    
    $pdo->exec("DELETE FROM team_registrations");
    $results[] = "? team_registrations クリア";
    
    $pdo->exec("DELETE FROM players");
    $results[] = "? players クリア（旧24件削除）";
    
    $pdo->exec("DELETE FROM teams");
    $results[] = "? teams クリア（旧25件削除）";
    
    // AUTO_INCREMENT リセット
    $pdo->exec("ALTER TABLE teams AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE players AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE team_registrations AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE player_registrations AUTO_INCREMENT = 1");
    $results[] = "? AUTO_INCREMENT リセット";
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // === 2. teams テーブルにカラム追加 ===
    // 既存カラムを確認してから追加
    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM teams");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    $additions = [
        'token'         => "VARCHAR(64) UNIQUE DEFAULT NULL",
        'rep_name'      => "VARCHAR(100) DEFAULT NULL",
        'rep_email'     => "VARCHAR(255) DEFAULT NULL",
        'rep_phone'     => "VARCHAR(20) DEFAULT NULL",
        'rep_line_name' => "VARCHAR(100) DEFAULT NULL",
    ];
    
    foreach ($additions as $col => $definition) {
        if (!in_array($col, $columns)) {
            $pdo->exec("ALTER TABLE teams ADD COLUMN {$col} {$definition}");
            $results[] = "? teams.{$col} 追加";
        } else {
            $results[] = "?? teams.{$col} 既存";
        }
    }
    
    // === 3. player_registrations にステータスカラム追加 ===
    $columns2 = [];
    $stmt2 = $pdo->query("SHOW COLUMNS FROM player_registrations");
    while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
        $columns2[] = $row['Field'];
    }
    
    $pr_additions = [
        'status'      => "ENUM('pending','approved','rejected') DEFAULT 'pending'",
        'admin_note'  => "TEXT DEFAULT NULL",
        'team_id'     => "INT DEFAULT NULL",
        'updated_at'  => "DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];
    
    foreach ($pr_additions as $col => $definition) {
        if (!in_array($col, $columns2)) {
            $pdo->exec("ALTER TABLE player_registrations ADD COLUMN {$col} {$definition}");
            $results[] = "? player_registrations.{$col} 追加";
        } else {
            $results[] = "?? player_registrations.{$col} 既存";
        }
    }
    
    // === 4. 最終確認 ===
    $teamCount = $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn();
    $playerCount = $pdo->query("SELECT COUNT(*) FROM players")->fetchColumn();
    $results[] = "---";
    $results[] = "? teams: {$teamCount} 件";
    $results[] = "? players: {$playerCount} 件";
    
    // teams テーブル構造表示
    $results[] = "---";
    $results[] = "? teams テーブル構造:";
    $stmt3 = $pdo->query("SHOW COLUMNS FROM teams");
    while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
        $results[] = "  {$row['Field']} ({$row['Type']}) {$row['Key']}";
    }
    
    $results[] = "---";
    $results[] = "? player_registrations テーブル構造:";
    $stmt4 = $pdo->query("SHOW COLUMNS FROM player_registrations");
    while ($row = $stmt4->fetch(PDO::FETCH_ASSOC)) {
        $results[] = "  {$row['Field']} ({$row['Type']}) {$row['Key']}";
    }
    
    // 出力
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== B2L Phase1 セットアップ結果 ===\n\n";
    foreach ($results as $r) {
        echo $r . "\n";
    }
    echo "\n?? このファイルを削除してください！\n";
    
} catch (PDOException $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "? エラー: " . $e->getMessage() . "\n";
}
?>
