<?php
// エラー表示
error_reporting(E_ALL);
ini_set('display_errors', '1');

// CORS設定
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// config.php の読み込み
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'config.php not found']);
    exit;
}
require_once $configPath;

// トークン認証関数（getDB()はconfig.phpで定義済み）
function verifyToken()
{
    $pdo = getDB();
    $allHeaders = function_exists('getallheaders') ? getallheaders() : [];

    $token = null;

    // ① X-Auth-Token カスタムヘッダー（最優先 - さくらサーバー対応）
    if (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        $token = trim($_SERVER['HTTP_X_AUTH_TOKEN']);
    }
    elseif (!empty($allHeaders['X-Auth-Token'])) {
        $token = trim($allHeaders['X-Auth-Token']);
    }
    elseif (!empty($allHeaders['x-auth-token'])) {
        $token = trim($allHeaders['x-auth-token']);
    }
    // ② POSTボディの token（フォールバック）
    elseif (!empty($_POST['token'])) {
        $token = trim($_POST['token']);
    }
    else {
        $raw = isset($GLOBALS['raw_input']) ? $GLOBALS['raw_input'] : file_get_contents('php://input');
        $input = json_decode($raw, true);
        if ($input && !empty($input['token'])) {
            $token = trim($input['token']);
        }
    }
    // ③ GETパラメータ（フォールバック）
    if (!$token && !empty($_GET['token'])) {
        $token = trim($_GET['token']);
    }

    if (!$token) {
        http_response_code(400);
        echo json_encode(['error' => 'Token is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, token, user_name, role, is_active FROM api_tokens WHERE token = ? LIMIT 1");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
    }
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Query failed', 'detail' => $e->getMessage()]);
        exit;
    }

    if (!$row) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token', 'success' => false]);
        exit;
    }

    if (isset($row['is_active']) && $row['is_active'] == 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Token is disabled', 'success' => false]);
        exit;
    }

    return $row;
}

// ---- ログインAPI処理 ----
// auth.php が直接リクエストされた場合のみ実行
$isDirectAccess = (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__));

if ($isDirectAccess && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? '';

    if (empty($token)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Token is required']);
        exit;
    }

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, token, user_name, role, is_active FROM api_tokens WHERE token = ? LIMIT 1");
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            exit;
        }

        if (isset($row['is_active']) && $row['is_active'] == 0) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Token is disabled']);
            exit;
        }

        // last_used を更新
        $updateStmt = $pdo->prepare("UPDATE api_tokens SET last_used = NOW() WHERE id = ?");
        $updateStmt->execute([$row['id']]);

        echo json_encode([
            'success' => true,
            'token'   => $row['token'],
            'scorer'  => $row['user_name'],
            'role'    => $row['role'] ?? 'scorer'
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}

// GETで直接アクセスされた場合
if ($isDirectAccess) {
    echo json_encode(['error' => 'POST method required']);
    exit;
}