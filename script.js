document.addEventListener('DOMContentLoaded', function() {
    // スタンドingsデータの取得と表示
    fetch('/b2l/api/teams/standings.php')
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById('standings-table').getElementsByTagName('tbody')[0];
            data.forEach(record => {
                const row = tableBody.insertRow();
                row.innerHTML = `<td>${record.rank}</td>
                                 <td>${record.team_name}</td>
                                 <td>${record.wins}</td>
                                 <td>${record.losses}</td>
                                 <td>${record.win_percentage}</td>`;
            });
        });

    // 試合データの取得と表示
    fetch('/b2l/api/games/upcoming.php')
        .then(response => response.json())
        .then(data => {
            const gamesList = document.getElementById('games-list');
            data.forEach(game => {
                const listItem = document.createElement('li');
                listItem.textContent = `${game.home_team} vs ${game.away_team} - ${game.date_time}`;
                gamesList.appendChild(listItem);
            });
        });
});
