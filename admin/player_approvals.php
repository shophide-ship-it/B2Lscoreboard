<?php
// /b2l/admin/player_approvals.php
require_once('../db/Database.php');

// 認証チェック（例）
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /b2l/admin/login.php'); // ログインページへリダイレクト
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// 未承認の選手登録申請を取得
$stmt = $conn->prepare("SELECT * FROM player_registrations WHERE status = 'pending'");
$stmt->execute();
$pendingRegistrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>未承認リスト</title>
</head>
<body>
    <h1>未承認選手登録申請一覧</h1>

    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>名前</th>
                <th>年齢</th>
                <th>申請日</th>
                <th>アクション</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pendingRegistrations)): ?>
                <tr>
                    <td colspan="5">未承認の申請はありません。</td>
                </tr>
            <?php else: ?>
                <?php foreach ($pendingRegistrations as $registration): ?>
                    <tr>
                        <td><?= htmlspecialchars($registration['id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($registration['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($registration['age'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($registration['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a href="approve.php?id=<?= htmlspecialchars($registration['id'], ENT_QUOTES, 'UTF-8') ?>">承認</a>
                            <a href="reject.php?id=<?= htmlspecialchars($registration['id'], ENT_QUOTES, 'UTF-8') ?>">却下</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
