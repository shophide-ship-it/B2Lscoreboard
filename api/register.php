// /b2l/api/register.php
require_once('../db/Database.php');

$db = new Database();
$conn = $db->getConnection();

// データの受け取り
$name = $_POST['name'];
$age = $_POST['age'];

// プリペアドステートメントでデータを挿入
$stmt = $conn->prepare("INSERT INTO player_registrations (name, age, status) VALUES (?, ?, 'pending')");
$stmt->execute([$name, $age]);

echo "選手登録申請が完了しました。";
