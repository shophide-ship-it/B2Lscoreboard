<?php
// admin_password.php
$password = 'X_MJJk5CfDwv4nf'; // 元のパスワード
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo $hashed_password; // ハッシュ化されたパスワードを表示
?>
