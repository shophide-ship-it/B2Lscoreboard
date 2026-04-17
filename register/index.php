<?php
// POSTリクエストの処理
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
        // データをAPIへ送信 (例: cURLを使うことが一般的です)
        header('Location: /b2l/api/register.php');
        exit();
    } else {
        // エラーメッセージを表示
        foreach ($errors as $error) {
            echo "<p style='color: red;'>$error</p>";
        }
    }
}
?>

<!-- フォーム -->
<form action="/b2l/register/index.php" method="post">
    <label for="name">選手名:</label>
    <input type="text" name="name" id="name" required>
    
    <label for="age">年齢:</label>
    <input type="number" name="age" id="age" required min="10">

    <input type="submit" value="登録">
</form>
