(function () {
    function normalizePath(path) {
        if (!path || path === '') return '/';
        var clean = path.replace(/\/+$/, '');
        return clean === '' ? '/' : clean;
    }

    function markCurrentLinks(selector) {
        var links = document.querySelectorAll(selector);
        if (!links.length) return;

        var path = normalizePath(window.location.pathname);
        links.forEach(function (link) {
            var href = link.getAttribute('href');
            if (!href || href[0] !== '/') return;

            var linkPath = normalizePath(href);
            if (path === linkPath || (linkPath !== '/' && path.indexOf(linkPath + '/') === 0)) {
                link.classList.add('is-current');
            }
        });
    }

    function attachPressFeedback() {
        var selector = '.btn, .cta, .chip, .tab, .option, .nav-links a, .footer-nav a, .bottom-nav a';

        document.addEventListener('pointerdown', function (event) {
            var target = event.target.closest(selector);
            if (target) target.classList.add('is-pressed');
        }, { passive: true });

        var clearPressed = function () {
            document.querySelectorAll('.is-pressed').forEach(function (el) {
                el.classList.remove('is-pressed');
            });
        };

        document.addEventListener('pointerup', clearPressed, { passive: true });
        document.addEventListener('pointercancel', clearPressed, { passive: true });
        document.addEventListener('visibilitychange', clearPressed);
    }



    function getStoredThemeMode() {
        try { return localStorage.getItem('lawyersThemeMode') || 'auto'; } catch (e) { return 'auto'; }
    }

    function setStoredThemeMode(mode) {
        try { localStorage.setItem('lawyersThemeMode', mode); } catch (e) {}
    }

    function resolveThemeMode(mode) {
        if (mode === 'light' || mode === 'dark') return mode;
        var prefersDark = false;
        try { prefersDark = !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches); } catch (e) {}
        return prefersDark ? 'dark' : 'light';
    }

    function applyThemeMode(mode) {
        var resolved = resolveThemeMode(mode);
        document.body.setAttribute('data-theme-mode', resolved);
        document.body.setAttribute('data-theme-mode-source', mode || 'auto');
        var btn = document.getElementById('themeModeToggle');
        if (btn) {
            btn.setAttribute('data-mode', mode || 'auto');
            btn.setAttribute('aria-label', 'Tema: ' + (mode || 'auto'));
            var currentMode = mode || 'auto';
            var map = { auto: 'Auto', dark: 'Oscuro', light: 'Claro' };
            var iconMap = { auto: '◌', dark: '☾', light: '☼' };
            var labelEl = btn.querySelector('.theme-toggle-label');
            var iconEl = btn.querySelector('.theme-toggle-icon');
            if (labelEl) labelEl.textContent = map[currentMode] || 'Tema';
            if (iconEl) iconEl.textContent = iconMap[currentMode] || '◌';
        }
    }

    function initThemeModeToggle() {
        if (!document.body) return;
        if (!document.getElementById('themeModeToggle')) {
            var host = document.createElement('button');
            host.type = 'button';
            host.id = 'themeModeToggle';
            host.className = 'theme-mode-toggle';
            host.innerHTML = '<span class="theme-toggle-dot" aria-hidden="true"></span><span class="theme-toggle-icon" aria-hidden="true">◌</span><span class="theme-toggle-label">Auto</span>';
            host.addEventListener('click', function () {
                var current = (document.body.getAttribute('data-theme-mode-source') || 'auto');
                var next = current === 'auto' ? 'dark' : (current === 'dark' ? 'light' : 'auto');
                setStoredThemeMode(next);
                applyThemeMode(next);
            });
            document.body.appendChild(host);
        }
        var saved = getStoredThemeMode();
        applyThemeMode(saved);
        if (window.matchMedia) {
            var media = window.matchMedia('(prefers-color-scheme: dark)');
            var onPref = function () {
                if ((document.body.getAttribute('data-theme-mode-source') || 'auto') === 'auto') {
                    applyThemeMode('auto');
                }
            };
            if (media.addEventListener) media.addEventListener('change', onPref);
            else if (media.addListener) media.addListener(onPref);
        }
    }



    function initThemeModeAutoHideOnScroll() {
        var btn = document.getElementById('themeModeToggle');
        if (!btn) return;
        var lastY = window.scrollY || 0;
        var ticking = false;
        var update = function () {
            var y = window.scrollY || 0;
            var delta = y - lastY;
            if (y < 24) {
                btn.classList.remove('is-hidden-by-scroll');
            } else if (delta > 8) {
                btn.classList.add('is-hidden-by-scroll');
            } else if (delta < -6) {
                btn.classList.remove('is-hidden-by-scroll');
            }
            lastY = y;
            ticking = false;
        };
        window.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(update);
        }, { passive: true });
    }

    document.addEventListener('DOMContentLoaded', function () {
        markCurrentLinks('.nav-links a');
        markCurrentLinks('.footer-nav a');
        markCurrentLinks('.bottom-nav a');
        attachPressFeedback();
        initThemeModeToggle();
        initThemeModeAutoHideOnScroll();
    });
})();
