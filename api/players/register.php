<?php
// B2L League - Player Registration API
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../config.php';

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$token    = trim($input['token'] ?? '');
$name     = trim($input['name'] ?? '');
$number   = $input['number'] ?? null;
$position = trim($input['position'] ?? '');
$height   = $input['height'] ?? null;

// ---- Validation ----
$errors = [];

if ($token === '') {
    $errors[] = 'Token is required';
}
if ($name === '' || mb_strlen($name) > 100) {
    $errors[] = 'Name is required (max 100 chars)';
}
if ($number === null || $number === '' || !is_numeric($number)) {
    $errors[] = 'Number is required';
} else {
    $number = (int)$number;
    if ($number < PLAYER_NUMBER_MIN || $number > PLAYER_NUMBER_MAX) {
        $errors[] = 'Number must be ' . PLAYER_NUMBER_MIN . '-' . PLAYER_NUMBER_MAX;
    }
}
$validPositions = ['PG', 'SG', 'SF', 'PF', 'C'];
if (!in_array($position, $validPositions, true)) {
    $errors[] = 'Invalid position';
}
if ($height !== null && $height !== '') {
    $height = (float)$height;
    if ($height < 100 || $height > 250) {
        $errors[] = 'Height must be 100-250cm';
    }
} else {
    $height = null;
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
    exit;
}

// ---- Registration deadline check ----
if (!isRegistrationOpen()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Registration period has ended']);
    exit;
}

try {
    $pdo = getDB();

    // Verify token -> get team
    $stmt = $pdo->prepare('SELECT id, name FROM teams WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $team = $stmt->fetch();

    if (!$team) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
        exit;
    }

    $teamId = (int)$team['id'];

    // Check player count limit
    $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM players WHERE team_id = ? AND is_active = 1');
    $stmt->execute([$teamId]);
    $count = (int)$stmt->fetch()['cnt'];

    if ($count >= PLAYER_MAX_PER_TEAM) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Maximum ' . PLAYER_MAX_PER_TEAM . ' players per team'
        ]);
        exit;
    }

    // Check duplicate number within team
    $stmt = $pdo->prepare('SELECT id FROM players WHERE team_id = ? AND number = ? AND is_active = 1');
    $stmt->execute([$teamId, $number]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Number ' . $number . ' is already used']);
        exit;
    }

    // Check duplicate name within team
    $stmt = $pdo->prepare('SELECT id FROM players WHERE team_id = ? AND name = ? AND is_active = 1');
    $stmt->execute([$teamId, $name]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Player name already exists']);
        exit;
    }

    // Insert
    $stmt = $pdo->prepare(
        'INSERT INTO players (team_id, number, name, position, height, is_active, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())'
    );
    $stmt->execute([$teamId, $number, $name, $position, $height]);
    $playerId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'player' => [
            'id'       => $playerId,
            'team_id'  => $teamId,
            'number'   => $number,
            'name'     => $name,
            'position' => $position,
            'height'   => $height
        ]
    ]);

} catch (PDOException $e) {
    error_log('B2L register error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}



