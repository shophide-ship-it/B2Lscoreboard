<?php
/**
 * API: イベント記録エンドポイント
 * 
 * 用途: 試合中にボタンタップで各種イベント（得点、ファウル、交代など）を記録
 * リクエスト例:
 *   POST /api/record_event.php
 *   {
 *     "session_token": "abc123xyz",
 *     "event_type": "score",
 *     "player_id": 15,
 *     "team_id": 3,
 *     "quarter": 2,
 *     "minute_in_quarter": 7,
 *     "event_data": {"points": 2}
 *   }
 * 
 * レスポンス:
 *   {
 *     "success": true,
 *     "event_id": 1234,
 *     "message": "得点を記録しました"
 *   }
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('無効なリクエスト形式です');
    }

    // ==========================================
    // 1. セッション検証
    // ==========================================
    $session_token = $input['session_token'] ?? '';
    $pdo = getDB();
    
    $session = $pdo->prepare("
        SELECT id, game_id, team_id, role FROM live_stat_sessions 
        WHERE session_token = ? AND login_status = 'active'
    ")->execute([$session_token]) ? 
    $pdo->query("SELECT id, game_id, team_id, role FROM live_stat_sessions WHERE session_token = '$session_token'")->fetch() : 
    null;

    if (!$session) {
        throw new Exception('セッションが無効です（タイムアウトした可能性があります）');
    }

    // ==========================================
    // 2. 入力値の検証
    // ==========================================
    $event_type = $input['event_type'] ?? '';
    $player_id = isset($input['player_id']) ? (int)$input['player_id'] : null;
    $team_id = (int)($input['team_id'] ?? 0);
    $quarter = (int)($input['quarter'] ?? 1);
    $minute_in_quarter = (int)($input['minute_in_quarter'] ?? 0);
    $event_data = $input['event_data'] ?? [];

    $valid_events = ['score', 'foul', 'substitution', 'rebound', 'assist', 'steal', 'block', 'turnover', 'timeout'];
    if (!in_array($event_type, $valid_events)) {
        throw new Exception('無効なイベントタイプです: ' . $event_type);
    }

    // ==========================================
    // 3. イベント記録
    // ==========================================
    $stmt = $pdo->prepare("
        INSERT INTO game_events (
            game_id, event_type, player_id, team_id, recorded_by_session_id,
            quarter, minute_in_quarter, event_data, confirmed, event_timestamp
        ) VALUES (
            :game_id, :event_type, :player_id, :team_id, :session_id,
            :quarter, :minute_in_quarter, :event_data, 1, NOW()
        )
    ");

    $stmt->execute([
        ':game_id' => $session['game_id'],
        ':event_type' => $event_type,
        ':player_id' => $player_id,
        ':team_id' => $team_id,
        ':session_id' => $session['id'],
        ':quarter' => $quarter,
        ':minute_in_quarter' => $minute_in_quarter,
        ':event_data' => json_encode($event_data, JSON_UNESCAPED_UNICODE)
    ]);

    $event_id = $pdo->lastInsertId();

    // ==========================================
    // 4. スコア状態を自動更新（イベントタイプ別）
    // ==========================================
    switch ($event_type) {
        case 'score':
            $points = $event_data['points'] ?? 2;
            if ($team_id == $pdo->query("SELECT home_team_id FROM games WHERE id = {$session['game_id']}")->fetchColumn()) {
                $pdo->prepare("UPDATE live_score_state SET home_score = home_score + ?, last_event_id = ? WHERE game_id = ?")->execute([$points, $event_id, $session['game_id']]);
            } else {
                $pdo->prepare("UPDATE live_score_state SET away_score = away_score + ?, last_event_id = ? WHERE game_id = ?")->execute([$points, $event_id, $session['game_id']]);
            }
            break;

        case 'foul':
            $pdo->prepare("UPDATE player_foul_log SET foul_number = foul_number + 1 WHERE game_id = ? AND player_id = ?")->execute([$session['game_id'], $player_id]);
            $foul_count = $pdo->query("SELECT foul_number FROM player_foul_log WHERE game_id = {$session['game_id']} AND player_id = $player_id")->fetchColumn();
            if ($foul_count >= 5) {
                $pdo->prepare("UPDATE player_foul_log SET fouled_out = 1 WHERE game_id = ? AND player_id = ?")->execute([$session['game_id'], $player_id]);
            }
            break;

        case 'substitution':
            // player_out と player_in を event_data から取得して処理
            break;
    }

    // ==========================================
    // 5. WebSocket 通知（実装時）
    // ==========================================
    // ここで WebSocket を使用して他のクライアントに通知
    // または SSE (Server-Sent Events) を使用

    // レスポンス
    echo json_encode([
        'success' => true,
        'event_id' => $event_id,
        'event_type' => $event_type,
        'message' => 'イベントを記録しました',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
