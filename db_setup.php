<?php
/**
 * ライブスタッツシステム - DBセットアップツール
 * 
 * 用途: SQLファイルを読み込んで実行
 * アクセス: http://dev-stats.local/db_setup.php
 */

require_once __DIR__ . '/config.php';

$success = false;
$messages = [];
$errors = [];

try {
    $pdo = getDB();
    
    // SQLファイルを読み込み
    $sqlFile = __DIR__ . '/sql/01_create_live_stats_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception('SQLファイルが見つかりません: ' . $sqlFile);
    }

    $sql = file_get_contents($sqlFile);
    
    // SQL を実行
    // コメントを削除し、複数のステートメントに分割
    $statements = preg_split('/;(?=\s*$)/m', $sql);
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;
        
        // コメント行を削除
        $stmt = preg_replace('/^--.*$/m', '', $stmt);
        $stmt = trim($stmt);
        
        if (empty($stmt)) continue;
        
        try {
            $pdo->exec($stmt);
            $tableName = preg_match('/CREATE TABLE.*?\s+(\w+)\s*\(/i', $stmt, $m) 
                ? $m[1] 
                : 'Unknown';
            $messages[] = "✅ テーブル作成成功: " . $tableName;
        } catch (PDOException $e) {
            // テーブルが既に存在する場合などはスキップ
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $messages[] = "⚠️ テーブルは既に存在します (スキップ)";
            } else {
                throw $e;
            }
        }
    }

    $success = true;
    $messages[] = "✅ DBセットアップ完了！";

} catch (Exception $e) {
    $errors[] = "❌ エラー: " . $e->getMessage();
}

// HTML レスポンス
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB セットアップ</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .message-box {
            margin-bottom: 20px;
            padding: 16px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .message-box.success {
            background: #e8f5e9;
            border-left-color: #4caf50;
            color: #2e7d32;
        }
        .message-box.error {
            background: #ffebee;
            border-left-color: #f44336;
            color: #c62828;
        }
        .message-box.warning {
            background: #fff3e0;
            border-left-color: #ff9800;
            color: #e65100;
        }
        .messages {
            margin-bottom: 20px;
        }
        .status {
            padding: 20px;
            border-radius: 4px;
            font-size: 16px;
            text-align: center;
            font-weight: bold;
        }
        .status.ok {
            background: #c8e6c9;
            color: #1b5e20;
        }
        .status.error {
            background: #ffcdd2;
            color: #b71c1c;
        }
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }
        a, button {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }
        .btn-primary {
            background: #1976d2;
            color: white;
        }
        .btn-primary:hover {
            background: #1565c0;
        }
        .btn-secondary {
            background: #ccc;
            color: #333;
        }
        .btn-secondary:hover {
            background: #999;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #1976d2;
            padding: 16px;
            border-radius: 4px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🗄️ ライブスタッツシステム - DBセットアップ</h1>
    
    <div class="messages">
        <?php if ($success): ?>
            <div class="status ok">✅ セットアップ成功</div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="status error">❌ エラーが発生しました</div>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <?php foreach ($messages as $msg): ?>
                <div class="message-box success">
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $err): ?>
                <div class="message-box error">
                    <?= htmlspecialchars($err) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="info-box">
        <strong>✅ 作成されたテーブル:</strong>
        <ul style="margin-top: 8px; margin-left: 20px;">
            <li><code>live_stat_sessions</code> - セッション管理</li>
            <li><code>game_events</code> - イベント時系列記録</li>
            <li><code>live_score_state</code> - リアルタイムスコア</li>
            <li><code>player_foul_log</code> - ファウル履歴</li>
        </ul>
    </div>

    <div class="button-group">
        <a href="admin/index.php" class="btn-primary">🏠 管理画面へ</a>
        <a href="<?= $_SERVER['REQUEST_URI'] ?>" class="btn-secondary">🔄 再実行</a>
    </div>
</div>
</body>
</html>
