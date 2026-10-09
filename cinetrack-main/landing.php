<?php
// Public landing page (no database needed).
require_once __DIR__ . '/includes/helpers.php';
$getStarted = 'register.php';
$signIn = 'login.php';

// Floating particles for the hero (deterministic, so the page looks the same on every load)
$seed = 11;
$rnd = function () use (&$seed): float { $seed = ($seed * 1103515245 + 12345) & 0x7fffffff; return $seed / 0x7fffffff; };
$dotColors = ['#e0409b', '#fbbf24', '#8b5cf6', '#4f8cff'];

$highlights = [
    ['Watch', 'Track what you want to watch with custom cosmic priority queues.', 'bookmark', 'rgba(139,92,246,.22)', '#fbbf24'],
    ['Rate', 'Give movies your personal rating with multi-tier star evaluations.', 'star', 'rgba(224,64,155,.22)', '#fbbf24'],
    ['Review', 'Write and save your thoughts, detailed critiques, and movie quotes.', 'edit', 'rgba(139,92,246,.22)', '#f472b6'],
    ['Remember', 'Keep your movie history organized across years, genres, and directors.', 'clock', 'rgba(251,191,36,.15)', '#fbbf24'],
];
$features = [
    ['Movie Collection', 'Organize all your movies in one sleek place. Custom categories, tagging systems, and instant cloud sync across devices.', 'Smart Filtering', 'clapper', 'c-violet'],
    ['Watchlist', 'Save movies you want to watch later. Receive reminders for re-releases, physical editions, or theater screenings.', 'Priority Queueing', 'bookmark', 'c-pink'],
    ['Ratings & Reviews', 'Rate movies on a 5-star precision scale and write personal critiques to capture exactly how each story made you feel.', 'Detailed Journaling', 'star', 'c-gold'],
    ['Favorites', 'Pin the movies you love most for instant access and build a personal hall of fame of must-rewatch classics.', 'Hall of Fame', 'heart', 'c-pink'],
];
$steps = [
    ['01', 'Discover', 'Browse thousands of curated movies or import your existing collection effortlessly.', '#8b5cf6'],
    ['02', 'Track', 'Add upcoming movies to your personal cosmic watchlist with priority tags.', '#e0409b'],
    ['03', 'Watch', 'Mark titles as watched when completed and log your viewing date history.', '#8b5cf6'],
    ['04', 'Rate', 'Give custom star ratings and publish personal reviews to reflect your thoughts.', '#fbbf24'],
];
$bars = [['Sci-Fi', 42], ['Action & Thriller', 35], ['Fantasy', 15], ['Drama', 8]];
// [title, genre, type, rating, year, status]
$showcase = [
    ['Chronos: Edge of Infinity', 'Sci-Fi', 'Movie', 4.9, 2026, 'Watched'],
    ['Astral Horizon', 'Sci-Fi', 'Movie', 4.8, 2025, 'In Watchlist'],
    ['Neon Cyberpunk 2099', 'Action', 'Movie', 4.7, 2026, 'Watched'],
    ['Velvet Shadow', 'Action', 'Movie', 4.6, 2024, 'In Watchlist'],
    ['Aetheria: Lost City', 'Fantasy', 'Movie', 4.9, 2025, 'Watched'],
    ['Starfall Chronicles', 'Sci-Fi', 'Series', 4.9, 2026, 'Watching'],
    ['Crown of Ash', 'Fantasy', 'Series', 4.7, 2025, 'In Watchlist'],
    ['Midnight Syndicate', 'Action', 'Series', 4.6, 2026, 'Watched'],
    ['The Last Cartographer', 'Drama', 'Movie', 4.8, 2024, 'Watched'],
    ['Hollow Hearts', 'Drama', 'Series', 4.8, 2025, 'Watching'],
    ['Midnight Lantern', 'Horror', 'Movie', 4.5, 2025, 'In Watchlist'],
    ['Echoes Beneath', 'Horror', 'Series', 4.4, 2024, 'In Watchlist'],
];
$statusClass = ['Watched' => 'badge-watched', 'In Watchlist' => 'badge-watchlist', 'Watching' => 'badge-watching'];
$filters = ['all' => 'All Titles', 'genre:Sci-Fi' => 'Sci-Fi', 'genre:Action' => 'Action', 'genre:Fantasy' => 'Fantasy', 'genre:Drama' => 'Drama', 'genre:Horror' => 'Horror', 'type:Series' => 'Series'];
$navLinks = ['home' => 'Home', 'highlights' => 'Highlights', 'features' => 'Features', 'how-it-works' => 'How It Works', 'showcase' => 'Showcase'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?> | <?= e(APP_TAGLINE) ?></title>
    <meta name="description" content="<?= e(APP_NAME) ?> is a personal movie watchlist and tracking platform to organize, rate, review, and remember the movies you love.">
    <meta name="theme-color" content="#08040f">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="assets/img/favicon-32.png" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/polish.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="landing-page">

<header class="site-header">
    <div class="wrap nav">
        <a class="brand" href="#home" aria-label="<?= e(APP_NAME) ?> home">
            <img class="brand-img" src="assets/img/favicon.svg" alt="" width="42" height="42">
            <span class="brand-text"><span class="brand-name caps"><?= e(APP_NAME) ?></span><span class="brand-tag"><?= e(APP_TAGLINE) ?></span></span>
        </a>
        <nav class="nav-pill" id="nav-links" aria-label="Page sections">
            <?php foreach ($navLinks as $id => $label): ?><a href="#<?= $id ?>" class="<?= $id === 'home' ? 'is-active' : '' ?>"><?= e($label) ?></a><?php endforeach; ?>
        </nav>
        <div class="nav-actions">
            <div class="nav-cta">
                <a class="btn btn-cta" href="<?= e($getStarted) ?>"><?= icon('play', 13) ?> Get Started</a>
                <a class="btn btn-dark" href="<?= e($signIn) ?>">Sign in</a>
            </div>
            <button class="icon-btn nav-toggle" type="button" aria-label="Toggle menu" aria-expanded="false"><?= icon('chevron', 18) ?></button>
        </div>
    </div>
</header>

<main class="lp">
    <!-- Hero -->
    <section class="lp-hero" id="home">
        <div class="dots" aria-hidden="true">
            <?php for ($i = 0; $i < 46; $i++): $z = round(2 + $rnd() * 2.6, 1); ?>
                <span class="dot" style="left:<?= round($rnd() * 100, 1) ?>%;top:<?= round($rnd() * 100, 1) ?>%;width:<?= $z ?>px;height:<?= $z ?>px;background:<?= $dotColors[(int)($rnd() * 4)] ?>;--d:<?= round(3 + $rnd() * 5, 1) ?>s;--delay:-<?= round($rnd() * 6, 1) ?>s"></span>
            <?php endfor; ?>
        </div>
        <div class="wrap inner">
            <span class="eyebrow"><i class="live-dot"></i> Personal movie universe</span>
            <img class="hero-logo" src="assets/img/favicon.svg" alt="<?= e(APP_NAME) ?> logo" width="84" height="84">
            <h1 class="hero-title"><?= e(APP_NAME) ?></h1>
            <p class="hero-quote">&ldquo;Your Movies. Your Story.&rdquo;</p>
            <p class="hero-copy"><?= e(APP_NAME) ?> is a personal movie watchlist and tracking platform that helps you organize, discover, rate, review, and keep track of the movies you love.</p>
            <div class="hero-cta">
                <a class="btn btn-cta btn-lg" href="<?= e($getStarted) ?>"><?= icon('rocket', 16) ?> Get Started</a>
                <a class="btn btn-dark btn-lg" href="#showcase"><?= icon('film', 16) ?> Explore Movies</a>
            </div>
            <div class="hero-stats">
                <div class="stat-chip"><b>100%</b><span>Personalized</span></div>
                <div class="stat-chip"><b class="gold">4.9/5</b><span>User Score</span></div>
                <div class="stat-chip"><b class="violet">Unlimited</b><span>Watchlists</span></div>
                <div class="stat-chip"><b class="pink">Zero Ads</b><span>Pure Cinema</span></div>
            </div>
        </div>
    </section>

    <!-- Highlights -->
    <section class="lp-section" id="highlights">
        <div class="wrap">
            <div class="lp-grid-4">
                <?php foreach ($highlights as [$t, $d, $ic, $bg, $c]): ?>
                <article class="lp-card reveal">
                    <span class="tile" style="--bg:<?= $bg ?>;--c:<?= $c ?>"><?= icon($ic, 20) ?></span>
                    <h3 class="card-caps"><?= e($t) ?></h3>
                    <p class="muted"><?= e($d) ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="lp-section" id="features">
        <div class="wrap">
            <div class="section-intro reveal">
                <span class="eyebrow">Powerful toolset</span>
                <h2 class="lp-title">Everything You Need for Your <span class="grad-text">Movie Collection</span></h2>
                <p class="muted">Designed specifically for movie enthusiasts who desire complete control over their cinematic journey.</p>
            </div>
            <div class="feat-grid">
                <?php foreach (array_slice($features, 0, 3) as [$t, $d, $link, $ic, $cls]): ?>
                <article class="lp-card feat reveal <?= $cls ?>">
                    <span class="tile"><?= icon($ic, 20) ?></span>
                    <h3><?= e($t) ?></h3>
                    <p class="muted"><?= e($d) ?></p>
                    <span class="feat-link"><?= e($link) ?> <?= icon('chevron', 13) ?></span>
                </article>
                <?php endforeach; ?>
                <?php [$t, $d, $link, $ic, $cls] = $features[3]; ?>
                <article class="lp-card feat reveal <?= $cls ?>">
                    <span class="tile"><?= icon($ic, 20) ?></span>
                    <h3><?= e($t) ?></h3>
                    <p class="muted"><?= e($d) ?></p>
                    <span class="feat-link"><?= e($link) ?> <?= icon('chevron', 13) ?></span>
                </article>
                <article class="lp-card feat feat-wide reveal c-violet">
                    <div>
                        <span class="tile"><?= icon('chart', 20) ?></span>
                        <h3>Movie Statistics</h3>
                        <p class="muted">Understand your viewing habits with clear breakdowns of total hours, favorite genres, and your most-watched directors.</p>
                        <span class="feat-link">Viewing Insights <?= icon('chevron', 13) ?></span>
                    </div>
                    <div class="runtime">
                        <div class="runtime-head"><span>Monthly Watch Runtime</span><b>142 hrs</b></div>
                        <?php foreach ($bars as [$name, $pct]): ?>
                        <div class="bar"><div class="bar-row2"><span><?= e($name) ?></span><span><?= $pct ?>%</span></div><i><b style="--w:<?= $pct ?>%"></b></i></div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="lp-section" id="how-it-works">
        <div class="wrap">
            <div class="section-intro reveal">
                <span class="eyebrow pink">Simple 4-step process</span>
                <h2 class="lp-title">How <?= e(APP_NAME) ?> Works</h2>
                <p class="muted">Start cataloging your personal cinema journey in seconds.</p>
            </div>
            <div class="steps">
                <?php foreach ($steps as [$n, $t, $d, $c]): ?>
                <article class="lp-card step reveal" style="--c:<?= $c ?>">
                    <span class="step-num"><?= $n ?></span>
                    <h3 class="card-caps"><?= e($t) ?></h3>
                    <p class="muted"><?= e($d) ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Showcase -->
    <section class="lp-section" id="showcase">
        <div class="wrap">
            <div class="showcase-head reveal">
                <div>
                    <span class="eyebrow">Original showcase</span>
                    <h2 class="lp-title left">Explore Your Universe</h2>
                    <p class="muted">Preview how fictional cinematic masterpieces appear inside <?= e(APP_NAME) ?>.</p>
                </div>
                <div class="genre-chips" role="tablist" aria-label="Filter showcase">
                    <?php foreach ($filters as $key => $label): ?><button type="button" class="chip-link <?= $key === 'all' ? 'is-on' : '' ?>" data-filter="<?= e($key) ?>"><?= e($label) ?></button><?php endforeach; ?>
                </div>
            </div>
            <div class="sc-grid">
                <?php foreach ($showcase as [$t, $g, $type, $rate, $yr, $status]): ?>
                <article class="mcard reveal" data-genre="<?= e($g) ?>" data-type="<?= e($type) ?>">
                    <div class="mcard-media">
                        <div class="poster"><?= poster_art($t) ?></div>
                        <span class="pill"><?= e($g) ?></span>
                        <span class="rate-pill"><?= icon('star', 12) ?> <?= number_format($rate, 1) ?></span>
                    </div>
                    <div class="mcard-body">
                        <div class="mcard-meta"><span><?= $yr ?><?= $type === 'Series' ? ' &bull; Series' : '' ?></span></div>
                        <h3><?= e($t) ?></h3>
                        <div class="mcard-foot"><span class="badge <?= $statusClass[$status] ?>"><?= e($status) ?></span><span class="info-link"><?= icon('chevron', 14) ?></span></div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Call to action -->
    <section class="lp-cta">
        <div class="wrap">
            <span class="cta-tile"><?= icon('film', 26) ?></span>
            <h2 class="lp-title">Ready for your next movie?</h2>
            <p class="muted">Start building your personal movie collection with <?= e(APP_NAME) ?> today.</p>
            <a class="btn btn-cta btn-lg" href="<?= e($getStarted) ?>"><?= icon('clapper', 16) ?> Start Tracking</a>
            <p class="muted small cta-note">No credit card required &bull; Instant access to cataloging tools</p>
        </div>
    </section>
</main>

<footer class="lp-footer">
    <div class="wrap">
        <div class="lp-foot">
            <a class="brand" href="#home">
                <img class="brand-img" src="assets/img/favicon.svg" alt="" width="36" height="36">
                <span class="brand-text"><span class="brand-name caps"><?= e(APP_NAME) ?></span><span class="brand-tag"><?= e(APP_TAGLINE) ?></span></span>
            </a>
            <nav aria-label="Footer">
                <?php foreach ($navLinks as $id => $label): ?><a href="#<?= $id ?>"><?= e($label) ?></a><?php endforeach; ?>
                <a href="privacy.php">Privacy</a>
                <a href="#">Terms</a>
            </nav>
            <div class="social">
                <a href="#" aria-label="X"><?= icon('x', 15) ?></a>
                <a href="#" aria-label="Chat community"><?= icon('chat', 15) ?></a>
                <a href="#" aria-label="Instagram"><?= icon('camera', 15) ?></a>
            </div>
        </div>
        <p class="lp-copy">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved. Personal movie watchlist and tracking platform.</p>
    </div>
</footer>
<script src="assets/js/app.js"></script>
<script src="assets/js/landing.js"></script>
</body>
</html>