// =============================================================
// CineTrack — Main UI JavaScript
// =============================================================

document.addEventListener('DOMContentLoaded', () => {

    // ---- 1. Mobile menu toggle ----
    const toggle = document.querySelector('.nav-toggle');
    const links = document.getElementById('nav-links');
    if (toggle && links) {
        toggle.addEventListener('click', () => {
            const open = links.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open);
        });
    }

    // ---- 2. Flash messages (dismiss + auto-hide) ----
    const flash = document.querySelector('.flash');
    if (flash) {
        const dismiss = () => {
            flash.classList.add('is-leaving');
            setTimeout(() => flash.remove(), 300);
        };
        flash.querySelector('.flash-close')?.addEventListener('click', dismiss);
        setTimeout(dismiss, 4500);
    }

    // ---- 3. Modal dialogs ----
    document.querySelectorAll('[data-modal-open]').forEach(btn =>
        btn.addEventListener('click', () => document.querySelector(btn.dataset.modalOpen)?.showModal()));
    document.querySelectorAll('[data-modal-close]').forEach(btn =>
        btn.addEventListener('click', () => btn.closest('dialog')?.close()));

    // ---- 4. Show / hide password toggle ----
    document.querySelectorAll('[data-pw-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('input');
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.classList.toggle('is-on', show);
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.innerHTML = show
                ? '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>'
                : '<svg class="icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>';
        });
    });

    // ---- 5. Loading state sa lahat ng forms ----
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', e => {
            const btn = e.submitter || form.querySelector('button[type=submit]');
            if (btn && !btn.disabled) {
                btn.classList.add('is-loading');
                // disable AFTER the browser has read the button's name/value
                setTimeout(() => { btn.disabled = true; }, 0);
            }
        });
    });

    // ---- 6. Card rails (prev/next scroll) ----
    document.querySelectorAll('[data-rail]').forEach(btn => btn.addEventListener('click', () => {
        const rail = document.querySelector(btn.dataset.rail);
        rail?.scrollBy({ left: (btn.dataset.dir === 'next' ? 1 : -1) * rail.clientWidth * 0.85, behavior: 'smooth' });
    }));

    // ---- 7. Profile menu — close on outside click ----
    document.addEventListener('click', e => {
        const open = document.querySelector('.profile[open]');
        if (open && !open.contains(e.target)) open.removeAttribute('open');
    });

    // ---- 8. Live search suggestions ----
    (() => {
        const input = document.querySelector('.nav-search input');
        if (!input) return;

        const box = document.createElement('div');
        box.className = 'search-suggest';
        input.parentElement.appendChild(box);

        let timer, lastQ = '';

        input.addEventListener('input', () => {
            const q = input.value.trim();
            if (q === lastQ) return;
            lastQ = q;

            clearTimeout(timer);
            if (q.length < 2) { box.innerHTML = ''; box.classList.remove('is-open'); return; }

            timer = setTimeout(async () => {
                try {
                    const res = await fetch('search_suggest.php?q=' + encodeURIComponent(q));
                    const items = res.ok ? await res.json() : [];
                    if (!items.length) {
                        box.innerHTML = '<div class="suggest-empty">No matches</div>';
                    } else {
                        const esc = s => String(s).replace(/[<>&"]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[c]));
                        box.innerHTML = items.map(it =>
                            `<a href="view_movie.php?id=${it.id}" class="suggest-item">
                                <strong>${esc(it.title)}</strong>
                                <small>${it.year} · ${esc(it.genre)}</small>
                            </a>`
                        ).join('');
                    }
                    box.classList.add('is-open');
                } catch {
                    box.innerHTML = '';
                    box.classList.remove('is-open');
                }
            }, 200);
        });

        input.addEventListener('blur', () => setTimeout(() => box.classList.remove('is-open'), 200));
        input.addEventListener('focus', () => { if (box.innerHTML) box.classList.add('is-open'); });
    })();

    // ---- 9. Persist view mode + sort preference ----
    (() => {
        const form = document.querySelector('form[data-autosubmit]');
        if (!form) return;

        const savedView = localStorage.getItem('movies.view');
        const savedSort = localStorage.getItem('movies.sort');
        const params = new URLSearchParams(location.search);

        if (!params.has('view') && savedView) {
            params.set('view', savedView);
            location.search = params.toString();
            return;
        }

        form.addEventListener('submit', () => {
            const view = form.querySelector('input[name=view]')?.value;
            const sort = form.querySelector('select[name=sort]')?.value;
            if (view) localStorage.setItem('movies.view', view);
            if (sort) localStorage.setItem('movies.sort', sort);
        });
    })();
});

// =============================================================
// Keyboard shortcuts (naka-attach sa document)
// =============================================================
(() => {
    let lastKey = '';
    let lastKeyTime = 0;

    document.addEventListener('keydown', e => {
        // Safe-guard: kung walang target o key, exit
        if (!e.key) return;

        const target = e.target || document.body;
        const tag = (target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || target.isContentEditable) {
            if (e.key === 'Escape') target.blur();
            return;
        }

        // "/" — focus search
        if (e.key === '/') {
            e.preventDefault();
            const search = document.querySelector('.nav-search input, input[name=q]');
            if (search) search.focus();
            return;
        }

        // "n" — add movie
        if (e.key.toLowerCase() === 'n' && !e.ctrlKey && !e.metaKey && !e.altKey) {
            window.location.href = 'add_movie.php';
            return;
        }

        const now = Date.now();
        const isSequence = (now - lastKeyTime) < 1000;

        // "g" then "h/m/w"
        if (e.key.toLowerCase() === 'g' && !e.ctrlKey && !e.metaKey) {
            lastKey = 'g';
            lastKeyTime = now;
            return;
        }

        if (isSequence && lastKey === 'g') {
            const dest = { 'h': 'index.php', 'm': 'movies.php', 'w': 'watchlist.php' }[e.key.toLowerCase()];
            if (dest) {
                e.preventDefault();
                window.location.href = dest;
            }
            lastKey = '';
        }
    });
})();

// Theme toggle
(() => {
    const toggles = document.querySelectorAll('.theme-toggle');
    if (!toggles.length) return;
    const get = () => document.documentElement.getAttribute('data-theme') || 'dark';
    const set = (t) => {
        document.documentElement.setAttribute('data-theme', t);
        localStorage.setItem('cinetrack.theme', t);
    };
    toggles.forEach(btn => btn.addEventListener('click', () => set(get() === 'dark' ? 'light' : 'dark')));
})();

// Command palette shortcut — Ctrl+Alt+P
document.addEventListener('keydown', e => {
    if (!e.key) return;
    const tag = (e.target?.tagName || '').toLowerCase();
    if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
    if ((e.ctrlKey || e.metaKey) && e.altKey && e.key.toLowerCase() === 'p') {
        e.preventDefault();
        if (window.CineTrack && window.CineTrack.openPalette) {
            window.CineTrack.openPalette();
        }
    }
});