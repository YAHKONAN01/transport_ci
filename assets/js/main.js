document.addEventListener('DOMContentLoaded', function () {

    // ── Swap départ / arrivée ──
    const swapBtn = document.getElementById('swapBtn');
    if (swapBtn) {
        swapBtn.addEventListener('click', function () {
            const selDep = document.querySelector('select[name="depart"]');
            const selArr = document.querySelector('select[name="arrivee"]');
            if (selDep && selArr) {
                const tmp = selDep.value;
                selDep.value = selArr.value;
                selArr.value = tmp;
            }
            this.style.transform = 'rotate(180deg)';
            setTimeout(() => this.style.transform = '', 400);
        });
    }

    // ── Animations au scroll ──
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.style.opacity = '1';
                e.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-up').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity .5s ease, transform .5s ease';
        observer.observe(el);
    });

    // ── Empêcher départ = arrivée ──
    const forms = document.querySelectorAll('form[action="resultats.php"]');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const dep = this.querySelector('[name="depart"]')?.value;
            const arr = this.querySelector('[name="arrivee"]')?.value;
            if (dep && arr && dep === arr) {
                e.preventDefault();
                alert('La ville de départ et la destination doivent être différentes.');
            }
        });
    });

    // ── Mise en valeur hover des destinations ──
    document.querySelectorAll('[href*="resultats.php"]').forEach(link => {
        link.addEventListener('mouseenter', function () {
            this.style.borderColor = 'var(--orange)';
            this.style.boxShadow = '0 4px 20px rgba(249,115,22,.15)';
        });
        link.addEventListener('mouseleave', function () {
            this.style.borderColor = '';
            this.style.boxShadow = '';
        });
    });
});
