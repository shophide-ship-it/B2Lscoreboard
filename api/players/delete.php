<?php
// B2L League - Player Delete API (soft delete)
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$token    = trim($input['token'] ?? '');
$playerId = $input['player_id'] ?? null;

if ($token === '' || !$playerId || !is_numeric($playerId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token and player_id are required']);
    exit;
}

if (!isRegistrationOpen()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Registration period has ended']);
    exit;
}

try {
    $pdo = getDB();
    $playerId = (int)$playerId;

    // Verify token
    $stmt = $pdo->prepare('SELECT id FROM teams WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $team = $stmt->fetch();
    if (!$team) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }
    $teamId = (int)$team['id'];

    // Verify player belongs to team
    $stmt = $pdo->prepare('SELECT id, name, number FROM players WHERE id = ? AND team_id = ? AND is_active = 1');
    $stmt->execute([$playerId, $teamId]);
    $player = $stmt->fetch();
    if (!$player) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Player not found']);
        exit;
    }

    // Soft delete
    $stmt = $pdo->prepare('UPDATE players SET is_active = 0, updated_at = NOW() WHERE id = ? AND team_id = ?');
    $stmt->execute([$playerId, $teamId]);

    echo json_encode([
        'success' => true,
        'deleted' => [
            'id'     => $playerId,
            'number' => (int)$player['number'],
            'name'   => $player['name']
        ]
    ]);

} catch (PDOException $e) {
    error_log('B2L delete error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}