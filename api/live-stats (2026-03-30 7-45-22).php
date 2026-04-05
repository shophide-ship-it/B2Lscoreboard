<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

$GLOBALS['raw_input'] = file_get_contents('php://input');

try {
    require_once __DIR__ . '/auth.php';
    $pdo = getDB();
    $user = verifyToken();
    
    $action = $_GET['action'] ?? '';
    $input = json_decode($GLOBALS['raw_input'], true);
    
    switch ($action) {
        case 'record':
            recordStat($pdo, $input);
            break;
        case 'undo':
            undoStat($pdo, $input);
            break;
        case 'get_stats':
            getStats($pdo, $input);
            break;
        case 'get_play_by_play':
            getPlayByPlay($pdo, $input);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function recordStat($pdo, $input) {
    $gameId = $input['game_id'];
    $quarter = $input['quarter'];
    $gameTime = $input['game_time'] ?? '10:00';
    $teamId = $input['team_id'];
    $playerId = $input['player_id'];
    $actionType = $input['action_type'];
    $points = $input['points'] ?? 0;
    
    $pdo->beginTransaction();
    
    try {
        // play by play記録
        $stmt = $pdo->prepare("INSERT INTO live_play_by_play (game_id, quarter, game_time, team_id, player_id, action_type, points) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$gameId, $quarter, $gameTime, $teamId, $playerId, $actionType, $points]);
        $playId = $pdo->lastInsertId();
        
        // 選手スタッツ更新
        $stmt = $pdo->prepare("INSERT INTO live_player_stats (game_id, player_id, team_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE team_id = team_id");
        $stmt->execute([$gameId, $playerId, $teamId]);
        
        updatePlayerStat($pdo, $gameId, $playerId, $actionType, $points, 1);
        updateGameScore($pdo, $gameId, $teamId, $actionType, $points, 1);
        
        $pdo->commit();
        
        $stats = getPlayerStats($pdo, $gameId);
        $score = getGameScore($pdo, $gameId);
        
        echo json_encode([
            'success' => true,
            'play_id' => $playId,
            'stats' => $stats,
            'score' => $score
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function undoStat($pdo, $input) {
    $gameId = $input['game_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM live_play_by_play WHERE game_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$gameId]);
    $lastPlay = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lastPlay) {
        echo json_encode(['success' => false, 'error' => 'No plays to undo']);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        updatePlayerStat($pdo, $gameId, $lastPlay['player_id'], $lastPlay['action_type'], $lastPlay['points'], -1);
        updateGameScore($pdo, $gameId, $lastPlay['team_id'], $lastPlay['action_type'], $lastPlay['points'], -1);
        
        $stmt = $pdo->prepare("DELETE FROM live_play_by_play WHERE id = ?");
        $stmt->execute([$lastPlay['id']]);
        
        $pdo->commit();
        
        $stats = getPlayerStats($pdo, $gameId);
        $score = getGameScore($pdo, $gameId);
        
        echo json_encode([
            'success' => true,
            'undone' => $lastPlay,
            'stats' => $stats,
            'score' => $score
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function updatePlayerStat($pdo, $gameId, $playerId, $actionType, $points, $direction) {
    $updates = [];
    
    switch ($actionType) {
        case '2pm':
            $updates = ['points' => 2 * $direction, 'fgm' => $direction, 'fga' => $direction];
            break;
        case '2pa':
            $updates = ['fga' => $direction];
            break;
        case '3pm':
            $updates = ['points' => 3 * $direction, 'three_pm' => $direction, 'three_pa' => $direction, 'fgm' => $direction, 'fga' => $direction];
            break;
        case '3pa':
            $updates = ['three_pa' => $direction, 'fga' => $direction];
            break;
        case 'ftm':
            $updates = ['points' => 1 * $direction, 'ftm' => $direction, 'fta' => $direction];
            break;
        case 'fta':
            $updates = ['fta' => $direction];
            break;
        case 'oreb':
            $updates = ['oreb' => $direction];
            break;
        case 'dreb':
            $updates = ['dreb' => $direction];
            break;
        case 'ast':
            $updates = ['ast' => $direction];
            break;
        case 'stl':
            $updates = ['stl' => $direction];
            break;
        case 'blk':
            $updates = ['blk' => $direction];
            break;
        case 'tov':
            $updates = ['turnovers' => $direction];
            break;
        case 'foul':
            $updates = ['fouls' => $direction];
            break;
    }
    
    if (!empty($updates)) {
        $setParts = [];
        $values = [];
        foreach ($updates as $col => $val) {
            $setParts[] = "`$col` = GREATEST(`$col` + ?, 0)";
            $values[] = $val;
        }
        $values[] = $gameId;
        $values[] = $playerId;
        
        $sql = "UPDATE live_player_stats SET " . implode(', ', $setParts) . " WHERE game_id = ? AND player_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
    }
}

function updateGameScore($pdo, $gameId, $teamId, $actionType, $points, $direction) {
    // live_gamesにレコードがなければ作成
    $stmt = $pdo->prepare("INSERT IGNORE INTO live_games (game_id) VALUES (?)");
    $stmt->execute([$gameId]);
    
    // ホーム/アウェイ判定
    $stmt = $pdo->prepare("SELECT home_team_id, away_team_id FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$game) return;
    
    $isHome = ($teamId == $game['home_team_id']);
    
    if (in_array($actionType, ['2pm', '3pm', 'ftm'])) {
        $scoreCol = $isHome ? 'home_score' : 'away_score';
        $stmt = $pdo->prepare("UPDATE live_games SET `$scoreCol` = GREATEST(`$scoreCol` + ?, 0) WHERE game_id = ?");
        $stmt->execute([$points * $direction, $gameId]);
    }
    
    if ($actionType === 'foul') {
        $foulCol = $isHome ? 'home_fouls' : 'away_fouls';
        $stmt = $pdo->prepare("UPDATE live_games SET `$foulCol` = GREATEST(`$foulCol` + ?, 0) WHERE game_id = ?");
        $stmt->execute([$direction, $gameId]);
    }
}

function getPlayerStats($pdo, $gameId) {
    $stmt = $pdo->prepare("
        SELECT ps.*, p.name, p.number, p.team_id
        FROM live_player_stats ps
        JOIN players p ON ps.player_id = p.id
        WHERE ps.game_id = ?
        ORDER BY ps.team_id, p.number
    ");
    $stmt->execute([$gameId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getGameScore($pdo, $gameId) {
    $stmt = $pdo->prepare("SELECT * FROM live_games WHERE game_id = ?");
    $stmt->execute([$gameId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['home_score' => 0, 'away_score' => 0, 'home_fouls' => 0, 'away_fouls' => 0];
}

function getStats($pdo, $input) {
    $gameId = $input['game_id'];
    $stats = getPlayerStats($pdo, $gameId);
    $score = getGameScore($pdo, $gameId);
    echo json_encode(['success' => true, 'stats' => $stats, 'score' => $score]);
}

function getPlayByPlay($pdo, $input) {
    $gameId = $input['game_id'];
    $stmt = $pdo->prepare("
        SELECT pbp.*, p.name, p.number
        FROM live_play_by_play pbp
        JOIN players p ON pbp.player_id = p.id
        WHERE pbp.game_id = ?
        ORDER BY pbp.id DESC
        LIMIT 50
    ");
    $stmt->execute([$gameId]);
    echo json_encode(['success' => true, 'plays' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

