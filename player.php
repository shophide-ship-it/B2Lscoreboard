<?php
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/../config.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    http_response_code(400);
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>Error</title></head><body><p>Invalid access</p></body></html>';
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE token = :token LIMIT 1");
    $stmt->execute([':token' => $token]);
    $team = $stmt->fetch();
} catch (Exception $e) {
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>Error</title></head><body><p>System error</p></body></html>';
    exit;
}

if (!$team) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>Error</title></head><body><p>Team not found</p></body></html>';
    exit;
}

$teamName = htmlspecialchars($team['name'], ENT_QUOTES, 'UTF-8');
$divNum = (int)$team['division'];
$isOpen = isRegistrationOpen();
$deadline = getDeadline();

// Parse deadline for display
$dl = new DateTime($deadline);
$dlYear = $dl->format('Y');
$dlMonth = $dl->format('n');
$dlDay = $dl->format('j');
$dlTime = $dl->format('H:i');

// Get registered players
$stmtP = $pdo->prepare("SELECT * FROM players WHERE team_id = :team_id ORDER BY number ASC");
$stmtP->execute([':team_id' => $team['id']]);
$players = $stmtP->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2L &#36984;&#25163;&#30331;&#37682; - <?= $teamName ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
            background: #f5f0ff;
            color: #333;
            min-height: 100vh;
        }
        .header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: white;
            text-align: center;
            padding: 24px 16px;
        }
        .header h1 { font-size: 18px; margin-bottom: 8px; }
        .header .team-name { font-size: 28px; font-weight: bold; color: #ffd700; }
        .header .division-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 4px 16px;
            border-radius: 20px;
            margin-top: 8px;
            font-size: 14px;
        }
        .container { max-width: 800px; margin: 0 auto; padding: 16px; }
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .deadline-info {
            background: #fff8e1;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 16px;
            text-align: center;
        }
        .deadline-info .date { font-size: 16px; }
        .closed-msg { color: #d32f2f; font-weight: bold; font-size: 16px; margin-top: 8px; }
        .open-msg { color: #2e7d32; font-weight: bold; font-size: 16px; margin-top: 8px; }
        .notice {
            background: #fff8e1;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 16px;
        }
        .notice p { text-align: center; margin: 4px 0; }
        .form-section { margin-top: 8px; }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 6px;
            font-size: 14px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #0f3460;
        }
        .form-row {
            display: grid;
            grid-template-columns: 100px 1fr 1fr;
            gap: 12px;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0f3460, #1a1a2e);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        .player-list { margin-top: 8px; }
        .player-item {
            display: grid;
            grid-template-columns: 50px 1fr 80px 80px;
            gap: 8px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            align-items: center;
            font-size: 14px;
        }
        .player-item:last-child { border-bottom: none; }
        .player-number {
            background: #0f3460;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .status-pending {
            background: #fff3e0;
            color: #e65100;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            text-align: center;
        }
        .status-approved {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            text-align: center;
        }
        .count-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
        }
        #message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
            text-align: center;
            font-weight: bold;
        }
        #message.success { display: block; background: #e8f5e9; color: #2e7d32; }
        #message.error { display: block; background: #ffebee; color: #d32f2f; }
    </style>
</head>
<body>
    <div class="header">
        <h1>&#127936; B2L &#36984;&#25163;&#30331;&#37682;&#12501;&#12457;&#12540;&#12512;</h1>
        <div class="team-name"><?= $teamName ?></div>
        <div class="division-badge"><?= $divNum ?>&#37096;</div>
    </div>

    <div class="container">
        <div class="deadline-info">
            <div class="date">&#9200; &#30331;&#37682;&#32224;&#20999;: <?= $dlYear ?>&#24180;<?= $dlMonth ?>&#26376;<?= $dlDay ?>&#26085; <?= $dlTime ?></div>
            <?php if (!$isOpen): ?>
                <div class="closed-msg">&#9888;&#65039; &#32224;&#20999;&#12434;&#36942;&#12366;&#12390;&#12356;&#12414;&#12377;&#12290;&#26032;&#35215;&#30331;&#37682;&#12399;&#12391;&#12365;&#12414;&#12379;&#12435;&#12290;</div>
            <?php else: ?>
                <div class="open-msg">&#9989; &#30331;&#37682;&#21463;&#20184;&#20013;</div>
            <?php endif; ?>
        </div>

        <div id="message"></div>

        <?php if ($isOpen): ?>
        <div class="card form-section">
            <h2 style="margin-bottom:16px; font-size:18px;">&#128221; &#26032;&#35215;&#36984;&#25163;&#30331;&#37682;</h2>
            <form id="playerForm">
                <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>&#32972;&#30058;&#21495;</label>
                        <input type="number" name="number" id="number" min="0" max="99" required placeholder="0-99">
                    </div>
                    <div class="form-group">
                        <label>&#27663;&#21517;</label>
                        <input type="text" name="name" id="playerName" required placeholder="&#23665;&#30000; &#22826;&#37070;">
                    </div>
                    <div class="form-group">
                        <label>&#12509;&#12472;&#12471;&#12519;&#12531;</label>
                        <select name="position" id="position">
                            <option value="PG">PG</option>
                            <option value="SG">SG</option>
                            <option value="SF">SF</option>
                            <option value="PF">PF</option>
                            <option value="C">C</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn">&#30331;&#37682;&#12377;&#12427;</button>
            </form>
        </div>
        <?php else: ?>
        <div class="notice">
            <p>&#128683; &#29694;&#22312;&#12289;&#26032;&#35215;&#30331;&#37682;&#21463;&#20184;&#26399;&#38291;&#22806;&#12391;&#12377;&#12290;</p>
        </div>
        <?php endif; ?>

        <div class="notice">
            <p>&#9888;&#65039; &#30331;&#37682;&#24460;&#12289;&#31649;&#29702;&#32773;&#12398;&#25215;&#35469;&#12364;&#24517;&#35201;&#12391;&#12377;&#12290;</p>
            <p>&#25215;&#35469;&#12373;&#12428;&#12383;&#36984;&#25163;&#12398;&#12415;&#35430;&#21512;&#12395;&#20986;&#22580;&#12391;&#12365;&#12414;&#12377;&#12290;</p>
        </div>

        <div class="card">
            <h2 style="margin-bottom:8px; font-size:18px;">&#128101; &#30331;&#37682;&#36984;&#25163;&#19968;&#35239;</h2>
            <div class="count-info">&#30331;&#37682;&#20154;&#25968;: <?= count($players) ?>&#21517;</div>
            <?php if (empty($players)): ?>
                <p style="color:#999; text-align:center; padding:20px;">&#12414;&#12384;&#36984;&#25163;&#12364;&#30331;&#37682;&#12373;&#12428;&#12390;&#12356;&#12414;&#12379;&#12435;</p>
            <?php else: ?>
                <div class="player-list">
                    <?php foreach ($players as $p): ?>
                        <div class="player-item">
                            <div class="player-number"><?= (int)$p['number'] ?></div>
                            <div><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div><?= htmlspecialchars($p['position'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div>
                                <?php if ($p['status'] === 'approved'): ?>
                                    <span class="status-approved">&#25215;&#35469;&#28168;&#12415;</span>
                                <?php else: ?>
                                    <span class="status-pending">&#23529;&#26619;&#20013;</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.getElementById('playerForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const msg = document.getElementById('message');
        const form = this;
        const btn = form.querySelector('button[type="submit"]');

        btn.disabled = true;
        msg.className = '';
        msg.style.display = 'none';

        const data = {
            team_id: form.team_id.value,
            token: form.token.value,
            number: form.number.value,
            name: form.playerName.value,
            position: form.position.value
        };

        try {
            const res = await fetch('/b2l/api/players/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();

            if (result.success) {
                msg.textContent = '\u2705 ' + (result.message || 'Registration complete');
                msg.className = 'success';
                setTimeout(() => location.reload(), 1500);
            } else {
                msg.textContent = '\u274c ' + (result.error || 'Error occurred');
                msg.className = 'error';
                btn.disabled = false;
            }
        } catch (err) {
            msg.textContent = '\u274c Communication error';
            msg.className = 'error';
            btn.disabled = false;
        }
    });
    </script>
</body>
</html>
