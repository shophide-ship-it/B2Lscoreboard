<?php
require_once __DIR__ . '/../admin/config.php';

header('Content-Type: application/json');

$pdo = getDB();

// 申請一覧取得（メンバー情報含む）
$stmt = $pdo->query("
    SELECT tr.*, 
           (SELECT COUNT(*) FROM player_registrations WHERE team_registration_id = tr.id) as member_count
    FROM team_registrations tr
    ORDER BY 
        CASE tr.status 
            WHEN 'pending' THEN 0 
            WHEN 'rejected' THEN 1 
            WHEN 'approved' THEN 2 
        END,
        tr.created_at DESC
");
$registrations = $stmt->fetchAll();

// 各申請にメンバー情報を付与
foreach ($registrations as &$reg) {
    $stmt = $pdo->prepare("
        SELECT number, name, position, height 
        FROM player_registrations 
        WHERE team_registration_id = ? 
        ORDER BY number ASC
    ");
    $stmt->execute([$reg['id']]);
    $reg['members'] = $stmt->fetchAll();
}

echo json_encode($registrations);
