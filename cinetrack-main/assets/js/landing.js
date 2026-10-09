// Landing page: scroll-spy nav, scroll reveal, and showcase filters.
document.addEventListener('DOMContentLoaded', () => {
    const links = [...document.querySelectorAll('.nav-pill a[href^="#"]')];
    const sections = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);

    if ('IntersectionObserver' in window) {
        const spy = new IntersectionObserver(entries => {
            entries.forEach(en => {
                if (en.isIntersecting) links.forEach(l => l.classList.toggle('is-active', l.getAttribute('href') === '#' + en.target.id));
            });
        }, { rootMargin: '-45% 0px -50% 0px' });
        sections.forEach(s => spy.observe(s));

        const reveal = new IntersectionObserver(entries => entries.forEach(en => {
            if (en.isIntersecting) { en.target.classList.add('in'); reveal.unobserve(en.target); }
        }), { threshold: 0.12 });
        document.querySelectorAll('.reveal').forEach(el => reveal.observe(el));
    } else {
        document.querySelectorAll('.reveal').forEach(el => el.classList.add('in'));
    }

    // Close the mobile menu after choosing a section
    const menu = document.getElementById('nav-links'), toggle = document.querySelector('.nav-toggle');
    links.forEach(a => a.addEventListener('click', () => { menu?.classList.remove('is-open'); toggle?.setAttribute('aria-expanded', 'false'); }));

    // Showcase filters: "all", "genre:Sci-Fi", "type:Series"
    const chips = document.querySelectorAll('[data-filter]');
    const cards = document.querySelectorAll('.sc-grid [data-genre]');
    chips.forEach(chip => chip.addEventListener('click', () => {
        chips.forEach(c => c.classList.toggle('is-on', c === chip));
        const [kind, value] = chip.dataset.filter.split(':');
        cards.forEach(card => {
            const show = chip.dataset.filter === 'all' || (kind === 'genre' && card.dataset.genre === value) || (kind === 'type' && card.dataset.type === value);
            card.hidden = !show;
            if (show) card.classList.add('in');
        });
    }));
});
