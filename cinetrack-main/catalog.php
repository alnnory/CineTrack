<?php
// The shared movie catalog: everyone can browse it and add movies to their own list. Only developers edit it.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
$uid = current_user_id();
const CAT_PER_PAGE = 24;

$q     = trim((string)($_GET['q'] ?? ''));
$genre = trim((string)($_GET['genre'] ?? ''));
$show  = ($_GET['show'] ?? '') === 'new' ? 'new' : 'all';
$page  = max(1, (int)($_GET['page'] ?? 1));

$where = []; $types = 'i'; $params = [$uid];
if ($q !== '') {
    $where[] = '(m.title LIKE ? OR m.director LIKE ? OR m.`cast` LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($params, $like, $like, $like);
    $types .= 'sss';
}
if ($genre !== '') { $where[] = 'm.genre = ?'; $params[] = $genre; $types .= 's'; }
if ($show === 'new') $where[] = 'um.user_id IS NULL';
$w    = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$from = 'FROM movies m LEFT JOIN user_movies um ON um.movie_id = m.movie_id AND um.user_id = ?';

function cat_query(mysqli $c, string $sql, string $types, array $p): mysqli_result {
    $s = $c->prepare($sql);
    $s->bind_param($types, ...$p);
    $s->execute();
    return $s->get_result();
}

$total  = (int)cat_query($conn, "SELECT COUNT(*) $from $w", $types, $params)->fetch_row()[0];
$pages  = max(1, (int)ceil($total / CAT_PER_PAGE));
$page   = min($page, $pages);
$offset = ($page - 1) * CAT_PER_PAGE;
$movies = cat_query($conn, "SELECT m.*, um.watch_status AS my_status $from $w ORDER BY m.title LIMIT " . CAT_PER_PAGE . " OFFSET $offset", $types, $params)->fetch_all(MYSQLI_ASSOC);
$genres = array_column($conn->query('SELECT DISTINCT genre FROM movies ORDER BY genre')->fetch_all(MYSQLI_NUM), 0);

$base = ['q' => $q, 'genre' => $genre, 'show' => $show === 'new' ? 'new' : ''];
$qs   = fn(array $o = []) => http_build_query(array_filter(array_merge($base, $o), fn($v) => $v !== '' && $v !== null));
$back = 'catalog.php?' . $qs(['page' => $page > 1 ? $page : '']);
$filtered = $q !== '' || $genre !== '' || $show === 'new';

$page_title = 'Catalog';
$active = 'catalog';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <span class="eyebrow">Shared catalog</span>
        <h1>Browse movies</h1>
        <p class="muted"><?= $total ?> <?= $total === 1 ? 'movie' : 'movies' ?><?= $filtered ? ' match your filters' : ' in the catalog' ?>. Add the ones you want to your own list.</p>
    </div>
    <?php if (is_developer()): ?><a class="btn btn-primary" href="add_movie.php"><?= icon('plus', 16) ?> Add movie to catalog</a><?php endif; ?>
</div>

<form class="card filters" method="get">
    <div class="f-row f-search">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search title, director, or cast" aria-label="Search the catalog">
        <button class="btn btn-primary btn-sm" type="submit"><?= icon('search', 15) ?> Search</button>
    </div>
    <div class="f-row f-selects">
        <select name="genre" aria-label="Genre" onchange="this.form.submit()"><option value="">All genres</option>
            <?php foreach ($genres as $g): ?><option <?= $g === $genre ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?></select>
        <select name="show" aria-label="Show" onchange="this.form.submit()">
            <option value="all" <?= $show === 'all' ? 'selected' : '' ?>>All movies</option>
            <option value="new" <?= $show === 'new' ? 'selected' : '' ?>>Not on my list yet</option></select>
        <?php if ($filtered): ?><a class="btn btn-sm" href="catalog.php">Clear filters</a><?php endif; ?>
    </div>
</form>

<?php if (!$movies): ?>
    <div class="card empty empty-rich">
        <span class="empty-icon"><?= icon('film', 48) ?></span>
        <h2>No movies found</h2>
        <p><?= $filtered ? 'Try a different search or clear the filters.' : (is_developer() ? 'The catalog is empty. Add the first movie.' : 'The catalog is empty. Please check back soon.') ?></p>
        <?php if ($filtered): ?><a class="btn btn-primary" href="catalog.php">Clear filters</a>
        <?php elseif (is_developer()): ?><a class="btn btn-primary" href="add_movie.php">Add a movie</a><?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid grid-movies">
    <?php foreach ($movies as $m): $mid = (int)$m['movie_id']; $url = 'view_movie.php?id=' . $mid; ?>
        <article class="mcard">
            <div class="mcard-media">
                <a href="<?= $url ?>" aria-label="View <?= e($m['title']) ?>"><?= poster($m['poster_url'], $m['title']) ?></a>
                <span class="pill"><?= e($m['genre']) ?></span>
            </div>
            <div class="mcard-body">
                <div class="mcard-meta"><span><?= (int)$m['release_year'] ?> &bull; <?= e(format_duration((int)$m['duration_minutes'])) ?></span></div>
                <h3><a href="<?= $url ?>"><?= e($m['title']) ?></a></h3>
                <div class="mcard-foot">
                    <?php if ($m['my_status'] !== null): ?>
                        <span class="badge <?= e(status_class($m['my_status'])) ?>">On my list &middot; <?= e($m['my_status']) ?></span>
                    <?php else: ?>
                        <form method="post" action="list_action.php" style="margin:0">
                            <?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="movie_id" value="<?= $mid ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
                            <button class="btn btn-sm btn-primary" type="submit"><?= icon('plus', 14) ?> Add to my list</button>
                        </form>
                    <?php endif; ?>
                    <a class="info-link" href="<?= $url ?>">Info <?= icon('chevron', 14) ?></a>
                </div>
                <?php if (is_developer()): ?>
                    <div class="mcard-actions"><a class="btn btn-sm" href="edit_movie.php?id=<?= $mid ?>"><?= icon('edit', 14) ?> Edit</a></div>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Pages">
        <?php if ($page > 1): ?><a class="btn btn-sm" href="?<?= e($qs(['page' => $page - 1])) ?>" aria-label="Previous page"><?= icon('chevron-left', 14) ?></a><?php endif; ?>
        <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
            <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : '' ?>" href="?<?= e($qs(['page' => $p])) ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a class="btn btn-sm" href="?<?= e($qs(['page' => $page + 1])) ?>" aria-label="Next page"><?= icon('chevron', 14) ?></a><?php endif; ?>
    </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>