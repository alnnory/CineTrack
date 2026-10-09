<?php
// CineTrack movie form and helper functions.
// Movie information and personal tracking are stored in the movies table.

declare(strict_types=1);

// Movie columns used when adding or editing a movie.
// The user_id is added separately in add_movie.php.
const MOVIE_COLS = [
    'title',
    'short_description',
    'release_year',
    'genre',
    'director',
    'cast',
    'duration_minutes',
    'language',
    'country',
    'age_rating',
    'poster_url',
    'streaming_platform',
    'watch_url'
];

// Types must follow the same order as MOVIE_COLS.
// s = string, i = integer.
const MOVIE_TYPES = 'ssisssissssss';


function movie_defaults(): array
{
    return array_merge(
        array_fill_keys(MOVIE_COLS, ''),
        ['language' => 'English']
    );
}


function movie_from_post(): array
{
    $m = [];

    foreach (MOVIE_COLS as $c) {
        $m[$c] = trim((string)($_POST[$c] ?? ''));
    }

    return $m;
}


function movie_validate(array $m): array
{
    $e = [];

    // Required text fields.
    $required = [
        'title' => 150,
        'genre' => 50
    ];

    foreach ($required as $c => $max) {
        if ($m[$c] === '') {
            $e[$c] = 'This field is required.';
        } elseif (mb_strlen($m[$c]) > $max) {
            $e[$c] = "Keep this under $max characters.";
        }
    }

    // Optional text fields and their maximum lengths.
    $optionalText = [
        'director' => 100,
        'cast' => 255,
        'language' => 40,
        'country' => 60,
        'streaming_platform' => 50
    ];

    foreach ($optionalText as $c => $max) {
        if (mb_strlen($m[$c]) > $max) {
            $e[$c] = "Keep this under $max characters.";
        }
    }

    // Description is optional.
    if (mb_strlen($m['short_description']) > 10000) {
        $e['short_description'] = 'Description is too long.';
    }

    // Release year.
    $year = filter_var(
        $m['release_year'],
        FILTER_VALIDATE_INT
    );

    if (
        $year === false ||
        $year < 1888 ||
        $year > 2100
    ) {
        $e['release_year'] = 'Enter a year between 1888 and 2100.';
    }

    // Duration is optional.
    if ($m['duration_minutes'] !== '') {
        $duration = filter_var(
            $m['duration_minutes'],
            FILTER_VALIDATE_INT
        );

        if (
            $duration === false ||
            $duration < 1 ||
            $duration > 1000
        ) {
            $e['duration_minutes'] =
                'Enter minutes between 1 and 1000.';
        }
    }

    // Age rating is optional.
    if ($m['age_rating'] !== '') {
        if (!in_array($m['age_rating'], AGE_RATINGS, true)) {
            $e['age_rating'] = 'Choose a valid age rating.';
        }
    }

    // Validate poster and watch URLs.
    foreach (['poster_url', 'watch_url'] as $u) {
        if ($m[$u] !== '') {
            $validUrl = filter_var(
                $m[$u],
                FILTER_VALIDATE_URL
            );

            $isHttp = preg_match(
                '#^https?://#i',
                $m[$u]
            );

            if (
                !$validUrl ||
                !$isHttp ||
                mb_strlen($m[$u]) > 500
            ) {
                $e[$u] =
                    'Enter a full http(s) URL, or leave blank.';
            }
        }
    }

    return $e;
}


/**
 * Return movie values in MOVIE_COLS order.
 * Optional database fields use NULL instead of empty strings.
 */
function movie_params(array $m): array
{
    $v = [];

    foreach (MOVIE_COLS as $c) {
        $x = $m[$c];

        // Integer fields.
        if ($c === 'release_year') {
            $x = (int)$x;
        } elseif ($c === 'duration_minutes') {
            $x = $x === '' ? null : (int)$x;
        }

        // Optional text fields.
        elseif (
            in_array(
                $c,
                [
                    'short_description',
                    'director',
                    'cast',
                    'country',
                    'age_rating',
                    'poster_url',
                    'streaming_platform',
                    'watch_url'
                ],
                true
            )
        ) {
            $x = $x === '' ? null : $x;
        }

        $v[] = $x;
    }

    return $v;
}


// ---- Personal tracking helpers ----

function track_defaults(): array
{
    return [
        'watch_status' => 'To Watch',
        'priority' => 'Medium',
        'watch_count' => '0',
        'user_rating' => '',
        'review' => '',
        'favorite' => 0
    ];
}


function track_from_post(): array
{
    return [
        'watch_status' => trim(
            (string)($_POST['watch_status'] ?? '')
        ),
        'priority' => trim(
            (string)($_POST['priority'] ?? '')
        ),
        'watch_count' => trim(
            (string)($_POST['watch_count'] ?? '')
        ),
        'user_rating' => trim(
            (string)($_POST['user_rating'] ?? '')
        ),
        'review' => trim(
            (string)($_POST['review'] ?? '')
        ),
        'favorite' => isset($_POST['favorite']) ? 1 : 0
    ];
}


function track_validate(array $t): array
{
    $e = [];

    if (!in_array($t['watch_status'], WATCH_STATUSES, true)) {
        $e['watch_status'] = 'Choose a status.';
    }

    if (!in_array($t['priority'], PRIORITIES, true)) {
        $e['priority'] = 'Choose a priority.';
    }

    $count = filter_var(
        $t['watch_count'],
        FILTER_VALIDATE_INT
    );

    if (
        $count === false ||
        $count < 0 ||
        $count > 65535
    ) {
        $e['watch_count'] = 'Enter a number from 0 to 65535.';
    }

    if ($t['user_rating'] !== '') {
        if (
            !is_numeric($t['user_rating']) ||
            (float)$t['user_rating'] < 0 ||
            (float)$t['user_rating'] > 10
        ) {
            $e['user_rating'] =
                'Enter a rating from 0 to 10, or leave blank.';
        }
    }

    if (mb_strlen($t['review']) > 5000) {
        $e['review'] = 'Keep your review under 5000 characters.';
    }

    return $e;
}


// ---- Form field helpers ----

function f_open(
    string $n,
    string $label,
    array $err,
    bool $req,
    bool $full
): void {
    echo '<div class="field' .
        ($full ? ' full' : '') .
        (isset($err[$n]) ? ' has-error' : '') .
        '">';

    echo '<label for="' . e($n) . '">' .
        e($label) .
        ($req ? ' <span class="req">*</span>' : '') .
        '</label>';
}


function f_close(string $n, array $err): void
{
    echo (
        isset($err[$n])
            ? '<div class="field-error">' .
                e($err[$n]) .
              '</div>'
            : ''
    ) . '</div>';
}


function f_input(
    string $n,
    string $label,
    array $m,
    array $err,
    string $type = 'text',
    array $a = [],
    bool $req = true,
    bool $full = false
): void {
    f_open($n, $label, $err, $req, $full);

    $attr = '';

    foreach ($a as $k => $v) {
        $attr .= ' ' . e($k) . '="' . e((string)$v) . '"';
    }

    $value = (string)($m[$n] ?? '');

    if ($type === 'textarea') {
        echo '<textarea id="' . e($n) .
            '" name="' . e($n) . '"' .
            $attr . '>' .
            e($value) .
            '</textarea>';
    } else {
        echo '<input id="' . e($n) .
            '" name="' . e($n) .
            '" type="' . e($type) .
            '" value="' . e($value) . '"' .
            $attr . '>';
    }

    f_close($n, $err);
}


function f_select(
    string $n,
    string $label,
    array $m,
    array $err,
    array $opts,
    bool $req = true
): void {
    f_open($n, $label, $err, $req, false);

    echo '<select id="' . e($n) .
        '" name="' . e($n) . '">';

    echo '<option value="">Choose...</option>';

    foreach ($opts as $o) {
        echo '<option value="' . e($o) . '"' .
            (($m[$n] ?? '') === $o ? ' selected' : '') .
            '>' . e($o) . '</option>';
    }

    echo '</select>';

    f_close($n, $err);
}


// ---- Movie form ----

function render_movie_form(
    array $m,
    array $err,
    string $submit,
    string $cancelUrl
): void {
    ?>
    <form method="post" novalidate class="form-page">
        <?= csrf_field() ?>

        <div class="form-main">

            <?php if ($err): ?>
                <div class="flash flash-error" role="alert">
                    <span>Please fix the highlighted fields.</span>
                </div>
            <?php endif; ?>

            <section class="card form-section">
                <h2 class="form-h" style="--bar:var(--magenta)">
                    Basic info
                </h2>

                <div class="form-grid">
                    <?php
                    f_input(
                        'title',
                        'Title',
                        $m,
                        $err,
                        'text',
                        ['maxlength' => 150],
                        true,
                        true
                    );

                    f_input(
                        'short_description',
                        'Description',
                        $m,
                        $err,
                        'textarea',
                        [],
                        false,
                        true
                    );
                    ?>
                </div>
            </section>

            <section class="card form-section">
                <h2 class="form-h" style="--bar:var(--gold)">
                    Movie details
                </h2>

                <div class="form-grid">
                    <?php
                    f_input(
                        'release_year',
                        'Release year',
                        $m,
                        $err,
                        'number',
                        ['min' => 1888, 'max' => 2100]
                    );

                    f_input(
                        'genre',
                        'Genre',
                        $m,
                        $err,
                        'text',
                        ['list' => 'genres', 'maxlength' => 50]
                    );

                    f_input(
                        'director',
                        'Director',
                        $m,
                        $err,
                        'text',
                        ['maxlength' => 100],
                        false
                    );

                    f_input(
                        'cast',
                        'Main cast',
                        $m,
                        $err,
                        'text',
                        ['maxlength' => 255],
                        false
                    );

                    f_input(
                        'duration_minutes',
                        'Duration (minutes)',
                        $m,
                        $err,
                        'number',
                        ['min' => 1, 'max' => 1000],
                        false
                    );

                    f_select(
                        'age_rating',
                        'Age rating',
                        $m,
                        $err,
                        AGE_RATINGS,
                        false
                    );

                    f_input(
                        'language',
                        'Language',
                        $m,
                        $err,
                        'text',
                        ['maxlength' => 40],
                        false
                    );

                    f_input(
                        'country',
                        'Country',
                        $m,
                        $err,
                        'text',
                        ['maxlength' => 60],
                        false
                    );
                    ?>
                </div>
            </section>

            <section class="card form-section">
                <h2 class="form-h" style="--bar:var(--blue)">
                    Poster &amp; where to watch
                </h2>

                <div class="form-grid">
                    <?php
                    f_input(
                        'poster_url',
                        'Poster URL',
                        $m,
                        $err,
                        'url',
                        ['placeholder' => 'https://...'],
                        false,
                        true
                    );

                    f_input(
                        'streaming_platform',
                        'Streaming platform',
                        $m,
                        $err,
                        'text',
                        ['maxlength' => 50],
                        false
                    );

                    f_input(
                        'watch_url',
                        'Watch URL',
                        $m,
                        $err,
                        'url',
                        [
                            'placeholder' =>
                                'https://www.netflix.com/title/...'
                        ],
                        false,
                        true
                    );
                    ?>
                </div>
            </section>
        </div>

        <aside class="form-side">
            <div class="card preview-card">
                <h2 class="form-h" style="--bar:var(--magenta)">
                    Poster preview
                </h2>

                <div class="poster">
                    <?= poster_art(
                        !empty($m['title'])
                            ? (string)$m['title']
                            : 'New movie'
                    ) ?>

                    <img
                        id="poster-preview"
                        alt="Poster preview"
                        hidden
                        <?= !empty($m['poster_url'])
                            ? 'src="' . e((string)$m['poster_url']) . '"'
                            : '' ?>
                    >
                </div>

                <p class="muted small">
                    Paste a poster link to preview it.
                    Without one, a generated poster is used.
                </p>
            </div>

            <div class="card side-actions">
                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    <?= icon('check', 16) ?>
                    <?= e($submit) ?>
                </button>

                <a class="btn" href="<?= e($cancelUrl) ?>">
                    Cancel
                </a>
            </div>
        </aside>

        <datalist id="genres">
            <?php
            foreach (
                [
                    'Action',
                    'Adventure',
                    'Animation',
                    'Comedy',
                    'Crime',
                    'Drama',
                    'Fantasy',
                    'Horror',
                    'Romance',
                    'Sci-Fi',
                    'Thriller'
                ] as $g
            ) {
                echo '<option value="' . e($g) . '">';
            }
            ?>
        </datalist>
    </form>

    <script>
    (() => {
        const input = document.getElementById('poster_url');
        const img = document.getElementById('poster-preview');

        if (!input || !img) return;

        const sync = () => {
            img.hidden = !(img.complete && img.naturalWidth > 0);
        };

        img.addEventListener('load', sync);

        img.addEventListener('error', () => {
            img.hidden = true;
        });

        input.addEventListener('input', () => {
            img.hidden = true;

            const value = input.value.trim();

            if (/^https?:\/\//i.test(value)) {
                img.src = value;
            } else {
                img.removeAttribute('src');
            }
        });

        sync();
    })();
    </script>
    <?php
}


// ---- Personal tracking form ----
// This still posts to list_action.php.
// Update list_action.php to save tracking fields in movies,
// using both movie_id and the logged-in user's user_id.

function render_track_form(
    array $t,
    int $movieId,
    string $back
): void {
    $err = [];
    ?>
    <form method="post" action="list_action.php" novalidate>
        <?= csrf_field() ?>

        <input type="hidden" name="action" value="track">

        <input
            type="hidden"
            name="movie_id"
            value="<?= $movieId ?>"
        >

        <input
            type="hidden"
            name="back"
            value="<?= e($back) ?>"
        >

        <div class="form-grid">
            <?php
            f_select(
                'watch_status',
                'Watch status',
                $t,
                $err,
                WATCH_STATUSES
            );

            f_select(
                'priority',
                'Priority',
                $t,
                $err,
                PRIORITIES
            );

            f_input(
                'watch_count',
                'Times watched',
                $t,
                $err,
                'number',
                ['min' => 0, 'max' => 65535]
            );

            f_input(
                'user_rating',
                'Your rating (0-10)',
                $t,
                $err,
                'number',
                ['min' => 0, 'max' => 10, 'step' => '0.1'],
                false
            );

            f_input(
                'review',
                'Your review',
                $t,
                $err,
                'textarea',
                ['rows' => 4],
                false,
                true
            );
            ?>

            <div class="field check">
                <input
                    id="favorite"
                    type="checkbox"
                    name="favorite"
                    value="1"
                    <?= !empty($t['favorite']) ? 'checked' : '' ?>
                >

                <label for="favorite">
                    Mark as favorite
                </label>
            </div>
        </div>

        <button
            class="btn btn-primary"
            type="submit"
            style="margin-top:1rem"
        >
            <?= icon('check', 16) ?>
            Save my details
        </button>
    </form>
    <?php
}
