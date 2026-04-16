<?php
// エラーメッセージを表示する設定
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// データベース接続の設定
try {
    $pdo = new PDO('mysql:host=mysql3114.db.sakura.ne.jp;dbname=kasugai-sp_b2l-league;charset=utf8', 'kasugai-sp_b2l-league', 'B2L_db2025secure');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "データベース接続成功"; // 成功メッセージ
} catch (PDOException $e) {
    echo 'データベース接続エラー: ' . htmlspecialchars($e->getMessage());
    exit; // スクリプトを終了
}

// 初期化
$flash = ['type' => '', 'message' => ''];
$filter = $_GET['filter'] ?? 'pending'; // フィルタ用

// 管理者ユーザーの判定
$adminUser = $_SERVER['PHP_AUTH_USER'] ?? 'ゲスト';

// 統計情報やDBクエリの処理部分
try {
    $stats = $pdo->query("SELECT ...")->fetch(); // 必要なSQL
} catch (Exception $e) {
    echo 'エラー発生: ' . htmlspecialchars($e->getMessage());
    exit; // スクリプトを終了
}
// 統計情報の表示部分やDB取得部分でも同様に初期化を行う

try {
    // 統計情報の取得
    $stats = $pdo->query("SELECT SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                                   SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                                   COUNT(*) as total_count 
                           FROM team_registrations")->fetch(PDO::FETCH_ASSOC);
    $totalPlayers = $pdo->query("SELECT COUNT(*) FROM player_registrations")->fetchColumn();
    // 後続の処理...
} catch (Exception $e) {
    // エラーハンドリング
    echo 'エラーが発生しました: ' . htmlspecialchars($e->getMessage());
}

// 表示部分

// 管理者情報
$adminUser = htmlspecialchars($_SERVER['PHP_AUTH_USER'] ?? 'ゲスト'); // デフォルトは「ゲスト」

echo "<h1>管理パネル</h1>";
echo "<p>管理者: {$adminUser}</p>";
echo "<p>保留中: {$stats['pending_count']}</p>";
echo "<p>承認済: {$stats['approved_count']}</p>";
echo "<p>却下: {$stats['rejected_count']}</p>";
echo "<p>総選手数: {$totalPlayers}</p>";
?>

// ============================================================
// ヘルパー関数
// ============================================================

/**
 * チーム名からshort_name(最大10文字)を自動生成
 * 英字ならそのまま切り詰め、日本語なら先頭文字を使用
 */
function generateShortName(string $teamName): string {
    // 英数字のみ抽出を試みる
    $ascii = preg_replace('/[^A-Za-z0-9]/', '', $teamName);
    if (strlen($ascii) >= 2) {
        return mb_substr(strtoupper($ascii), 0, 10);
    }
    // 日本語の場合: 先頭最大10文字
    return mb_substr($teamName, 0, 10);
}

/**
 * LINE Messaging API でブロードキャスト通知
 */
function sendLineNotification(string $message): bool {
    $token = 'kbZCHXeFaL7WyqEPU/MW45EnWweTNjTDkKkMXlT+Cf2qzyrDkG3v9EG2+lFPY0Xc9uJZznCnMd6ERm/gLZRBy7Oq8M15DP66qRt/B2K1IPKFjZgGb2S9TogAJM/rlNMkNcX0C1i8f2Cqsvi4z6UydQdB04t89/1O/w1cDnyilFU=';

    $ch = curl_init('https://api.line.me/v2/bot/message/broadcast');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_POSTFIELDS     => json_encode([
            'messages' => [['type' => 'text', 'text' => $message]]
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode === 200);
}

// ============================================================
// POST処理（承認・却下）
// ============================================================
$flash = ['type' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $teamRegId = intval($_POST['team_registration_id'] ?? 0);

    if ($teamRegId <= 0) {
        $flash = ['type' => 'error', 'message' => '無効なリクエストです。'];
    } else {
        try {
            // ==========================
            // 承認処理
            // ==========================
            if ($action === 'approve') {
                $division = intval($_POST['division'] ?? 3);
                if ($division < 1 || $division > 3) $division = 3;

                $pdo->beginTransaction();

                // 1. チーム登録情報を取得
                $stmt = $pdo->prepare("
                    SELECT * FROM team_registrations WHERE id = :id AND status = 'pending'
                ");
                $stmt->execute([':id' => $teamRegId]);
                $teamReg = $stmt->fetch();

                if (!$teamReg) {
                    throw new Exception('対象の登録が見つからないか、既に処理済みです。');
                }

                // 2. short_name生成（NOT NULL制約対応）
                $shortName = generateShortName($teamReg['team_name']);

                // short_name重複チェック
                $chkStmt = $pdo->prepare("SELECT COUNT(*) FROM teams WHERE short_name = :sn");
                $chkStmt->execute([':sn' => $shortName]);
                if ($chkStmt->fetchColumn() > 0) {
                    $shortName = mb_substr($shortName, 0, 7) . $teamRegId;
                }

                // 3. tokenを生成（チーム編集用）
                $token = bin2hex(random_bytes(32));

                // 4. teamsテーブルにINSERT
                $stmt = $pdo->prepare("
                    INSERT INTO teams (
                        name, short_name, division, registration_id, token,
                        rep_name, rep_email, rep_phone, rep_line_name
                    ) VALUES (
                        :name, :short_name, :division, :reg_id, :token,
                        :rep_name, :rep_email, :rep_phone, :rep_line_name
                    )
                ");
                $stmt->execute([
                    ':name'          => $teamReg['team_name'],
                    ':short_name'    => $shortName,
                    ':division'      => $division,
                    ':reg_id'        => $teamRegId,
                    ':token'         => $token,
                    ':rep_name'      => $teamReg['representative_name'] ?? null,
                    ':rep_email'     => $teamReg['email'] ?? null,
                    ':rep_phone'     => $teamReg['phone'] ?? null,
                    ':rep_line_name' => $teamReg['line_name'] ?? null,
                ]);
                $newTeamId = $pdo->lastInsertId();

                // 5. player_registrationsから選手を取得してplayersにINSERT
                $stmt = $pdo->prepare("
                    SELECT * FROM player_registrations WHERE team_registration_id = :trid
                ");
                $stmt->execute([':trid' => $teamRegId]);
                $players = $stmt->fetchAll();

                $playerInsert = $pdo->prepare("
                    INSERT INTO players (team_id, number, name, position, height, registration_id)
                    VALUES (:team_id, :number, :name, :position, :height, :reg_id)
                ");

                $migratedCount = 0;
                foreach ($players as $p) {
                    // positionがNULLの場合のデフォルト（players.positionはNOT NULL）
                    $position = $p['position'] ?? 'PG';

                    $playerInsert->execute([
                        ':team_id'  => $newTeamId,
                        ':number'   => $p['number'],
                        ':name'     => $p['name'],
                        ':position' => $position,
                        ':height'   => $p['height'],
                        ':reg_id'   => $p['id'],
                    ]);
                    $migratedCount++;
                }

                // 6. player_registrationsのステータス更新 & team_id設定
                $stmt = $pdo->prepare("
                    UPDATE player_registrations 
                    SET status = 'approved', team_id = :team_id
                    WHERE team_registration_id = :trid
                ");
                $stmt->execute([':team_id' => $newTeamId, ':trid' => $teamRegId]);

                // 7. team_registrationsのステータスを承認に
                $stmt = $pdo->prepare("
                    UPDATE team_registrations SET status = 'approved' WHERE id = :id
                ");
                $stmt->execute([':id' => $teamRegId]);

                $pdo->commit();

                // LINE通知
                $divNames = [1 => '1部', 2 => '2部', 3 => '3部'];
                $lineMsg = "【B2L 登録承認】\n"
                         . "チーム「{$teamReg['team_name']}」が承認されました！\n"
                         . "部門: {$divNames[$division]}\n"
                         . "選手数: {$migratedCount}名\n"
                         . "チームID: #{$newTeamId}";
                sendLineNotification($lineMsg);

                $flash = [
                    'type' => 'success',
                    'message' => "「{$teamReg['team_name']}」を{$divNames[$division]}として承認しました。（選手{$migratedCount}名移行完了）"
                ];

            // ==========================
            // 却下処理
            // ==========================
            } elseif ($action === 'reject') {
                $adminNote = trim($_POST['admin_note'] ?? '');
                if ($adminNote === '') {
                    $flash = ['type' => 'error', 'message' => '却下理由を入力してください。'];
                } else {
                    $pdo->beginTransaction();

                    // チーム名を取得
                    $stmt = $pdo->prepare("SELECT team_name FROM team_registrations WHERE id = :id AND status = 'pending'");
                    $stmt->execute([':id' => $teamRegId]);
                    $teamReg = $stmt->fetch();

                    if (!$teamReg) {
                        throw new Exception('対象の登録が見つからないか、既に処理済みです。');
                    }

                    // team_registrationsを却下に
                    $stmt = $pdo->prepare("
                        UPDATE team_registrations 
                        SET status = 'rejected', admin_note = :note
                        WHERE id = :id
                    ");
                    $stmt->execute([':note' => $adminNote, ':id' => $teamRegId]);

                    // player_registrationsも却下に
                    $stmt = $pdo->prepare("
                        UPDATE player_registrations 
                        SET status = 'rejected', admin_note = :note
                        WHERE team_registration_id = :trid
                    ");
                    $stmt->execute([':note' => $adminNote, ':trid' => $teamRegId]);

                    $pdo->commit();

                    // LINE通知
                    $lineMsg = "【B2L 登録却下】\n"
                             . "チーム「{$teamReg['team_name']}」の登録が却下されました。\n"
                             . "理由: {$adminNote}";
                    sendLineNotification($lineMsg);

                    $flash = [
                        'type' => 'warning',
                        'message' => "「{$teamReg['team_name']}」を却下しました。"
                    ];
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $flash = ['type' => 'error', 'message' => 'エラー: ' . $e->getMessage()];
        }
    }
}

// ============================================================
// データ取得
// ============================================================

// 統計情報
$stats = $pdo->query("
    SELECT 
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
        COUNT(*) as total_count
    FROM team_registrations
")->fetch();

$totalPlayers = $pdo->query("SELECT COUNT(*) FROM player_registrations")->fetchColumn();

// 保留中のチーム登録（選手情報含む）
$pendingTeams = $pdo->query("
    SELECT tr.*, 
           (SELECT COUNT(*) FROM player_registrations WHERE team_registration_id = tr.id) as player_count
    FROM team_registrations tr
    WHERE tr.status = 'pending'
    ORDER BY tr.created_at DESC
")->fetchAll();

// 各チームの選手一覧を事前取得
$pendingPlayers = [];
foreach ($pendingTeams as $team) {
    $stmt = $pdo->prepare("
        SELECT id, number, name, position, height 
        FROM player_registrations 
        WHERE team_registration_id = :trid
        ORDER BY number ASC
    ");
    $stmt->execute([':trid' => $team['id']]);
    $pendingPlayers[$team['id']] = $stmt->fetchAll();
}

// 処理済みチーム（直近20件）
$processedTeams = $pdo->query("
    SELECT tr.*, 
           (SELECT COUNT(*) FROM player_registrations WHERE team_registration_id = tr.id) as player_count
    FROM team_registrations tr
    WHERE tr.status IN ('approved', 'rejected')
    ORDER BY tr.updated_at DESC
    LIMIT 20
")->fetchAll();

// フィルタ用
$filter = $_GET['filter'] ?? 'pending';
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a2e;
            color: #e0e0e0;
            min-height: 100vh;
        }

        /* ヘッダー */
        .admin-header {
            background: linear-gradient(135deg, #0d1b3e 0%, #1a0d3e 100%);
            border-bottom: 1px solid rgba(102,126,234,0.3);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-header h1 {
            font-size: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .admin-header .user-info {
            font-size: 13px;
            color: #888;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        /* フラッシュメッセージ */
        .flash {
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .flash.success {
            background: rgba(39,174,96,0.15);
            border: 1px solid rgba(39,174,96,0.4);
            color: #2ecc71;
        }
        .flash.error {
            background: rgba(231,76,60,0.15);
            border: 1px solid rgba(231,76,60,0.4);
            color: #e74c3c;
        }
        .flash.warning {
            background: rgba(243,156,18,0.15);
            border: 1px solid rgba(243,156,18,0.4);
            color: #f39c12;
        }

        /* 統計カード */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .stat-card .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .stat-card .stat-label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-card.pending .stat-number { color: #f39c12; }
        .stat-card.approved .stat-number { color: #2ecc71; }
        .stat-card.rejected .stat-number { color: #e74c3c; }
        .stat-card.total .stat-number { color: #667eea; }

        /* タブ */
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 12px;
        }
        .tab {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            text-decoration: none;
            color: #888;
            transition: all 0.2s;
        }
        .tab:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .tab.active {
            color: #fff;
            background: rgba(102,126,234,0.2);
            border: 1px solid rgba(102,126,234,0.4);
        }
        .tab .badge {
            display: inline-block;
            background: #e74c3c;
            color: #fff;
            font-size: 11px;
            padding: 2px 7px;
            border-radius: 10px;
            margin-left: 6px;
            font-weight: 600;
        }

        /* チームカード */
        .team-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            margin-bottom: 20px;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .team-card:hover {
            border-color: rgba(102,126,234,0.3);
        }
        .team-card-header {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            cursor: pointer;
        }
        .team-card-header:hover {
            background: rgba(255,255,255,0.02);
        }
        .team-info h3 {
            font-size: 18px;
            color: #fff;
            margin-bottom: 6px;
        }
        .team-info .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            font-size: 13px;
            color: #888;
        }
        .team-info .meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .team-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .team-badge.pending {
            background: rgba(243,156,18,0.15);
            color: #f39c12;
            border: 1px solid rgba(243,156,18,0.3);
        }
        .team-badge.approved {
            background: rgba(39,174,96,0.15);
            color: #2ecc71;
            border: 1px solid rgba(39,174,96,0.3);
        }
        .team-badge.rejected {
            background: rgba(231,76,60,0.15);
            color: #e74c3c;
            border: 1px solid rgba(231,76,60,0.3);
        }

        /* 展開エリア */
        .team-card-body {
            display: none;
            padding: 20px 24px;
        }
        .team-card-body.open {
            display: block;
        }

        /* 選手テーブル */
        .player-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .player-table th {
            text-align: left;
            padding: 8px 12px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #667eea;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .player-table td {
            padding: 8px 12px;
            font-size: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .player-table tr:hover td {
            background: rgba(255,255,255,0.02);
        }
        .player-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: rgba(102,126,234,0.15);
            border: 1px solid rgba(102,126,234,0.3);
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            color: #667eea;
        }
        .pos-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(255,255,255,0.06);
            color: #aaa;
        }

        /* アクションエリア */
        .action-area {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .action-group {
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }
        .action-group label {
            font-size: 12px;
            color: #888;
            display: block;
            margin-bottom: 4px;
        }
        .action-group select, .action-group input[type="text"] {
            padding: 8px 12px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            outline: none;
        }
        .action-group select option {
            background: #1a1a3e;
        }
        .action-group input[type="text"] {
            width: 280px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-approve {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: #fff;
        }
        .btn-approve:hover {
            box-shadow: 0 4px 15px rgba(39,174,96,0.4);
            transform: translateY(-1px);
        }
        .btn-reject {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            color: #fff;
        }
        .btn-reject:hover {
            box-shadow: 0 4px 15px rgba(231,76,60,0.4);
            transform: translateY(-1px);
        }

        /* 処理済みリスト */
        .processed-note {
            font-size: 13px;
            color: #f39c12;
            margin-top: 4px;
        }

        /* 空状態 */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #555;
        }
        .empty-state .icon { font-size: 48px; margin-bottom: 12px; }
        .empty-state h3 { font-size: 18px; color: #888; margin-bottom: 8px; }

        /* レスポンシブ */
        @media (max-width: 768px) {
            .container { padding: 16px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .action-area { flex-direction: column; align-items: stretch; }
            .action-group input[type="text"] { width: 100%; }
            .team-card-header { flex-direction: column; gap: 10px; }
            .player-table { font-size: 12px; }
            .player-table th, .player-table td { padding: 6px 8px; }
        }
    </style>
</head>
<body>

<!-- ヘッダー -->
<div class="admin-header">
    <h1>🏀 B2L 管理画面</h1>
    <div class="user-info">管理者: <?= htmlspecialchars($_SERVER['PHP_AUTH_USER']) ?></div>
</div>

<div class="container">

    <!-- フラッシュメッセージ -->
    <?php if ($flash['message']): ?>
        <div class="flash <?= $flash['type'] ?>">
            <?php
                $icons = ['success' => '✅', 'error' => '❌', 'warning' => '⚠️'];
                echo ($icons[$flash['type']] ?? 'ℹ️') . ' ';
            ?>
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- 統計ダッシュボード -->
    <div class="stats-grid">
        <div class="stat-card pending">
            <div class="stat-number"><?= intval($stats['pending_count']) ?></div>
            <div class="stat-label">保留中</div>
        </div>
        <div class="stat-card approved">
            <div class="stat-number"><?= intval($stats['approved_count']) ?></div>
            <div class="stat-label">承認済</div>
        </div>
        <div class="stat-card rejected">
            <div class="stat-number"><?= intval($stats['rejected_count']) ?></div>
            <div class="stat-label">却下</div>
        </div>
        <div class="stat-card total">
            <div class="stat-number"><?= intval($totalPlayers) ?></div>
            <div class="stat-label">総選手数</div>
        </div>
    </div>

    <!-- タブ -->
    <div class="tabs">
        <a href="?filter=pending" class="tab <?= $filter === 'pending' ? 'active' : '' ?>">
            保留中
            <?php if ($stats['pending_count'] > 0): ?>
                <span class="badge"><?= intval($stats['pending_count']) ?></span>
            <?php endif; ?>
        </a>
        <a href="?filter=processed" class="tab <?= $filter === 'processed' ? 'active' : '' ?>">処理済み</a>
    </div>

    <!-- ============================== -->
    <!-- 保留中タブ -->
    <!-- ============================== -->
    <?php if ($filter === 'pending'): ?>

        <?php if (empty($pendingTeams)): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>保留中の登録はありません</h3>
                <p>新しいチーム登録があるとここに表示されます。</p>
            </div>
        <?php else: ?>
            <?php foreach ($pendingTeams as $team): ?>
                <div class="team-card" id="card-<?= $team['id'] ?>">
                    <div class="team-card-header" onclick="toggleCard(<?= $team['id'] ?>)">
                        <div class="team-info">
                            <h3><?= htmlspecialchars($team['team_name']) ?></h3>
                            <div class="meta">
                                <span>👤 <?= htmlspecialchars($team['representative_name'] ?? '未設定') ?></span>
                                <span>📧 <?= htmlspecialchars($team['email'] ?? '未設定') ?></span>
                                <?php if (!empty($team['phone'])): ?>
                                    <span>📱 <?= htmlspecialchars($team['phone']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($team['line_name'])): ?>
                                    <span>💬 <?= htmlspecialchars($team['line_name']) ?></span>
                                <?php endif; ?>
                                <span>🏃 選手 <?= $team['player_count'] ?>名</span>
                                <span>📅 <?= date('Y/m/d H:i', strtotime($team['created_at'])) ?></span>
                            </div>
                        </div>
                        <span class="team-badge pending">保留中</span>
                    </div>

                    <div class="team-card-body" id="body-<?= $team['id'] ?>">
                        <!-- 選手一覧テーブル -->
                        <?php if (!empty($pendingPlayers[$team['id']])): ?>
                            <table class="player-table">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>選手名</th>
                                        <th>ポジション</th>
                                        <th>身長</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingPlayers[$team['id']] as $p): ?>
                                        <tr>
                                            <td><span class="player-number"><?= intval($p['number']) ?></span></td>
                                            <td><?= htmlspecialchars($p['name']) ?></td>
                                            <td><span class="pos-badge"><?= htmlspecialchars($p['position'] ?? '-') ?></span></td>
                                            <td><?= $p['height'] ? number_format($p['height'], 1) . ' cm' : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color:#888; margin-bottom:16px;">選手データがありません。</p>
                        <?php endif; ?>

                        <!-- アクションエリア -->
                        <div class="action-area">
                            <!-- 承認フォーム -->
                            <form method="POST" style="display:flex; gap:8px; align-items:flex-end;" 
                                  onsubmit="return confirm('「<?= htmlspecialchars(addslashes($team['team_name'])) ?>」を承認します。よろしいですか？')">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="team_registration_id" value="<?= $team['id'] ?>">
                                <div class="action-group">
                                    <div>
                                        <label>部門</label>
                                        <select name="division">
                                            <option value="1">1部</option>
                                            <option value="2">2部</option>
                                            <option value="3" selected>3部</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-approve">✅ 承認</button>
                            </form>

                            <!-- 却下フォーム -->
                            <form method="POST" style="display:flex; gap:8px; align-items:flex-end;"
                                  onsubmit="return validateReject(this)">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="team_registration_id" value="<?= $team['id'] ?>">
                                <div class="action-group">
                                    <div>
                                        <label>却下理由（必須）</label>
                                        <input type="text" name="admin_note" placeholder="却下理由を入力...">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-reject">❌ 却下</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <!-- ============================== -->
    <!-- 処理済みタブ -->
    <!-- ============================== -->
    <?php elseif ($filter === 'processed'): ?>

        <?php if (empty($processedTeams)): ?>
            <div class="empty-state">
                <div class="icon">📋</div>
                <h3>処理済みの登録はありません</h3>
            </div>
        <?php else: ?>
            <?php foreach ($processedTeams as $team): ?>
                <div class="team-card">
                    <div class="team-card-header" onclick="toggleCard(<?= $team['id'] ?>)">
                        <div class="team-info">
                            <h3><?= htmlspecialchars($team['team_name']) ?></h3>
                            <div class="meta">
                                <span>👤 <?= htmlspecialchars($team['representative_name'] ?? '未設定') ?></span>
                                <span>🏃 選手 <?= $team['player_count'] ?>名</span>
                                <span>📅 <?= date('Y/m/d H:i', strtotime($team['updated_at'] ?? $team['created_at'])) ?></span>
                            </div>
                            <?php if ($team['status'] === 'rejected' && !empty($team['admin_note'])): ?>
                                <div class="processed-note">💬 却下理由: <?= htmlspecialchars($team['admin_note']) ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="team-badge <?= $team['status'] ?>">
                            <?= $team['status'] === 'approved' ? '承認済' : '却下' ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
// カード展開/折りたたみ
function toggleCard(id) {
    const body = document.getElementById('body-' + id);
    if (body) {
        body.classList.toggle('open');
    }
}

// 却下バリデーション
function validateReject(form) {
    const note = form.querySelector('input[name="admin_note"]').value.trim();
    if (note === '') {
        alert('却下理由を入力してください。');
        return false;
    }
    return confirm('本当に却下しますか？');
}

// 保留中がある場合、最初のカードを自動展開
document.addEventListener('DOMContentLoaded', function() {
    const firstBody = document.querySelector('.team-card-body');
    if (firstBody) {
        firstBody.classList.add('open');
    }
});
</script>

</body>
</html>
