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
