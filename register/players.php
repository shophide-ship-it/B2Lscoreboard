<?php
// B2L League - Player Registration Page
// All Japanese text uses numeric character references to avoid encoding issues
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>B2L League - &#36984;&#25163;&#30331;&#37682;</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', sans-serif;
    background: #f0f2f5;
    color: #333;
    line-height: 1.6;
}
.container { max-width: 800px; margin: 0 auto; padding: 16px; }

/* Header */
.header {
    background: linear-gradient(135deg, #1a237e, #283593);
    color: #fff;
    padding: 20px 16px;
    text-align: center;
    border-radius: 12px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.header h1 { font-size: 20px; margin-bottom: 4px; }
.header .team-name { font-size: 24px; font-weight: bold; margin: 8px 0; }
.header .division-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 4px 16px;
    border-radius: 20px;
    font-size: 14px;
}
.header .deadline {
    margin-top: 8px;
    font-size: 13px;
    opacity: 0.9;
}

/* Info bar */
.info-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.player-count { font-size: 16px; font-weight: bold; }
.player-count .current { color: #1a237e; font-size: 24px; }

/* Form */
.form-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.form-card h2 {
    font-size: 16px;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #1a237e;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}
.form-row.full { grid-template-columns: 1fr; }
.form-group { display: flex; flex-direction: column; }
.form-group label {
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 4px;
    color: #555;
}
.form-group label .required {
    color: #d32f2f;
    margin-left: 4px;
}
.form-group input,
.form-group select {
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s;
    -webkit-appearance: none;
}
.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #1a237e;
    box-shadow: 0 0 0 3px rgba(26,35,126,0.1);
}
.btn {
    display: block;
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-primary {
    background: #1a237e;
    color: #fff;
}
.btn-primary:hover { background: #283593; }
.btn-primary:disabled {
    background: #9e9e9e;
    cursor: not-allowed;
}
.btn-danger {
    background: #d32f2f;
    color: #fff;
}
.btn-danger:hover { background: #b71c1c; }
.btn-secondary {
    background: #757575;
    color: #fff;
}
.btn-secondary:hover { background: #616161; }
.btn-sm {
    display: inline-block;
    width: auto;
    padding: 6px 14px;
    font-size: 13px;
    border-radius: 6px;
}

/* Player list */
.player-list {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.player-list h2 {
    font-size: 16px;
    padding: 16px 16px 12px;
    border-bottom: 2px solid #1a237e;
    margin: 0;
}
.player-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.15s;
}
.player-item:hover { background: #f8f9ff; }
.player-item:last-child { border-bottom: none; }
.player-number {
    width: 44px;
    height: 44px;
    background: #1a237e;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
    margin-right: 12px;
    flex-shrink: 0;
}
.player-info { flex: 1; }
.player-name { font-size: 16px; font-weight: bold; }
.player-detail {
    font-size: 13px;
    color: #777;
    margin-top: 2px;
}
.player-actions { display: flex; gap: 8px; }
.empty-message {
    text-align: center;
    padding: 40px 16px;
    color: #999;
    font-size: 14px;
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.modal-overlay.active { display: flex; }
.modal {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow-y: auto;
}
.modal h3 {
    font-size: 18px;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #1a237e;
}
.modal .btn-row {
    display: flex;
    gap: 8px;
    margin-top: 16px;
}
.modal .btn-row .btn { flex: 1; }

/* Messages */
.message {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 12px;
    font-size: 14px;
    display: none;
}
.message.success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}
.message.error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}
.message.show { display: block; }

/* Loading */
.loading {
    text-align: center;
    padding: 40px;
    color: #999;
}
.spinner {
    display: inline-block;
    width: 32px;
    height: 32px;
    border: 3px solid #e0e0e0;
    border-top-color: #1a237e;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Error page */
.error-page {
    text-align: center;
    padding: 60px 16px;
}
.error-page h2 { color: #d32f2f; margin-bottom: 12px; }
.error-page p { color: #777; }

/* Responsive */
@media (max-width: 480px) {
    .form-row { grid-template-columns: 1fr; }
    .player-actions { flex-direction: column; }
    .player-actions .btn-sm { width: 100%; text-align: center; }
}
</style>
</head>
<body>

<div class="container" id="app">
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p style="margin-top:12px;">&#35501;&#12415;&#36796;&#12415;&#20013;...</p>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h3>&#9998; &#36984;&#25163;&#24773;&#22577;&#32232;&#38598;</h3>
        <input type="hidden" id="editPlayerId">
        <div class="form-row">
            <div class="form-group">
                <label>&#32972;&#30058;&#21495;<span class="required">*</span></label>
                <input type="number" id="editNumber" min="0" max="99">
            </div>
            <div class="form-group">
                <label>&#12509;&#12472;&#12471;&#12519;&#12531;<span class="required">*</span></label>
                <select id="editPosition">
                    <option value="PG">PG</option>
                    <option value="SG">SG</option>
                    <option value="SF">SF</option>
                    <option value="PF">PF</option>
                    <option value="C">C</option>
                </select>
            </div>
        </div>
        <div class="form-row full">
            <div class="form-group">
                <label>&#27663;&#21517;<span class="required">*</span></label>
                <input type="text" id="editName" maxlength="100">
            </div>
        </div>
        <div class="form-row full">
            <div class="form-group">
                <label>&#36523;&#38263; (cm)</label>
                <input type="number" id="editHeight" min="100" max="250" step="0.1">
            </div>
        </div>
        <div id="editError" class="message error"></div>
        <div class="btn-row">
            <button class="btn btn-secondary" onclick="closeEditModal()">&#12461;&#12515;&#12531;&#12475;&#12523;</button>
            <button class="btn btn-primary" id="editSaveBtn" onclick="saveEdit()">&#20445;&#23384;</button>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>&#36984;&#25163;&#21066;&#38500;&#30906;&#35469;</h3>
        <input type="hidden" id="deletePlayerId">
        <p style="margin-bottom:16px;">
            <strong id="deletePlayerInfo"></strong> &#12434;&#21066;&#38500;&#12375;&#12414;&#12377;&#12363;&#65311;
        </p>
        <p style="font-size:13px;color:#d32f2f;margin-bottom:16px;">
            &#8251; &#12371;&#12398;&#25805;&#20316;&#12399;&#21462;&#12426;&#28040;&#12379;&#12414;&#12379;&#12435;
        </p>
        <div class="btn-row">
            <button class="btn btn-secondary" onclick="closeDeleteModal()">&#12461;&#12515;&#12531;&#12475;&#12523;</button>
            <button class="btn btn-danger" id="deleteConfirmBtn" onclick="confirmDelete()">&#21066;&#38500;</button>
        </div>
    </div>
</div>

<script>
// ===== Configuration =====
const API_BASE = '/b2l/api/players';
const POSITIONS = ['PG', 'SG', 'SF', 'PF', 'C'];

// Get token from URL
const urlParams = new URLSearchParams(window.location.search);
const TOKEN = urlParams.get('token') || '';

let teamData = null;
let playersData = [];
let isOpen = false;

// ===== Initialize =====
document.addEventListener('DOMContentLoaded', function() {
    if (!TOKEN) {
        showError('&#12488;&#12540;&#12463;&#12531;&#12364;&#25351;&#23450;&#12373;&#12428;&#12390;&#12356;&#12414;&#12379;&#12435;', '&#12481;&#12540;&#12512;&#23554;&#29992;URL&#12363;&#12425;&#12450;&#12463;&#12475;&#12473;&#12375;&#12390;&#12367;&#12384;&#12373;&#12356;&#12290;');
        return;
    }
    loadPlayers();
});

// ===== API Calls =====
async function loadPlayers() {
    showLoading(true);
    try {
        const res = await fetch(API_BASE + '/list.php?token=' + encodeURIComponent(TOKEN));
        const data = await res.json();
        if (!data.success) {
            showError('&#12456;&#12521;&#12540;', data.error || '&#12487;&#12540;&#12479;&#12398;&#21462;&#24471;&#12395;&#22833;&#25943;&#12375;&#12414;&#12375;&#12383;');
            return;
        }
        teamData = data.team;
        playersData = data.players;
        isOpen = data.registration_open;
        renderPage();
    } catch (e) {
        showError('&#36890;&#20449;&#12456;&#12521;&#12540;', '&#12469;&#12540;&#12496;&#12540;&#12395;&#25509;&#32154;&#12391;&#12365;&#12414;&#12379;&#12435;&#12391;&#12375;&#12383;');
    }
}

async function registerPlayer(formData) {
    try {
        const res = await fetch(API_BASE + '/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: TOKEN, ...formData })
        });
        return await res.json();
    } catch (e) {
        return { success: false, error: 'Network error' };
    }
}

async function updatePlayer(playerId, formData) {
    try {
        const res = await fetch(API_BASE + '/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: TOKEN, player_id: playerId, ...formData })
        });
        return await res.json();
    } catch (e) {
        return { success: false, error: 'Network error' };
    }
}

async function deletePlayer(playerId) {
    try {
        const res = await fetch(API_BASE + '/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token: TOKEN, player_id: playerId })
        });
        return await res.json();
    } catch (e) {
        return { success: false, error: 'Network error' };
    }
}

// ===== Render =====
function renderPage() {
    const app = document.getElementById('app');
    const divNames = { 1: '1\u90E8', 2: '2\u90E8', 3: '3\u90E8' };
    const divName = divNames[teamData.division] || teamData.division + '\u90E8';
    const maxPlayers = <?php require_once __DIR__ . '/../config.php'; echo PLAYER_MAX_PER_TEAM; ?>;
    const deadline = '<?php echo PLAYER_REGISTRATION_DEADLINE; ?>';

    let html = '';

    // Header
    html += '<div class="header">';
    html += '<h1>B2L League \u9078\u624B\u767B\u9332</h1>';
    html += '<div class="team-name">' + escapeHtml(teamData.name) + '</div>';
    html += '<div class="division-badge">' + divName + '</div>';
    html += '<div class="deadline">\u767B\u9332\u671F\u9650: ' + deadline + '</div>';
    html += '</div>';

    // Message area
    html += '<div id="message" class="message"></div>';

    // Info bar
    html += '<div class="info-bar">';
    html += '<div class="player-count">\u767B\u9332\u4EBA\u6570: <span class="current">' + playersData.length + '</span> / ' + maxPlayers + '</div>';
    if (!isOpen) {
        html += '<span style="color:#d32f2f;font-weight:bold;">\u767B\u9332\u671F\u9650\u7D42\u4E86</span>';
    }
    html += '</div>';

    // Registration form (only if open and under limit)
    if (isOpen && playersData.length < maxPlayers) {
        html += '<div class="form-card">';
        html += '<h2>\u65B0\u898F\u9078\u624B\u767B\u9332</h2>';
        html += '<form id="registerForm" onsubmit="handleRegister(event)">';
        html += '<div class="form-row">';
        html += '<div class="form-group"><label>\u80CC\u756A\u53F7<span class="required">*</span></label>';
        html += '<input type="number" id="regNumber" min="0" max="99" required placeholder="0-99"></div>';
        html += '<div class="form-group"><label>\u30DD\u30B8\u30B7\u30E7\u30F3<span class="required">*</span></label>';
        html += '<select id="regPosition" required>';
        html += '<option value="">\u9078\u629E...</option>';
        POSITIONS.forEach(p => { html += '<option value="'+p+'">'+p+'</option>'; });
        html += '</select></div>';
        html += '</div>';
        html += '<div class="form-row full">';
        html += '<div class="form-group"><label>\u6C0F\u540D<span class="required">*</span></label>';
        html += '<input type="text" id="regName" maxlength="100" required placeholder="\u4F8B: \u5C71\u7530 \u592A\u90CE"></div>';
        html += '</div>';
        html += '<div class="form-row full">';
        html += '<div class="form-group"><label>\u8EAB\u9577 (cm)</label>';
        html += '<input type="number" id="regHeight" min="100" max="250" step="0.1" placeholder="\u4F8B: 175.5"></div>';
        html += '</div>';
        html += '<button type="submit" class="btn btn-primary" id="regBtn">\u767B\u9332</button>';
        html += '</form>';
        html += '</div>';
    }

    // Player list
    html += '<div class="player-list">';
    html += '<h2>\u767B\u9332\u9078\u624B\u4E00\u89A7 (' + playersData.length + '\u540D)</h2>';

    if (playersData.length === 0) {
        html += '<div class="empty-message">\u767B\u9332\u3055\u308C\u305F\u9078\u624B\u306F\u3044\u307E\u305B\u3093</div>';
    } else {
        playersData.forEach(function(p) {
            html += '<div class="player-item">';
            html += '<div class="player-number">' + p.number + '</div>';
            html += '<div class="player-info">';
            html += '<div class="player-name">' + escapeHtml(p.name) + '</div>';
            html += '<div class="player-detail">' + p.position;
            if (p.height) html += ' / ' + p.height + 'cm';
            html += '</div></div>';
            if (isOpen) {
                html += '<div class="player-actions">';
                html += '<button class="btn btn-primary btn-sm" onclick="openEditModal(' + p.id + ')">\u7DE8\u96C6</button>';
                html += '<button class="btn btn-danger btn-sm" onclick="openDeleteModal(' + p.id + ')">\u524A\u9664</button>';
                html += '</div>';
            }
            html += '</div>';
        });
    }
    html += '</div>';

    app.innerHTML = html;
}

// ===== Handlers =====
async function handleRegister(e) {
    e.preventDefault();
    const btn = document.getElementById('regBtn');
    btn.disabled = true;
    btn.textContent = '\u767B\u9332\u4E2D...';
    hideMessage();

    const formData = {
        number: parseInt(document.getElementById('regNumber').value),
        name: document.getElementById('regName').value.trim(),
        position: document.getElementById('regPosition').value,
        height: document.getElementById('regHeight').value || null
    };

    if (formData.height) formData.height = parseFloat(formData.height);

    const result = await registerPlayer(formData);

    if (result.success) {
        showMessage('\u9078\u624B\u3092\u767B\u9332\u3057\u307E\u3057\u305F', 'success');
        await loadPlayers();
    } else {
        showMessage(result.error || '\u767B\u9332\u306B\u5931\u6557\u3057\u307E\u3057\u305F', 'error');
        btn.disabled = false;
        btn.textContent = '\u767B\u9332';
    }
}

function openEditModal(playerId) {
    const player = playersData.find(p => p.id === playerId);
    if (!player) return;
    document.getElementById('editPlayerId').value = player.id;
    document.getElementById('editNumber').value = player.number;
    document.getElementById('editName').value = player.name;
    document.getElementById('editPosition').value = player.position;
    document.getElementById('editHeight').value = player.height || '';
    document.getElementById('editError').classList.remove('show');
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

async function saveEdit() {
    const btn = document.getElementById('editSaveBtn');
    btn.disabled = true;
    btn.textContent = '\u4FDD\u5B58\u4E2D...';

    const playerId = parseInt(document.getElementById('editPlayerId').value);
    const formData = {
        number: parseInt(document.getElementById('editNumber').value),
        name: document.getElementById('editName').value.trim(),
        position: document.getElementById('editPosition').value,
        height: document.getElementById('editHeight').value || null
    };
    if (formData.height) formData.height = parseFloat(formData.height);

    const result = await updatePlayer(playerId, formData);

    if (result.success) {
        closeEditModal();
        showMessage('\u9078\u624B\u60C5\u5831\u3092\u66F4\u65B0\u3057\u307E\u3057\u305F', 'success');
        await loadPlayers();
    } else {
        const errEl = document.getElementById('editError');
        errEl.textContent = result.error || '\u66F4\u65B0\u306B\u5931\u6557\u3057\u307E\u3057\u305F';
        errEl.classList.add('show');
    }

    btn.disabled = false;
    btn.textContent = '\u4FDD\u5B58';
}

function openDeleteModal(playerId) {
    const player = playersData.find(p => p.id === playerId);
    if (!player) return;
    document.getElementById('deletePlayerId').value = player.id;
    document.getElementById('deletePlayerInfo').textContent = '#' + player.number + ' ' + player.name;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

async function confirmDelete() {
    const btn = document.getElementById('deleteConfirmBtn');
    btn.disabled = true;
    btn.textContent = '\u524A\u9664\u4E2D...';

    const playerId = parseInt(document.getElementById('deletePlayerId').value);
    const result = await deletePlayer(playerId);

    if (result.success) {
        closeDeleteModal();
        showMessage('\u9078\u624B\u3092\u524A\u9664\u3057\u307E\u3057\u305F', 'success');
        await loadPlayers();
    } else {
        closeDeleteModal();
        showMessage(result.error || '\u524A\u9664\u306B\u5931\u6557\u3057\u307E\u3057\u305F', 'error');
    }

    btn.disabled = false;
    btn.textContent = '\u524A\u9664';
}

// ===== Helpers =====
function showLoading(show) {
    document.getElementById('loading').style.display = show ? 'block' : 'none';
}

function showError(title, msg) {
    const app = document.getElementById('app');
    app.innerHTML = '<div class="error-page"><h2>' + title + '</h2><p>' + msg + '</p></div>';
}

function showMessage(text, type) {
    const el = document.getElementById('message');
    if (!el) return;
    el.textContent = text;
    el.className = 'message ' + type + ' show';
    if (type === 'success') {
        setTimeout(() => { el.classList.remove('show'); }, 3000);
    }
}

function hideMessage() {
    const el = document.getElementById('message');
    if (el) el.classList.remove('show');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>
</body>
</html>
