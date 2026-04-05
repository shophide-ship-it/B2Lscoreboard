<?php
// B2L League - Player Update API
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

$token     = trim($input['token'] ?? '');
$playerId  = $input['player_id'] ?? null;
$name      = trim($input['name'] ?? '');
$number    = $input['number'] ?? null;
$position  = trim($input['position'] ?? '');
$height    = $input['height'] ?? null;

// Validation
$errors = [];
if ($token === '')  $errors[] = 'Token is required';
if (!$playerId || !is_numeric($playerId)) $errors[] = 'Player ID is required';
if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Name is required (max 100)';
if ($number === null || $number === '' || !is_numeric($number)) {
    $errors[] = 'Number is required';
} else {
    $number = (int)$number;
    if ($number < PLAYER_NUMBER_MIN || $number > PLAYER_NUMBER_MAX) {
        $errors[] = 'Number must be ' . PLAYER_NUMBER_MIN . '-' . PLAYER_NUMBER_MAX;
    }
}
$validPositions = ['PG', 'SG', 'SF', 'PF', 'C'];
if (!in_array($position, $validPositions, true)) $errors[] = 'Invalid position';
if ($height !== null && $height !== '') {
    $height = (float)$height;
    if ($height < 100 || $height > 250) $errors[] = 'Height must be 100-250cm';
} else {
    $height = null;
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
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

    // Verify player belongs to this team
    $stmt = $pdo->prepare('SELECT id FROM players WHERE id = ? AND team_id = ? AND is_active = 1');
    $stmt->execute([$playerId, $teamId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Player not found']);
        exit;
    }

    // Check duplicate number (exclude self)
    $stmt = $pdo->prepare('SELECT id FROM players WHERE team_id = ? AND number = ? AND id != ? AND is_active = 1');
    $stmt->execute([$teamId, $number, $playerId]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Number ' . $number . ' is already used']);
        exit;
    }

    // Check duplicate name (exclude self)
    $stmt = $pdo->prepare('SELECT id FROM players WHERE team_id = ? AND name = ? AND id != ? AND is_active = 1');
    $stmt->execute([$teamId, $name, $playerId]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Player name already exists']);
        exit;
    }

    // Update
    $stmt = $pdo->prepare(
        'UPDATE players SET number = ?, name = ?, position = ?, height = ?, updated_at = NOW()
         WHERE id = ? AND team_id = ?'
    );
    $stmt->execute([$number, $name, $position, $height, $playerId, $teamId]);

    echo json_encode([
        'success' => true,
        'player' => [
            'id'       => $playerId,
            'number'   => $number,
            'name'     => $name,
            'position' => $position,
            'height'   => $height
        ]
    ]);

} catch (PDOException $e) {
    error_log('B2L update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

