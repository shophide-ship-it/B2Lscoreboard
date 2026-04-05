// ===== ゲームスコアリングロジック =====
const Game = {
    data: null,
    currentTeam: 'home',
    selectedPlayer: null,
    quarter: 1,
    playLog: [],

    async loadGame(gameId) {
        try {
            this.data = await App.api('/games.php?action=detail&id=' + gameId);
            this.quarter = this.data.live ? this.data.live.current_quarter : 1;
            this.selectedPlayer = null;
            this.playLog = [];
            this.renderScoreboard();
            this.renderPlayers();
            this.hideActionPanel();
            this.refreshPlayLog();
            App.showScreen('scoring');

            // ライブゲームが未開始なら開始確認
            if (!this.data.live || this.data.live.status === 'ready') {
                if (confirm('この試合を開始しますか？')) {
                    await App.api('/games.php?action=start', {
                        method: 'POST',
                        body: JSON.stringify({ game_id: gameId })
                    });
                }
            }
        } catch (e) {
            alert('試合データの取得に失敗: ' + e.message);
        }
    },

    renderScoreboard() {
        const d = this.data;
        const live = d.live || {};
        document.getElementById('home-name').textContent = d.home_short || d.home_team_name;
        document.getElementById('away-name').textContent = d.away_short || d.away_team_name;
        document.getElementById('home-score').textContent = live.home_score || 0;
        document.getElementById('away-score').textContent = live.away_score || 0;
        document.getElementById('home-fouls').textContent = live.home_fouls || 0;
        document.getElementById('away-fouls').textContent = live.away_fouls || 0;
        document.getElementById('quarter-display').textContent =
            this.quarter <= 4 ? this.quarter + 'Q' : 'OT';
        document.getElementById('tab-home').textContent = d.home_short || 'HOME';
        document.getElementById('tab-away').textContent = d.away_short || 'AWAY';
    },

    renderPlayers() {
        const players = this.currentTeam === 'home'
            ? this.data.home_players
            : this.data.away_players;
        const grid = document.getElementById('players-grid');

        grid.innerHTML = players.map(p => `
            <div class="player-btn ${p.is_oncourt ? 'oncourt' : ''} ${this.selectedPlayer && this.selectedPlayer.id == p.id ? 'selected' : ''}"
                 onclick="Game.selectPlayer(${p.id})" data-pid="${p.id}">
                <div class="number">#${p.number}</div>
                <div class="name">${p.name}</div>
                <div class="pts">${p.live_points || 0}pts</div>
            </div>
        `).join('');
    },

    selectTeam(team) {
        this.currentTeam = team;
        this.selectedPlayer = null;
        this.hideActionPanel();
        document.getElementById('tab-home').classList.toggle('active', team === 'home');
        document.getElementById('tab-away').classList.toggle('active', team === 'away');
        this.renderPlayers();
    },

    selectPlayer(playerId) {
        const players = this.currentTeam === 'home'
            ? this.data.home_players
            : this.data.away_players;
        this.selectedPlayer = players.find(p => p.id == playerId);

        if (this.selectedPlayer) {
            document.getElementById('selected-player-name').textContent =
                `#${this.selectedPlayer.number} ${this.selectedPlayer.name}`;
            document.getElementById('action-panel').classList.remove('hidden');
        }
        this.renderPlayers();
    },

    deselectPlayer() {
        this.selectedPlayer = null;
        this.hideActionPanel();
        this.renderPlayers();
    },

    hideActionPanel() {
        document.getElementById('action-panel').classList.add('hidden');
    },

    async record(actionType, points) {
        if (!this.selectedPlayer) return;

        const teamId = this.currentTeam === 'home'
            ? this.data.home_team_id
            : this.data.away_team_id;

        // 触覚フィードバック
        if (navigator.vibrate) navigator.vibrate(50);

        // FGM → FGA も自動追加
        let autoActions = [];
        if (actionType === 'fgm') autoActions.push({ action: 'fga', pts: 0 });
        if (actionType === '3pm') {
            autoActions.push({ action: '3pa', pts: 0 });
            autoActions.push({ action: 'fgm', pts: 0 });
            autoActions.push({ action: 'fga', pts: 0 });
        }
        if (actionType === 'ftm') autoActions.push({ action: 'fta', pts: 0 });

        try {
            // メインアクション送信
            const result = await App.api('/live-stats.php?action=record', {
                method: 'POST',
                body: JSON.stringify({
                    game_id: this.data.id,
                    quarter: this.quarter,
                    game_time: document.getElementById('time-display').textContent,
                    team_id: teamId,
                    player_id: this.selectedPlayer.id,
                    action_type: actionType,
                    points: points
                })
            });

            // 自動付随アクション
            for (const auto of autoActions) {
                await App.api('/live-stats.php?action=record', {
                    method: 'POST',
                    body: JSON.stringify({
                        game_id: this.data.id,
                        quarter: this.quarter,
                        game_time: document.getElementById('time-display').textContent,
                        team_id: teamId,
                        player_id: this.selectedPlayer.id,
                        action_type: auto.action,
                        points: auto.pts
                    })
                });
            }

            // プレイログに追加（UNDO用）
            this.playLog.unshift({
                id: result.play_id,
                player: `#${this.selectedPlayer.number} ${this.selectedPlayer.name}`,
                action: actionType,
                points: points,
                team: this.currentTeam
            });

            // スコアボード更新
            if (result.scores) {
                document.getElementById('home-score').textContent = result.scores.home_score;
                document.getElementById('away-score').textContent = result.scores.away_score;
                document.getElementById('home-fouls').textContent = result.scores.home_fouls;
                document.getElementById('away-fouls').textContent = result.scores.away_fouls;
                // data内も更新
                if (!this.data.live) this.data.live = {};
                Object.assign(this.data.live, result.scores);
            }

            // 選手ポイント更新
            this.selectedPlayer.live_points = (parseInt(this.selectedPlayer.live_points) || 0) + points;
            this.renderPlayers();
            this.renderPlayLog();

            // 成功フラッシュ
            this.flash(points > 0 ? 'made' : (actionType.includes('foul') || actionType === 'turnover' ? 'neg' : 'neutral'));

        } catch (e) {
            alert('記録エラー: ' + e.message);
        }
    },

    flash(type) {
        const colors = { made: '#00c853', neg: '#ff5252', neutral: '#0f3460' };
        const el = document.getElementById('scoreboard');
        el.style.transition = 'box-shadow 0.15s';
        el.style.boxShadow = `inset 0 0 30px ${colors[type] || colors.neutral}`;
        setTimeout(() => { el.style.boxShadow = 'none'; }, 300);
    },

    renderPlayLog() {
        const list = document.getElementById('play-list');
        const actionLabels = {
            'fgm': '2P○', 'fga': '2P✕', '3pm': '3P○', '3pa': '3P✕',
            'ftm': 'FT○', 'fta': 'FT✕', 'oreb': 'OREB', 'dreb': 'DREB',
            'ast': 'AST', 'stl': 'STL', 'blk': 'BLK',
            'turnover': 'TO', 'foul': 'FOUL'
        };
        // メインアクションのみ表示（自動付随は除外）
        const mainPlays = this.playLog.filter(p =>
            !(['fga', 'fta', '3pa'].includes(p.action) && p.points === 0 &&
                this.playLog.some(q => q.id > p.id))
        );

        list.innerHTML = mainPlays.slice(0, 10).map(p => `
            <div class="play-item">
                <span class="play-desc">
                    ${p.player} <strong>${actionLabels[p.action] || p.action}</strong>
                    ${p.points > 0 ? `+${p.points}` : ''}
                </span>
                <button class="play-undo" onclick="Game.undo(${p.id})">UNDO</button>
            </div>
        `).join('');
    },

    async undo(playId) {
        if (!confirm('この記録を取り消しますか？')) return;
        try {
            await App.api('/live-stats.php?action=undo', {
                method: 'POST',
                body: JSON.stringify({ play_id: playId })
            });

            // スコア再取得
            const updated = await App.api('/games.php?action=detail&id=' + this.data.id);
            this.data = updated;
            this.renderScoreboard();
            this.renderPlayers();

            // サーバーからプレイログ再取得（ローカルフィルタではなく完全同期）
            await this.refreshPlayLog();

        } catch (e) {
            alert('取消エラー: ' + e.message);
        }
    },

    async nextQuarter() {
        if (this.quarter >= 5) return;
        this.quarter++;
        try {
            await App.api('/live-stats.php?action=quarter', {
                method: 'POST',
                body: JSON.stringify({ game_id: this.data.id, quarter: this.quarter })
            });
        } catch (e) { /* サーバー同期失敗しても進める */ }
        document.getElementById('quarter-display').textContent =
            this.quarter <= 4 ? this.quarter + 'Q' : 'OT';
    },

    prevQuarter() {
        if (this.quarter <= 1) return;
        this.quarter--;
        document.getElementById('quarter-display').textContent =
            this.quarter <= 4 ? this.quarter + 'Q' : 'OT';
    },

    async toggleBoxScore() {
        const overlay = document.getElementById('boxscore-overlay');
        if (!overlay.classList.contains('hidden')) {
            overlay.classList.add('hidden');
            return;
        }
        try {
            const stats = await App.api('/live-stats.php?action=boxscore&id=' + this.data.id);
            const content = document.getElementById('boxscore-content');

            const renderTeamTable = (teamStats, teamName) => {
                if (teamStats.length === 0) return `<p>${teamName}: データなし</p>`;
                return `
                    <h4 style="color:var(--primary);margin:10px 0 5px">${teamName}</h4>
                    <table class="box-table">
                        <tr><th>#</th><th>選手</th><th>PTS</th><th>FG</th><th>3P</th><th>FT</th><th>REB</th><th>AST</th><th>STL</th><th>BLK</th><th>TO</th><th>F</th></tr>
                        ${teamStats.map(s => `
                            <tr>
                                <td>${s.number}</td><td style="text-align:left">${s.name}</td>
                                <td><strong>${s.points}</strong></td>
                                <td>${s.fgm}/${s.fga}</td>
                                <td>${s.three_pm}/${s.three_pa}</td>
                                <td>${s.ftm}/${s.fta}</td>
                                <td>${s.oreb + s.dreb}</td>
                                <td>${s.ast}</td><td>${s.stl}</td><td>${s.blk}</td>
                                <td>${s.turnovers}</td><td>${s.fouls}</td>
                            </tr>
                        `).join('')}
                    </table>
                `;
            };

            const homeStats = stats.filter(s => s.team_id == this.data.home_team_id);
            const awayStats = stats.filter(s => s.team_id == this.data.away_team_id);

            content.innerHTML =
                renderTeamTable(homeStats, this.data.home_team_name) +
                renderTeamTable(awayStats, this.data.away_team_name);

            overlay.classList.remove('hidden');
        } catch (e) {
            alert('ボックススコア取得エラー');
        }
    },

    async refreshPlayLog() {
        try {
            const plays = await App.api('/live-stats.php?action=plays&id=' + this.data.id);
            this.playLog = plays.map(p => ({
                id: p.id,
                player: `#${p.player_number} ${p.player_name}`,
                action: p.action_type,
                points: p.points,
                team: p.team_short
            }));
            this.renderPlayLog();
        } catch (e) { /* 初回読み込みなので無視 */ }
    },

    async finishGame() {
        if (!confirm('試合を終了してスタッツを確定しますか？\nこの操作は取り消せません。')) return;
        if (!confirm('本当に終了しますか？（最終確認）')) return;

        try {
            const result = await App.api('/live-stats.php?action=finish', {
                method: 'POST',
                body: JSON.stringify({ game_id: this.data.id })
            });
            alert('✅ ' + result.message);
            App.fetchGames();
            App.showScreen('games');
        } catch (e) {
            alert('終了処理エラー: ' + e.message);
        }
    }
};
