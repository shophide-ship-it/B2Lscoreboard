<?php
require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);
$note = trim($input['note'] ?? '');

if (!$id) {
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare("UPDATE team_registrations SET status = 'rejected', admin_note = ? WHERE id = ? AND status = 'pending'");
$stmt->execute([$note, $id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => '差し戻しました']);
} else {
    echo json_encode(['error' => '該当する申請がありません']);
}
