<?php
// /b2l/admin/index.php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// --- Basic認証 ---
$admin_user = 'b2ladmin';
$admin_pass = 'X_MJJk5CfDwv4nf';

if (!isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== $admin_user ||
    $_SERVER['PHP_AUTH_PW'] !== $admin_pass) {
    header('WWW-Authenticate: Basic realm="B2L Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}

// --- DB接続 ---
$db_host = 'mysql80.kasugai-sp.sakura.ne.jp';
$db_name = 'kasugai-sp_b2l';
$db_user = 'kasugai-sp';
$db_pass = 'Basketball2025';

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("DB接続エラー: " . $e->getMessage());
}

// --- 承認・却下処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regId = intval($_POST['registration_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note = trim($_POST['admin_note'] ?? '');

    if ($regId > 0 && in_array($action, ['approve', 'reject'])) {
        try {
            $pdo->beginTransaction();

            if ($action === 'approve') {
                // チーム情報を取得
                $stmt = $pdo->prepare("SELECT * FROM team_registrations WHERE id = ? AND status = 'pending'");
                $stmt->execute([$regId]);
                $teamReg = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($teamReg) {
                    // teams テーブルに挿入
                    $stmt = $pdo->prepare("INSERT INTO teams (name, registration_id) VALUES (?, ?)");
                    $stmt->execute([$teamReg['team_name'], $regId]);
                    $teamId = $pdo->lastInsertId();

                    // 選手情報を取得して players テーブルに挿入
                    $stmt = $pdo->prepare("SELECT * FROM player_registrations WHERE team_registration_id = ?");
                    $stmt->execute([$regId]);
                    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $stmtInsert = $pdo->prepare("INSERT INTO players (team_id, number, name, position, registration_id) VALUES (?, ?, ?, ?, ?)");
                    foreach ($players as $p) {
                        $stmtInsert->execute([$teamId, $p['number'], $p['name'], $p['position'], $p['id']]);
                    }

                    // ステータス更新
                    $stmt = $pdo->prepare("UPDATE team_registrations SET status = 'approved', admin_note = ? WHERE id = ?");
                    $stmt->execute([$note, $regId]);
                }
            } else {
                // 却下
                $stmt = $pdo->prepare("UPDATE team_registrations SET status = 'rejected', admin_note = ? WHERE id = ?");
                $stmt->execute([$note, $regId]);
            }

            $pdo->commit();

            // LINE通知
            notifyResult($pdo, $regId, $action);

        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = $e->getMessage();
        }
    }
}

// --- LINE通知 ---
function notifyResult($pdo, $regId, $action) {
    $stmt = $pdo->prepare("SELECT * FROM team_registrations WHERE id = ?");
    $stmt->execute([$regId]);
    $reg = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reg) return;

    $token = 'kbZCHXeFaL7WyqEPU/MW45EnWweTNjTDkKkMXlT+Cf2qzyrDkG3v9EG2+lFPY0Xc9uJZznCnMd6ERm/gLZRBy7Oq8M15DP66qRt/B2K1IPKFjZgGb2S9TogAJM/rlNMkNcX0C1i8f2Cqsvi4z6UydQdB04t89/1O/w1cDnyilFU=';
    $statusText = $action === 'approve' ? '✅ 承認されました' : '❌ 却下されました';
    $message = "【B2L 登録結果】\n"
             . "チーム名: {$reg['team_name']}\n"
             . "結果: {$statusText}\n";
    if ($reg['admin_note']) {
        $message .= "備考: {$reg['admin_note']}";
    }

    $ch = curl_init('https://api.line.me/v2/bot/message/broadcast');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'messages' => [['type' => 'text', 'text' => $message]]
        ]),
        CURLOPT_RETURNTRANSFER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// --- データ取得 ---
$filter = $_GET['filter'] ?? 'pending';
$validFilters = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filter, $validFilters)) $filter = 'pending';

if ($filter === 'all') {
    $stmt = $pdo->query("SELECT * FROM team_registrations ORDER BY created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM team_registrations WHERE status = ? ORDER BY created_at DESC");
    $stmt->execute([$filter]);
}
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 各チームの選手数を取得
$countStmt = $pdo->query("SELECT team_registration_id, COUNT(*) as cnt FROM player_registrations GROUP BY team_registration_id");
$playerCounts = [];
while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
    $playerCounts[$row['team_registration_id']] = $row['cnt'];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2L 管理画面 - チーム登録管理</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0a0a2e;
            color: #fff;
            min-height: 100vh;
        }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .header h1 { font-size: 22px; color: #667eea; }

        .filters {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filters a {
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }
        .filters a.active {
            background: #667eea;
            color: #fff;
        }
        .filters a:not(.active) {
            background: rgba(255,255,255,0.08);
            color: #aaa;
        }
        .filters a:hover:not(.active) {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }

        .card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .card-header h3 { font-size: 18px; }
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge.pending { background: rgba(241,196,15,0.2); color: #f1c40f; }
        .badge.approved { background: rgba(46,204,113,0.2); color: #2ecc71; }
        .badge.rejected { background: rgba(231,76,60,0.2); color: #e74c3c; }

        .card-info {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 8px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .card-info span { color: #888; }

        .player-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 13px;
        }
        .player-table th {
            background: rgba(255,255,255,0.08);
            padding: 8px;
            text-align: left;
            color: #aaa;
        }
        .player-table td {
            padding: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .action-form {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        .action-form input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: 8px 12px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 6px;
            color: #fff;
            font-size: 13px;
            outline: none;
        }
        .btn-approve, .btn-reject {
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.1s;
        }
        .btn-approve { background: #27ae60; color: #fff; }
        .btn-reject { background: #e74c3c; color: #fff; }
        .btn-approve:hover, .btn-reject:hover { transform: translateY(-1px); }

        .toggle-players {
            background: rgba(102,126,234,0.15);
            border: 1px solid rgba(102,126,234,0.3);
            color: #667eea;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state p { font-size: 16px; }

        .error-msg {
            background: rgba(231,76,60,0.2);
            border: 1px solid #e74c3c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            color: #e74c3c;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🏀 B2L 管理画面</h1>
        <span style="color:#888; font-size:13px;">チーム登録管理</span>
    </div>

    <?php if (!empty($errorMsg)): ?>
        <div class="error-msg">⚠️ <?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="filters">
        <a href="?filter=pending" class="<?= $filter === 'pending' ? 'active' : '' ?>">⏳ 承認待ち</a>
        <a href="?filter=approved" class="<?= $filter === 'approved' ? 'active' : '' ?>">✅ 承認済み</a>
        <a href="?filter=rejected" class="<?= $filter === 'rejected' ? 'active' : '' ?>">❌ 却下</a>
        <a href="?filter=all" class="<?= $filter === 'all' ? 'active' : '' ?>">📋 すべて</a>
    </div>

    <?php if (empty($registrations)): ?>
        <div class="empty-state">
            <p>📭 該当する登録データはありません</p>
        </div>
    <?php endif; ?>

    <?php foreach ($registrations as $reg): ?>
        <div class="card">
            <div class="card-header">
                <h3><?= htmlspecialchars($reg['team_name']) ?> <small style="color:#888;">#<?= $reg['id'] ?></small></h3>
                <span class="badge <?= $reg['status'] ?>"><?= strtoupper($reg['status']) ?></span>
            </div>
            <div class="card-info">
                <div><span>代表者:</span> <?= htmlspecialchars($reg['representative_name']) ?></div>
                <div><span>Email:</span> <?= htmlspecialchars($reg['email']) ?></div>
                <div><span>電話:</span> <?= htmlspecialchars($reg['phone'] ?? '-') ?></div>
                <div><span>LINE:</span> <?= htmlspecialchars($reg['line_name'] ?? '-') ?></div>
                <div><span>選手数:</span> <?= $playerCounts[$reg['id']] ?? 0 ?>名</div>
                <div><span>申請日:</span> <?= date('Y/m/d H:i', strtotime($reg['created_at'])) ?></div>
            </div>

            <?php if ($reg['admin_note']): ?>
                <div style="background:rgba(255,255,255,0.05); padding:8px 12px; border-radius:6px; font-size:13px; color:#aaa; margin-bottom:8px;">
                    📝 <?= htmlspecialchars($reg['admin_note']) ?>
                </div>
            <?php endif; ?>

            <!-- 選手一覧トグル -->
            <button class="toggle-players" onclick="togglePlayers(<?= $reg['id'] ?>)">
                👥 選手一覧を表示
            </button>
            <div id="players-<?= $reg['id'] ?>" style="display:none;">
                <?php
                $pStmt = $pdo->prepare("SELECT * FROM player_registrations WHERE team_registration_id = ? ORDER BY number");
                $pStmt->execute([$reg['id']]);
                $players = $pStmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <table class="player-table">
                    <thead><tr><th>No.</th><th>選手名</th><th>ポジション</th><th>身長</th></tr></thead>
                    <tbody>
                    <?php foreach ($players as $p): ?>
                        <tr>
                            <td><?= $p['number'] ?></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= $p['position'] ?></td>
                            <td><?= $p['height'] ? $p['height'] . 'cm' : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- 承認/却下ボタン（pending の場合のみ） -->
            <?php if ($reg['status'] === 'pending'): ?>
                <form method="POST" class="action-form">
                    <input type="hidden" name="registration_id" value="<?= $reg['id'] ?>">
                    <input type="text" name="admin_note" placeholder="備考（任意）">
                    <button type="submit" name="action" value="approve" class="btn-approve"
                            onclick="return confirm('「<?= htmlspecialchars($reg['team_name']) ?>」を承認しますか？')">
                        ✅ 承認
                    </button>
                    <button type="submit" name="action" value="reject" class="btn-reject"
                            onclick="return confirm('「<?= htmlspecialchars($reg['team_name']) ?>」を却下しますか？')">
                        ❌ 却下
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
function togglePlayers(id) {
    const el = document.getElementById('players-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
</body>
</html>

