document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    const mainNav = document.querySelector('.main-nav');
    if (mobileBtn && mainNav) {
        mobileBtn.addEventListener('click', () => mainNav.classList.toggle('open'));
    }

    // Division Tabs
    document.querySelectorAll('.division-tab[data-division]').forEach(tab => {
        tab.addEventListener('click', function() {
            const group = this.closest('.division-tabs');
            const target = this.dataset.division;
            const container = this.closest('.division-section') || this.closest('section') || document.body;
            group.querySelectorAll('.division-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            container.querySelectorAll('.division-content').forEach(c => {
                c.style.display = c.dataset.division === target ? '' : 'none';
            });
        });
    });

    // Delete confirmation
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('本当に削除しますか？')) e.preventDefault();
        });
    });

    // Modal close
    document.querySelectorAll('.modal-close').forEach(el => {
        el.addEventListener('click', function() {
            this.closest('.modal-overlay').classList.remove('active');
        });
    });
    document.querySelectorAll('.modal-overlay').forEach(el => {
        el.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    });

    // Stats auto-calc
    const statsForm = document.getElementById('stats-form');
    if (statsForm) {
        ['fgm', 'fga', 'three_pm', 'three_pa', 'ftm', 'fta', 'oreb', 'dreb'].forEach(name => {
            const el = statsForm.querySelector(`[name="${name}"]`);
            if (el) el.addEventListener('input', calcStats);
        });
    }
});

function calcStats() {
    const f = document.getElementById('stats-form');
    if (!f) return;
    const v = n => parseInt(f.querySelector(`[name="${n}"]`)?.value) || 0;
    const rebF = f.querySelector('[name="reb"]');
    const ptsF = f.querySelector('[name="pts"]');
    if (rebF) rebF.value = v('oreb') + v('dreb');
    if (ptsF) ptsF.value = (v('fgm') * 2) + v('three_pm') + v('ftm');
}


