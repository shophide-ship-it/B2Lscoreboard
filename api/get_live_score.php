<?php
/**
 * API: リアルタイムスコア取得エンドポイント
 * 
 * 用途: 公開スコアボード用のリアルタイムスコア情報取得
 * リクエスト例:
 *   GET /api/get_live_score.php?game_id=123
 * 
 * レスポンス:
 *   {
 *     "game_id": 123,
 *     "home_team": {
 *       "id": 1,
 *       "name": "Team A",
 *       "short_name": "A",
 *       "score": 45,
 *       "fouls": 3
 *     },
 *     "away_team": {
 *       "id": 2,
 *       "name": "Team B",
 *       "short_name": "B",
 *       "score": 42,
 *       "fouls": 4
 *     },
 *     "game_info": {
 *       "quarter": 2,
 *       "minute_in_quarter": 7,
 *       "possession_team_id": 1,
 *       "status": "live"
 *     },
 *     "last_events": [
 *       {
 *         "event_type": "score",
 *         "player_name": "山田太郎",
 *         "points": 2,
 *         "timestamp": "2026-04-27 14:32:15"
 *       }
 *     ]
 *   }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config.php';

try {
    $game_id = (int)($_GET['game_id'] ?? 0);
    if ($game_id <= 0) {
        throw new Exception('game_id パラメータが必須です');
    }

    $pdo = getDB();

    // ==========================================
    // 1. 試合情報を取得
    // ==========================================
    $game = $pdo->prepare("
        SELECT g.*, ht.id as home_id, ht.name as home_name, ht.short_name as home_short,
               at.id as away_id, at.name as away_name, at.short_name as away_short
        FROM games g
        JOIN teams ht ON g.home_team_id = ht.id
        JOIN teams at ON g.away_team_id = at.id
        WHERE g.id = ?
    ")->execute([$game_id]) ? $pdo->query("
        SELECT g.*, ht.id as home_id, ht.name as home_name, ht.short_name as home_short,
               at.id as away_id, at.name as away_name, at.short_name as away_short
        FROM games g
        JOIN teams ht ON g.home_team_id = ht.id
        JOIN teams at ON g.away_team_id = at.id
        WHERE g.id = $game_id
    ")->fetch() : null;

    if (!$game) {
        throw new Exception('試合が見つかりません');
    }

    // ==========================================
    // 2. ライブスコア状態を取得
    // ==========================================
    $liveScore = $pdo->query("SELECT * FROM live_score_state WHERE game_id = $game_id")->fetch();
    if (!$liveScore) {
        // ライブスコア初期化
        $pdo->prepare("INSERT INTO live_score_state (game_id) VALUES (?)")->execute([$game_id]);
        $liveScore = $pdo->query("SELECT * FROM live_score_state WHERE game_id = $game_id")->fetch();
    }

    // ==========================================
    // 3. 各チームのファウル数を取得
    // ==========================================
    $homeTeamFouls = $pdo->query("
        SELECT COUNT(*) as foul_count FROM game_events 
        WHERE game_id = $game_id AND event_type = 'foul' AND team_id = {$game['home_id']}
    ")->fetch()['foul_count'] ?? 0;

    $awayTeamFouls = $pdo->query("
        SELECT COUNT(*) as foul_count FROM game_events 
        WHERE game_id = $game_id AND event_type = 'foul' AND team_id = {$game['away_id']}
    ")->fetch()['foul_count'] ?? 0;

    // ==========================================
    // 4. 最新イベント（最後の5件）を取得
    // ==========================================
    $lastEvents = $pdo->query("
        SELECT ge.event_type, ge.event_timestamp, p.name as player_name, 
               (ge.event_data->>'$.points') as points
        FROM game_events ge
        LEFT JOIN players p ON ge.player_id = p.id
        WHERE ge.game_id = $game_id
        ORDER BY ge.created_at DESC
        LIMIT 5
    ")->fetchAll();

    // ==========================================
    // 5. レスポンス作成
    // ==========================================
    echo json_encode([
        'success' => true,
        'game_id' => $game_id,
        'home_team' => [
            'id' => $game['home_id'],
            'name' => $game['home_name'],
            'short_name' => $game['home_short'],
            'score' => (int)$liveScore['home_score'],
            'fouls' => (int)$homeTeamFouls
        ],
        'away_team' => [
            'id' => $game['away_id'],
            'name' => $game['away_name'],
            'short_name' => $game['away_short'],
            'score' => (int)$liveScore['away_score'],
            'fouls' => (int)$awayTeamFouls
        ],
        'game_info' => [
            'quarter' => (int)$liveScore['quarter'],
            'minute_in_quarter' => (int)$liveScore['minute_in_quarter'],
            'possession_team_id' => $liveScore['possession_team_id'],
            'status' => $game['status'],
            'division' => $game['division']
        ],
        'last_events' => array_map(function($e) {
            return [
                'event_type' => $e['event_type'],
                'player_name' => $e['player_name'],
                'points' => $e['points'],
                'timestamp' => $e['event_timestamp']
            ];
        }, $lastEvents),
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
