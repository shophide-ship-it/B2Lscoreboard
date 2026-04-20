<?php
// index.php

session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: auth.php');
    exit();
}

// 管理画面の内容

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
</head>
<body>
    <form method="POST" action="auth.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        
        <input type="submit" value="Login">
    </form>
    <?php if (isset($error)) { echo "<p>$error</p>"; } ?>
</body>
</html>

<?php
require_once __DIR__ . '/auth.php';
handleLogout();
$loginError = handleLogin();

if (isLoggedIn()):
    $pdo = getDB();
    $tc = $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn();
    $pc = $pdo->query("SELECT COUNT(*) FROM players")->fetchColumn();
    $gc = $pdo->query("SELECT COUNT(*) FROM games")->fetchColumn();
    $sc = $pdo->query("SELECT COUNT(*) FROM player_stats")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>管理画面 - <?= SITE_NAME ?></title><link rel="stylesheet" href="<?= url('css/style.css') ?>"></head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-logo"><h2>B2L <span>LEAGUE</span></h2><p>管理パネル</p></div>
        <nav class="admin-nav">
            <a href="<?= url('admin/index.php') ?>" class="active"><span class="icon">📊</span> ダッシュボード</a>
            <a href="<?= url('admin/teams.php') ?>"><span class="icon">🏀</span> チーム管理</a>
            <a href="<?= url('admin/players.php') ?>"><span class="icon">👤</span> 選手管理</a>
            <a href="<?= url('admin/games.php') ?>"><span class="icon">📅</span> 試合管理</a>
            <a href="<?= url('admin/stats.php') ?>"><span class="icon">📈</span> スタッツ入力</a>
            <a href="<?= url('index.php') ?>"><span class="icon">🌐</span> サイト表示</a>
            <a href="<?= url('admin/index.php?action=logout') ?>"><span class="icon">🚪</span> ログアウト</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="admin-header"><h1>ダッシュボード</h1></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:30px">
            <div class="card" style="padding:24px;text-align:center"><div style="font-size:36px;font-weight:900;color:var(--accent-blue)"><?= $tc ?></div><div style="font-size:13px;color:var(--text-muted);margin-top:8px">チーム</div></div>
            <div class="card" style="padding:24px;text-align:center"><div style="font-size:36px;font-weight:900;color:var(--accent-green)"><?= $pc ?></div><div style="font-size:13px;color:var(--text-muted);margin-top:8px">選手</div></div>
            <div class="card" style="padding:24px;text-align:center"><div style="font-size:36px;font-weight:900;color:var(--accent-orange)"><?= $gc ?></div><div style="font-size:13px;color:var(--text-muted);margin-top:8px">試合</div></div>
            <div class="card" style="padding:24px;text-align:center"><div style="font-size:36px;font-weight:900;color:var(--accent-red)"><?= $sc ?></div><div style="font-size:13px;color:var(--text-muted);margin-top:8px">スタッツ</div></div>
        </div>
        <div class="card"><div class="card-header"><h3>クイックリンク</h3></div><div class="card-body"><div style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="<?= url('admin/teams.php') ?>" class="btn btn-primary">チーム管理</a>
            <a href="<?= url('admin/players.php') ?>" class="btn btn-primary">選手管理</a>
            <a href="<?= url('admin/games.php') ?>" class="btn btn-primary">試合管理</a>
            <a href="<?= url('admin/stats.php') ?>" class="btn btn-primary">スタッツ入力</a>
            <a href="<?= url('install.php') ?>" class="btn btn-outline">DB初期化</a>
        </div></div></div>
    </main>
</div>
<script src="<?= url('js/app.js') ?>"></script>
</body>
</html>
<?php else: ?>
<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>ログイン - <?= SITE_NAME ?></title><link rel="stylesheet" href="<?= url('css/style.css') ?>">
<style>.login-wrapper{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0d1b3e,#0a0a0a,#2d0a0a)}.login-box{background:var(--dark-card);border:1px solid var(--dark-border);border-radius:var(--radius-lg);padding:40px;width:100%;max-width:400px;text-align:center}.login-box h1{font-size:24px;font-weight:800;margin-bottom:8px}.login-box p{color:var(--text-muted);font-size:14px;margin-bottom:30px}</style>
</head>
<body>
<div class="login-wrapper"><div class="login-box">
    <div style="font-size:48px;margin-bottom:16px">🏀</div>
    <h1>B2L <span style="color:var(--nba-red)">LEAGUE</span></h1>
    <p>管理パネルログイン</p>
    <?php if ($loginError): ?><div class="alert alert-error"><?= $loginError ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="action" value="login">
        <div class="form-group"><label>ユーザー名</label><input type="text" name="username" class="form-control" required></div>
        <div class="form-group"><label>パスワード</label><input type="password" name="password" class="form-control" required></div>
        <button type="submit" class="btn btn-primary btn-lg" style="width:100%">ログイン</button>
    </form>
    <div style="margin-top:20px"><a href="<?= url('index.php') ?>" style="font-size:13px;color:var(--text-muted)">← サイトトップへ</a></div>
</div></div>
</body>
</html>
<?php endif; ?>

