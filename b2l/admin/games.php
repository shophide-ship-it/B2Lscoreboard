<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$pdo = getDB();
$msg = '';

function updateStandings($pdo) {
    $teams = $pdo->query("SELECT id, division FROM teams")->fetchAll();
    foreach ($teams as $team) {
        $tid = $team['id'];
        $w = (int)$pdo->prepare("SELECT COUNT(*) FROM games WHERE status='finished' AND ((home_team_id=? AND home_score>away_score) OR (away_team_id=? AND away_score>home_score))")->execute([$tid,$tid]) ? $pdo->query("SELECT COUNT(*) FROM games WHERE status='finished' AND ((home_team_id=$tid AND home_score>away_score) OR (away_team_id=$tid AND away_score>home_score))")->fetchColumn() : 0;
        $l = (int)$pdo->query("SELECT COUNT(*) FROM games WHERE status='finished' AND ((home_team_id=$tid AND home_score<away_score) OR (away_team_id=$tid AND away_score<home_score))")->fetchColumn();
        $pf = (int)$pdo->query("SELECT COALESCE(SUM(CASE WHEN home_team_id=$tid THEN home_score WHEN away_team_id=$tid THEN away_score END),0) FROM games WHERE status='finished' AND (home_team_id=$tid OR away_team_id=$tid)")->fetchColumn();
        $pa = (int)$pdo->query("SELECT COALESCE(SUM(CASE WHEN home_team_id=$tid THEN away_score WHEN away_team_id=$tid THEN home_score END),0) FROM games WHERE status='finished' AND (home_team_id=$tid OR away_team_id=$tid)")->fetchColumn();
        $wp = ($w+$l)>0?$w/($w+$l):0;

        $recent = $pdo->query("SELECT CASE WHEN (home_team_id=$tid AND home_score>away_score) OR (away_team_id=$tid AND away_score>home_score) THEN 'W' ELSE 'L' END as r FROM games WHERE status='finished' AND (home_team_id=$tid OR away_team_id=$tid) ORDER BY game_date DESC, id DESC LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
        $streak = '-';
        if (!empty($recent)) { $f=$recent[0]; $c=0; foreach($recent as $r){if($r===$f)$c++;else break;} $streak=$f.$c; }

        $pdo->prepare("INSERT INTO standings (team_id,division,season,wins,losses,win_pct,points_for,points_against,streak) VALUES (?,?,'2024-25',?,?,?,?,?,?) ON DUPLICATE KEY UPDATE wins=?,losses=?,win_pct=?,points_for=?,points_against=?,streak=?")->execute([$tid,$team['division'],$w,$l,$wp,$pf,$pa,$streak,$w,$l,$wp,$pf,$pa,$streak]);
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action']??'';
    if ($act==='add') {
        $hs = $_POST['status']==='finished'?(int)$_POST['home_score']:null;
        $as = $_POST['status']==='finished'?(int)$_POST['away_score']:null;
        $gt = !empty($_POST['game_time'])?$_POST['game_time']:null;
        $pdo->prepare("INSERT INTO games (division,home_team_id,away_team_id,game_date,game_time,venue,status,home_score,away_score) VALUES (?,?,?,?,?,?,?,?,?)")->execute([(int)$_POST['division'],(int)$_POST['home_team_id'],(int)$_POST['away_team_id'],$_POST['game_date'],$gt,$_POST['venue'],$_POST['status'],$hs,$as]);
        updateStandings($pdo); $msg = '試合を追加しました。';
    }
    if ($act==='edit') {
        $hs = in_array($_POST['status'],['finished','live'])?(int)$_POST['home_score']:null;
        $as = in_array($_POST['status'],['finished','live'])?(int)$_POST['away_score']:null;
        $gt = !empty($_POST['game_time'])?$_POST['game_time']:null;
        $pdo->prepare("UPDATE games SET division=?,home_team_id=?,away_team_id=?,game_date=?,game_time=?,venue=?,status=?,home_score=?,away_score=? WHERE id=?")->execute([(int)$_POST['division'],(int)$_POST['home_team_id'],(int)$_POST['away_team_id'],$_POST['game_date'],$gt,$_POST['venue'],$_POST['status'],$hs,$as,(int)$_POST['id']]);
        updateStandings($pdo); $msg = '試合を更新しました。';
    }
    if ($act==='delete') {
        $pdo->prepare("DELETE FROM games WHERE id=?")->execute([(int)$_POST['id']]);
        updateStandings($pdo); $msg = '試合を削除しました。';
    }
}

$games = $pdo->query("SELECT g.*, ht.name as home_name, ht.short_name as home_short, at.name as away_name, at.short_name as away_short FROM games g JOIN teams ht ON g.home_team_id=ht.id JOIN teams at ON g.away_team_id=at.id ORDER BY g.game_date DESC, g.game_time DESC LIMIT 50")->fetchAll();
$teams = $pdo->query("SELECT * FROM teams ORDER BY division, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>試合管理 - <?= SITE_NAME ?></title><link rel="stylesheet" href="<?= url('css/style.css') ?>"></head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-logo"><h2>B2L <span>LEAGUE</span></h2><p>管理パネル</p></div>
        <nav class="admin-nav">
            <a href="<?= url('admin/index.php') ?>"><span class="icon">📊</span> ダッシュボード</a>
            <a href="<?= url('admin/teams.php') ?>"><span class="icon">🏀</span> チーム管理</a>
            <a href="<?= url('admin/players.php') ?>"><span class="icon">👤</span> 選手管理</a>
            <a href="<?= url('admin/games.php') ?>" class="active"><span class="icon">📅</span> 試合管理</a>
            <a href="<?= url('admin/stats.php') ?>"><span class="icon">📈</span> スタッツ入力</a>
            <a href="<?= url('index.php') ?>"><span class="icon">🌐</span> サイト表示</a>
            <a href="<?= url('admin/index.php?action=logout') ?>"><span class="icon">🚪</span> ログアウト</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="admin-header"><h1>試合管理</h1></div>
        <?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
        <div class="alert alert-info">💡 試合結果を入力し「終了」にすると順位表が自動更新されます。</div>

        <div class="card mb-3"><div class="card-header"><h3>試合追加</h3></div><div class="card-body">
            <form method="POST"><input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group"><label>部門</label><select name="division" class="form-control"><option value="1">1部</option><option value="2">2部</option><option value="3">3部</option></select></div>
                    <div class="form-group"><label>日付</label><input type="date" name="game_date" class="form-control" required></div>
                    <div class="form-group"><label>時間</label><input type="time" name="game_time" class="form-control"></div>
                    <div class="form-group"><label>ステータス</label><select name="status" class="form-control"><option value="scheduled">予定</option><option value="live">LIVE</option><option value="finished">終了</option></select></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>ホーム</label><select name="home_team_id" class="form-control" required><option value="">選択...</option><?php foreach($teams as $t): ?><option value="<?= $t['id'] ?>"><?= getDivisionName($t['division']) ?> - <?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>ホームスコア</label><input type="number" name="home_score" class="form-control" min="0" value="0"></div>
                    <div class="form-group"><label>アウェイ</label><select name="away_team_id" class="form-control" required><option value="">選択...</option><?php foreach($teams as $t): ?><option value="<?= $t['id'] ?>"><?= getDivisionName($t['division']) ?> - <?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>アウェイスコア</label><input type="number" name="away_score" class="form-control" min="0" value="0"></div>
                </div>
                <div class="form-group"><label>会場</label><input type="text" name="venue" class="form-control"></div>
                <button type="submit" class="btn btn-primary">追加</button>
            </form>
        </div></div>

        <div class="card"><div class="card-header"><h3>試合一覧</h3></div><div class="card-body" style="padding:0">
            <div class="stats-table-wrapper"><table class="stats-table">
                <thead><tr><th style="text-align:left">日付</th><th>部門</th><th style="text-align:left">ホーム</th><th>スコア</th><th style="text-align:left">アウェイ</th><th>状態</th><th>操作</th></tr></thead>
                <tbody>
                <?php foreach ($games as $g): ?>
                <tr>
                    <td style="text-align:left"><?= date('n/j',strtotime($g['game_date'])) ?></td>
                    <td><?= getDivisionName($g['division']) ?></td>
                    <td style="text-align:left;font-weight:600"><?= htmlspecialchars($g['home_name']) ?></td>
                    <td class="fw-bold"><?= ($g['status']==='finished'||$g['status']==='live')?$g['home_score'].' - '.$g['away_score']:'- vs -' ?></td>
                    <td style="text-align:left;font-weight:600"><?= htmlspecialchars($g['away_name']) ?></td>
                    <td><span class="status-badge status-<?= $g['status'] ?>" style="font-size:11px"><?= $g['status']==='finished'?'終了':($g['status']==='live'?'LIVE':'予定') ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-outline" onclick='editG(<?= json_encode($g) ?>)'>編集</button>
                        <form method="POST" style="display:inline"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $g['id'] ?>"><button type="submit" class="btn btn-sm btn-danger btn-delete">削除</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div></div>

        <div id="egModal" class="modal-overlay"><div class="modal" style="max-width:700px"><div class="modal-header"><h3>試合編集</h3><button class="modal-close">&times;</button></div>
        <form method="POST"><div class="modal-body">
            <input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="eg-id">
            <div class="form-row">
                <div class="form-group"><label>部門</label><select name="division" id="eg-div" class="form-control"><option value="1">1部</option><option value="2">2部</option><option value="3">3部</option></select></div>
                <div class="form-group"><label>日付</label><input type="date" name="game_date" id="eg-date" class="form-control" required></div>
                <div class="form-group"><label>時間</label><input type="time" name="game_time" id="eg-time" class="form-control"></div>
                <div class="form-group"><label>ステータス</label><select name="status" id="eg-st" class="form-control"><option value="scheduled">予定</option><option value="live">LIVE</option><option value="finished">終了</option></select></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>ホーム</label><select name="home_team_id" id="eg-home" class="form-control"><?php foreach($teams as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>ホームスコア</label><input type="number" name="home_score" id="eg-hs" class="form-control" min="0"></div>
                <div class="form-group"><label>アウェイ</label><select name="away_team_id" id="eg-away" class="form-control"><?php foreach($teams as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>アウェイスコア</label><input type="number" name="away_score" id="eg-as" class="form-control" min="0"></div>
            </div>
            <div class="form-group"><label>会場</label><input type="text" name="venue" id="eg-v" class="form-control"></div>
        </div><div class="modal-footer"><button type="button" class="btn btn-outline modal-close">キャンセル</button><button type="submit" class="btn btn-primary">更新</button></div></form></div></div>
    </main>
</div>
<script src="<?= url('js/app.js') ?>"></script>
<script>
function editG(g){
    document.getElementById('eg-id').value=g.id;
    document.getElementById('eg-div').value=g.division;
    document.getElementById('eg-date').value=g.game_date;
    document.getElementById('eg-time').value=g.game_time||'';
    document.getElementById('eg-st').value=g.status;
    document.getElementById('eg-home').value=g.home_team_id;
    document.getElementById('eg-away').value=g.away_team_id;
    document.getElementById('eg-hs').value=g.home_score||0;
    document.getElementById('eg-as').value=g.away_score||0;
    document.getElementById('eg-v').value=g.venue||'';
    document.getElementById('egModal').classList.add('active');
}
</script>
</body>
</html>
