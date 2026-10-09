<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

// If not logged in, show the public landing page instead of the login form
if (!is_logged_in()) {
    require __DIR__ . '/landing.php';
    exit;
}

$uid = current_user_id();

$st_stmt = $conn->prepare("SELECT COUNT(*) total,
        COALESCE(SUM(watch_status = 'Watched'), 0) watched,
        COALESCE(SUM(watch_status = 'To Watch'), 0) to_watch,
        COALESCE(SUM(watch_status = 'Watching'), 0) watching,
        COALESCE(SUM(favorite), 0) favs,
        AVG(user_rating) avg_rating,
        COALESCE(SUM(duration_minutes * watch_count), 0) mins
    FROM movies WHERE user_id = ?");
$st_stmt->bind_param('i', $uid); $st_stmt->execute();
$st = $st_stmt->get_result()->fetch_assoc();
$hours = (int)round($st['mins'] / 60);

function movie_rows(mysqli $c, string $sql, int $uid): array {
    $s = $c->prepare($sql);
    $s->bind_param('i', $uid);
    $s->execute();
    return $s->get_result()->fetch_all(MYSQLI_ASSOC);
}

$continue = movie_rows($conn, "SELECT * FROM movies WHERE user_id = ? AND watch_status = 'Watching' ORDER BY date_added DESC LIMIT 12", $uid);
$next     = movie_rows($conn, "SELECT * FROM movies WHERE user_id = ? AND watch_status = 'To Watch' AND priority = 'High' ORDER BY date_added ASC, title ASC LIMIT 12", $uid);
$top      = movie_rows($conn, "SELECT * FROM movies WHERE user_id = ? AND user_rating IS NOT NULL ORDER BY user_rating DESC, watch_count DESC, title ASC LIMIT 12", $uid);
$recent   = movie_rows($conn, "SELECT * FROM movies WHERE user_id = ? ORDER BY date_added DESC, movie_id DESC LIMIT 12", $uid);

$g_stmt = $conn->prepare("SELECT genre, COUNT(*) n FROM movies WHERE user_id = ? GROUP BY genre ORDER BY n DESC, genre ASC LIMIT 10");
$g_stmt->bind_param('i', $uid); $g_stmt->execute();
$genres = $g_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$feature  = $top[0] ?? ($recent[0] ?? null);

// "Pick something for tonight": random unwatched movie, weighted by priority.
$pick = null;
if (isset($_GET['pick'])) {
    $pool = movie_rows($conn, "SELECT * FROM movies WHERE user_id = ? AND watch_status IN ('To Watch','Watching')", $uid);
    if ($pool) {
        $w = ['High' => 3, 'Medium' => 2, 'Low' => 1];
        $roll = random_int(1, array_sum(array_map(fn($m) => $w[$m['priority']] ?? 1, $pool)));
        foreach ($pool as $m) { $roll -= $w[$m['priority']] ?? 1; if ($roll <= 0) { $pick = $m; break; } }
    }
}

$back = 'index.php';
function rail_section(string $id, string $title, string $sub, string $link, string $color, array $rows, string $back): void { ?>
<section class="section">
    <div class="sec-title" style="--bar:<?= $color ?>">
        <div><h2><?= e($title) ?></h2><p><?= e($sub) ?></p></div>
        <div class="rail-nav">
            <a class="link-arrow" href="<?= e($link) ?>">View all <?= icon('arrow', 15) ?></a>
            <button class="icon-btn" type="button" data-rail="#<?= $id ?>" data-dir="prev" aria-label="Scroll left"><?= icon('chevron-left', 16) ?></button>
            <button class="icon-btn" type="button" data-rail="#<?= $id ?>" data-dir="next" aria-label="Scroll right"><?= icon('chevron', 16) ?></button>
        </div>
    </div>
    <div class="rail" id="<?= $id ?>"><?php foreach ($rows as $m) echo movie_card($m, $back); ?></div>
</section>
<?php }

$page_title = 'Dashboard';
$active = 'home';
require __DIR__ . '/includes/header.php';
?>
<section class="landing">
    <div>
        <span class="eyebrow">Your personal movie universe</span>
        <h1>Every Movie.<br><span class="grad-text">One Place.</span></h1>
        <p class="lead">Track what you watch, save what is next, and keep your favorite stories close. <?= (int)($st['to_watch'] + $st['watching']) ?> movies are waiting on your watchlist.</p>
        <div class="landing-cta">
            <a class="btn btn-primary" href="movies.php"><?= icon('film', 16) ?> Explore movies</a>
            <a class="btn" href="?pick=1#pick"><?= icon('dice', 16) ?> Pick something for tonight</a>
        </div>
        <div class="landing-stats">
            <div><b><?= (int)$st['total'] ?></b><span>Movies tracked</span></div>
            <div><b><?= $st['avg_rating'] !== null ? number_format((float)$st['avg_rating'], 1) : '-' ?> <span class="gold"><?= icon('star', 18) ?></span></b><span>Average rating</span></div>
            <div><b><?= number_format($hours) ?>h</b><span>Hours watched</span></div>
        </div>
    </div>
    <?php if ($feature): ?>
    <a class="landing-feature" href="view_movie.php?id=<?= (int)$feature['movie_id'] ?>">
        <?= poster($feature['poster_url'], $feature['title']) ?>
        <div class="feature-cap"><small>Featured &middot; <?= $feature['user_rating'] !== null ? 'top rated' : 'recently added' ?></small><strong><?= e($feature['title']) ?></strong></div>
    </a>
    <?php endif; ?>
</section>

<?php if ($pick): ?>
<section class="card pick" id="pick">
    <a href="view_movie.php?id=<?= (int)$pick['movie_id'] ?>"><?= poster($pick['poster_url'], $pick['title']) ?></a>
    <div>
        <span class="eyebrow">Tonight's pick</span>
        <h2><?= e($pick['title']) ?></h2>
        <p class="muted"><?= (int)$pick['release_year'] ?> &middot; <?= e(format_duration((int)$pick['duration_minutes'])) ?> &middot; <?= e($pick['genre']) ?><?= $pick['streaming_platform'] ? ' &middot; on ' . e($pick['streaming_platform']) : '' ?></p>
        <p><?= e($pick['short_description']) ?></p>
        <a class="btn btn-primary btn-sm" href="view_movie.php?id=<?= (int)$pick['movie_id'] ?>">View details</a>
        <a class="btn btn-sm" href="?pick=1#pick"><?= icon('dice', 15) ?> Pick another</a>
    </div>
</section>
<?php elseif (isset($_GET['pick'])): ?>
<section class="card empty" id="pick"><h2>Nothing left to pick</h2><p>Every movie is marked as watched. Add something new.</p><a class="btn btn-primary" href="add_movie.php">Add movie</a></section>
<?php endif; ?>

<div class="grid grid-stats section">
    <div class="card stat"><span class="stat-icon"><?= icon('film', 22) ?></span><div><div class="stat-value"><?= (int)$st['total'] ?></div><div class="stat-label">Total movies</div></div></div>
    <div class="card stat"><span class="stat-icon t-green"><?= icon('check', 22) ?></span><div><div class="stat-value"><?= (int)$st['watched'] ?></div><div class="stat-label">Watched</div></div></div>
    <div class="card stat"><span class="stat-icon t-pink"><?= icon('bookmark', 22) ?></span><div><div class="stat-value"><?= (int)$st['to_watch'] ?></div><div class="stat-label">To watch</div></div></div>
    <div class="card stat"><span class="stat-icon t-gold"><?= icon('heart', 22) ?></span><div><div class="stat-value"><?= (int)$st['favs'] ?></div><div class="stat-label">Favorites</div></div></div>
    <div class="card stat"><span class="stat-icon t-gold"><?= icon('star', 22) ?></span><div><div class="stat-value"><?= $st['avg_rating'] !== null ? number_format((float)$st['avg_rating'], 1) : '-' ?></div><div class="stat-label">Avg rating</div></div></div>
    <div class="card stat"><span class="stat-icon t-blue"><?= icon('clock', 22) ?></span><div><div class="stat-value"><?= number_format($hours) ?></div><div class="stat-label">Hours watched</div></div></div>
</div>

<?php if ($continue) rail_section('rail-continue', 'Continue Watching', 'Pick up where you left off', 'movies.php?status=Watching', 'var(--blue)', $continue, $back); ?>
<?php if ($next) rail_section('rail-next', 'High Priority Next', 'The movies you want to see first', 'movies.php?status=To+Watch&priority=High', 'var(--gold)', $next, $back); ?>
<?php if ($top) rail_section('rail-top', 'Top Rated', 'Your highest rated movies', 'movies.php?sort=rating_desc', 'var(--magenta)', $top, $back); ?>

<?php if ($genres): ?>
<section class="section">
    <div class="sec-title" style="--bar:var(--gold)"><div><h2>Explore by Genre</h2><p>Jump into your collection by category</p></div></div>
    <div class="genre-chips">
        <a class="chip-link is-on" href="movies.php">All <small><?= (int)$st['total'] ?></small></a>
        <?php foreach ($genres as $g): ?><a class="chip-link" href="movies.php?genre=<?= urlencode($g['genre']) ?>"><?= e($g['genre']) ?> <small><?= (int)$g['n'] ?></small></a><?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($recent) rail_section('rail-recent', 'Recently Added', 'Fresh additions to your timeline', 'movies.php?sort=added_desc', 'var(--magenta)', $recent, $back); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>