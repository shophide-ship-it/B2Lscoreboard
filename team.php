<?php
// /b2l/register/team.php
require_once __DIR__ . '/../admin/config.php';

$pdo = getDB();
$token = $_GET['token'] ?? '';
$message = '';
$messageType = '';
$step = 'team_info'; // team_info → players → done

if (empty($token)) {
    die('無効なURLです。');
}

// チーム取得
$stmt = $pdo->prepare("SELECT * FROM teams WHERE token = ?");
$stmt->execute([$token]);
$team = $stmt->fetch();

if (!$team) {
    die('無効なURLです。チームが見つかりません。');
}

// チーム情報が入力済みならステップ判定
$hasTeamInfo = !empty($team['rep_name']);

// 選手登録済みチェック
$stmt = $pdo->prepare("SELECT COUNT(*) FROM player_registrations WHERE team_id = ?");
$stmt->execute([$team['id']]);
$playerCount = $stmt->fetchColumn();

if ($hasTeamInfo && $playerCount > 0) {
    $step = 'done';
} elseif ($hasTeamInfo) {
    $step = 'players';
}

// === チーム情報保存 ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'save_team_info') {
        $short_name = trim($_POST['short_name'] ?? '');
        $rep_name = trim($_POST['rep_name'] ?? '');
        $rep_phone = trim($_POST['rep_phone'] ?? '');
        $rep_line_name = trim($_POST['rep_line_name'] ?? '');
        $primary_color = trim($_POST['primary_color'] ?? '');
        $secondary_color = trim($_POST['secondary_color'] ?? '');
        
        if (empty($short_name) || empty($rep_name) || empty($rep_phone)) {
            $message = '必須項目を入力してください';
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE teams SET short_name = ?, rep_name = ?, rep_phone = ?, rep_line_name = ?, primary_color = ?, secondary_color = ? WHERE id = ?");
            $stmt->execute([$short_name, $rep_name, $rep_phone, $rep_line_name, $primary_color, $secondary_color, $team['id']]);
            
            // チーム情報を再取得
            $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
            $stmt->execute([$team['id']]);
            $team = $stmt->fetch();
            
            $step = 'players';
            $message = 'チーム情報を保存しました。続けて選手を登録してください。';
            $messageType = 'success';
        }
    }
    
    if ($_POST['action'] === 'save_players') {
        $numbers = $_POST['number'] ?? [];
        $names = $_POST['player_name'] ?? [];
        $kanas = $_POST['kana'] ?? [];
        
        $validPlayers = [];
        for ($i = 0; $i < count($names); $i++) {
            $num = trim($numbers[$i] ?? '');
            $name = trim($names[$i] ?? '');
            $kana = trim($kanas[$i] ?? '');
            if (!empty($name) && $num !== '') {
                $validPlayers[] = ['number' => (int)$num, 'name' => $name, 'kana' => $kana];
            }
        }
        
        if (count($validPlayers) < 5) {
            $message = '最低5名の選手を登録してください';
            $messageType = 'error';
            $step = 'players';
        } elseif (count($validPlayers) > 15) {
            $message = '選手は最大15名までです';
            $messageType = 'error';
            $step = 'players';
        } else {
            // 背番号重複チェック
            $nums = array_column($validPlayers, 'number');
            if (count($nums) !== count(array_unique($nums))) {
                $message = '背番号が重複しています';
                $messageType = 'error';
                $step = 'players';
            } else {
                // 既存の申請を削除して再登録
                $pdo->prepare("DELETE FROM player_registrations WHERE team_id = ?")->execute([$team['id']]);
                
                $stmt = $pdo->prepare("INSERT INTO player_registrations (team_id, number, name, kana, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
                foreach ($validPlayers as $p) {
                    $stmt->execute([$team['id'], $p['number'], $p['name'], $p['kana']]);
                }
                
                $playerCount = count($validPlayers);
                $step = 'done';
                $message = count($validPlayers) . '名の選手を申請しました';
                $messageType = 'success';
            }
        }
    }
}

// 既存の申請データ取得（編集時用）
$existingPlayers = [];
if ($step === 'done') {
    $stmt = $pdo->prepare("SELECT * FROM player_registrations WHERE team_id = ? ORDER BY number");
    $stmt->execute([$team['id']]);
    $existingPlayers = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($team['name']) ?> - B2L LEAGUE 登録</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Helvetica Neue', Arial, 'Hiragino Sans', sans-serif; background: #1a1a2e; color: #fff; min-height: 100vh; }
.header { background: linear-gradient(135deg, #667eea, #764ba2); padding: 20px; text-align: center; }
.header h1 { font-size: 20px; }
.header .team-name-display { font-size: 28px; font-weight: bold; margin-top: 8px; }
.header p { opacity: 0.8; margin-top: 4px; font-size: 14px; }

.container { max-width: 600px; margin: 0 auto; padding: 20px; }

.message { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
.message.success { background: rgba(46,204,113,0.2); border: 1px solid #2ecc71; color: #2ecc71; }
.message.error { background: rgba(231,76,60,0.2); border: 1px solid #e74c3c; color: #e74c3c; }

/* ステップインジケーター */
.steps { display: flex; margin-bottom: 24px; }
.step { flex: 1; text-align: center; padding: 12px 8px; font-size: 13px; font-weight: bold; position: relative; }
.step::after { content:''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: rgba(255,255,255,0.1); }
.step.active::after { background: #667eea; }
.step.done::after { background: #2ecc71; }
.step.active { color: #667eea; }
.step.done { color: #2ecc71; }
.step.pending { opacity: 0.4; }

/* フォーム */
.form-card { background: rgba(255,255,255,0.08); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
.form-card h2 { font-size: 18px; margin-bottom: 16px; }
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 13px; opacity: 0.7; margin-bottom: 4px; }
.form-group label .required { color: #e74c3c; }
.form-group input, .form-group select { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: #fff; font-size: 15px; }
.form-group input::placeholder { color: rgba(255,255,255,0.3); }
.form-row-2 { display: flex; gap: 12px; }
.form-row-2 .form-group { flex: 1; }

.color-options { display: flex; flex-wrap: wrap; gap: 8px; }
.color-option { width: 36px; height: 36px; border-radius: 50%; cursor: pointer; border: 3px solid transparent; transition: 0.2s; }
.color-option:hover { transform: scale(1.1); }
.color-option.selected { border-color: #fff; box-shadow: 0 0 10px rgba(255,255,255,0.5); }
input[name="primary_color"], input[name="secondary_color"] { display: none; }

/* 選手入力テーブル */
.player-table { width: 100%; border-collapse: collapse; }
.player-table th { font-size: 12px; opacity: 0.6; padding: 8px 4px; text-align: left; }
.player-table td { padding: 4px; }
.player-table input { width: 100%; padding: 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1); color: #fff; font-size: 14px; }
.player-table input::placeholder { color: rgba(255,255,255,0.3); }
.player-table .num-input { width: 60px; text-align: center; }
.row-number { text-align: center; opacity: 0.4; font-size: 13px; width: 30px; }

.btn-primary { display: block; width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 16px; }
.btn-primary:hover { opacity: 0.9; }

.btn-secondary { display: block; width: 100%; padding: 12px; background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 10px; font-size: 14px; cursor: pointer; margin-top: 10px; }
.btn-secondary:hover { background: rgba(255,255,255,0.15); }

/* 完了画面 */
.done-card { background: rgba(46,204,113,0.1); border: 1px solid #2ecc71; border-radius: 12px; padding: 24px; text-align: center; }
.done-card .icon { font-size: 48px; margin-bottom: 12px; }
.done-card h2 { color: #2ecc71; margin-bottom: 8px; }
.done-card p { opacity: 0.7; font-size: 14px; }

.player-summary { background: rgba(255,255,255,0.06); border-radius: 10px; margin-top: 16px; overflow: hidden; }
.player-summary table { width: 100%; border-collapse: collapse; }
.player-summary th { background: rgba(255,255,255,0.05); padding: 8px 12px; font-size: 12px; text-align: left; }
.player-summary td { padding: 8px 12px; font-size: 14px; border-top: 1px solid rgba(255,255,255,0.05); }
.player-summary .num { text-align: center; font-weight: bold; }

.note
