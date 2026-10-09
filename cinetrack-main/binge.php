<?php
// Binge Watch result: the movie the user typed, or a weighted random pick when they are undecided.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/binge.php';

require_login();
$rows = binge_candidates($conn, current_user_id());
$list = 'movies.php?status=Watched';
if (!$rows) { flash('You have no watched movies to rewatch yet.', 'error'); redirect($list); }

$chosen = null; $random = isset($_GET['random']);
$title = trim((string)($_GET['title'] ?? ''));
if ($random) {
    $chosen = binge_pick_random($rows);
} elseif (isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    foreach ($rows as $r) if ((int)$r['movie_id'] === $id) $chosen = $r;
} else {
    if ($title === '') { flash('Type a movie title, or let CineTrack pick for you.', 'error'); redirect($list . '&binge=1'); }
    $needle = mb_strtolower($title);
    foreach ($rows as $r) if (mb_strtolower($r['title']) === $needle) $chosen = $r;                     // exact title
    if (!$chosen) {
        $hits = array_values(array_filter($rows, fn($r) => str_contains(mb_strtolower($r['title']), $needle)));
        if (count($hits) === 1) $chosen = $hits[0];                                                      // one partial match
    }
}
if (!$chosen) {
    flash($title !== '' ? 'No watched movie matches "' . $title . '". Pick one from the list, or let CineTrack choose.' : 'That movie is not on your watched list.', 'error');
    redirect($list . '&binge=1');
}

$page_title = 'Binge Watch';
$active = 'watched';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <span class="eyebrow">Binge Watch</span>
        <h1><?= $random ? "Tonight's rewatch" : 'Your rewatch' ?></h1>
        <p class="muted"><?= $random ? 'CineTrack picked this from your ' . count($rows) . ' watched movies, leaning toward high ratings, rarely rewatched and low priority titles.' : 'Great choice. Press play and log it when you are done.' ?></p>
    </div>
    <a class="btn" href="<?= e($list) ?>"><?= icon('chevron-left', 16) ?> Back to watched</a>
</div>
<section class="card pick">
    <a href="view_movie.php?id=<?= (int)$chosen['movie_id'] ?>"><?= poster($chosen['poster_url'], $chosen['title']) ?></a>
    <div>
        <h2><?= e($chosen['title']) ?></h2>
        <p class="muted"><?= (int)$chosen['release_year'] ?> &middot; <?= e(format_duration((int)$chosen['duration_minutes'])) ?> &middot; <?= e($chosen['genre']) ?><?= $chosen['streaming_platform'] ? ' &middot; on ' . e($chosen['streaming_platform']) : '' ?></p>
        <?php if ($chosen['why']): ?><p class="tags"><?php foreach ($chosen['why'] as $w) echo '<span class="tag">' . e($w) . '</span>'; ?></p><?php endif; ?>
        <p>You have watched it <?= (int)$chosen['watch_count'] ?> <?= (int)$chosen['watch_count'] === 1 ? 'time' : 'times' ?><?= $chosen['user_rating'] !== null ? ' and rated it ' . number_format((float)$chosen['user_rating'], 1) . '/10' : '' ?>.</p>
        <div class="head-actions">
            <form method="post" action="quick_action.php">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$chosen['movie_id'] ?>"><input type="hidden" name="action" value="watched"><input type="hidden" name="back" value="<?= e($list) ?>">
                <button class="btn btn-primary" type="submit"><?= icon('check', 16) ?> Log this rewatch (+1)</button>
            </form>
            <a class="btn" href="view_movie.php?id=<?= (int)$chosen['movie_id'] ?>">View details</a>
            <a class="btn" href="binge.php?random=1"><?= icon('dice', 16) ?> Pick another for me</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
