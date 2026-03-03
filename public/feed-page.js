/* Feed page scripts extracted from templates/feed.php */
        function sendUxEvent(eventName, payload) {
            const body = JSON.stringify({ event: eventName, payload: payload || {} });
            if (navigator.sendBeacon) {
                const blob = new Blob([body], { type: 'application/json' });
                navigator.sendBeacon('/api/event', blob);
            } else {
                fetch('/api/event', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: body,
                    keepalive: true
                }).catch(() => {});
            }
        }

        function setFilterSheetOpen(open) {
            document.body.classList.toggle('show-feed-filters', !!open);
            if (window.innerWidth <= 900) {
                document.body.style.overflow = open ? 'hidden' : '';
            }
            const btn = document.getElementById('toggleMobileFiltersBtn');
            if (btn) btn.textContent = open ? 'Ocultar filtros' : 'Mostrar filtros';
        }
        document.getElementById('toggleMobileFiltersBtn')?.addEventListener('click', function () {
            setFilterSheetOpen(!document.body.classList.contains('show-feed-filters'));
        });
        document.getElementById('filterBackdrop')?.addEventListener('click', function () {
            setFilterSheetOpen(false);
        });
        document.getElementById('closeFiltersBtn')?.addEventListener('click', function () {
            setFilterSheetOpen(false);
        });

        (function initGoogleBannerAutoHide() {
            const banner = document.querySelector('.google-login-banner');
            if (!banner) return;
            let lastY = window.scrollY || 0;
            let ticking = false;
            const apply = () => {
                const y = window.scrollY || 0;
                const delta = y - lastY;
                if (y < 24) banner.classList.remove('is-hidden-by-scroll');
                else if (delta > 8) banner.classList.add('is-hidden-by-scroll');
                else if (delta < -6) banner.classList.remove('is-hidden-by-scroll');
                lastY = y;
                ticking = false;
            };
            window.addEventListener('scroll', function(){ if (ticking) return; ticking = true; requestAnimationFrame(apply); }, { passive:true });
        })();
        (function enableFilterSheetSwipeDownClose() {
            const sheet = document.querySelector('.feed-mobile-filters');
            const handle = document.getElementById('filterDragHandle');
            if (!sheet || !handle) return;
            let startY = 0;
            let tracking = false;
            let currentDelta = 0;
            const maxDrag = 120;
            const start = (y) => {
                if (window.innerWidth > 900 || !document.body.classList.contains('show-feed-filters')) return;
                tracking = true;
                startY = y;
                currentDelta = 0;
                sheet.style.transition = 'none';
            };
            const move = (y) => {
                if (!tracking) return;
                currentDelta = Math.max(0, Math.min(maxDrag, y - startY));
                sheet.style.transform = `translateY(${currentDelta}px)`;
                sheet.style.opacity = String(1 - Math.min(0.45, currentDelta / 220));
            };
            const end = () => {
                if (!tracking) return;
                tracking = false;
                sheet.style.transition = '';
                if (currentDelta > 55) {
                    setFilterSheetOpen(false);
                } else {
                    sheet.style.transform = '';
                    sheet.style.opacity = '';
                }
                currentDelta = 0;
            };
            handle.addEventListener('touchstart', (e) => start(e.touches[0].clientY), { passive: true });
            handle.addEventListener('touchmove', (e) => move(e.touches[0].clientY), { passive: true });
            handle.addEventListener('touchend', end);
            handle.addEventListener('mousedown', (e) => { e.preventDefault(); start(e.clientY); });
            window.addEventListener('mousemove', (e) => move(e.clientY));
            window.addEventListener('mouseup', end);
        })();

        (function startInGridModeOnMobile() {
            if (window.innerWidth > 900) return;
            setFilterSheetOpen(false);
        })();

        document.querySelector('.filter-form form')?.addEventListener('submit', function () {
            if (window.innerWidth <= 900) {
                setFilterSheetOpen(false);
            }
            sendUxEvent('location_filter_used', {
                has_materia: !!document.querySelector('select[name="especialidad"]')?.value,
                has_region: !!document.querySelector('select[name="region"]')?.value,
                simple_filters: 1
            });
        });

        (function enableInfiniteReveal() {
            const cards = Array.from(document.querySelectorAll('.feed-card-item'));
            const sentinel = document.getElementById('feedLoadSentinel');
            if (!cards.length) {
                if (sentinel) sentinel.style.display = 'none';
                return;
            }
            let visibleCount = cards.length;
            const batchSize = cards.length;
            function renderCards() {
                cards.forEach((card, idx) => {
                    card.classList.toggle('feed-card-hidden', idx >= visibleCount);
                });
                if (sentinel) {
                    sentinel.textContent = visibleCount >= cards.length
                        ? 'Has visto todos los abogados'
                        : `Desliza para cargar más abogados (${Math.min(visibleCount, cards.length)}/${cards.length})`;
                }
            }
            function loadMore() {
                if (visibleCount >= cards.length) return;
                visibleCount = Math.min(cards.length, visibleCount + batchSize);
                renderCards();
            }
            renderCards();
            if (!sentinel || !('IntersectionObserver' in window)) {
                window.addEventListener('scroll', function () {
                    const nearBottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - 300;
                    if (nearBottom) loadMore();
                }, { passive: true });
                return;
            }
            const io = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) loadMore();
                });
            }, { rootMargin: '300px 0px' });
            io.observe(sentinel);
        })();

        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) {
                document.body.style.overflow = '';
                document.body.classList.remove('show-feed-filters');
            }
        });
