// ===== B2L スコアラーアプリ メイン =====
const API_BASE = '/b2l/api';

const App = {
    token: localStorage.getItem('b2l_token') || '',

    async init() {
        if (this.token) {
            try {
                await this.fetchGames();
                this.showScreen('games');
            } catch (e) {
                console.log('ゲーム取得失敗、ログイン画面へ:', e);
                this.showScreen('login');
            }
        } else {
            this.showScreen('login');
        }
    },

    showScreen(name) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById('screen-' + name).classList.add('active');
    },

    async login() {
        const token = document.getElementById('token-input').value.trim();
        if (!token) return;

        const errorEl = document.getElementById('login-error');
        errorEl.textContent = '';

        try {
            const res = await fetch(API_BASE + '/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token })
            });
            const data = await res.json();

            if (data.success) {
                this.token = token;
                localStorage.setItem('b2l_token', token);

                // ゲーム取得を試みるが、失敗してもログインは成功
                try {
                    await this.fetchGames();
                } catch (e) {
                    console.log('ゲーム一覧取得スキップ:', e);
                }

                this.showScreen('games');
            } else {
                errorEl.textContent = data.error || 'ログイン失敗';
            }
        } catch (e) {
            console.error('ログインエラー:', e);
            errorEl.textContent = '通信エラー: ' + e.message;
        }
    },

    logout() {
        this.token = '';
        localStorage.removeItem('b2l_token');
        this.showScreen('login');
    },

    async api(endpoint, options = {}) {
        const url = API_BASE + endpoint;
        const headers = {
            'Content-Type': 'application/json',
            'X-Auth-Token': this.token
        };

        const res = await fetch(url, { ...options, headers });
        if (res.status === 401) {
            this.logout();
            throw new Error('認証エラー');
        }
        return res.json();
    },

    async fetchGames() {
        const games = await this.api('/games.php?action=upcoming');
        const list = document.getElementById('games-list');
        if (games.length === 0) {
            list.innerHTML = '<p style="text-align:center;color:#888;padding:40px">予定されている試合はありません</p>';
            return;
        }
        list.innerHTML = games.map(g => `
            <div class="game-card ${g.live_status === 'q1' || g.live_status === 'q2' || g.live_status === 'q3' || g.live_status === 'q4' ? 'live' : ''}"
                 onclick="Game.loadGame(${g.id})">
                <div class="teams">
                    <span>${g.home_team_name}</span>
                    <span class="vs">VS</span>
                    <span>${g.away_team_name}</span>
                </div>
                <div class="meta">
                    📅 ${g.game_date} 🏀 ${g.game_time || '--:--'}
                    ${g.live_status && g.live_status !== 'ready' && g.live_status !== 'finished'
                ? '<br>🔴 ' + g.live_home_score + ' - ' + g.live_away_score + ' (' + g.live_status.toUpperCase() + ')'
                : ''}
                </div>
            </div>
        `).join('');
    }
};

// 起動
document.addEventListener('DOMContentLoaded', () => App.init());
