<?php
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者ログイン</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
        }
        .error {
            color: red;
            text-align: center;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>管理者ログイン</h1>
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="post">
            <label for="username">ユーザー名:</label>
            <input type="text" name="username" id="username" required>
            
            <label for="password">パスワード:</label>
            <input type="password" name="password" id="password" required>
            
            <input type="submit" value="ログイン">
        </form>
    </div>
</body>
</html>
