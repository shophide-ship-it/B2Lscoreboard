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

try {
    $pdo->beginTransaction();

    // 申請データ取得
    $stmt = $pdo->prepare("SELECT * FROM team_registrations WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    $reg = $stmt->fetch();

    if (!$reg) {
        echo json_encode(['error' => '該当する申請がありません']);
        exit;
    }

    // 正式チーム登録
    $stmt = $pdo->prepare("
        INSERT INTO teams (registration_id, team_name, representative_name, representative_email, representative_phone, representative_line_name)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $reg['id'],
        $reg['team_name'],
        $reg['representative_name'],
        $reg['representative_email'],
        $reg['representative_phone'],
        $reg['representative_line_name']
    ]);
    $team_id = $pdo->lastInsertId();

    // メンバー取得して正式登録
    $stmt = $pdo->prepare("SELECT * FROM player_registrations WHERE team_registration_id = ?");
    $stmt->execute([$id]);
    $members = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        INSERT INTO players (team_id, number, name, position, height)
        VALUES (?, ?, ?, ?, ?)
    ");
    foreach ($members as $m) {
        $stmt->execute([
            $team_id,
            $m['number'],
            $m['name'],
            $m['position'],
            $m['height']
        ]);
    }

    // ステータス更新
    $stmt = $pdo->prepare("UPDATE team_registrations SET status = 'approved', admin_note = ? WHERE id = ?");
    $stmt->execute([$note, $id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'team_id' => $team_id,
        'message' => 'チームを承認しました'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => '処理中にエラーが発生しました: ' . $e->getMessage()]);
}
