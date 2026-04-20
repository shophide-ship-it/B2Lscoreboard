<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action']??'';
    if ($act==='add') {
        $pdo->prepare("INSERT INTO teams (name,short_name,division,logo_color) VALUES (?,?,?,?)")->execute([$_POST['name'],strtoupper($_POST['short_name']),(int)$_POST['division'],$_POST['logo_color']]);
        $tid = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO standings (team_id,division,season) VALUES (?,?,'2024-25')")->execute([$tid,(int)$_POST['division']]);
        $msg = 'チームを追加しました。';
    }
    if ($act==='edit') {
        $pdo->prepare("UPDATE teams SET name=?,short_name=?,division=?,logo_color=? WHERE id=?")->execute([$_POST['name'],strtoupper($_POST['short_name']),(int)$_POST['division'],$_POST['logo_color'],(int)$_POST['id']]);
        $pdo->prepare("UPDATE standings SET division=? WHERE team_id=?")->execute([(int)$_POST['division'],(int)$_POST['id']]);
        $msg = 'チームを更新しました。';
    }
    if ($act==='delete') {
        $pdo->prepare("DELETE FROM teams WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = 'チームを削除しました。';
    }
}
$teams = $pdo->query("SELECT * FROM teams ORDER BY division, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>チーム管理 - <?= SITE_NAME ?></title><link rel="stylesheet" href="<?= url('css/style.css') ?>"></head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-logo"><h2>B2L <span>LEAGUE</span></h2><p>管理パネル</p></div>
        <nav class="admin-nav">
            <a href="<?= url('admin/index.php') ?>"><span class="icon">📊</span> ダッシュボード</a>
            <a href="<?= url('admin/teams.php') ?>" class="active"><span class="icon">🏀</span> チーム管理</a>
            <a href="<?= url('admin/players.php') ?>"><span class="icon">👤</span> 選手管理</a>
            <a href="<?= url('admin/games.php') ?>"><span class="icon">📅</span> 試合管理</a>
            <a href="<?= url('admin/stats.php') ?>"><span class="icon">📈</span> スタッツ入力</a>
            <a href="<?= url('index.php') ?>"><span class="icon">🌐</span> サイト表示</a>
            <a href="<?= url('admin/index.php?action=logout') ?>"><span class="icon">🚪</span> ログアウト</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="admin-header"><h1>チーム管理</h1></div>
        <?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
        <div class="card mb-3"><div class="card-header"><h3>チーム追加</h3></div><div class="card-body">
            <form method="POST"><input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group"><label>チーム名</label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>略称</label><input type="text" name="short_name" class="form-control" required maxlength="5"></div>
                    <div class="form-group"><label>部門</label><select name="division" class="form-control"><option value="1">1部</option><option value="2">2部</option><option value="3">3部</option></select></div>
                    <div class="form-group"><label>カラー</label><input type="color" name="logo_color" class="form-control" value="#1d428a" style="height:44px;padding:4px"></div>
                </div>
                <button type="submit" class="btn btn-primary">追加</button>
            </form>
        </div></div>

        <div class="card"><div class="card-header"><h3>チーム一覧 (<?= count($teams) ?>)</h3></div><div class="card-body" style="padding:0">
            <div class="standings-table-wrapper"><table class="standings-table">
                <thead><tr><th>ID</th><th>チーム名</th><th>略称</th><th class="text-center">部門</th><th class="text-center">カラー</th><th class="text-center">操作</th></tr></thead>
                <tbody>
                <?php foreach ($teams as $t): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td><div class="team-cell"><div class="team-mini-logo" style="background:<?= $t['logo_color'] ?>"><?= $t['short_name'] ?></div><?= htmlspecialchars($t['name']) ?></div></td>
                    <td><?= $t['short_name'] ?></td>
                    <td class="text-center"><?= getDivisionName($t['division']) ?></td>
                    <td class="text-center"><span style="display:inline-block;width:20px;height:20px;border-radius:50%;background:<?= $t['logo_color'] ?>;vertical-align:middle"></span></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline" onclick="editTeam(<?= $t['id'] ?>,'<?= htmlspecialchars($t['name'],ENT_QUOTES) ?>','<?= $t['short_name'] ?>',<?= $t['division'] ?>,'<?= $t['logo_color'] ?>')">編集</button>
                        <form method="POST" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button type="submit" class="btn btn-sm btn-danger btn-delete">削除</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div></div>

        <div id="editModal" class="modal-overlay"><div class="modal"><div class="modal-header"><h3>チーム編集</h3><button class="modal-close">&times;</button></div>
        <form method="POST"><div class="modal-body">
            <input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="e-id">
            <div class="form-group"><label>チーム名</label><input type="text" name="name" id="e-name" class="form-control" required></div>
            <div class="form-row">
                <div class="form-group"><label>略称</label><input type="text" name="short_name" id="e-short" class="form-control" required maxlength="5"></div>
                <div class="form-group"><label>部門</label><select name="division" id="e-div" class="form-control"><option value="1">1部</option><option value="2">2部</option><option value="3">3部</option></select></div>
                <div class="form-group"><label>カラー</label><input type="color" name="logo_color" id="e-color" class="form-control" style="height:44px;padding:4px"></div>
            </div>
        </div><div class="modal-footer"><button type="button" class="btn btn-outline modal-close">キャンセル</button><button type="submit" class="btn btn-primary">更新</button></div></form></div></div>
    </main>
</div>
<script src="<?= url('js/app.js') ?>"></script>
<script>
function editTeam(id,name,short,div,color){
    document.getElementById('e-id').value=id;
    document.getElementById('e-name').value=name;
    document.getElementById('e-short').value=short;
    document.getElementById('e-div').value=div;
    document.getElementById('e-color').value=color;
    document.getElementById('editModal').classList.add('active');
}
</script>
</body>
</html>

