// /b2l/admin/login.php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ログイン処理
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // ここでユーザー名とパスワードを確認
    if ($username === 'admin' && $password === 'password') { // 例: ハードコーディングでの確認
        $_SESSION['user_id'] = $username;
        header('Location: /b2l/admin/player_approvals.php');
        exit();
    } else {
        $error = "ユーザー名またはパスワードが不正です。";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理者ログイン</title>
</head>
<body>
    <h1>管理者ログイン</h1>
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <form method="post">
        <label for="username">ユーザー名:</label>
        <input type="text" name="username" id="username" required>
        <br>
        <label for="password">パスワード:</label>
        <input type="password" name="password" id="password" required>
        <br>
        <input type="submit" value="ログイン">
    </form>
</body>
</html>

