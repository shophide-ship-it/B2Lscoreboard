<?php
// b2l/api/live-stats.php
require_once __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
// 公開アクション（認証不要・GET許可）
$publicActions = ['boxscore', 'plays', 'game_status'];

if (in_array($action, $publicActions)) {
    $pdo = getDB();
}
else {
    $user = verifyToken();
    $pdo = getDB();
}
switch ($action) {
    // === スタッツ記録 ===
    case 'record':
        if ($method !== 'POST')
            break;
        $input = json_decode(file_get_contents('php://input'), true);

        $gameId = (int)($input['game_id'] ?? 0);
        $quarter = (int)($input['quarter'] ?? 1);
        $gameTime = $input['game_time'] ?? '10:00';
        $teamId = (int)($input['team_id'] ?? 0);
        $playerId = $input['player_id'] ? (int)$input['player_id'] : null;
        $action_type = $input['action_type'] ?? '';
        $points = (int)($input['points'] ?? 0);

        $pdo->beginTransaction();
        try {
            // プレイバイプレイに記録
            $stmt = $pdo->prepare("
                INSERT INTO play_by_play 
                (game_id, quarter, game_time, team_id, player_id, action_type, points)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$gameId, $quarter, $gameTime, $teamId, $playerId, $action_type, $points]);
            $playId = $pdo->lastInsertId();

            // 個人スタッツ更新（選手アクションの場合）
            if ($playerId) {
                // レコードがなければ作成
                $pdo->prepare("
                    INSERT IGNORE INTO live_player_stats (game_id, player_id, team_id)
                    VALUES (?, ?, ?)
                ")->execute([$gameId, $playerId, $teamId]);

                $fieldMap = [
                    'fgm' => 'fgm', 'fga' => 'fga',
                    '3pm' => 'three_pm', '3pa' => 'three_pa',
                    'ftm' => 'ftm', 'fta' => 'fta',
                    'oreb' => 'oreb', 'dreb' => 'dreb',
                    'ast' => 'ast', 'stl' => 'stl',
                    'blk' => 'blk', 'turnover' => 'turnovers',
                    'foul' => 'fouls'
                ];

                if (isset($fieldMap[$action_type])) {
                    $field = $fieldMap[$action_type];
                    $pdo->prepare("
                        UPDATE live_player_stats 
                        SET {$field} = {$field} + 1, points = points + ?
                        WHERE game_id = ? AND player_id = ?
                    ")->execute([$points, $gameId, $playerId]);
                }
            }

            // チームスコア更新
            if ($points > 0) {
                // ホームかアウェイか判定
                $stmt = $pdo->prepare("SELECT home_team_id, away_team_id FROM games WHERE id = ?");
                $stmt->execute([$gameId]);
                $game = $stmt->fetch();

                if ($teamId == $game['home_team_id']) {
                    $pdo->prepare("UPDATE live_games SET home_score = home_score + ? WHERE game_id = ?")
                        ->execute([$points, $gameId]);
                }
                else {
                    $pdo->prepare("UPDATE live_games SET away_score = away_score + ? WHERE game_id = ?")
                        ->execute([$points, $gameId]);
                }
            }

            // チームファウル更新
            if ($action_type === 'foul') {
                $stmt = $pdo->prepare("SELECT home_team_id FROM games WHERE id = ?");
                $stmt->execute([$gameId]);
                $game = $stmt->fetch();
                $foulField = ($teamId == $game['home_team_id']) ? 'home_fouls' : 'away_fouls';
                $pdo->prepare("UPDATE live_games SET {$foulField} = {$foulField} + 1 WHERE game_id = ?")
                    ->execute([$gameId]);
            }

            $pdo->commit();

            // 最新スコアを返す
            $stmt = $pdo->prepare("SELECT home_score, away_score, home_fouls, away_fouls FROM live_games WHERE game_id = ?");
            $stmt->execute([$gameId]);
            $scores = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'play_id' => $playId,
                'scores' => $scores
            ]);

        }
        catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // === スタッツ取り消し（UNDO） ===
    case 'undo':
        if ($method !== 'POST')
            break;
        $input = json_decode(file_get_contents('php://input'), true);
        $playId = (int)($input['play_id'] ?? 0);

        $pdo->beginTransaction();
        try {
            // プレイ情報取得
            $stmt = $pdo->prepare("SELECT * FROM play_by_play WHERE id = ?");
            $stmt->execute([$playId]);
            $play = $stmt->fetch();

            if (!$play)
                throw new Exception('記録が見つかりません');

            // === 関連する付随アクションも特定 ===
            // 同じ試合・同じ選手・同じクォーター・同じ時刻で、
            // このplay_idの直後に記録された付随アクションを取得
            $autoActions = [
                '3pm' => ['3pa', 'fgm', 'fga'],
                'fgm' => ['fga'],
                'ftm' => ['fta'],
            ];

            $relatedIds = [$playId];

            if (isset($autoActions[$play['action_type']])) {
                $targets = $autoActions[$play['action_type']];
                $placeholders = implode(',', array_fill(0, count($targets), '?'));

                $stmt = $pdo->prepare("
                SELECT id, action_type, points FROM play_by_play
                WHERE game_id = ? AND player_id = ? AND quarter = ? AND game_time = ?
                  AND action_type IN ({$placeholders})
                  AND id > ?
                ORDER BY id ASC
                LIMIT " . count($targets)
                );
                $params = [
                    $play['game_id'], $play['player_id'], $play['quarter'], $play['game_time']
                ];
                $params = array_merge($params, $targets, [$playId]);
                $stmt->execute($params);
                $related = $stmt->fetchAll();

                foreach ($related as $r) {
                    $relatedIds[] = $r['id'];
                }
            }

            // === 全関連プレイのスタッツを戻す ===
            $fieldMap = [
                'fgm' => 'fgm', 'fga' => 'fga',
                '3pm' => 'three_pm', '3pa' => 'three_pa',
                'ftm' => 'ftm', 'fta' => 'fta',
                'oreb' => 'oreb', 'dreb' => 'dreb',
                'ast' => 'ast', 'stl' => 'stl',
                'blk' => 'blk', 'turnover' => 'turnovers',
                'foul' => 'fouls'
            ];

            // 関連プレイ全件取得
            $phIds = implode(',', array_fill(0, count($relatedIds), '?'));
            $stmt = $pdo->prepare("SELECT * FROM play_by_play WHERE id IN ({$phIds})");
            $stmt->execute($relatedIds);
            $allPlays = $stmt->fetchAll();

            $totalPointsToRevert = 0;

            foreach ($allPlays as $p) {
                // 個人スタッツを戻す
                if ($p['player_id'] && isset($fieldMap[$p['action_type']])) {
                    $field = $fieldMap[$p['action_type']];
                    $pdo->prepare("
                    UPDATE live_player_stats 
                    SET {$field} = GREATEST({$field} - 1, 0),
                        points = GREATEST(points - ?, 0)
                    WHERE game_id = ? AND player_id = ?
                ")->execute([$p['points'], $p['game_id'], $p['player_id']]);
                }
                $totalPointsToRevert += $p['points'];
            }

            // スコアを戻す
            if ($totalPointsToRevert > 0) {
                $stmt = $pdo->prepare("SELECT home_team_id FROM games WHERE id = ?");
                $stmt->execute([$play['game_id']]);
                $game = $stmt->fetch();
                $scoreField = ($play['team_id'] == $game['home_team_id']) ? 'home_score' : 'away_score';
                $pdo->prepare("
                UPDATE live_games SET {$scoreField} = GREATEST({$scoreField} - ?, 0) WHERE game_id = ?
            ")->execute([$totalPointsToRevert, $play['game_id']]);
            }

            // ファウルを戻す
            if ($play['action_type'] === 'foul') {
                $stmt = $pdo->prepare("SELECT home_team_id FROM games WHERE id = ?");
                $stmt->execute([$play['game_id']]);
                $game = $stmt->fetch();
                $foulField = ($play['team_id'] == $game['home_team_id']) ? 'home_fouls' : 'away_fouls';
                $pdo->prepare("
                UPDATE live_games SET {$foulField} = GREATEST({$foulField} - 1, 0) WHERE game_id = ?
            ")->execute([$play['game_id']]);
            }

            // 全関連プレイを削除
            $pdo->prepare("DELETE FROM play_by_play WHERE id IN ({$phIds})")->execute($relatedIds);

            $pdo->commit();
            echo json_encode(['success' => true]);
        }
        catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // === クォーター変更 ===
    case 'quarter':
        if ($method !== 'POST')
            break;
        $input = json_decode(file_get_contents('php://input'), true);
        $gameId = (int)($input['game_id'] ?? 0);
        $quarter = (int)($input['quarter'] ?? 1);
        $statusMap = [1 => 'q1', 2 => 'q2', 3 => 'q3', 4 => 'q4', 5 => 'ot'];
        $status = $statusMap[$quarter] ?? 'q1';

        $pdo->prepare("
            UPDATE live_games SET current_quarter = ?, status = ?, home_fouls = 0, away_fouls = 0
            WHERE game_id = ?
        ")->execute([$quarter, $status, $gameId]);

        echo json_encode(['success' => true, 'quarter' => $quarter]);
        break;

    // === 試合終了 → 管理サイトに反映 ===
    case 'finish':
        if ($method !== 'POST')
            break;
        $input = json_decode(file_get_contents('php://input'), true);
        $gameId = (int)($input['game_id'] ?? 0);

        $pdo->beginTransaction();
        try {
            // ライブスコア取得
            $stmt = $pdo->prepare("SELECT * FROM live_games WHERE game_id = ?");
            $stmt->execute([$gameId]);
            $live = $stmt->fetch();

            // gamesテーブルに最終スコアを反映
            $pdo->prepare("
                UPDATE games SET 
                    home_score = ?, away_score = ?, 
                    status = 'finished'
                WHERE id = ?
            ")->execute([$live['home_score'], $live['away_score'], $gameId]);

            // live_player_statsをplayer_statsに転記
            $stmt = $pdo->prepare("SELECT * FROM live_player_stats WHERE game_id = ?");
            $stmt->execute([$gameId]);
            $allStats = $stmt->fetchAll();

            foreach ($allStats as $s) {
                $reb = $s['oreb'] + $s['dreb'];
                $pts = ($s['fgm'] - $s['three_pm']) * 2 + $s['three_pm'] * 3 + $s['ftm'];

                $pdo->prepare("
                    INSERT INTO player_stats 
                    (game_id, player_id, team_id, minutes, fgm, fga, three_pm, three_pa, ftm, fta, oreb, dreb, reb, ast, stl, blk, turnovers, fouls, points)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    minutes=VALUES(minutes), fgm=VALUES(fgm), fga=VALUES(fga),
                    three_pm=VALUES(three_pm), three_pa=VALUES(three_pa),
                    ftm=VALUES(ftm), fta=VALUES(fta), oreb=VALUES(oreb), dreb=VALUES(dreb),
                    reb=VALUES(reb), ast=VALUES(ast), stl=VALUES(stl), blk=VALUES(blk),
                    turnovers=VALUES(turnovers), fouls=VALUES(fouls), points=VALUES(points)
                ")->execute([
                    $gameId, $s['player_id'], $s['team_id'], $s['minutes'],
                    $s['fgm'], $s['fga'], $s['three_pm'], $s['three_pa'],
                    $s['ftm'], $s['fta'], $s['oreb'], $s['dreb'], $reb,
                    $s['ast'], $s['stl'], $s['blk'], $s['turnovers'], $s['fouls'], $pts
                ]);
            }

            // ライブステータスを完了に
            $pdo->prepare("UPDATE live_games SET status = 'finished' WHERE game_id = ?")
                ->execute([$gameId]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => '試合終了・スタッツ反映完了']);
        }
        catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // === ライブボックススコア取得 ===
    case 'boxscore':
        $gameId = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT lps.*, p.name, p.number, p.position
            FROM live_player_stats lps
            JOIN players p ON lps.player_id = p.id
            WHERE lps.game_id = ?
            ORDER BY lps.team_id, lps.points DESC
        ");
        $stmt->execute([$gameId]);
        echo json_encode($stmt->fetchAll());
        break;

    // === プレイバイプレイ取得 ===
    case 'plays':
        $gameId = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT pbp.*, p.name AS player_name, p.number AS player_number, t.short_name AS team_short
            FROM play_by_play pbp
            LEFT JOIN players p ON pbp.player_id = p.id
            JOIN teams t ON pbp.team_id = t.id
            WHERE pbp.game_id = ?
            ORDER BY pbp.id DESC
            LIMIT 50
        ");
        $stmt->execute([$gameId]);
        echo json_encode($stmt->fetchAll());
        break;

}
?>