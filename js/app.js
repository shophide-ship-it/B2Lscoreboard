// ============================================
// B2L League - Frontend JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    // Mobile Menu Toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mainNav = document.querySelector('.main-nav');
    if (mobileMenuBtn && mainNav) {
        mobileMenuBtn.addEventListener('click', () => {
            mainNav.classList.toggle('open');
        });
    }

    // Division Tabs
    initDivisionTabs();

    // Admin features
    initAdminFeatures();
});

function initDivisionTabs() {
    const tabs = document.querySelectorAll('.division-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const group = this.closest('.division-tabs');
            const target = this.dataset.division;
            const container = this.closest('.division-section') || this.closest('section') || document.body;

            group.querySelectorAll('.division-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            container.querySelectorAll('.division-content').forEach(content => {
                if (content.dataset.division === target) {
                    content.style.display = '';
                } else {
                    content.style.display = 'none';
                }
            });
        });
    });
}

function initAdminFeatures() {
    // Delete confirmations
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm('本当に削除しますか？この操作は取り消せません。')) {
                e.preventDefault();
            }
        });
    });

    // Modal
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', function () {
            const modal = document.getElementById(this.dataset.modal);
            if (modal) modal.classList.add('active');
        });
    });

    document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
        el.addEventListener('click', function (e) {
            if (e.target === this) {
                this.closest('.modal-overlay').classList.remove('active');
            }
        });
    });

    // Auto calculate FG%, 3P%, FT%
    const statsForm = document.getElementById('stats-form');
    if (statsForm) {
        const calcFields = ['fgm', 'fga', 'three_pm', 'three_pa', 'ftm', 'fta', 'oreb', 'dreb'];
        calcFields.forEach(field => {
            const el = statsForm.querySelector(`[name="${field}"]`);
            if (el) {
                el.addEventListener('input', calculateDerived);
            }
        });
    }
}

function calculateDerived() {
    const form = document.getElementById('stats-form');
    if (!form) return;

    const get = name => parseInt(form.querySelector(`[name="${name}"]`)?.value) || 0;

    // REB = OREB + DREB
    const oreb = get('oreb');
    const dreb = get('dreb');
    const rebField = form.querySelector('[name="reb"]');
    if (rebField) rebField.value = oreb + dreb;

    // PTS = FTM + 2*(FGM-3PM) + 3*3PM = 2*FGM + 3PM + FTM
    const fgm = get('fgm');
    const three_pm = get('three_pm');
    const ftm = get('ftm');
    const ptsField = form.querySelector('[name="pts"]');
    if (ptsField) ptsField.value = (fgm * 2) + three_pm + ftm;
}

// Utility
function formatDate(dateStr) {
    const date = new Date(dateStr);
    const months = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
    return `${date.getFullYear()}年${months[date.getMonth()]}${date.getDate()}日`;
}
