<?php
// /b2l/admin/teams.php - B2L League Admin Team Management
session_start();
require_once __DIR__ . '/../config.php';

// --- Authentication ---
$ADMIN_USER = 'b2ladmin';
$ADMIN_PASS = 'B2L2025!admin';

if (!isset($_SESSION['b2l_admin_logged_in']) || $_SESSION['b2l_admin_logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        if ($_POST['username'] === $ADMIN_USER && $_POST['password'] === $ADMIN_PASS) {
            $_SESSION['b2l_admin_logged_in'] = true;
        } else {
            $login_error = 'ユーザー名またはパスワードが違います';
        }
    }
    if (!isset($_SESSION['b2l_admin_logged_in']) || $_SESSION['b2l_admin_logged_in'] !== true) {
        ?>
        <!DOCTYPE html>
        <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>B2L 管理者ログイン</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #1a1a2e; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
                .login-box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); width: 350px; }
                .login-box h1 { text-align: center; color: #1a1a2e; margin-bottom: 8px; font-size: 24px; }
                .login-box p { text-align: center; color: #666; margin-bottom: 24px; font-size: 14px; }
                .login-box input { width: 100%; padding: 12px 16px; margin-bottom: 16px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px; }
                .login-box input:focus { border-color: #e67e22; outline: none; }
                .login-box button { width: 100%; padding: 14px; background: #e67e22; color: #fff; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; }
                .login-box button:hover { background: #d35400; }
                .error { background: #ffe0e0; color: #c00; padding: 10px; border-radius: 6px; margin-bottom: 16px; text-align: center; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h1>🏀 B2L League</h1>
                <p>管理者ログイン</p>
                <?php if (isset($login_error)): ?>
                    <div class="error"><?= htmlspecialchars($login_error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="text" name="username" placeholder="ユーザー名" required>
                    <input type="password" name="password" placeholder="パスワード" required>
                    <button type="submit" name="login" value="1">ログイン</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// --- Logout ---
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: teams.php');
    exit;
}

$db = getDB();
$message = '';
$message_type = '';

// --- Handle Team Registration (name only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team'])) {
    $name = trim($_POST['team_name'] ?? '');
    $division = trim($_POST['division'] ?? '1部');
    
    if (empty($name)) {
        $message = 'チーム名を入力してください';
        $message_type = 'error';
    } else {
        // Check duplicate
        $check = $db->prepare("SELECT id FROM teams WHERE name = ?");
        $check->execute([$name]);
        if ($check->fetch()) {
            $message = '同じチーム名が既に登録されています';
            $message_type = 'error';
        } else {
            $token = bin2hex(random_bytes(32));
            $stmt = $db->prepare("INSERT INTO teams (name, short_name, division, token, primary_color, secondary_color) VALUES (?, '', ?, ?, '#000000', '#FFFFFF')");
            $stmt->execute([$name, $division, $token]);
            $message = "「{$name}」を登録しました！";
            $message_type = 'success';
        }
    }
}

// --- Handle Delete ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_team'])) {
    $team_id = (int)$_POST['team_id'];
    $stmt = $db->prepare("DELETE FROM teams WHERE id = ?");
    $stmt->execute([$team_id]);
    $message = 'チームを削除しました';
    $message_type = 'success';
}

// --- Handle Token Regeneration ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerate_token'])) {
    $team_id = (int)$_POST['team_id'];
    $new_token = bin2hex(random_bytes(32));
    $stmt = $db->prepare("UPDATE teams SET token = ? WHERE id = ?");
    $stmt->execute([$new_token, $team_id]);
    $message = 'トークンを再発行しました';
    $message_type = 'success';
}

// --- Fetch Teams ---
$teams = $db->query("SELECT * FROM teams ORDER BY division, name")->fetchAll(PDO::FETCH_ASSOC);

// Group by division
$divisions = [];
foreach ($teams as $t) {
    $divisions[$t['division']][] = $t;
}

$base_url = 'https://kasugai-sp.sakura.ne.jp/b2l/register/players.php?token=';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2L チーム管理</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Hiragino Sans', 'Noto Sans JP', sans-serif; background: #f0f2f5; color: #333; }
        
        .header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 20px; }
        .header nav a { color: #ffd700; text-decoration: none; margin-left: 20px; font-size: 14px; }
        .header nav a:hover { text-decoration: underline; }
        .logout-btn { color: #ff6b6b !important; }
        
        .container { max-width: 1000px; margin: 24px auto; padding: 0 16px; }
        
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); padding: 28px; margin-bottom: 24px; }
        .card h2 { font-size: 20px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #f0f0f0; }
        
        .msg { padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .form-row { display: flex; gap: 16px; align-items: end; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 6px; }
        .form-group input, .form-group select { width: 100%; padding: 10px 14px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 15px; }
        .form-group input:focus, .form-group select:focus { border-color: #e67e22; outline: none; }
        
        .btn { padding: 10px 24px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #e67e22; color: #fff; }
        .btn-primary:hover { background: #d35400; }
        .btn-danger { background: #e74c3c; color: #fff; padding: 6px 12px; font-size: 13px; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #6c757d; color: #fff; padding: 6px 12px; font-size: 13px; }
        .btn-secondary:hover { background: #5a6268; }
        
        .division-header { background: #fff3e0; padding: 10px 16px; border-radius: 8px; margin: 20px 0 12px; font-weight: bold; color: #e67e22; font-size: 16px; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px 12px; color: #666; font-size: 13px; border-bottom: 2px solid #eee; }
        td { padding: 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
        
        .team-name { font-weight: 700; font-size: 16px; }
        
        .token-url { font-size: 12px; word-break: break-all; color: #2196F3; max-width: 300px; display: block; }
        .token-url:hover { text-decoration: underline; }
        
        .actions { display: flex; gap: 6px; }
        
        .copy-btn { background: #2196F3; color: #fff; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .copy-btn:hover { background: #1976D2; }
        .copy-btn.copied { background: #4CAF50; }
        
        @media (max-width: 768px) {
            .form-row { flex-direction: column; }
            .form-group { min-width: 100%; }
            table { font-size: 13px; }
            td, th { padding: 8px 6px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏀 B2L League 管理</h1>
        <nav>
            <a href="teams.php">チーム管理</a>
            <a href="player_approvals.php">選手承認</a>
            <a href="teams.php?logout=1" class="logout-btn">ログアウト</a>
        </nav>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="msg <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <!-- Team Registration Form -->
        <div class="card">
            <h2>➕ チーム新規登録</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group" style="flex:2;">
                        <label>チーム名 *</label>
                        <input type="text" name="team_name" placeholder="例: CLEVER" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>部門</label>
                        <select name="division">
                            <option value="1部">1部</option>
                            <option value="2部">2部</option>
                            <option value="3部">3部</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex:0 0 auto;">
                        <button type="submit" name="add_team" value="1" class="btn btn-primary">トークン発行</button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Team List -->
        <div class="card">
            <h2>📋 登録チーム一覧</h2>
            
            <?php if (empty($divisions)): ?>
                <p style="color:#999; text-align:center; padding:40px;">まだチームが登録されていません</p>
            <?php else: ?>
                <?php foreach ($divisions as $div => $div_teams): ?>
                    <div class="division-header">🏀 <?= htmlspecialchars($div) ?>（<?= count($div_teams) ?>チーム）</div>
                    <table>
                        <tr>
                            <th>チーム名</th>
                            <th>代表者</th>
                            <th>選手登録URL</th>
                            <th>操作</th>
                        </tr>
                        <?php foreach ($div_teams as $team): ?>
                            <tr>
                                <td>
                                    <div class="team-name"><?= htmlspecialchars($team['name']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($team['rep_name'] ?: '未登録') ?></td>
                                <td>
                                    <?php $url = $base_url . $team['token']; ?>
                                    <a href="<?= $url ?>" target="_blank" class="token-url" id="url-<?= $team['id'] ?>"><?= htmlspecialchars($url) ?></a>
                                    <button class="copy-btn" onclick="copyURL(<?= $team['id'] ?>)">📋コピー</button>
                                </td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" onsubmit="return confirm('トークンを再発行しますか？\n既存のURLは無効になります')">
                                            <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                                            <button type="submit" name="regenerate_token" value="1" class="btn-secondary">🔄再発行</button>
                                        </form>
                                        <form method="POST" onsubmit="return confirm('「<?= htmlspecialchars($team['name']) ?>」を削除しますか？')">
                                            <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                                            <button type="submit" name="delete_team" value="1" class="btn-danger">🗑️削除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    function copyURL(teamId) {
        const url = document.getElementById('url-' + teamId).textContent;
        navigator.clipboard.writeText(url).then(() => {
            const btn = event.target;
            btn.textContent = '✅コピー済';
            btn.classList.add('copied');
            setTimeout(() => {
                btn.textContent = '📋コピー';
                btn.classList.remove('copied');
            }, 2000);
        });
    }
    </script>
</body>
</html>
