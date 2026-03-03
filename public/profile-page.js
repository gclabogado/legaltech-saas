/* Profile page scripts extracted from templates/profile.php */
(function () {
    var configEl = document.getElementById('profilePageConfig');
    var abogadoId = Number((configEl && configEl.dataset && configEl.dataset.abogadoId) || 0);
    var abogadoSlug = (configEl && configEl.dataset && configEl.dataset.abogadoSlug) || '';

    function sendUxEvent(eventName, payload) {
        var body = JSON.stringify({ event: eventName, payload: payload || {} });
        if (navigator.sendBeacon) {
            var blob = new Blob([body], { type: 'application/json' });
            navigator.sendBeacon('/api/event', blob);
        } else {
            fetch('/api/event', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: body,
                keepalive: true
            }).catch(function () {});
        }
    }

    var dashMessage = document.querySelector('.dash-message[data-autodismiss-ms]');
    if (dashMessage) {
        var dismissMs = Number(dashMessage.getAttribute('data-autodismiss-ms') || 3500);
        setTimeout(function () { dashMessage.remove(); }, dismissMs);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (abogadoId > 0) {
            fetch('/api/view/' + abogadoId, { method: 'POST' }).catch(function () {});
        }
    });

    document.getElementById('revealBtn')?.addEventListener('click', function () {
        document.getElementById('contactBlock')?.classList.remove('hidden');
        var realName = document.getElementById('realName');
        var displayName = document.getElementById('displayName');
        if (realName && displayName) displayName.textContent = realName.textContent;
        this.classList.add('hidden');
    });

    document.getElementById('likeBtn')?.addEventListener('click', function () {
        if (abogadoId <= 0) return;
        var btn = this;
        fetch('/api/like/' + abogadoId, { method: 'POST' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.success !== true) return;
                var likesEl = document.getElementById('profileLikesCount');
                if (likesEl && typeof data.likes !== 'undefined') likesEl.textContent = String(data.likes);
                btn.textContent = data.already_liked ? '👍 Ya te gusta este perfil' : '👍 Te gusta este perfil';
                btn.disabled = true;
            })
            .catch(function () {});
    });

    document.getElementById('recommendBtn')?.addEventListener('click', function () {
        if (abogadoId <= 0) return;
        var btn = this;
        fetch('/api/recommend/' + abogadoId, { method: 'POST' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.success !== true) return;
                var recEl = document.getElementById('profileRecommendationsCount');
                if (recEl && typeof data.recomendaciones !== 'undefined') recEl.textContent = String(data.recomendaciones);
                btn.textContent = data.already_recommended ? '⭐ Ya recomendaste este perfil' : '⭐ Perfil recomendado';
                btn.disabled = true;
            })
            .catch(function () {});
    });

    document.getElementById('copyProfileLinkBtn')?.addEventListener('click', async function () {
        var url = window.location.href;
        try {
            await navigator.clipboard.writeText(url);
            this.textContent = 'Link copiado';
            setTimeout(() => { this.textContent = 'Copiar link'; }, 1800);
        } catch (e) {}
    });

    var googleGateModal = document.getElementById('googleGateModal');
    var googleGateLoginLink = document.getElementById('googleGateLoginLink');

    function openGoogleGateModal(href) {
        if (!googleGateModal) return;
        if (googleGateLoginLink && href) googleGateLoginLink.href = href;
        googleGateModal.classList.add('is-open');
        googleGateModal.setAttribute('aria-hidden', 'false');
    }

    function closeGoogleGateModal() {
        if (!googleGateModal) return;
        googleGateModal.classList.remove('is-open');
        googleGateModal.setAttribute('aria-hidden', 'true');
    }

    document.getElementById('closeGoogleGateModal')?.addEventListener('click', closeGoogleGateModal);
    googleGateModal?.addEventListener('click', function (e) {
        if (e.target === googleGateModal) closeGoogleGateModal();
    });

    document.querySelectorAll('[data-requires-google]').forEach(function (el) {
        el.addEventListener('click', function (ev) {
            ev.preventDefault();
            try { openGoogleGateModal(this.getAttribute('href')); } catch (e) {}
        });
    });

    var leadCaseForm = document.getElementById('leadCaseForm');
    leadCaseForm?.addEventListener('submit', function () {
        var status = document.getElementById('leadSendStatus');
        if (status) status.classList.add('is-active');
        var submitBtn = this.querySelector('button[type=submit]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';
        }
    });

    document.getElementById('shareProfileBtn')?.addEventListener('click', async function () {
        var url = window.location.href;
        var title = document.getElementById('displayName')?.textContent || 'Perfil abogado en Tu Estudio Juridico';
        try {
            if (navigator.share) {
                await navigator.share({ title: title, url: url });
                sendUxEvent('profile_shared', {
                    abogado_id: abogadoId > 0 ? abogadoId : null,
                    abogado_slug: abogadoSlug || null,
                    method: 'navigator_share'
                });
                return;
            }
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(url);
                this.textContent = 'Link copiado';
                sendUxEvent('profile_shared', {
                    abogado_id: abogadoId > 0 ? abogadoId : null,
                    abogado_slug: abogadoSlug || null,
                    method: 'clipboard'
                });
                setTimeout(() => { this.textContent = 'Compartir perfil'; }, 1500);
            }
        } catch (e) {}
    });

    document.querySelectorAll('.icon-btn[data-fallback]').forEach(function (el) {
        el.addEventListener('click', function (ev) {
            ev.preventDefault();
            var fallback = this.getAttribute('data-fallback');
            if (!fallback) return;
            var started = Date.now();
            var hidden = false;
            var onHide = function () { hidden = true; };
            document.addEventListener('visibilitychange', onHide, { once: true });
            setTimeout(function () {
                if (!hidden && (Date.now() - started) < 1800) {
                    window.open(fallback, '_blank', 'noopener');
                }
            }, 900);
        });
    });

    (function () {
        var root = document.documentElement;
        var onScrollFx = function () {
            var maxY = Math.max(1, document.body.scrollHeight - window.innerHeight);
            var ratio = Math.min(1, Math.max(0, window.scrollY / maxY));
            root.style.setProperty('--fx-y', String(12 + ratio * 68));
            root.style.setProperty('--fx-x', String(50 + Math.sin(ratio * Math.PI * 2) * 18));
            root.style.setProperty('--fx-hue-shift', String(Math.round(ratio * 18)));
        };
        onScrollFx();
        window.addEventListener('scroll', onScrollFx, { passive: true });
    })();
})();
