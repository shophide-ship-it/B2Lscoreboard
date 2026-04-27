/**
 * Live Stats UI コンポーネント
 * 
 * 機能: 試合中のボタンタップ入力、リアルタイム更新
 * 使用例:
 *   const liveStats = new LiveStatsUI('container-id', 'session-token');
 *   liveStats.recordScore(playerId, points);
 *   liveStats.recordFoul(playerId);
 */

class LiveStatsUI {
    constructor(containerId, sessionToken) {
        this.container = document.getElementById(containerId);
        this.sessionToken = sessionToken;
        this.gameId = null;
        this.teamId = null;
        this.autoUpdateInterval = null;
        
        // キープアライブ (30秒ごと)
        this.startKeepalive();
        // リアルタイム更新 (3秒ごと)
        this.startAutoUpdate();
    }

    // ==========================================
    // イベント記録メソッド
    // ==========================================

    /**
     * 得点を記録
     */
    recordScore(playerId, points = 2, quarter = 1, minuteInQuarter = 0) {
        return this.recordEvent('score', playerId, {
            points: points
        }, quarter, minuteInQuarter);
    }

    /**
     * ファウルを記録
     */
    recordFoul(playerId, foulType = 'personal', quarter = 1, minuteInQuarter = 0) {
        return this.recordEvent('foul', playerId, {
            foul_type: foulType
        }, quarter, minuteInQuarter);
    }

    /**
     * リバウンドを記録
     */
    recordRebound(playerId, reboundType = 'defensive', quarter = 1, minuteInQuarter = 0) {
        return this.recordEvent('rebound', playerId, {
            rebound_type: reboundType
        }, quarter, minuteInQuarter);
    }

    /**
     * アシストを記録
     */
    recordAssist(playerId, quarter = 1, minuteInQuarter = 0) {
        return this.recordEvent('assist', playerId, {}, quarter, minuteInQuarter);
    }

    /**
     * 交代を記録
     */
    recordSubstitution(playerOut, playerIn, quarter = 1, minuteInQuarter = 0) {
        return this.recordEvent('substitution', playerOut, {
            player_out: playerOut,
            player_in: playerIn
        }, quarter, minuteInQuarter);
    }

    /**
     * 汎用イベント記録
     */
    recordEvent(eventType, playerId, eventData, quarter = 1, minuteInQuarter = 0) {
        const payload = {
            session_token: this.sessionToken,
            event_type: eventType,
            player_id: playerId,
            team_id: this.teamId,
            quarter: quarter,
            minute_in_quarter: minuteInQuarter,
            event_data: eventData
        };

        return fetch('/api/record_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ イベント記録:', data.message);
                this.updateDisplay();  // 画面更新
                return data;
            } else {
                console.error('❌ エラー:', data.error);
                this.showError(data.error);
                throw new Error(data.error);
            }
        })
        .catch(error => {
            console.error('通信エラー:', error);
            this.showError('通信エラーが発生しました');
        });
    }

    // ==========================================
    // リアルタイム更新
    // ==========================================

    startAutoUpdate() {
        if (this.autoUpdateInterval) clearInterval(this.autoUpdateInterval);
        
        this.autoUpdateInterval = setInterval(() => {
            this.updateDisplay();
        }, 3000);  // 3秒ごと
    }

    updateDisplay() {
        if (!this.gameId) return;

        fetch(`/api/get_live_score.php?game_id=${this.gameId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.renderScoreboard(data);
                    this.renderEventLog(data.last_events);
                }
            })
            .catch(error => console.error('更新失敗:', error));
    }

    // ==========================================
    // UI レンダリング
    // ==========================================

    renderScoreboard(data) {
        const html = `
            <div class="live-scoreboard">
                <div class="team home">
                    <div class="team-name">${data.home_team.name}</div>
                    <div class="score">${data.home_team.score}</div>
                    <div class="fouls">FOULS: ${data.home_team.fouls}</div>
                </div>
                
                <div class="game-time">
                    <div class="quarter">Q${data.game_info.quarter}</div>
                    <div class="time">${data.game_info.minute_in_quarter}:00</div>
                </div>
                
                <div class="team away">
                    <div class="team-name">${data.away_team.name}</div>
                    <div class="score">${data.away_team.score}</div>
                    <div class="fouls">FOULS: ${data.away_team.fouls}</div>
                </div>
            </div>
        `;
        
        const scoreboardEl = this.container.querySelector('.scoreboard-area');
        if (scoreboardEl) scoreboardEl.innerHTML = html;
    }

    renderEventLog(events) {
        const html = events.map(e => `
            <div class="event-item event-${e.event_type}">
                <span class="time">${e.timestamp}</span>
                <span class="player">${e.player_name || '-'}</span>
                <span class="event">${this.formatEventType(e.event_type)}</span>
                ${e.points ? `<span class="points">${e.points}pt</span>` : ''}
            </div>
        `).join('');

        const logEl = this.container.querySelector('.event-log');
        if (logEl) logEl.innerHTML = html;
    }

    // ==========================================
    // セッション管理
    // ==========================================

    startKeepalive() {
        setInterval(() => {
            fetch('/api/session.php?action=keepalive', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_token: this.sessionToken })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.warn('⚠️ セッション警告:', data.error);
                    this.showWarning('セッションが切れる可能性があります');
                }
            })
            .catch(error => console.error('キープアライブ失敗:', error));
        }, 30000);  // 30秒ごと
    }

    logout() {
        return fetch('/api/session.php?action=logout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session_token: this.sessionToken })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('✅ ログアウト:', data.message);
                if (this.autoUpdateInterval) clearInterval(this.autoUpdateInterval);
                return true;
            }
            return false;
        });
    }

    // ==========================================
    // ユーティリティ
    // ==========================================

    formatEventType(type) {
        const labels = {
            'score': '得点',
            'foul': 'ファウル',
            'rebound': 'リバウンド',
            'assist': 'アシスト',
            'substitution': '交代',
            'steal': 'スティール',
            'block': 'ブロック'
        };
        return labels[type] || type;
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.textContent = '❌ ' + message;
        this.container.prepend(alert);
        setTimeout(() => alert.remove(), 5000);
    }

    showWarning(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-warning';
        alert.textContent = '⚠️ ' + message;
        this.container.prepend(alert);
        setTimeout(() => alert.remove(), 5000);
    }
}

// ==========================================
// 初期化例
// ==========================================
/*
document.addEventListener('DOMContentLoaded', function() {
    const liveStats = new LiveStatsUI('live-stats-container', 'session-token-here');
    liveStats.gameId = 123;
    liveStats.teamId = 1;

    // ボタンのイベントリスナー設定
    document.querySelectorAll('.btn-score').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const playerId = parseInt(btn.dataset.playerId);
            const points = parseInt(btn.dataset.points) || 2;
            liveStats.recordScore(playerId, points);
        });
    });

    document.querySelectorAll('.btn-foul').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const playerId = parseInt(btn.dataset.playerId);
            liveStats.recordFoul(playerId);
        });
    });
});
*/
