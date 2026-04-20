<?php
ini_set('display_errors', 1);   // エラーを表示
error_reporting(E_ALL);           // すべてのエラーを報告

// 例: 存在しない変数を出力しようとする
echo $undefined_variable;  // ここでエラーが発生します

// パスワードのハッシュ生成
$password = 'your_password'; // 実際のパスワードを使用
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "Hashed Password: " . $hashed_password; // ハッシュを表示
?>
