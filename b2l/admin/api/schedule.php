if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $home_team_id = $_POST['home_team'];
    $away_team_id = $_POST['away_team'];
    $official_team_id = $_POST['official_team'];

    // 試合スケジュールを保存するためのロジック
    save_game_schedule($home_team_id, $away_team_id, $official_team_id, $game_date);
}
