<?php
/**
 * B2L 管理画面 - 選手承認
 */
session_start();

define('ADMIN_USER', 'b2ladmin');
define('ADMIN_PASS', 'B2L2025!admin');

if (!isset($_SESSION['b2l_admin'])) {
    header('Location: teams.php');
    exit;
}

require_once __DIR__ . '/../config.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$message = '';
$messageType = '';

// === アクション処理 ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 個別承認
    if ($_POST['action'] === 'approve') {
        $regId = (int)$_POST['reg_id'];
        $reg = $pdo->prepare("SELECT * FROM player_registrations WHERE id = ?");
        $reg->execute([$regId]);
        $player = $reg->fetch(PDO::FETCH_ASSOC);
        
        if ($player) {
            // players テーブルに正式登録
            $ins = $pdo->prepare("
                INSERT INTO players (team_id, number, name, position, height)
                VALUES (?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $player['team_id'],
                $player['number'],
                $player['name'],
                $player['position'],
                $player['height']
            ]);
            
            // ステータス更新
            $upd = $pdo->prepare("UPDATE player_registrations SET status = 'approved' WHERE id = ?");
            $upd->execute([$regId]);
            
            $message = htmlspecialchars($player['name']) . ' を承認しました';
            $messageType = 'success';
        }
    }
    
    // 個別却下
    if ($_POST['action'] === 'reject') {
        $regId = (int)$_POST['reg_id'];
        $note = trim($_POST['admin_note'] ?? '');
        $upd = $pdo->prepare("UPDATE player_registrations SET status = 'rejected', admin_note = ? WHERE id = ?");
        $upd->execute([$note, $regId]);
        $message = '却下しました';
        $messageType = 'success';
    }
    
    // 一括承認
    if ($_POST['action'] === 'approve_all_team') {
        $teamId = (int)$_POST['team_id'];
        $regs = $pdo->prepare("SELECT * FROM player_registrations WHERE team_id = ? AND status = 'pending'");
        $regs->execute([$teamId]);
        $players = $regs->fetchAll(PDO::FETCH_ASSOC);
        
        $count = 0;
        $ins = $pdo->prepare("INSERT INTO players (team_id, number, name, position, height) VALUES (?, ?, ?, ?, ?)");
        $upd = $pdo->prepare("UPDATE player_registrations SET status = 'approved' WHERE id = ?");
        
        foreach ($players as $p) {
            $ins->execute([$p['team_id'], $p['number'], $p['name'], $p['position'], $p['height']]);
            $upd->execute([$p['id']]);
            $count++;
        }
        
        $message = "{$count}名を一括承認しました";
        $messageType = 'success';
    }
}

// === データ取得 ===
// pendingの選手をチームごとにグループ化
$stmt = $pdo->query("
    SELECT pr.*, t.name as team_name, t.division
    FROM player_registrations pr
    JOIN teams t ON pr.team_id = t.id
    WHERE pr.status = 'pending'
    ORDER BY t.division, t.name, pr.number
");
$pendingAll = $stmt->fetchAll(PDO::FETCH_ASSOC);

// チームごとにグループ化
$grouped = [];
foreach ($pendingAll as $p) {
    $tid = $p['team_id'];
    if (!isset($grouped[$tid])) {
        $grouped[$tid] = [
            'team_name' => $p['team_name'],
            'division' => $p['division'],
            'team_id' => $tid,
            'players' => []
        ];
    }
    $grouped[$tid]['players'][] = $p;
}

// 最近の処理履歴
$history = $pdo->query("
    SELECT pr.*, t.name as team_name
    FROM player_registrations pr
    JOIN teams t ON pr.team_id = t.id
    WHERE pr.status IN ('approved', 'rejected')
    ORDER BY pr.updated_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>B2L 管理 - 選手承認</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f0f2f5; color: #333; }
.header { background: #1a1a2e; color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
.header h1 { font-size: 18px; }
.header a { color: #8be9fd; text-decoration: none; font-size: 14px; }
.container { max-width: 1000px; margin: 0 auto; padding: 20px; }

.msg { padding: 12px 16px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
.msg.success { background: #d4edda; color: #155724; }

.card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.card h2 { font-size: 16px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef; }

.team-group { border: 1px solid #e9ecef; border-radius: 8px; margin-bottom: 15px; overflow: hidden; }
.team-group-header { background: #f8f9fa; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; }
.team-group-header h3 { font-size: 15px; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
.badge-1 { background: #ffd700; color: #333; }
.badge-2 { background: #c0c0c0; color: #333; }
.badge-3 { background: #cd7f32; color: #fff; }
.badge-count { background: #dc3545; color: #fff; margin-left: 8px; }

.player-approval-row { display: grid; grid-template-columns: 50px 1fr 70px 70px 120px; gap: 8px; padding: 10px 16px; border-bottom: 1px solid #f0f0f0; align-items: center; font-size: 14px; }
.player-approval-row:last-child { border-bottom: none; }
.player-approval-row:hover { background: #f8f9fa; }
.player-approval-row .num { font-weight: bold; color: #0d6efd; font-size: 16px; }

.btn { padding: 6px 14px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; }
.btn-success { background: #198754; color: #fff; }
.btn-danger { background: #dc3545; color: #fff; }
.btn-primary { background: #0d6efd; color: #fff; }
.btn-sm { padding: 4px 10px; font-size: 12px; }

.actions { display: flex; gap: 4px; }

.empty { text-align: center; color: #999; padding: 30px; }

/* 履歴 */
.history-row { display: grid; grid-template-columns: 1fr 80px 60px 120px; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; align-items: center; }
.status-approved { color: #198754; font-weight: 600; }
.status-rejected { color: #dc3545; font-weight: 600; }

@media (max-width: 600px) {
    .player-approval-row { grid-template-columns: 40px 1fr 80px; }
    .player-approval-row .pos, .player-approval-row .ht { display: none; }
    .history-row { grid-template-columns: 1fr 60px; }
}
</style>
</head>
<body>

<div class="header">
    <h1>? B2L 管理 - 選手承認</h1>
    <div>
        <a href="teams.php">チーム管理</a>
        <a href="?logout=1" style="margin-left:15px; color:#ff6b6b;">ログアウト</a>
    </div>
</div>

<div class="container">

<?php if ($message): ?>
<div class="msg <?= $messageType ?>"><?= $message ?></div>
<?php endif; ?>

<!-- 承認待ち -->
<div class="card">
    <h2>? 承認待ちの選手（<?= count($pendingAll) ?>名）</h2>
    
    <?php if (empty($grouped)): ?>
        <div class="empty">承認待ちの選手はいません ?</div>
    <?php else: ?>
        <?php foreach ($grouped as $tid => $group): ?>
        <div class="team-group">
            <div class="team-group-header">
                <h3>
                    <span class="badge badge-<?= $group['division'] ?>"><?= $group['division'] ?>部</span>
                    <?= htmlspecialchars($group['team_name']) ?>
                    <span class="badge badge-count"><?= count($group['players']) ?>名</span>
                </h3>
                <form method="post" onsubmit="return confirm('<?= htmlspecialchars($group['team_name']) ?> の全選手を承認しますか？')">
                    <input type="hidden" name="action" value="approve_all_team">
                    <input type="hidden" name="team_id" value="<?= $tid ?>">
                    <button class="btn btn-primary">全員承認</button>
                </form>
            </div>
            
            <?php foreach ($group['players'] as $p): ?>
            <div class="player-approval-row">
                <div class="num">#<?= $p['number'] ?></div>
                <div><strong><?= htmlspecialchars($p['name']) ?></strong></div>
                <div class="pos"><?= $p['position'] ?: '-' ?></div>
                <div class="ht"><?= $p['height'] ? $p['height'] . 'cm' : '-' ?></div>
                <div class="actions">
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="reg_id" value="<?= $p['id'] ?>">
                        <button class="btn btn-success btn-sm">承認</button>
                    </form>
                    <form method="post" style="display:inline;" onsubmit="return confirm('却下しますか？')">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="reg_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="admin_note" value="">
                        <button class="btn btn-danger btn-sm">却下</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- 処理履歴 -->
<div class="card">
    <h2>? 最近の処理（直近20件）</h2>
    <?php if (empty($history)): ?>
        <div class="empty">処理履歴はありません</div>
    <?php else: ?>
        <?php foreach ($history as $h): ?>
        <div class="history-row">
            <div>
                <strong><?= htmlspecialchars($h['name']) ?></strong>
                <small style="color:#999;">（<?= htmlspecialchars($h['team_name']) ?> #<?= $h['number'] ?>）</small>
            </div>
            <div class="status-<?= $h['status'] ?>">
                <?= $h['status'] === 'approved' ? '? 承認' : '? 却下' ?>
            </div>
            <div style="font-size:12px; color:#999;">
                <?= date('m/d H:i', strtotime($h['updated_at'])) ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>

</body>
</html>
<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: teams.php');
    exit;
}
?>
