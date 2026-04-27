<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$pdo = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action']??'';
    if ($act==='add_stats') {
        $gid=(int)$_POST['game_id']; $pid=(int)$_POST['player_id'];
        $tid=(int)$pdo->prepare("SELECT team_id FROM players WHERE id=?")->execute([$pid])?$pdo->query("SELECT team_id FROM players WHERE id=$pid")->fetchColumn():0;
        $oreb=(int)$_POST['oreb']; $dreb=(int)$_POST['dreb']; $reb=$oreb+$dreb;
        $fgm=(int)$_POST['fgm']; $tpm=(int)$_POST['three_pm']; $ftm=(int)$_POST['ftm'];
        $pts=($fgm*2)+$tpm+$ftm;
        $pdo->prepare("INSERT INTO player_stats (game_id,player_id,team_id,pts,reb,ast,stl,blk,fgm,fga,three_pm,three_pa,ftm,fta,oreb,dreb,tov,pf,plus_minus) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE pts=VALUES(pts),reb=VALUES(reb),ast=VALUES(ast),stl=VALUES(stl),blk=VALUES(blk),fgm=VALUES(fgm),fga=VALUES(fga),three_pm=VALUES(three_pm),three_pa=VALUES(three_pa),ftm=VALUES(ftm),fta=VALUES(fta),oreb=VALUES(oreb),dreb=VALUES(dreb),tov=VALUES(tov),pf=VALUES(pf),plus_minus=VALUES(plus_minus)")
        ->execute([$gid,$pid,$tid,$pts,$reb,(int)$_POST['ast'],(int)$_POST['stl'],(int)$_POST['blk'],$fgm,(int)$_POST['fga'],$tpm,(int)$_POST['three_pa'],$ftm,(int)$_POST['fta'],$oreb,$dreb,(int)$_POST['tov'],(int)$_POST['pf'],(int)$_POST['plus_minus']]);
        $msg = "スタッツ保存 (PTS:$pts, REB:$reb)";
    }
    if ($act==='delete_stat') {
        $pdo->prepare("DELETE FROM player_stats WHERE id=?")->execute([(int)$_POST['stat_id']]);
        $msg = 'スタッツ削除完了';
    }
}

$selGame = isset($_GET['game_id'])?(int)$_GET['game_id']:0;
$games = $pdo->query("SELECT g.id,g.game_date,g.status,g.division,ht.short_name as hs,at.short_name as aws FROM games g JOIN teams ht ON g.home_team_id=ht.id JOIN teams at ON g.away_team_id=at.id ORDER BY g.game_date DESC LIMIT 50")->fetchAll();

$gameInfo=null; $gamePlayers=[]; $existStats=[];
if ($selGame) {
    $s=$pdo->prepare("SELECT g.*,ht.name as hn,at.name as an FROM games g JOIN teams ht ON g.home_team_id=ht.id JOIN teams at ON g.away_team_id=at.id WHERE g.id=?");
    $s->execute([$selGame]); $gameInfo=$s->fetch();
    if ($gameInfo) {
        $s=$pdo->prepare("SELECT p.*,t.name as tn,t.short_name as sn FROM players p JOIN teams t ON p.team_id=t.id WHERE p.team_id IN (?,?) AND p.is_active=1 ORDER BY p.team_id,p.number");
        $s->execute([$gameInfo['home_team_id'],$gameInfo['away_team_id']]); $gamePlayers=$s->fetchAll();
        $s=$pdo->prepare("SELECT ps.*,p.name as pn,p.number as pnum,t.short_name as sn FROM player_stats ps JOIN players p ON ps.player_id=p.id JOIN teams t ON ps.team_id=t.id WHERE ps.game_id=? ORDER BY t.name,p.number");
        $s->execute([$selGame]); $existStats=$s->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>スタッツ入力 - <?= SITE_NAME ?></title><link rel="stylesheet" href="<?= url('css/style.css') ?>"></head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-logo"><h2>B2L <span>LEAGUE</span></h2><p>管理パネル</p></div>
        <nav class="admin-nav">
            <a href="<?= url('admin/index.php') ?>"><span class="icon">📊</span> ダッシュボード</a>
            <a href="<?= url('admin/teams.php') ?>"><span class="icon">🏀</span> チーム管理</a>
            <a href="<?= url('admin/players.php') ?>"><span class="icon">👤</span> 選手管理</a>
            <a href="<?= url('admin/games.php') ?>"><span class="icon">📅</span> 試合管理</a>
            <a href="<?= url('admin/stats.php') ?>" class="active"><span class="icon">📈</span> スタッツ入力</a>
            <a href="<?= url('admin/live-stats.php') ?>"><span class="icon">🔴</span> ライブ入力</a>
            <a href="<?= url('index.php') ?>"><span class="icon">🌐</span> サイト表示</a>
            <a href="<?= url('admin/index.php?action=logout') ?>"><span class="icon">🚪</span> ログアウト</a>
        </nav>
    </aside>
    <main class="admin-main">
        <div class="admin-header"><h1>スタッツ入力</h1></div>
        <?php if ($msg): ?><div class="alert alert-success">✅ <?= $msg ?></div><?php endif; ?>
        <div class="alert alert-info">💡 FGM/3PM/FTMとOREB/DREBを入力するとPTSとREBが自動計算されます。</div>

        <div class="card mb-3"><div class="card-header"><h3>試合選択</h3></div><div class="card-body">
            <form method="GET"><div class="form-group"><label>試合</label><select name="game_id" class="form-control" onchange="this.form.submit()">
                <option value="">選択...</option>
                <?php foreach($games as $g): ?><option value="<?= $g['id'] ?>" <?= $selGame==$g['id']?'selected':'' ?>><?= date('n/j',strtotime($g['game_date'])) ?> [<?= getDivisionName($g['division']) ?>] <?= $g['hs'] ?> vs <?= $g['aws'] ?> (<?= $g['status']==='finished'?'終了':($g['status']==='live'?'LIVE':'予定') ?>)</option><?php endforeach; ?>
            </select></div></form>
        </div></div>

        <?php if ($gameInfo): ?>
        <div class="card mb-3"><div class="card-header"><h3><?= htmlspecialchars($gameInfo['hn']) ?> vs <?= htmlspecialchars($gameInfo['an']) ?></h3></div><div class="card-body">
            <form method="POST" id="stats-form"><input type="hidden" name="action" value="add_stats"><input type="hidden" name="game_id" value="<?= $selGame ?>">
                <div class="form-group"><label>選手</label><select name="player_id" class="form-control" required><option value="">選択...</option>
                <?php $ct=''; foreach($gamePlayers as $p): if($ct!==$p['tn']){if($ct!=='')echo'</optgroup>'; echo'<optgroup label="'.htmlspecialchars($p['tn']).'">'; $ct=$p['tn'];} ?>
                <option value="<?= $p['id'] ?>">#<?= $p['number'] ?> <?= htmlspecialchars($p['name']) ?> (<?= $p['position'] ?>)</option>
                <?php endforeach; if($ct!=='')echo'</optgroup>'; ?></select></div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px">
                    <?php $fields=['FGM'=>'fgm','FGA'=>'fga','3PM'=>'three_pm','3PA'=>'three_pa','FTM'=>'ftm','FTA'=>'fta','OREB'=>'oreb','DREB'=>'dreb']; foreach($fields as $label=>$name): ?>
                    <div class="form-group"><label><?= $label ?></label><input type="number" name="<?= $name ?>" class="form-control" min="0" value="0"></div>
                    <?php endforeach; ?>
                    <div class="form-group"><label>REB(自動)</label><input type="number" name="reb" class="form-control" value="0" readonly style="background:#222"></div>
                    <div class="form-group"><label>PTS(自動)</label><input type="number" name="pts" class="form-control" value="0" readonly style="background:#222"></div>
                    <?php $fields2=['AST'=>'ast','STL'=>'stl','BLK'=>'blk','TOV'=>'tov','PF'=>'pf']; foreach($fields2 as $label=>$name): ?>
                    <div class="form-group"><label><?= $label ?></label><input type="number" name="<?= $name ?>" class="form-control" min="0" value="0"></div>
                    <?php endforeach; ?>
                    <div class="form-group"><label>+/-</label><input type="number" name="plus_minus" class="form-control" value="0"></div>
                </div>
                <button type="submit" class="btn btn-success btn-lg mt-2">スタッツを保存</button>
            </form>
        </div></div>

        <?php if (!empty($existStats)): ?>
        <div class="card"><div class="card-header"><h3>入力済みスタッツ</h3></div><div class="card-body" style="padding:0">
            <div class="stats-table-wrapper"><table class="stats-table">
                <thead><tr><th style="text-align:left">選手</th><th>TEAM</th><th>PTS</th><th>REB</th><th>AST</th><th>STL</th><th>BLK</th><th>FGM</th><th>FGA</th><th>3PM</th><th>3PA</th><th>FTM</th><th>FTA</th><th>OREB</th><th>DREB</th><th>TOV</th><th>PF</th><th>+/-</th><th>操作</th></tr></thead>
                <tbody>
                <?php foreach ($existStats as $s): ?>
                <tr>
                    <td style="text-align:left;font-weight:600">#<?= $s['pnum'] ?> <?= htmlspecialchars($s['pn']) ?></td>
                    <td><?= $s['sn'] ?></td><td class="fw-bold"><?= $s['pts'] ?></td><td><?= $s['reb'] ?></td><td><?= $s['ast'] ?></td><td><?= $s['stl'] ?></td><td><?= $s['blk'] ?></td>
                    <td><?= $s['fgm'] ?></td><td><?= $s['fga'] ?></td><td><?= $s['three_pm'] ?></td><td><?= $s['three_pa'] ?></td><td><?= $s['ftm'] ?></td><td><?= $s['fta'] ?></td>
                    <td><?= $s['oreb'] ?></td><td><?= $s['dreb'] ?></td><td><?= $s['tov'] ?></td><td><?= $s['pf'] ?></td>
                    <td><?= $s['plus_minus']>0?'+'.$s['plus_minus']:$s['plus_minus'] ?></td>
                    <td><form method="POST" style="display:inline"><input type="hidden" name="action" value="delete_stat"><input type="hidden" name="stat_id" value="<?= $s['id'] ?>"><button type="submit" class="btn btn-sm btn-danger btn-delete">削除</button></form></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        </div></div>
        <?php endif; ?>
        <?php else: ?>
            <div class="empty-state"><div class="icon">📈</div><p>試合を選択してスタッツを入力してください</p></div>
        <?php endif; ?>
    </main>
</div>
<script src="<?= url('js/app.js') ?>"></script>
</body>
</html>
