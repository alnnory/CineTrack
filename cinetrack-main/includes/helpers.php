<?php
// Shared helpers: escaping, formatting, CSRF, flash messages, posters.
declare(strict_types=1);

// Rename the app here (also update README.md and assets/img/ if you change the logo).
const APP_NAME = 'CineTrack';
const APP_TAGLINE = 'Your movies. Your story.';

const WATCH_STATUSES = ['To Watch', 'Watching', 'Watched'];
const PRIORITIES = ['Low', 'Medium', 'High'];
const AGE_RATINGS = ['G', 'PG', 'PG-13', 'R', 'NC-17', 'NR'];

// ---- General helpers --------------------------------------------------

function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** 181 -> "3h 1m", 90 -> "1h 30m", 45 -> "45m" */
function format_duration(?int $min): string
{
    if (!$min || $min < 1) {
        return 'Not specified';
    }

    $h = intdiv($min, 60);
    $m = $min % 60;

    return $h > 0
        ? ($m > 0 ? "{$h}h {$m}m" : "{$h}h")
        : "{$m}m";
}

/** Guarded date formatting: zero/invalid dates never leak through. */
function format_date(?string $date): string
{
    if (!$date || str_starts_with($date, '0000')) {
        return 'Not specified';
    }

    $d = DateTime::createFromFormat('Y-m-d', substr($date, 0, 10));

    return $d ? $d->format('M j, Y') : 'Not specified';
}

function format_rating(?string $rating): string
{
    return $rating === null || $rating === ''
        ? 'Not rated'
        : number_format((float)$rating, 1) . ' / 10';
}

function status_class(string $status): string
{
    return 'badge-' . strtolower(str_replace(' ', '-', $status));
}

// ---- CSRF -------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' .
        e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals(
            (string)$_SESSION['csrf'],
            (string)$_POST['csrf']
        );
}

// ---- Flash messages ---------------------------------------------------

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function take_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $f;
}

// ---- Posters ----------------------------------------------------------

function hsl_hex(float $h, float $s, float $l): string
{
    $h = fmod(fmod($h, 360) + 360, 360);
    $s /= 100;
    $l /= 100;

    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
    $m = $l - $c / 2;

    [$r, $g, $b] = match ((int)floor($h / 60)) {
        0 => [$c, $x, 0],
        1 => [$x, $c, 0],
        2 => [0, $c, $x],
        3 => [0, $x, $c],
        4 => [$x, 0, $c],
        default => [$c, 0, $x],
    };

    return sprintf(
        '#%02x%02x%02x',
        (int)round(($r + $m) * 255),
        (int)round(($g + $m) * 255),
        (int)round(($b + $m) * 255)
    );
}

/**
 * Generated poster: colors, bokeh, stars, center motif and title
 * are derived from the movie title.
 */
function poster_art(string $title): string
{
    $seed = crc32($title);

    $r = function () use (&$seed): float {
        $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
        return $seed / 0x7fffffff;
    };

    $u = 'p' . dechex(crc32($title));
    $h = (int)($r() * 360);
    $h2 = ($h + 40 + (int)($r() * 70)) % 360;

    $svg = '<svg class="poster-art" viewBox="0 0 200 300" '
        . 'preserveAspectRatio="xMidYMid slice" '
        . 'xmlns="http://www.w3.org/2000/svg" role="img" '
        . 'aria-label="Generated poster for ' . e($title) . '">'

        . '<defs>'
        . '<linearGradient id="' . $u . 'b" x1="0" y1="0" x2="0" y2="1">'
        . '<stop offset="0" stop-color="' . hsl_hex($h, 80, 40) . '"/>'
        . '<stop offset="1" stop-color="' . hsl_hex($h2, 70, 9) . '"/>'
        . '</linearGradient>'

        . '<linearGradient id="' . $u . 'f" x1="0" y1="0" x2="0" y2="1">'
        . '<stop offset="0" stop-color="#06020e" stop-opacity="0"/>'
        . '<stop offset="1" stop-color="#06020e" stop-opacity=".88"/>'
        . '</linearGradient>'

        . '<filter id="' . $u . 'x" x="-30%" y="-30%" '
        . 'width="160%" height="160%">'
        . '<feGaussianBlur stdDeviation="9"/>'
        . '</filter>'
        . '</defs>'

        . '<rect width="200" height="300" fill="url(#' . $u . 'b)"/>'
        . '<g filter="url(#' . $u . 'x)">';

    for ($i = 0, $n = 5 + (int)($r() * 3); $i < $n; $i++) {
        $svg .= '<circle cx="' . round($r() * 200)
            . '" cy="' . round($r() * 210 + 10)
            . '" r="' . round(26 + $r() * 44)
            . '" fill="' . hsl_hex($h + $r() * 90 - 45, 90, 58)
            . '" opacity="' . round(.28 + $r() * .32, 2)
            . '"/>';
    }

    $svg .= '</g>';

    for ($i = 0; $i < 24; $i++) {
        $svg .= '<circle cx="' . round($r() * 200)
            . '" cy="' . round($r() * 290)
            . '" r="' . round(.5 + $r() * .7, 1)
            . '" fill="#fff" opacity="' . round(.3 + $r() * .6, 1)
            . '"/>';
    }

    $svg .= '<g fill="none" stroke="#fff" '
        . 'stroke-opacity=".3" stroke-width="1.1">';

    $svg .= match ((int)($r() * 4)) {
        0 => '<circle cx="100" cy="140" r="54"/>'
            . '<circle cx="100" cy="140" r="36"/>'
            . '<rect x="70" y="110" width="60" height="60" '
            . 'transform="rotate(45 100 140)"/>',

        1 => '<circle cx="100" cy="140" r="58"/>'
            . '<polygon points="100,92 146,172 54,172"/>',

        2 => '<circle cx="100" cy="140" r="26"/>'
            . '<polygon points="150,140 125,183.3 75,183.3 '
            . '50,140 75,96.7 125,96.7"/>',

        default => '<ellipse cx="100" cy="140" rx="62" ry="22" '
            . 'transform="rotate(-25 100 140)"/>'
            . '<ellipse cx="100" cy="140" rx="62" ry="22" '
            . 'transform="rotate(25 100 140)"/>'
            . '<circle cx="100" cy="140" r="4" fill="#fff"/>',
    };

    $svg .= '</g>'
        . '<rect y="150" width="200" height="150" '
        . 'fill="url(#' . $u . 'f)"/>';

    $accent = ['#fbbf24', '#22d3ee', '#f472b6', '#a3e635']
        [(int)($r() * 4)];

    $lines = [];
    $cur = '';

    foreach (preg_split('/\s+/', trim($title)) ?: [$title] as $w) {
        if ($cur !== '' && mb_strlen($cur . ' ' . $w) > 15) {
            $lines[] = $cur;
            $cur = $w;
        } else {
            $cur = $cur === '' ? $w : $cur . ' ' . $w;
        }
    }

    if ($cur !== '') {
        $lines[] = $cur;
    }

    if (count($lines) > 3) {
        $lines = array_slice($lines, 0, 3);
        $lines[2] = rtrim(mb_substr($lines[2], 0, 13)) . '...';
    }

    $y0 = 272 - (count($lines) - 1) * 17;

    foreach ($lines as $i => $ln) {
        $fill = count($lines) > 1 && $i === count($lines) - 1
            ? $accent
            : '#ffffff';

        $svg .= '<text x="100" y="' . ($y0 + $i * 17)
            . '" text-anchor="middle" '
            . 'font-family="Outfit,system-ui,sans-serif" '
            . 'font-weight="700" font-size="14" fill="' . $fill . '">'
            . e($ln)
            . '</text>';
    }

    return $svg . '</svg>';
}

/** Real poster image on top; generated art remains underneath as a fallback. */
function poster(?string $url, string $title, string $class = ''): string
{
    $img = $url
        ? '<img src="' . e($url) . '" alt="Poster for ' . e($title)
            . '" loading="lazy" onerror="this.remove()">'
        : '';

    return '<div class="poster ' . e($class) . '">'
        . poster_art($title)
        . $img
        . '</div>';
}

// ---- Icons ------------------------------------------------------------

function icon(string $name, int $size = 18): string
{
    static $paths = [
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'film' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 3v18M17 3v18M3 8h4M3 16h4M17 8h4M17 16h4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16v4z"/><path d="M13.5 6.5l4 4"/>',
        'trash' => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
        'heart' => '<path d="M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.5-7 10-7 10z"/>',
        'check' => '<path d="M5 12l5 5 9-10"/>',
        'star' => '<path d="M12 3l2.8 6 6.2.7-4.7 4.3 1.4 6.5-5.7-3.3-5.7 3.3 1.4-6.5L3 9.7 9.2 9z"/>',
        'search' => '<circle cx="11" cy="11" r="6"/><path d="M16 16l4 4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'bookmark' => '<path d="M6 3h12v18l-6-4-6 4z"/>',
        'chevron' => '<path d="M9 6l6 6-6 6"/>',
        'chevron-left' => '<path d="M15 6l-6 6 6 6"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'down' => '<path d="M6 9l6 6 6-6"/>',
        'play' => '<path fill="currentColor" d="M8 5v14l11-7z"/>',
        'rocket' => '<path d="M12 3c3 2 5 5 5 9l-2 3H9l-2-3c0-4 2-7 5-9z"/><circle cx="12" cy="10" r="1.6"/><path d="M9 15l-2 3M15 15l2 3M12 16v4"/>',
        'clapper' => '<rect x="3" y="9" width="18" height="12" rx="2"/><path d="M3 9l2.5-5 4 1.5L7 9M10 9l2.5-5 4 1.5L14 9M17 9l2.5-4"/>',
        'chart' => '<circle cx="12" cy="12" r="9"/><path d="M12 3v9h9"/>',
        'x' => '<path d="M5 5l14 14M19 5L5 19"/>',
        'chat' => '<path d="M4 5h16v11H9l-5 4z"/>',
        'camera' => '<rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="3.5"/><circle cx="17" cy="7" r=".8" fill="currentColor"/>',
        'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'shuffle' => '<path d="M16 4h4v4M4 20L20 4M20 16v4h-4M15 15l5 5M4 4l5 5"/>',
        'repeat' => '<path d="M17 2l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 22l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><path d="M16 5.2a3.2 3.2 0 0 1 0 5.6M18 14.3c1.8.8 3 2.6 3 5.7"/>',
        'dice' => '<rect x="4" y="4" width="16" height="16" rx="3"/><g fill="currentColor" stroke="none"><circle cx="9" cy="9" r="1.3"/><circle cx="15" cy="9" r="1.3"/><circle cx="9" cy="15" r="1.3"/><circle cx="15" cy="15" r="1.3"/></g>',
    ];

    return '<svg class="icon" width="' . $size . '" height="' . $size
        . '" viewBox="0 0 24 24" aria-hidden="true">'
        . ($paths[$name] ?? '')
        . '</svg>';
}

// ---- Movie card -------------------------------------------------------
// $full = true adds the "Watched +1" and quick-rate controls.

function movie_card(array $m, string $back, bool $full = false): string
{
    $id = (int)($m['movie_id'] ?? 0);
    $title = (string)($m['title'] ?? 'Untitled');
    $genre = (string)($m['genre'] ?? 'Not specified');
    $posterUrl = isset($m['poster_url']) && $m['poster_url'] !== ''
        ? (string)$m['poster_url']
        : null;

    $duration = isset($m['duration_minutes'])
        ? (int)$m['duration_minutes']
        : null;

    $year = isset($m['release_year'])
        ? (int)$m['release_year']
        : 0;

    $rating = $m['user_rating'] ?? null;
    $status = (string)($m['watch_status'] ?? 'To Watch');
    $watchCount = (int)($m['watch_count'] ?? 0);
    $favorite = (int)($m['favorite'] ?? 0) === 1;

    $url = 'view_movie.php?id=' . $id;

    $hid = csrf_field()
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<input type="hidden" name="back" value="' . e($back) . '">';

    ob_start();
    ?>
    <article class="mcard">
        <div class="mcard-media">
            <a href="<?= e($url) ?>" aria-label="View <?= e($title) ?>">
                <?= poster($posterUrl, $title) ?>
            </a>

            <span class="pill"><?= e($genre) ?></span>

            <form class="mcard-fav" method="post" action="quick_action.php">
                <?= $hid ?>
                <input type="hidden" name="action" value="favorite">

                <button
                    class="icon-btn <?= $favorite ? 'is-on' : '' ?>"
                    type="submit"
                    aria-pressed="<?= $favorite ? 'true' : 'false' ?>"
                    title="<?= $favorite ? 'Remove from favorites' : 'Add to favorites' ?>"
                    aria-label="Toggle favorite">
                    <?= icon('heart', 16) ?>
                </button>
            </form>
        </div>

        <div class="mcard-body">
            <div class="mcard-meta">
                <span>
                    <?= $year > 0 ? $year : 'Year not specified' ?>
                    &bull;
                    <?= e(format_duration($duration)) ?>
                </span>

                <?php if ($rating !== null && $rating !== ''): ?>
                    <span class="rate">
                        <?= icon('star', 13) ?>
                        <?= number_format((float)$rating, 1) ?>
                    </span>
                <?php endif; ?>
            </div>

            <h3>
                <a href="<?= e($url) ?>"><?= e($title) ?></a>
            </h3>

            <div class="mcard-foot">
                <span class="badge <?= e(status_class($status)) ?>">
                    <?= e($status) ?>
                    <?= $watchCount > 0 ? ' &times;' . $watchCount : '' ?>
                </span>

                <a class="info-link" href="<?= e($url) ?>">
                    Info <?= icon('chevron', 14) ?>
                </a>
            </div>

            <?php if ($full): ?>
                <div class="mcard-actions">
                    <form method="post" action="quick_action.php">
                        <?= $hid ?>
                        <input type="hidden" name="action" value="watched">

                        <button
                            class="btn btn-sm"
                            type="submit"
                            title="Set to Watched and add one to the watch count">
                            <?= icon('check', 15) ?> Watched +1
                        </button>
                    </form>

                    <form method="post" action="quick_action.php">
                        <?= $hid ?>
                        <input type="hidden" name="action" value="rate">

                        <select
                            name="value"
                            class="rate-select"
                            aria-label="Rate <?= e($title) ?>"
                            onchange="this.form.submit()">

                            <option value="">Rate</option>

                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>

                            <?php if ($rating !== null && $rating !== ''): ?>
                                <option value="none">Clear</option>
                            <?php endif; ?>
                        </select>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </article>
    <?php

    return (string)ob_get_clean();
}