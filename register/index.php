<?php
// /b2l/register/index.php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// --- DB接続 ---
$db_host = 'mysql80.kasugai-sp.sakura.ne.jp';
$db_name = 'kasugai-sp_b2l';
$db_user = 'kasugai-sp';
$db_pass = 'X_MJJk5CfDwv4nf';

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

// --- 締切チェック関数 ---
function isRegistrationClosed($pdo) {
    $stmt = $pdo->query("SELECT MIN(game_datetime) as next_game FROM game_schedule WHERE game_datetime > NOW()");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['next_game']) {
        $deadline = strtotime($row['next_game']) - (36 * 3600);
        if (time() > $deadline) {
            return true;
        }
    }
    return false;
}

$closed = isRegistrationClosed($pdo);
$success = false;
$error = '';

// --- フォーム送信処理 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$closed) {
    try {
        $pdo->beginTransaction();

        // チーム情報を登録
        $stmt = $pdo->prepare("
            INSERT INTO team_registrations (team_name, representative_name, email, phone, line_name, status)
            VALUES (:team_name, :rep_name, :email, :phone, :line_name, 'pending')
        ");
        $stmt->execute([
            ':team_name' => trim($_POST['team_name'] ?? ''),
            ':rep_name'  => trim($_POST['representative_name'] ?? ''),
            ':email'     => trim($_POST['email'] ?? ''),
            ':phone'     => trim($_POST['phone'] ?? ''),
            ':line_name' => trim($_POST['line_name'] ?? ''),
        ]);
        $teamRegId = $pdo->lastInsertId();

        // 選手情報を登録
        $playerCount = 0;
        if (isset($_POST['players']) && is_array($_POST['players'])) {
            $stmtPlayer = $pdo->prepare("
                INSERT INTO player_registrations (team_registration_id, number, name, position, height)
                VALUES (:team_reg_id, :number, :name, :position, :height)
            ");
            foreach ($_POST['players'] as $player) {
                $name = trim($player['name'] ?? '');
                if ($name === '') continue;
                $stmtPlayer->execute([
                    ':team_reg_id' => $teamRegId,
                    ':number'      => intval($player['number'] ?? 0),
                    ':name'        => $name,
                    ':position'    => $player['position'] ?? 'PG',
                    ':height'      => floatval($player['height'] ?? 0),
                ]);
                $playerCount++;
            }
        }

        if ($playerCount === 0) {
            throw new Exception('最低1名の選手を登録してください。');
        }

        $pdo->commit();
        $success = true;

        // LINE通知（管理者へ）
        notifyAdmin($teamRegId, trim($_POST['team_name'] ?? ''), $playerCount);

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// --- LINE通知関数 ---
function notifyAdmin($regId, $teamName, $playerCount) {
    $token = 'kbZCHXeFaL7WyqEPU/MW45EnWweTNjTDkKkMXlT+Cf2qzyrDkG3v9EG2+lFPY0Xc9uJZznCnMd6ERm/gLZRBy7Oq8M15DP66qRt/B2K1IPKFjZgGb2S9TogAJM/rlNMkNcX0C1i8f2Cqsvi4z6UydQdB04t89/1O/w1cDnyilFU=';
    $message = "【B2L 新規チーム登録】\n"
             . "チーム名: {$teamName}\n"
             . "選手数: {$playerCount}名\n"
             . "登録ID: #{$regId}\n"
             . "管理画面で確認してください。";

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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2L チーム登録</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0a0a2e;
            color: #fff;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 30px 0 20px;
        }
        .header h1 {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        .header p { color: #888; font-size: 14px; }

        .closed-banner {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin: 40px 0;
        }
        .closed-banner h2 { font-size: 20px; margin-bottom: 8px; }

        .success-banner {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin: 40px 0;
        }

        .error-banner {
            background: rgba(231,76,60,0.2);
            border: 1px solid #e74c3c;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #e74c3c;
        }

        .section {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .section h2 {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .form-grid.full { grid-template-columns: 1fr; }

        .form-group { margin-bottom: 12px; }
        .form-group label {
            display: block;
            font-size: 13px;
            color: #aaa;
            margin-bottom: 4px;
        }
        .form-group label .required {
            color: #e74c3c;
            margin-left: 4px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: border 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #667eea;
        }
        .form-group select option { background: #1a1a3e; color: #fff; }

        .player-row {
            display: grid;
            grid-template-columns: 60px 1fr 90px 80px 40px;
            gap: 8px;
            align-items: end;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .player-row:first-child { padding-top: 0; }
        .player-row label { font-size: 11px; color: #888; }
        .player-row input, .player-row select {
            width: 100%;
            padding: 8px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 6px;
            color: #fff;
            font-size: 14px;
            outline: none;
        }
        .player-row input:focus, .player-row select:focus {
            border-color: #667eea;
        }
        .player-row select option { background: #1a1a3e; }

        .btn-remove {
            background: rgba(231,76,60,0.2);
            border: 1px solid rgba(231,76,60,0.4);
            color: #e74c3c;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            padding: 8px;
            transition: background 0.2s;
        }
        .btn-remove:hover { background: rgba(231,76,60,0.4); }

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(102,126,234,0.15);
            border: 1px dashed rgba(102,126,234,0.4);
            color: #667eea;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 12px;
            transition: background 0.2s;
        }
        .btn-add:hover { background: rgba(102,126,234,0.25); }

        .player-count {
            font-size: 13px;
            color: #888;
            margin-top: 8px;
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(102,126,234,0.4);
        }
        .btn-submit:active { transform: translateY(0); }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .player-row {
                grid-template-columns: 50px 1fr 80px;
                gap: 6px;
            }
            .player-row .pos-col, .player-row .height-col {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>? B2L LEAGUE</h1>
        <p>チーム・選手登録フォーム</p>
    </div>

    <?php if ($closed): ?>
        <div class="closed-banner">
            <h2>? 登録受付は締め切りました</h2>
            <p>次の試合開始36時間前のため、現在登録を受け付けておりません。</p>
        </div>

    <?php elseif ($success): ?>
        <div class="success-banner">
            <h2>? 登録が完了しました！</h2>
            <p>管理者の承認後、正式に登録されます。<br>結果はLINEでお知らせします。</p>
        </div>

    <?php else: ?>
        <?php if ($error): ?>
            <div class="error-banner">?? <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="regForm">
            <!-- チーム情報 -->
            <div class="section">
                <h2>? チーム情報</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>チーム名<span class="required">*</span></label>
                        <input type="text" name="team_name" required placeholder="例: B2L Warriors">
                    </div>
                    <div class="form-group">
                        <label>代表者名<span class="required">*</span></label>
                        <input type="text" name="representative_name" required placeholder="例: 山田太郎">
                    </div>
                    <div class="form-group">
                        <label>メールアドレス<span class="required">*</span></label>
                        <input type="email" name="email" required placeholder="例: taro@example.com">
                    </div>
                    <div class="form-group">
                        <label>電話番号</label>
                        <input type="tel" name="phone" placeholder="例: 090-1234-5678">
                    </div>
                </div>
                <div class="form-group">
                    <label>LINE名</label>
                    <input type="text" name="line_name" placeholder="例: taro_yamada">
                </div>
            </div>

            <!-- 選手情報 -->
            <div class="section">
                <h2>? 選手情報（最大30名）</h2>
                <div id="playerList">
                    <!-- 初期5行 -->
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <div class="player-row" data-index="<?= $i ?>">
                        <div>
                            <?php if ($i === 0): ?><label>背番号</label><?php endif; ?>
                            <input type="number" name="players[<?= $i ?>][number]" min="0" max="99" placeholder="No.">
                        </div>
                        <div>
                            <?php if ($i === 0): ?><label>選手名</label><?php endif; ?>
                            <input type="text" name="players[<?= $i ?>][name]" placeholder="選手名">
                        </div>
                        <div>
                            <?php if ($i === 0): ?><label>ポジション</label><?php endif; ?>
                            <select name="players[<?= $i ?>][position]">
                                <option value="PG">PG</option>
                                <option value="SG">SG</option>
                                <option value="SF">SF</option>
                                <option value="PF">PF</option>
                                <option value="C">C</option>
                            </select>
                        </div>
                        <div>
                            <?php if ($i === 0): ?><label>身長cm</label><?php endif; ?>
                            <input type="number" name="players[<?= $i ?>][height]" min="100" max="250" step="0.1" placeholder="175">
                        </div>
                        <div>
                            <?php if ($i === 0): ?><label>&nbsp;</label><?php endif; ?>
                            <button type="button" class="btn-remove" onclick="removePlayer(this)" title="削除">×</button>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
                <button type="button" class="btn-add" onclick="addPlayer()">＋ 選手を追加</button>
                <div class="player-count" id="playerCount">登録選手数: 5 / 30</div>
            </div>

            <button type="submit" class="btn-submit">? 登録を申請する</button>
        </form>
    <?php endif; ?>
</div>

<script>
let playerIndex = 5;
const maxPlayers = 30;

function addPlayer() {
    const list = document.getElementById('playerList');
    const count = list.querySelectorAll('.player-row').length;
    if (count >= maxPlayers) {
        alert('最大30名までです');
        return;
    }
    const i = playerIndex++;
    const row = document.createElement('div');
    row.className = 'player-row';
    row.dataset.index = i;
    row.innerHTML = `
        <div><input type="number" name="players[${i}][number]" min="0" max="99" placeholder="No."></div>
        <div><input type="text" name="players[${i}][name]" placeholder="選手名"></div>
        <div>
            <select name="players[${i}][position]">
                <option value="PG">PG</option>
                <option value="SG">SG</option>
                <option value="SF">SF</option>
                <option value="PF">PF</option>
                <option value="C">C</option>
            </select>
        </div>
        <div><input type="number" name="players[${i}][height]" min="100" max="250" step="0.1" placeholder="175"></div>
        <div><button type="button" class="btn-remove" onclick="removePlayer(this)" title="削除">×</button></div>
    `;
    list.appendChild(row);
    updateCount();
}

function removePlayer(btn) {
    const list = document.getElementById('playerList');
    if (list.querySelectorAll('.player-row').length <= 1) {
        alert('最低1名は必要です');
        return;
    }
    btn.closest('.player-row').remove();
    updateCount();
}

function updateCount() {
    const count = document.getElementById('playerList').querySelectorAll('.player-row').length;
    document.getElementById('playerCount').textContent = `登録選手数: ${count} / ${maxPlayers}`;
}
</script>
</body>
</html>

