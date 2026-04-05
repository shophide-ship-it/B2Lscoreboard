<?php
require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 36時間チェック
if (!isRegistrationOpen()) {
    http_response_code(403);
    echo json_encode(['error' => '登録受付は締め切りました']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// バリデーション
$team_name = trim($input['team_name'] ?? '');
$rep_name = trim($input['representative_name'] ?? '');
$rep_phone = trim($input['representative_phone'] ?? '');
$rep_email = trim($input['representative_email'] ?? '');
$rep_line = trim($input['representative_line_name'] ?? '');
$members = $input['members'] ?? [];

if (!$team_name || !$rep_name || !$rep_phone) {
    echo json_encode(['error' => 'チーム名、代表者氏名、電話番号は必須です']);
    exit;
}

if (empty($members)) {
    echo json_encode(['error' => '最低1人のメンバーが必要です']);
    exit;
}

if (count($members) > 30) {
    echo json_encode(['error' => 'メンバーは最大30人までです']);
    exit;
}

// 背番号重複チェック
$numbers = array_column($members, 'number');
if (count($numbers) !== count(array_unique($numbers))) {
    echo json_encode(['error' => '背番号が重複しています']);
    exit;
}

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // チーム登録
    $stmt = $pdo->prepare("
        INSERT INTO team_registrations 
        (team_name, representative_name, representative_email, representative_phone, representative_line_name)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$team_name, $rep_name, $rep_email, $rep_phone, $rep_line]);
    $reg_id = $pdo->lastInsertId();

    // メンバー登録
    $stmt = $pdo->prepare("
        INSERT INTO player_registrations 
        (team_registration_id, number, name, position, height)
        VALUES (?, ?, ?, ?, ?)
    ");
    foreach ($members as $m) {
        $stmt->execute([
            $reg_id,
            $m['number'],
            $m['name'],
            $m['position'] ?: null,
            $m['height'] ?: null
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'registration_id' => $reg_id,
        'message' => '申請を受け付けました'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => '登録処理中にエラーが発生しました']);
}
