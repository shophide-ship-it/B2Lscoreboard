<?php
/**
 * API: セッション管理エンドポイント
 * 
 * 機能:
 * 1. セッション作成 (POST /api/session.php?action=create)
 * 2. ハートビート送信 (POST /api/session.php?action=keepalive)
 * 3. セッション終了 (POST /api/session.php?action=logout)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'create';
    $pdo = getDB();

    // ==========================================
    // アクション1: セッション作成
    // ==========================================
    if ($action === 'create') {
        $input = json_decode(file_get_contents('php://input'), true);
        $game_id = (int)($input['game_id'] ?? 0);
        $team_id = (int)($input['team_id'] ?? 0);
        $role = $input['role'] ?? 'stat';
        
        if ($game_id <= 0 || $team_id <= 0) {
            throw new Exception('game_id と team_id は必須です');
        }

        // セッショントークン生成
        $token = bin2hex(random_bytes(32));

        // 既存セッションの確認（同一ユーザー・同一チーム）
        $existing = $pdo->query("
            SELECT id FROM live_stat_sessions 
            WHERE game_id = $game_id AND team_id = $team_id AND role = '$role' 
            AND login_status = 'active' LIMIT 1
        ")->fetch();

        if ($existing) {
            throw new Exception('このゲーム・チーム・ロールに既に入力者がいます');
        }

        // セッション作成
        $stmt = $pdo->prepare("
            INSERT INTO live_stat_sessions 
            (game_id, team_id, role, session_token, ip_address, device_type, login_status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $device = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 20);

        $stmt->execute([$game_id, $team_id, $role, $token, $ip, $device]);

        echo json_encode([
            'success' => true,
            'session_token' => $token,
            'message' => 'セッションを作成しました',
            'expires_in' => 3600  // 1時間
        ], JSON_UNESCAPED_UNICODE);
    }

    // ==========================================
    // アクション2: ハートビート（キープアライブ）
    // ==========================================
    else if ($action === 'keepalive') {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['session_token'] ?? '';

        if (!$token) {
            throw new Exception('session_token が必須です');
        }

        // セッションを更新
        $stmt = $pdo->prepare("
            UPDATE live_stat_sessions 
            SET last_active_at = NOW(), login_status = 'active'
            WHERE session_token = ? AND login_status IN ('active', 'standby')
        ");
        $result = $stmt->execute([$token]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('セッションが見つかりません');
        }

        echo json_encode([
            'success' => true,
            'message' => 'キープアライブを送信しました'
        ], JSON_UNESCAPED_UNICODE);
    }

    // ==========================================
    // アクション3: ログアウト
    // ==========================================
    else if ($action === 'logout') {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['session_token'] ?? '';

        if (!$token) {
            throw new Exception('session_token が必須です');
        }

        $stmt = $pdo->prepare("
            UPDATE live_stat_sessions 
            SET login_status = 'disconnected'
            WHERE session_token = ?
        ");
        $stmt->execute([$token]);

        echo json_encode([
            'success' => true,
            'message' => 'ログアウトしました'
        ], JSON_UNESCAPED_UNICODE);
    }

    else {
        throw new Exception('無効なアクション: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
