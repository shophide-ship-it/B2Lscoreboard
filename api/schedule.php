<?php
require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json');

$pdo = getDB();

// GET: 一覧取得
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT id, game_datetime, venue,
               DATE_SUB(game_datetime, INTERVAL 36 HOUR) as deadline
        FROM game_schedule 
        ORDER BY game_datetime ASC
    ");
    echo json_encode($stmt->fetchAll());
    exit;
}

// POST: 新規登録
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $datetime = $input['game_datetime'] ?? '';
    $venue = trim($input['venue'] ?? '');

    if (!$datetime) {
        echo json_encode(['error' => '日時が必要です']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO game_schedule (game_datetime, venue) VALUES (?, ?)");
    $stmt->execute([$datetime, $venue]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// DELETE: 削除
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    if (!$id) {
        echo json_encode(['error' => 'Invalid ID']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM game_schedule WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}
