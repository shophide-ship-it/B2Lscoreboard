<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

<?php
// Basic Authentication
$validUsername = 'b2ladmin';
$validPassword = 'X_MJJk5CfDwv4nf';

// 認証用のヘッダーが送信されたか確認
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'このページにアクセスするには認証が必要です。';
    exit;
} else {
    // ユーザー名とパスワードの確認
    if ($_SERVER['PHP_AUTH_USER'] !== $validUsername || $_SERVER['PHP_AUTH_PW'] !== $validPassword) {
        header('HTTP/1.0 403 Forbidden');
        echo '無効な資格情報です。';
        exit;
    }
}
?>
