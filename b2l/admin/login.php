<?php
// データベース接続設定
$servername = "mysql3114.db.sakura.ne.jp"; // DBホスト
$username = "kasugai-sp_b2l-league"; // DBユーザー名
$password = "B2L_db2025secure"; // DBパスワード
$dbname = "kasugai-sp_b2l-league"; // DB名

// 接続
$conn = new mysqli($servername, $username, $password, $dbname);

// 接続チェック
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// フォームデータの取得
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];

    // ユーザーの取得
    $sql = "SELECT * FROM admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $input_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $hashed_password = $row['password'];

        // パスワードの照合
        if (password_verify($input_password, $hashed_password)) {
            // 認証成功
            echo "ログイン成功！";
            // セッション開始、リダイレクト等をここで行う
        } else {
            echo "パスワードが間違っています。";
        }
    } else {
        echo "ユーザー名が存在しません。";
    }
}

$conn->close();
?>
