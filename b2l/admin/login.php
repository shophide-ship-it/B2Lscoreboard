<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 認証処理
    $username = $_POST["username"];
    $password = $_POST["password"];
    
    // 簡易的な認証チェック
    if ($username == "admin" && $password == "password") {
        $_SESSION["loggedin"] = true;
        header("location: index.php");
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理者ログイン | B2L League</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <main>
        <h2>管理者ログイン</h2>
        <form method="POST">
            <label for="username">ユーザー名:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">パスワード:</label>
            <input type="password" id="password" name="password" required>
            <input type="submit" value="ログイン">
        </form>
    </main>
</body>
</html>
