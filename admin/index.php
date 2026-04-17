<?php
// Basic認証の設定
$correct_username = 'b2ladmin';
$correct_password = 'X_MJJk5CfDwv4nf';

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="管理者ログイン"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'このページにアクセスするには認証が必要です。';
    exit;
} else {
    if ($_SERVER['PHP_AUTH_USER'] === $correct_username && $_SERVER['PHP_AUTH_PW'] === $correct_password) {
        // 認証成功
        echo 'ログイン成功!';
    } else {
        // 認証失敗
        echo 'ユーザー名またはパスワードが不正です。';
        exit;
    }
}
?>
// /b2l/register/index.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // バリデーション
    if (empty($_POST['name'])) {
        $errors[] = '選手名は必須です。';
    }
    
    if (!is_numeric($_POST['age']) || $_POST['age'] < 10) {
        $errors[] = '年齢は10歳以上の数字が必要です。';
    }
    
    // エラーチェック
    if (empty($errors)) {
        // データをAPIへ送信
        header('Location: /b2l/api/register.php');
        exit();
    } else {
        foreach ($errors as $error) {
            echo "<p>$error</p>"; // エラーメッセージを表示
        }
    }
}