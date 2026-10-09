// =============================================================
// CineTrack — Premium 2026 animations
// =============================================================
(() => {
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ---- 1. Scroll progress bar ----
    const bar = document.createElement('div');
    bar.className = 'scroll-progress';
    document.body.appendChild(bar);

    const updateBar = () => {
        const h = document.documentElement.scrollHeight - window.innerHeight;
        const pct = h > 0 ? (window.scrollY / h) * 100 : 0;
        bar.style.width = pct + '%';
    };
    window.addEventListener('scroll', updateBar, { passive: true });
    updateBar();

    // ---- 2. Floating orbs: removed (bg3d.js + the CSS mesh already cover the background) ----

    // ---- 3. Page leave transition ----
    if (!reduce) {
        document.querySelectorAll('a[href]').forEach(a => {
            const href = a.getAttribute('href');
            if (!href) return;
            if (href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
            if (a.target === '_blank') return;
            if (href.startsWith('http') && !href.includes(location.host)) return;
            if (a.hasAttribute('data-no-transition') || a.hasAttribute('download')) return;

            a.addEventListener('click', e => {
                if (e.ctrlKey || e.metaKey || e.shiftKey) return;
                const url = a.href;
                if (!url || url === location.href) return;
                e.preventDefault();
                document.body.classList.add('is-leaving');
                setTimeout(() => { location.href = url; }, 220);
            });
        });

        // Handle browser back/forward (bfcache)
        window.addEventListener('pageshow', e => {
            if (e.persisted) document.body.classList.remove('is-leaving');
        });
    }

    // ---- 4. Nav pill sliding indicator ----
    const navPill = document.querySelector('.nav-pill');
    if (navPill) {
        const activeLink = navPill.querySelector('a.is-active');
        const indicator = navPill; // we use ::before
        const moveIndicator = () => {
            if (!activeLink) return;
            const parentRect = navPill.getBoundingClientRect();
            const linkRect = activeLink.getBoundingClientRect();
            const left = linkRect.left - parentRect.left;
            navPill.style.setProperty('--indicator-x', left + 'px');
            navPill.style.setProperty('--indicator-w', linkRect.width + 'px');
            navPill.classList.add('has-active');
        };
        // Inject inline styles for the ::before
        const style = document.createElement('style');
        style.textContent = `
            .nav-pill.has-active::before {
                left: var(--indicator-x, 0);
                width: var(--indicator-w, 0);
            }
        `;
        document.head.appendChild(style);
        moveIndicator();
        window.addEventListener('resize', moveIndicator);
    }

    // ---- 5. Button ripple ----
    if (!reduce) {
        document.addEventListener('click', e => {
            const btn = e.target.closest('.btn, .icon-btn, .chip-link, .chip');
            if (!btn) return;
            const r = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            const size = Math.max(r.width, r.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - r.left - size / 2) + 'px';
            ripple.style.top  = (e.clientY - r.top  - size / 2) + 'px';
            btn.appendChild(ripple);
            setTimeout(() => ripple.remove(), 650);
        });
    }

    // ---- 6. Stat number counter animation ----
    const animateNumber = (el) => {
        const target = parseFloat(el.textContent.replace(/[^\d.-]/g, ''));
        if (isNaN(target)) return;
        const isDecimal = el.textContent.includes('.');
        const duration = 900;
        const start = performance.now();
        const step = (now) => {
            const t = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - t, 3); // ease-out cubic
            const val = target * eased;
            el.textContent = isDecimal ? val.toFixed(1) : Math.round(val).toLocaleString();
            if (t < 1) requestAnimationFrame(step);
            else el.classList.add('is-updating');
        };
        requestAnimationFrame(step);
    };

    if (!reduce && 'IntersectionObserver' in window) {
        const numObserver = new IntersectionObserver((entries) => {
            entries.forEach(en => {
                if (en.isIntersecting) {
                    animateNumber(en.target);
                    numObserver.unobserve(en.target);
                }
            });
        }, { threshold: 0.6 });

        document.querySelectorAll('.stat-value').forEach(el => {
            const txt = el.textContent.trim();
            if (/^[\d.,]+$/.test(txt.replace(/\s/g, ''))) {
                el.textContent = '0';
                numObserver.observe(el);
            }
        });
    }

    // ---- 7. Parallax scroll sa hero ----
    if (!reduce && window.innerWidth > 900) {
        const hero = document.querySelector('.landing');
        if (hero) {
            let ticking = false;
            window.addEventListener('scroll', () => {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(() => {
                    const y = Math.min(window.scrollY, 600);
                    hero.style.transform = `translateY(${y * 0.15}px)`;
                    ticking = false;
                });
            }, { passive: true });
        }
    }

    // ---- 8. Cursor glow ----
    if (!reduce && matchMedia('(hover: hover) and (pointer: fine)').matches && window.innerWidth > 900) {
        const glow = document.createElement('div');
        glow.className = 'cursor-glow';
        document.body.appendChild(glow);
        let gx = innerWidth / 2, gy = innerHeight / 2;
        let cx = gx, cy = gy;
        window.addEventListener('pointermove', e => { gx = e.clientX; gy = e.clientY; }, { passive: true });
        const tick = () => {
            cx += (gx - cx) * 0.15;
            cy += (gy - cy) * 0.15;
            glow.style.transform = `translate(${cx}px, ${cy}px)`;
            requestAnimationFrame(tick);
        };
        tick();
    }

    // ---- 9. Page loader (first paint lang) ----
    if (!sessionStorage.getItem('cinetrack.loaded')) {
        const loader = document.createElement('div');
        loader.className = 'page-loader';
        loader.innerHTML = `
            <div>
                <div class="page-loader-logo">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <path d="M7 3v18M17 3v18M3 8h4M3 16h4M17 8h4M17 16h4"/>
                    </svg>
                </div>
                <div class="page-loader-bar"></div>
            </div>
        `;
        document.body.appendChild(loader);
        sessionStorage.setItem('cinetrack.loaded', '1');

        window.addEventListener('load', () => {
            setTimeout(() => loader.classList.add('is-done'), 400);
        });

        // Safety: force hide after 2s
        setTimeout(() => loader.classList.add('is-done'), 2000);
    }

    // ---- 10. Reveal on scroll (in case effects.js wala) ----
    if ('IntersectionObserver' in window && !document.querySelector('.reveal-up.in')) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach(en => {
                if (en.isIntersecting) {
                    en.target.classList.add('in');
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.reveal-up').forEach(el => io.observe(el));
    }
})();