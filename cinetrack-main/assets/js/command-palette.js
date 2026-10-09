// =============================================================
// CineTrack — Command Palette
// Shortcut: Ctrl+Alt+P (or Cmd+Option+P on Mac)
// =============================================================
(() => {
    const dlg = document.getElementById('cmdk');
    if (!dlg) return;
    const input   = document.getElementById('cmdk-input');
    const results = document.getElementById('cmdk-results');

    const COMMANDS = [
        { id: 'home',      label: 'Go to Home',      hint: 'Dashboard',    icon: 'home',     url: 'index.php' },
        { id: 'movies',    label: 'Go to Movies',    hint: 'Collection',   icon: 'film',     url: 'movies.php' },
        { id: 'watchlist', label: 'Go to Watchlist', hint: 'To Watch',     icon: 'bookmark', url: 'watchlist.php' },
        { id: 'watched',   label: 'Go to Watched',   hint: 'Already seen', icon: 'check',    url: 'movies.php?status=Watched' },
        { id: 'favorites', label: 'Go to Favorites', hint: 'Loved movies', icon: 'heart',    url: 'movies.php?fav=1' },
        { id: 'add',       label: 'Add new movie',   hint: 'Create',       icon: 'plus',     url: 'add_movie.php' },
        { id: 'profile',   label: 'My profile',      hint: 'Account',      icon: 'camera',   url: 'profile.php' },
        { id: 'theme',     label: 'Toggle theme',    hint: 'Light / Dark', icon: 'star',     action: 'theme' },
        { id: 'logout',    label: 'Sign out',        hint: 'End session',  icon: 'x',        url: 'logout.php' },
    ];

    const ICONS = {
        home:     '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        film:     '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 3v18M17 3v18M3 8h4M3 16h4M17 8h4M17 16h4"/>',
        bookmark: '<path d="M6 3h12v18l-6-4-6 4z"/>',
        check:    '<path d="M5 12l5 5 9-10"/>',
        heart:    '<path d="M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.5-7 10-7 10z"/>',
        plus:     '<path d="M12 5v14M5 12h14"/>',
        camera:   '<rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="3.5"/>',
        star:     '<path d="M12 3l2.8 6 6.2.7-4.7 4.3 1.4 6.5-5.7-3.3-5.7 3.3 1.4-6.5L3 9.7 9.2 9z"/>',
        x:        '<path d="M5 5l14 14M19 5L5 19"/>',
        movie:    '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 3v18M17 3v18"/>',
    };
    const svg = (name, size = 16) => `<svg class="icon" width="${size}" height="${size}" viewBox="0 0 24 24" aria-hidden="true">${ICONS[name] || ''}</svg>`;

    let query = '';
    let movieResults = [];
    let filtered = [];
    let activeIndex = 0;
    let debounceTimer;

    function render() {
        const items = filtered;
        if (!items.length) {
            results.innerHTML = `<div class="cmdk-empty">No matches for "${escapeHtml(query)}"</div>`;
            return;
        }
        results.innerHTML = items.map((item, i) => {
            const active = i === activeIndex ? ' is-active' : '';
            if (item.type === 'movie') {
                return `<a class="cmdk-item${active}" role="option" href="view_movie.php?id=${item.id}" data-index="${i}">
                    <span class="cmdk-icon">${svg('movie', 18)}</span>
                    <span class="cmdk-text">
                        <strong>${escapeHtml(item.title)}</strong>
                        <small>${item.year} · ${escapeHtml(item.genre)}</small>
                    </span>
                    <span class="cmdk-arrow">↵</span>
                </a>`;
            }
            return `<a class="cmdk-item${active}" role="option" href="${item.url || '#'}" data-index="${i}" data-action="${item.action || ''}">
                <span class="cmdk-icon">${svg(item.icon, 18)}</span>
                <span class="cmdk-text">
                    <strong>${escapeHtml(item.label)}</strong>
                    <small>${escapeHtml(item.hint || '')}</small>
                </span>
                <span class="cmdk-arrow">↵</span>
            </a>`;
        }).join('');
    }

    function escapeHtml(s) {
        return String(s).replace(/[<>&"']/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function filterCommands(q) {
        const needle = q.toLowerCase().trim();
        if (!needle) return COMMANDS.map(c => ({ ...c, type: 'command' }));
        return COMMANDS
            .filter(c => (c.label + ' ' + (c.hint || '')).toLowerCase().includes(needle))
            .map(c => ({ ...c, type: 'command' }));
    }

    function update(q) {
        query = q;
        const cmds = filterCommands(q);
        filtered = [...cmds, ...movieResults];
        activeIndex = 0;
        render();
    }

    async function fetchMovies(q) {
        if (q.length < 2) { movieResults = []; update(q); return; }
        try {
            const res = await fetch('search_suggest.php?q=' + encodeURIComponent(q));
            const items = res.ok ? await res.json() : [];
            movieResults = items.map(m => ({ ...m, type: 'movie' }));
        } catch { movieResults = []; }
        update(q);
    }

    function openPalette() {
        if (dlg.open) return;
        input.value = '';
        movieResults = [];
        update('');
        dlg.showModal();
        setTimeout(() => input.focus(), 50);
    }

    function closePalette() { if (dlg.open) dlg.close(); }

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = input.value;
        update(q);
        debounceTimer = setTimeout(() => fetchMovies(q), 180);
    });

    input.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (!filtered.length) return;
            activeIndex = (activeIndex + 1) % filtered.length;
            render();
            results.querySelector('.cmdk-item.is-active')?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (!filtered.length) return;
            activeIndex = (activeIndex - 1 + filtered.length) % filtered.length;
            render();
            results.querySelector('.cmdk-item.is-active')?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const item = filtered[activeIndex];
            if (item) executeItem(item);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            closePalette();
        }
    });

    function executeItem(item) {
        if (item.type === 'movie') { location.href = `view_movie.php?id=${item.id}`; return; }
        if (item.action === 'theme') {
            const cur = document.documentElement.getAttribute('data-theme') || 'dark';
            const next = cur === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('cinetrack.theme', next);
            closePalette();
            return;
        }
        if (item.url) location.href = item.url;
    }

    results.addEventListener('click', e => {
        const a = e.target.closest('.cmdk-item');
        if (!a) return;
        const idx = Number(a.dataset.index);
        const item = filtered[idx];
        if (item && item.action) {
            e.preventDefault();
            executeItem(item);
        }
    });

    dlg.addEventListener('click', e => { if (e.target === dlg) closePalette(); });

    document.querySelectorAll('[data-cmdk-open]').forEach(btn => {
        btn.addEventListener('click', e => { e.preventDefault(); openPalette(); });
    });

    // Expose para sa app.js
    window.CineTrack = window.CineTrack || {};
    window.CineTrack.openPalette = openPalette;
    window.CineTrack.closePalette = closePalette;

})();