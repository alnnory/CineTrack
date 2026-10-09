<?php
// Usage: $page_title = 'Movies'; $active = 'movies'; require 'includes/header.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

$page_title = $page_title ?? APP_NAME;
$active     = $active ?? '';
$auth_page  = $auth_page ?? false;
sync_session_user($conn);
$flash      = take_flash();

$user     = current_user() ?? ['name' => 'Guest', 'email' => null];
$userName = (string)($user['name'] ?? 'Guest');
$initial  = $userName !== '' ? mb_strtoupper(mb_substr($userName, 0, 1)) : '?';

$navWatchlist = 0;
if (is_logged_in()) {
    $uid = current_user_id();
    $nw  = $conn->prepare("SELECT COUNT(*) FROM movies WHERE user_id = ? AND watch_status = 'To Watch'");
    $nw->bind_param('i', $uid);
    $nw->execute();
    $nwRes = $nw->get_result()->fetch_row();
    $navWatchlist = (int)($nwRes[0] ?? 0);
}

$nameA = APP_NAME;
$nameB = '';
if (preg_match('/^(.+?)([A-Z][a-z0-9]*)$/', APP_NAME, $nm)) {
    $nameA = $nm[1];
    $nameB = $nm[2];
}

$navLinks = [
    'home'      => ['index.php', 'home', 'Home'],
    'movies'    => ['movies.php', 'film', 'Movies'],
    'watchlist' => ['watchlist.php', 'bookmark', 'Watchlist'],
    'watched'   => ['movies.php?status=Watched', 'check', 'Watched'],
    'favorites' => ['movies.php?fav=1', 'heart', 'Favorites'],
];
if (is_developer()) $navLinks['admin'] = ['admin.php', 'shield', 'Developer'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (!empty($base_href)): ?><base href="<?= e($base_href) ?>"><?php endif; ?>
    <title><?= e($page_title) ?> | <?= e(APP_NAME) ?></title>
    <meta name="description" content="<?= e(APP_NAME) ?>: <?= e(APP_TAGLINE) ?>">
    <meta name="theme-color" content="#08040f">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="assets/img/favicon-32.png" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Theme script — DAPAT BAGO mag-CSS para walang flash -->
    <script>
        (function() {
            var t = localStorage.getItem('cinetrack.theme') || 'dark';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/polish.css">
    <link rel="stylesheet" href="assets/css/animations.css">
</head>
<body class="<?= $auth_page ? 'auth-body' : '' ?>">

<?php if (!$auth_page): ?>
<header class="site-header">
    <div class="wrap nav">
        <a class="brand" href="index.php" aria-label="<?= e(APP_NAME) ?> home">
            <span class="brand-mark"><?= icon('film', 22) ?></span>
            <span class="brand-text">
                <span class="brand-name"><?= e($nameA) ?><b><?= e($nameB) ?></b></span>
                <span class="brand-tag"><?= e(APP_TAGLINE) ?></span>
            </span>
        </a>

        <nav class="nav-pill" id="nav-links" aria-label="Main">
            <?php foreach ($navLinks as $key => $item): ?>
                <?php [$href, $ic, $label] = $item; ?>
                <a href="<?= $href ?>" class="<?= $active === $key ? 'is-active' : '' ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>>
                    <?= icon($ic, 16) ?> <?= e($label) ?>
                    <?php if ($key === 'watchlist' && $navWatchlist > 0): ?><span class="count"><?= $navWatchlist ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="nav-actions">
            <form class="nav-search" action="movies.php" method="get" role="search">
                <?= icon('search', 16) ?>
                <input type="search" name="q" placeholder="Search movies..." aria-label="Search movies">
            </form>
            <a class="icon-btn icon-btn-primary" href="add_movie.php" title="Add movie" aria-label="Add movie"><?= icon('plus', 18) ?></a>

            <!-- Theme toggle -->
            <button class="theme-toggle" type="button" aria-label="Toggle theme" title="Toggle light/dark mode">
                <svg class="icon icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <svg class="icon icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            </button>

            <!-- Command palette button — command/⌘ icon -->
            <button class="icon-btn" type="button" data-cmdk-open title="Command palette (Ctrl+Alt+P)" aria-label="Open command palette">
                <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6v12a3 3 0 1 0 3-3H6a3 3 0 1 0 3 3V6a3 3 0 1 0-3 3h12a3 3 0 1 0-3-3"/></svg>
            </button>

            <details class="profile">
                <summary aria-label="Account menu"><span class="avatar"><?= e($initial) ?></span><?= icon('down', 14) ?></summary>
                <div class="profile-menu">
                    <div class="profile-head">
                        <span class="avatar"><?= e($initial) ?></span>
                        <div><strong><?= e($userName) ?><?= is_developer() ? ' <span class="badge">Developer</span>' : '' ?></strong><small><?= e($user['email'] ?? 'Guest mode') ?></small></div>
                    </div>
                    <?php if (is_logged_in()): ?>
                        <a class="menu-item" href="profile.php">My profile</a>
                        <form action="logout.php" method="post" class="logout-form" style="margin:0">
                            <?= csrf_field() ?>
                            <a class="menu-item" href="#" role="button" onclick="this.closest('form').submit(); return false;">Sign out</a>
                        </form>
                    <?php else: ?>
                        <a class="menu-item" href="login.php">Sign in</a>
                        <a class="menu-item" href="register.php">Create account</a>
                    <?php endif; ?>
                </div>
            </details>
            <button class="icon-btn nav-toggle" type="button" aria-label="Toggle menu" aria-expanded="false"><?= icon('chevron', 18) ?></button>
        </div>
    </div>
</header>
<main class="wrap">
<?php else: ?>
<main class="auth-wrap">
<?php endif; ?>

<?php if ($flash && !$auth_page): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="status">
        <span><?= e($flash['message']) ?></span>
        <button type="button" class="flash-close" aria-label="Dismiss">&times;</button>
    </div>
<?php endif; ?>