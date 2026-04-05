<?php
// B2L League - Player List API
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config.php';

// GET only
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token is required']);
    exit;
}

try {
    $pdo = getDB();

    // Verify token
    $stmt = $pdo->prepare('SELECT id, name, division FROM teams WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $team = $stmt->fetch();

    if (!$team) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }

    // Get active players
    $stmt = $pdo->prepare(
        'SELECT id, number, name, position, height, created_at, updated_at
         FROM players
         WHERE team_id = ? AND is_active = 1
         ORDER BY number ASC'
    );
    $stmt->execute([(int)$team['id']]);
    $players = $stmt->fetchAll();

    // Format height
    foreach ($players as &$p) {
        $p['id'] = (int)$p['id'];
        $p['number'] = (int)$p['number'];
        $p['height'] = $p['height'] !== null ? (float)$p['height'] : null;
    }
    unset($p);

    echo json_encode([
        'success' => true,
        'team' => [
            'id'       => (int)$team['id'],
            'name'     => $team['name'],
            'division' => (int)$team['division']
        ],
        'players'  => $players,
        'count'    => count($players),
        'max'      => PLAYER_MAX_PER_TEAM,
        'deadline' => PLAYER_REGISTRATION_DEADLINE,
        'registration_open' => isRegistrationOpen()
    ]);

} catch (PDOException $e) {
    error_log('B2L list error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
