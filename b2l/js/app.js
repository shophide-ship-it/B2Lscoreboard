document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mainNav = document.querySelector('.main-nav');
    if (mobileMenuBtn && mainNav) {
        mobileMenuBtn.addEventListener('click', () => {
            mainNav.classList.toggle('open');
        });
    }
    initDivisionTabs();
    initAdminFeatures();
});

function initDivisionTabs() {
    const tabs = document.querySelectorAll('.division-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const group = this.closest('.division-tabs');
            const target = this.dataset.division;
            const container = this.closest('.division-section') || this.closest('section') || document.body;
            group.querySelectorAll('.division-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            container.querySelectorAll('.division-content').forEach(content => {
                content.style.display = content.dataset.division === target ? '' : 'none';
            });
        });
    });
}

function initAdminFeatures() {
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('本当に削除しますか？この操作は取り消せません。')) {
                e.preventDefault();
            }
        });
    });
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', function() {
            const modal = document.getElementById(this.dataset.modal);
            if (modal) modal.classList.add('active');
        });
    });
    document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
        el.addEventListener('click', function(e) {
            if (e.target === this) {
                this.closest('.modal-overlay').classList.remove('active');
            }
        });
    });
    const statsForm = document.getElementById('stats-form');
    if (statsForm) {
        const calcFields = ['fgm', 'fga', 'three_pm', 'three_pa', 'ftm', 'fta', 'oreb', 'dreb'];
        calcFields.forEach(field => {
            const el = statsForm.querySelector(`[name="${field}"]`);
            if (el) el.addEventListener('input', calculateDerived);
        });
    }
}

function calculateDerived() {
    const form = document.getElementById('stats-form');
    if (!form) return;
    const get = name => parseInt(form.querySelector(`[name="${name}"]`)?.value) || 0;
    const oreb = get('oreb');
    const dreb = get('dreb');
    const rebField = form.querySelector('[name="reb"]');
    if (rebField) rebField.value = oreb + dreb;
    const fgm = get('fgm');
    const three_pm = get('three_pm');
    const ftm = get('ftm');
    const ptsField = form.querySelector('[name="pts"]');
    if (ptsField) ptsField.value = (fgm * 2) + three_pm + ftm;
}