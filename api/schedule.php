<?php
// db.phpをインクルードして接続
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // スケジュールデータの取得
        $team1 = $_POST['team1'];
        $team2 = $_POST['team2'];
        $date = $_POST['date'];
        $time = $_POST['time'];

        // SQLクエリの準備
        $stmt = $pdo->prepare("INSERT INTO game_schedule (team1, team2, date, time) VALUES (?, ?, ?, ?)");
        $stmt->execute([$team1, $team2, $date, $time]);

        echo json_encode(['status' => 'success', 'message' => 'スケジュールが登録されました。']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
