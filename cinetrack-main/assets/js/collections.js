
// Watchlist features: shuffle dialog, multi-select picker,
// Binge Watch helpers, and folder preview.

document.addEventListener('DOMContentLoaded', () => {

    // ---- Shuffle dialog ----
    // Fetches random movies from shuffle.php.

    const dlg = document.getElementById('shuffle-dialog');

    if (dlg) {
        const box = dlg.querySelector('#shuffle-results');
        let scope = 'watchlist';

        const ghosts = '<div class="shuffle-card ghost"></div>'.repeat(5);

        const load = async () => {
            if (!box) return;

            if (!box.children.length) {
                box.innerHTML = ghosts;
            }

            box.classList.add('is-shuffling');

            let html = null;

            try {
                const response = await fetch(
                    'shuffle.php?scope=' + encodeURIComponent(scope)
                );

                if (response.ok) {
                    html = await response.text();
                }

            } catch (error) {
                html = null;
            }

            box.innerHTML = html ??
                '<p class="muted shuffle-note">Could not shuffle right now. Please try again.</p>';

            box.classList.remove('is-shuffling');
        };

        document.querySelectorAll('[data-shuffle]').forEach(button => {
            button.addEventListener('click', () => {
                scope = button.dataset.shuffle || 'watchlist';
                dlg.showModal();
                load();
            });
        });

        document.getElementById('shuffle-again')
            ?.addEventListener('click', load);
    }


    // ---- Movie picker ----
    // Live search, checkbox selection, and select all shown.

    document.querySelectorAll('[data-picker]').forEach(form => {
        const q = form.querySelector('[data-q]');
        const cards = [...form.querySelectorAll('.pick-card')];

        const count = form.querySelector('[data-count]');
        const shown = form.querySelector('[data-shown]');
        const submit = form.querySelector('[data-submit]');
        const label = form.querySelector('[data-submit-label]');
        const empty = form.querySelector('[data-empty]');

        const selectShown = form.querySelector('[data-select-shown]');
        const clear = form.querySelector('[data-clear]');

        const boxes = cards
            .map(card => card.querySelector('input[type="checkbox"]'))
            .filter(Boolean);

        // Update the selected movie count and submit button.
        const update = () => {
            const selected = boxes.filter(box =>
                box.checked && !box.disabled
            ).length;

            if (count) {
                count.textContent = selected + ' selected';
            }

            if (submit) {
                submit.disabled = selected === 0;
            }

            if (label) {
                label.textContent = selected > 0
                    ? `Add ${selected} movie${selected === 1 ? '' : 's'} to ${form.dataset.target || 'your list'}`
                    : 'Select movies to add';
            }
        };

        // Search by the movie information stored in data-search.
        const filter = () => {
            const term = (q?.value || '').trim().toLowerCase();
            let visible = 0;

            cards.forEach(card => {
                const searchText = card.dataset.search || '';
                const matches = !term || searchText.includes(term);

                card.hidden = !matches;

                if (matches) {
                    visible++;
                }
            });

            if (shown) {
                shown.textContent = visible + ' shown';
            }

            if (empty) {
                empty.hidden = visible > 0;
            }
        };

        if (q) {
            q.addEventListener('input', filter);

            // Prevent Enter from submitting while searching.
            q.addEventListener('keydown', event => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });
        }

        form.addEventListener('change', update);

        // Select all visible, enabled movies.
        selectShown?.addEventListener('click', () => {
            cards.forEach(card => {
                const checkbox = card.querySelector('input[type="checkbox"]');

                if (checkbox && !card.hidden && !checkbox.disabled) {
                    checkbox.checked = true;
                }
            });

            update();
        });

        // Clear all selected movies.
        clear?.addEventListener('click', () => {
            boxes.forEach(box => {
                if (!box.disabled) {
                    box.checked = false;
                }
            });

            update();
        });

        filter();
        update();
    });


    // ---- Binge Watch helpers ----
    // Clicking a movie fills the answer field.

    document.querySelectorAll('[data-fill]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById('binge-title');

            if (input) {
                input.value = button.dataset.fill || '';
                input.focus();
            }
        });
    });

    // Reopen the Binge Watch dialog when the URL contains ?binge.
    if (new URLSearchParams(location.search).has('binge')) {
        document.getElementById('binge-dialog')?.showModal();
    }


    // ---- Folder form preview ----
    // Update the preview when the user changes folder details.

    const ff = document.querySelector('[data-folder-form]');

    if (ff) {
        const prev = ff.querySelector('[data-preview]');
        const nameInput = ff.querySelector('[name="name"]');
        const descInput = ff.querySelector('[name="description"]');
        const prevName = ff.querySelector('[data-prev-name]');
        const prevDesc = ff.querySelector('[data-prev-desc]');
        const prevIcon = ff.querySelector('[data-prev-icon]');

        const sync = () => {
            if (nameInput && prevName) {
                prevName.textContent =
                    nameInput.value.trim() || 'Folder name';
            }

            if (descInput && prevDesc) {
                prevDesc.textContent =
                    descInput.value.trim() || 'Description';
            }

            const color = ff.querySelector('input[name="color"]:checked');
            const iconChoice = ff.querySelector('input[name="icon"]:checked');

            if (color && prev && color.nextElementSibling) {
                const swatchColor =
                    color.nextElementSibling.style.getPropertyValue('--sw');

                if (swatchColor) {
                    prev.style.setProperty('--fc', swatchColor);
                }
            }

            if (iconChoice && prevIcon && iconChoice.nextElementSibling) {
                prevIcon.innerHTML = iconChoice.nextElementSibling.innerHTML;
            }
        };

        ff.addEventListener('input', sync);
        ff.addEventListener('change', sync);

        sync();
    }

});
