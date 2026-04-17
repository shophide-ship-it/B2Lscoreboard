<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2L League Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>B2L League Dashboard</h1>
    </header>
    <main>
        <section class="standings">
            <h2>Ranking</h2>
            <table id="standings-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Team</th>
                        <th>Wins</th>
                        <th>Losses</th>
                        <th>Winning Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- 動的にデータを挿入 -->
                </tbody>
            </table>
        </section>
        <section class="games">
            <h2>Upcoming Games</h2>
            <ul id="games-list">
                <!-- 動的にデータを挿入 -->
            </ul>
        </section>
    </main>
    <script src="script.js"></script>
</body>
</html>
