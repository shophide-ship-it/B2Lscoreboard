<?php
// エラー表示
error_reporting(E_ALL);
ini_set('display_errors', '1');

// CORS設定
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// config.php の読み込み
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'config.php not found']);
    exit;
}
require_once $configPath;

// データベース接続
try {
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// アクション判定（GETでもPOSTでも対応）
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// upcoming と detail は認証不要、それ以外は認証必要
if ($action !== 'upcoming' && $action !== 'detail') {
    require_once __DIR__ . '/auth.php';
    $user = verifyToken();
}


// --- ここから下は既存のswitch文をそのまま残す ---
switch ($action) {

    // 試合一覧（今日の試合・予定試合）
    case 'list':
        $date = $_GET['date'] ?? date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT g.*, 
                   ht.name AS home_team_name, ht.short_name AS home_short,
                   at.name AS away_team_name, at.short_name AS away_short,
                   lg.status AS live_status, lg.current_quarter, lg.quarter_time,
                   lg.home_score AS live_home_score, lg.away_score AS live_away_score
            FROM games g
            JOIN teams ht ON g.home_team_id = ht.id
            JOIN teams at ON g.away_team_id = at.id
            LEFT JOIN live_games lg ON g.id = lg.game_id
            WHERE g.game_date = ?
            ORDER BY g.game_time
        ");
        $stmt->execute([$date]);
        echo json_encode($stmt->fetchAll());
        break;

    // 試合の予定一覧（未完了）
    case 'upcoming':
        $stmt = $pdo->query("
            SELECT g.*, 
                   ht.name AS home_team_name, ht.short_name AS home_short,
                   at.name AS away_team_name, at.short_name AS away_short
            FROM games g
            JOIN teams ht ON g.home_team_id = ht.id
            JOIN teams at ON g.away_team_id = at.id
            WHERE g.status IN ('scheduled','live')
            ORDER BY g.game_date, g.game_time
            LIMIT 20
        ");
        echo json_encode($stmt->fetchAll());
        break;

    // 試合詳細（ロスター含む）
    case 'detail':
        $gameId = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("
            SELECT g.*, 
                   ht.name AS home_team_name, ht.short_name AS home_short,
                   at.name AS away_team_name, at.short_name AS away_short,
                   ht.primary_color AS home_color, at.primary_color AS away_color
            FROM games g
            JOIN teams ht ON g.home_team_id = ht.id
            JOIN teams at ON g.away_team_id = at.id
            WHERE g.id = ?
        ");
        $stmt->execute([$gameId]);
        $game = $stmt->fetch();

        if (!$game) {
            http_response_code(404);
            echo json_encode(['error' => '試合が見つかりません']);
            break;
        }

        // 両チームの選手取得
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   COALESCE(lps.points, 0) AS live_points,
                   COALESCE(lps.fouls, 0) AS live_fouls,
                   COALESCE(lps.is_oncourt, 0) AS is_oncourt
            FROM players p
            LEFT JOIN live_player_stats lps ON p.id = lps.player_id AND lps.game_id = ?
            WHERE p.team_id = ? AND p.is_active = 1
            ORDER BY p.position, p.number
        ");

        $stmt->execute([$gameId, $game['home_team_id']]);
        $game['home_players'] = $stmt->fetchAll();

        $stmt->execute([$gameId, $game['away_team_id']]);
        $game['away_players'] = $stmt->fetchAll();

        // ライブ情報取得
        $stmt = $pdo->prepare("SELECT * FROM live_games WHERE game_id = ?");
        $stmt->execute([$gameId]);
        $game['live'] = $stmt->fetch() ?: null;

        echo json_encode($game);
        break;

    // ライブゲーム開始
    case 'start':
        if ($method !== 'POST')
            break;
        $input = json_decode(file_get_contents('php://input'), true);
        $gameId = (int)($input['game_id'] ?? 0);

        // live_games レコード作成
        $stmt = $pdo->prepare("
            INSERT INTO live_games (game_id, status, started_at) 
            VALUES (?, 'q1', NOW())
            ON DUPLICATE KEY UPDATE status = 'q1', started_at = NOW()
        ");
        $stmt->execute([$gameId]);

        // ゲームステータス更新
        $pdo->prepare("UPDATE games SET status = 'live' WHERE id = ?")->execute([$gameId]);

        echo json_encode(['success' => true, 'message' => '試合開始']);
        break;
}
?>