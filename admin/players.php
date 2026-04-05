<?php
// /b2l/register/players.php
// 選手登録フォーム（チーム代表者用）

header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../config.php';

// トークン検証
$token = $_GET['token'] ?? '';
if (empty($token)) {
    die('<h1>無効なアクセスです</h1><p>チーム代表者用URLからアクセスしてください。</p>');
}

$db = getDB();

// チーム情報取得
$stmt = $db->prepare("SELECT * FROM teams WHERE token = ?");
$stmt->execute([$token]);
$team = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$team) {
    die('<h1>無効なトークンです</h1><p>URLが正しいか確認してください。</p>');
}

$teamId = $team['id'];
$message = '';
$messageType = '';

// 締切チェック
$registrationOpen = isRegistrationOpen();
$deadline = getDeadline();

// 既存の登録選手を取得
$stmt = $db->prepare("SELECT * FROM player_registrations WHERE team_id = ? ORDER BY number ASC");
$stmt->execute([$teamId]);
$existingPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $registrationOpen) {
    $action = $_POST['action'] ?? 'register';
    
    if ($action === 'register') {
        $names = $_POST['name'] ?? [];
        $numbers = $_POST['number'] ?? [];
        $positions = $_POST['position'] ?? [];
        
        $playerCount = 0;
        $errors = [];
        
        // バリデーション
        $validPlayers = [];
        for ($i = 0; $i < count($names); $i++) {
            $name = trim($names[$i] ?? '');
            $number = trim($numbers[$i] ?? '');
            $position = trim($positions[$i] ?? '');
            
            if (empty($name) && empty($number)) continue_

