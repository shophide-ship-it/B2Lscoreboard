<?php
ini_set('display_errors', 1);   // エラーを表示
error_reporting(E_ALL);           // すべてのエラーを報告

// ハッシュ化したいパスワードを設定
$password = 'X_MJJk5CfDwv4nf'; // 実際のパスワードをここに記入

// パスワードのハッシュ生成
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// ハッシュを表示
echo "Hashed Password: " . $hashed_password;
?>
