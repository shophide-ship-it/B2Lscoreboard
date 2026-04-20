<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action']??'';
    if ($act==='add') {
        $h = $_POST['height']!==''?(float)$_POST['height']:null;
        $pdo->prepare("INSERT INTO players (team_id,number,name,height,position) VALUES (?,?,?,?,?)")->execute([(int)$_POST['team_id'],(int)$_POST['number'],$_POST['name'],$h,$_POST['position']]);
        $msg = '選手を追加しました。';
    }
    if ($act==='edit') {
        $h = $_POST['height']!==''?(float)$_POST['height']:null;
        $pdo->prepare("UPDATE players SET team_id=?,number=?,name=?,height=?,position=? WHERE id=?")->execute([(int)$_POST['team_id'],(int)$_POST['number'],$_POST['name'],$h,$_POST['position'],(int)$_POST['id']]);
        $msg = '選手を更新しました。';
    }
    if ($act==='delete') {
        $pdo->prepare("DELETE FROM players WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = '選手を削除しました。';
    }
}

$tf = isset($_GET['team_id'])?(int)$_GET['team_id']:0;
$sql = "SELECT p.*, t.name as team_name, t.short_name, t.logo_color, t.division FROM players p JOIN teams t ON p.team_id=t.id";
if ($tf>0) $sql .= " WHERE p.team_id=$tf";
$sql .= " ORDER BY t.division, t.name, p.number";
$players = $pdo->query($sql)->fetchAll();
$teams = $pdo->query("SELECT * FROM teams ORDER BY division, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>選手管理 - <?= SITE_NAME ?></title><link rel="stylesheet" href="<?= url('css/style.css') ?>"></head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-logo"><h2>B2L <span>LEAGUE</span></h2><p>管理パネル</p></div>
        <nav class="admin-nav">
            <a href="<?= url('admin/index.php') ?>"><span class="icon">📊</span> ダッシュボード</a>
            <a href="<?= url('admin/teams.php') ?>"><span class="icon">🏀</span> チーム管理</a>
            <a href="<?= url('admin/players.php') ?>" class="active"><span class="icon">👤</span> 選手管理</a>
            <a href="<?= url('admin/games.php') ?>"><span class="icon">📅</span> 試合管理</a>
            <a href="<?= url('admin/stats.php') ?>"><span class="icon">📈</span> スタッツ入力</a>
            <a href="<?= url('index.php') ?>"><span class="icon">🌐</span> サイト表示</a>
            <a href="<?= url('admin/index.php?action=logout') ?>"><span class="icon">🚪</span> ログアウト</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="admin-header"><h1>選手管理</h1></div>
        <?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>

        <div class="card mb-3"><div class="card-header"><h3>選手追加</h3></div><div class="card-body">
            <form method="POST"><input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group"><label>チーム</label><select name="team_id" class="form-control" required><option value="">選択...</option><?php foreach($teams as $t): ?><option value="<?= $t['id'] ?>"><?= getDivisionName($t['division']) ?> - <?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>背番号</label><input type="number" name="number" class="form-control" required min="0" max="99"></div>
                    <div class="form-group"><label>名前</label><input type="text" name="name" class="form-control" required></div>
                    <div class="form-group"><label>身長(cm)</label><input type="number" name="height" class="form-control" step="0.1"></div>
                    <div class="form-group"><label>POS</label><select name="position" class="form-control"><option value="PG">PG</option><option value="SG">SG</option><option value="SF">SF</option><option value="PF">PF</option><option value="C">C</option></select></div>
                </div>
                <button type="submit" class="btn btn-primary">追加</button>
            </form>
        </div></div>

        <div class="mb-2"><form method="GET" style="display:flex;gap:12px;align-items:end"><div class="form-group" style="margin:0"><label>チームで絞り込み</label><select name="team_id" class="form-control" onchange="this.form.submit()"><option value="0">全チーム</option><?php foreach($teams as $t): ?><option value="<?= $t['id'] ?>" <?= $tf==$t['id']?'selected':'' ?>><?= getDivisionName($t['division']) ?> - <?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div></form></div>

        <div class="card"><div class="card-header"><h3>選手一覧 (<?= count($players) ?>)</h3></div><div class="card-body" style="padding:0">
            <div class="stats-table-wrapper"><table class="stats-table">
                <thead><tr><th style="text-align:left">#</th><th style="text-align:left">名前</th><th style="text-align:left">チーム</th><th>POS</th><th>身長</th><th>操作</th></tr></thead>
                <tbody>
                <?php foreach ($players as $p): ?>
                <tr>
                    <td style="text-align:left;font-weight:600;color:var(--text-muted)"><?= $p['number'] ?></td>
                    <td style="text-align:left;font-weight:600"><?= htmlspecialchars($p['name']) ?></td>
                    <td style="text-align:left"><div class="team-cell"><div class="team-mini-logo" style="background:<?= $p['logo_color'] ?>;width:28px;height:28px;font-size:8px"><?= $p['short_name'] ?></div><?= htmlspecialchars($p['team_name']) ?></div></td>
                    <td><?= $p['position'] ?></td><td><?= $p['height']?$p['height'].'cm':'-' ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick="editP(<?= $p['id'] ?>,<?= $p['team_id'] ?>,<?= $p['number'] ?>,'<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>','<?= $p['height'] ?>','<?= $p['position'] ?>')">編集</button>
                        <form method="POST" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button type="submit" class="btn btn-sm btn-danger btn-delete">削除</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div></div>

        <div id="epModal" class="modal-overlay"><div class="modal"><div class="modal-header"><h3>選手編集</h3><button class="modal-close">&times;</button></div>
        <form method="POST"><div class="modal-body">
            <input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="ep-id">
            <div class="form-row">
                <div class="form-group"><label>チーム</label><select name="team_id" id="ep-team" class="form-control"><?php foreach($teams as $t): ?><option value="<?= $t['id'] ?>"><?= getDivisionName($t['division']) ?> - <?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>背番号</label><input type="number" name="number" id="ep-num" class="form-control" required></div>
            </div>
            <div class="form-group"><label>名前</label><input type="text" name="name" id="ep-name" class="form-control" required></div>
            <div class="form-row">
                <div class="form-group"><label>身長(cm)</label><input type="number" name="height" id="ep-h" class="form-control" step="0.1"></div>
                <div class="form-group"><label>POS</label><select name="position" id="ep-pos" class="form-control"><option value="PG">PG</option><option value="SG">SG</option><option value="SF">SF</option><option value="PF">PF</option><option value="C">C</option></select></div>
            </div>
        </div><div class="modal-footer"><button type="button" class="btn btn-outline modal-close">キャンセル</button><button type="submit" class="btn btn-primary">更新</button></div></form></div></div>
    </main>
</div>
<script src="<?= url('js/app.js') ?>"></script>
<script>
function editP(id,tid,num,name,h,pos){
    document.getElementById('ep-id').value=id;
    document.getElementById('ep-team').value=tid;
    document.getElementById('ep-num').value=num;
    document.getElementById('ep-name').value=name;
    document.getElementById('ep-h').value=h||'';
    document.getElementById('ep-pos').value=pos;
    document.getElementById('epModal').classList.add('active');
}
</script>
</body>
</html>

