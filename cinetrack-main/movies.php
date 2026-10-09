<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/binge.php';

require_login();

const PER_PAGE = 24;
$sorts = [
    'added_desc'  => ['Recently added', 'date_added DESC, movie_id DESC'],
    'title_asc'   => ['Title A-Z', 'title ASC'],
    'title_desc'  => ['Title Z-A', 'title DESC'],
    'year_desc'   => ['Newest release', 'release_year DESC, title ASC'],
    'year_asc'    => ['Oldest release', 'release_year ASC, title ASC'],
    'rating_desc' => ['Highest rated', 'user_rating IS NULL, user_rating DESC, title ASC'],
    'priority'    => ['Priority', "FIELD(priority,'High','Medium','Low'), title ASC"],
];

$uid = current_user_id();

// ---- Read filters (all whitelisted) ----
$q        = trim((string)($_GET['q'] ?? ''));
$genre    = trim((string)($_GET['genre'] ?? ''));
$platform = trim((string)($_GET['platform'] ?? ''));
$status   = in_array($_GET['status'] ?? '', WATCH_STATUSES, true) ? $_GET['status'] : '';
$priority = in_array($_GET['priority'] ?? '', PRIORITIES, true) ? $_GET['priority'] : '';
$fav      = !empty($_GET['fav']);
$sort     = array_key_exists($_GET['sort'] ?? '', $sorts) ? $_GET['sort'] : 'added_desc';
$view     = ($_GET['view'] ?? '') === 'list' ? 'list' : 'grid';
$page     = max(1, (int)($_GET['page'] ?? 1));
$yr = fn($k) => filter_var($_GET[$k] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1888, 'max_range' => 2100]]);
$yearFrom = $yr('year_from') ?: '';
$yearTo   = $yr('year_to') ?: '';
if ($yearFrom !== '' && $yearTo !== '' && $yearFrom > $yearTo) [$yearFrom, $yearTo] = [$yearTo, $yearFrom];
$minRating = in_array($_GET['min_rating'] ?? '', ['5', '6', '7', '8', '9'], true) ? $_GET['min_rating'] : '';
$runtimes  = ['short' => ['Under 2 hours', 'duration_minutes < 120'], 'medium' => ['2 to 3 hours', 'duration_minutes BETWEEN 120 AND 180'], 'long' => ['Over 3 hours', 'duration_minutes > 180']];
$runtime   = array_key_exists($_GET['runtime'] ?? '', $runtimes) ? $_GET['runtime'] : '';

// ---- Build the query with prepared parameters ----
$where  = ['user_id = ?'];
$types  = 'i';
$params = [$uid];

if ($q !== '') {
    $where[] = '(title LIKE ? OR director LIKE ? OR `cast` LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($params, $like, $like, $like);
    $types .= 'sss';
}
foreach (['genre' => $genre, 'watch_status' => $status, 'priority' => $priority, 'streaming_platform' => $platform] as $col => $val) {
    if ($val !== '') { $where[] = "$col = ?"; $params[] = $val; $types .= 's'; }
}
if ($fav) $where[] = 'favorite = 1';
if ($yearFrom !== '') { $where[] = 'release_year >= ?'; $params[] = $yearFrom; $types .= 'i'; }
if ($yearTo !== '')   { $where[] = 'release_year <= ?'; $params[] = $yearTo;   $types .= 'i'; }
if ($minRating !== '') { $where[] = 'user_rating >= ?'; $params[] = (float)$minRating; $types .= 'd'; }
if ($runtime !== '') $where[] = $runtimes[$runtime][1];
$w = 'WHERE ' . implode(' AND ', $where);

function run_query(mysqli $c, string $sql, string $types, array $p): mysqli_result {
    $s = $c->prepare($sql);
    if ($p) $s->bind_param($types, ...$p);
    $s->execute();
    return $s->get_result();
}

$total  = (int)run_query($conn, "SELECT COUNT(*) FROM movies $w", $types, $params)->fetch_row()[0];
$pages  = max(1, (int)ceil($total / PER_PAGE));
$page   = min($page, $pages);
$offset = ($page - 1) * PER_PAGE;
$movies = run_query($conn, "SELECT * FROM movies $w ORDER BY {$sorts[$sort][1]} LIMIT " . PER_PAGE . " OFFSET $offset", $types, $params)->fetch_all(MYSQLI_ASSOC);

$g_stmt = $conn->prepare("SELECT DISTINCT genre FROM movies WHERE user_id = ? ORDER BY genre");
$g_stmt->bind_param('i', $uid); $g_stmt->execute();
$genres = array_column($g_stmt->get_result()->fetch_all(MYSQLI_NUM), 0);

$p_stmt = $conn->prepare("SELECT DISTINCT streaming_platform FROM movies WHERE user_id = ? AND streaming_platform IS NOT NULL AND streaming_platform <> '' ORDER BY streaming_platform");
$p_stmt->bind_param('i', $uid); $p_stmt->execute();
$platforms = array_column($p_stmt->get_result()->fetch_all(MYSQLI_NUM), 0);

$base = ['q' => $q, 'genre' => $genre, 'status' => $status, 'priority' => $priority, 'platform' => $platform, 'fav' => $fav ? 1 : '', 'year_from' => $yearFrom, 'year_to' => $yearTo, 'min_rating' => $minRating, 'runtime' => $runtime, 'sort' => $sort, 'view' => $view];
$qs = fn(array $o = []) => http_build_query(array_filter(array_merge($base, $o), fn($v) => $v !== '' && $v !== null));
$back = 'movies.php?' . $qs(['page' => $page]);
$filtered = $q !== '' || $genre !== '' || $status !== '' || $priority !== '' || $platform !== '' || $fav || $yearFrom !== '' || $yearTo !== '' || $minRating !== '' || $runtime !== '';

$chips = [];
if ($q !== '')        $chips[] = ['Search: ' . $q, ['q']];
if ($genre !== '')    $chips[] = ['Genre: ' . $genre, ['genre']];
if ($status !== '')   $chips[] = ['Status: ' . $status, ['status']];
if ($priority !== '') $chips[] = ['Priority: ' . $priority, ['priority']];
if ($platform !== '') $chips[] = ['On ' . $platform, ['platform']];
if ($fav)             $chips[] = ['Favorites', ['fav']];
if ($yearFrom !== '' || $yearTo !== '')
    $chips[] = [$yearFrom !== '' && $yearTo !== '' ? "Years $yearFrom-$yearTo" : ($yearFrom !== '' ? "From $yearFrom" : "Up to $yearTo"), ['year_from', 'year_to']];
if ($minRating !== '') $chips[] = ["Rated $minRating+", ['min_rating']];
if ($runtime !== '')   $chips[] = [$runtimes[$runtime][0], ['runtime']];

$ctx = $status === 'Watched' ? 'watched' : ($fav && $status === '' ? 'favorites' : 'default');
$heading = ['default' => ['Collection', 'Your movies'], 'watched' => ['Watched', 'Movies you already watched'], 'favorites' => ['Favorites', 'Your favorite movies']][$ctx];
if ($ctx === 'watched') { $binge = binge_candidates($conn, $uid); $extra_js = ['assets/js/collections.js']; }
$page_title = $heading[1];
$active = $ctx === 'favorites' ? 'favorites' : ($ctx === 'watched' ? 'watched' : 'movies');
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <span class="eyebrow"><?= e($heading[0]) ?></span>
        <h1><?= e($heading[1]) ?></h1>
        <p class="muted"><?= $total ?> <?= $total === 1 ? 'movie' : 'movies' ?><?= $filtered ? ' match your filters' : ' in your collection' ?></p>
    </div>
    <?php if ($ctx === 'watched'): ?>
        <button class="btn btn-primary" type="button" data-modal-open="#binge-dialog"><?= icon('repeat', 16) ?> Binge Watch</button>
    <?php elseif ($ctx === 'default'): ?>
        <a class="btn btn-primary" href="add_movie.php"><?= icon('plus', 16) ?> Add movie</a>
    <?php endif; ?>
</div>

<form class="card filters" method="get" data-autosubmit>
    <input type="hidden" name="view" value="<?= e($view) ?>">
    <div class="f-row f-search">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search title, director, or cast" aria-label="Search movies" data-live-search>
        <button class="btn btn-primary btn-sm" type="submit"><?= icon('search', 15) ?> Search</button>
    </div>
    <div class="f-row f-selects">
        <select name="genre" aria-label="Genre"><option value="">All genres</option>
            <?php foreach ($genres as $g): ?><option <?= $g === $genre ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?></select>
        <select name="status" aria-label="Status"><option value="">Any status</option>
            <?php foreach (WATCH_STATUSES as $s): ?><option <?= $s === $status ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
        <select name="priority" aria-label="Priority"><option value="">Any priority</option>
            <?php foreach (PRIORITIES as $p): ?><option <?= $p === $priority ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?></select>
        <select name="platform" aria-label="Streaming platform"><option value="">Any platform</option>
            <?php foreach ($platforms as $p): ?><option <?= $p === $platform ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?></select>
        <select name="min_rating" aria-label="Minimum rating"><option value="">Any rating</option>
            <?php foreach ([5, 6, 7, 8, 9] as $r): ?><option value="<?= $r ?>" <?= (string)$r === $minRating ? 'selected' : '' ?>><?= $r ?>+ stars</option><?php endforeach; ?></select>
        <select name="runtime" aria-label="Runtime"><option value="">Any length</option>
            <?php foreach ($runtimes as $k => $r): ?><option value="<?= $k ?>" <?= $k === $runtime ? 'selected' : '' ?>><?= e($r[0]) ?></option><?php endforeach; ?></select>
    </div>
    <div class="f-row f-more">
        <select name="sort" aria-label="Sort by">
            <?php foreach ($sorts as $k => $s): ?><option value="<?= $k ?>" <?= $k === $sort ? 'selected' : '' ?>><?= e($s[0]) ?></option><?php endforeach; ?></select>
        <input type="number" class="year" name="year_from" value="<?= e((string)$yearFrom) ?>" placeholder="From year" min="1888" max="2100" aria-label="From year">
        <input type="number" class="year" name="year_to" value="<?= e((string)$yearTo) ?>" placeholder="To year" min="1888" max="2100" aria-label="To year">
        <label class="check-inline"><input type="checkbox" name="fav" value="1" <?= $fav ? 'checked' : '' ?>> Favorites</label>
        <div class="f-right">
            <?php if ($filtered): ?><a class="btn btn-sm" href="movies.php?view=<?= e($view) ?>">Clear filters</a><?php endif; ?>
            <div class="view-toggle">
                <a class="btn btn-sm <?= $view === 'grid' ? 'btn-primary' : '' ?>" href="?<?= e($qs(['view' => 'grid', 'page' => ''])) ?>">Grid</a>
                <a class="btn btn-sm <?= $view === 'list' ? 'btn-primary' : '' ?>" href="?<?= e($qs(['view' => 'list', 'page' => ''])) ?>">List</a>
            </div>
        </div>
    </div>
</form>

<form method="post" action="bulk_action.php" id="bulk-form" hidden>
    <?= csrf_field() ?>
    <input type="hidden" name="back" value="<?= e($back) ?>">
    <div class="bulk-bar">
        <span class="muted"><strong data-bulk-count>0</strong> selected</span>
        <div class="bulk-actions">
            <button class="btn btn-sm" type="submit" name="action" value="watched"><?= icon('check', 15) ?> Watched</button>
            <button class="btn btn-sm" type="submit" name="action" value="to-watch"><?= icon('bookmark', 15) ?> To Watch</button>
            <button class="btn btn-sm" type="submit" name="action" value="favorite"><?= icon('heart', 15) ?> Favorite</button>
            <button class="btn btn-sm btn-danger" type="submit" name="action" value="delete" onclick="return confirm('Delete selected? Cannot be undone.')"><?= icon('trash', 15) ?> Delete</button>
            <button class="btn btn-sm" type="button" data-bulk-cancel><?= icon('x', 15) ?> Cancel</button>
        </div>
    </div>
</form>

<?php if ($chips): ?>
<div class="chips" aria-label="Active filters">
    <?php foreach ($chips as $chip): ?>
        <a class="chip" href="?<?= e($qs(array_fill_keys($chip[1], '') + ['page' => ''])) ?>" title="Remove this filter"><?= e($chip[0]) ?> <span aria-hidden="true">&times;</span></a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!$movies): ?>
    <div class="card empty empty-rich">
        <span class="empty-icon"><?= icon('film', 48) ?></span>
        <h2>No movies found</h2>
        <p><?= $filtered ? 'Try a different search or clear the filters.' : 'Add your first movie to get started.' ?></p>
        <a class="btn btn-primary" href="<?= $filtered ? 'movies.php' : 'add_movie.php' ?>">
            <?= $filtered ? 'Clear filters' : icon('plus', 16) . ' Add your first movie' ?>
        </a>
    </div>
<?php else: ?>
    <div class="grid grid-movies <?= $view === 'list' ? 'is-list' : '' ?>">
        <?php foreach ($movies as $m): $mid = (int)$m['movie_id']; ?>
        <label class="mcard-select">
            <input type="checkbox" name="movie_ids[]" value="<?= $mid ?>" data-bulk-item>
            <?= movie_card($m, $back, true) ?>
        </label>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Pages">
        <?php if ($page > 1): ?>
            <a class="btn btn-sm" href="?<?= e($qs(['page' => $page - 1])) ?>" aria-label="Previous page"><?= icon('chevron-left', 14) ?></a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1) {
            echo '<a class="btn btn-sm" href="?' . e($qs(['page' => 1])) . '">1</a>';
            if ($start > 2) echo '<span class="muted">…</span>';
        }
        for ($p = $start; $p <= $end; $p++):
        ?>
            <a class="btn btn-sm <?= $p === $page ? 'btn-primary' : '' ?>" href="?<?= e($qs(['page' => $p])) ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>><?= $p ?></a>
        <?php
        endfor;
        if ($end < $pages) {
            if ($end < $pages - 1) echo '<span class="muted">…</span>';
            echo '<a class="btn btn-sm" href="?' . e($qs(['page' => $pages])) . '">' . $pages . '</a>';
        }
        ?>

        <?php if ($page < $pages): ?>
            <a class="btn btn-sm" href="?<?= e($qs(['page' => $page + 1])) ?>" aria-label="Next page"><?= icon('chevron', 14) ?></a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
<?php endif; ?>

<script>
(() => {
    const form = document.querySelector('form[data-autosubmit]');
    if (!form) return;
    const search = form.querySelector('[data-live-search]');
    let timer;
    form.addEventListener('change', e => { if (e.target !== search) form.submit(); });
    search.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => { sessionStorage.setItem('refocus', '1'); form.submit(); }, 450);
    });
    if (sessionStorage.getItem('refocus')) {
        sessionStorage.removeItem('refocus');
        search.focus();
        search.setSelectionRange(search.value.length, search.value.length);
    }
})();
</script>

<?php if ($ctx === 'watched' && !empty($binge)): ?>
<dialog id="binge-dialog" class="dlg-wide">
    <div class="dlg-head">
        <h2>What movie to rewatch?</h2>
        <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon('x', 16) ?></button>
    </div>
    <p class="muted">Type a title, or tap one from the lists below. Undecided? Let CineTrack choose for you.</p>
    <form method="get" action="binge.php" class="binge-form">
        <label class="muted small" for="binge-title">Your answer</label>
        <input id="binge-title" name="title" list="watched-titles" placeholder="Type a movie title..." autocomplete="off" required>
        <datalist id="watched-titles"><?php foreach ($binge as $b) echo '<option value="' . e($b['title']) . '">'; ?></datalist>
        <div class="dialog-actions">
            <button class="btn btn-primary" type="submit"><?= icon('repeat', 16) ?> Rewatch this movie</button>
            <button class="btn" type="submit" name="random" value="1" formnovalidate><?= icon('dice', 16) ?> I'm undecided, pick for me</button>
        </div>
    </form>
    <h3 class="dlg-sub">Recommended for a rewatch</h3>
    <div class="reco">
        <?php foreach (array_slice($binge, 0, 5) as $b): ?>
        <button type="button" class="reco-item" data-fill="<?= e($b['title']) ?>">
            <strong><?= e($b['title']) ?></strong>
            <span class="tags"><?php foreach ($b['why'] as $w) echo '<span class="tag">' . e($w) . '</span>'; ?></span>
        </button>
        <?php endforeach; ?>
    </div>
    <h3 class="dlg-sub">Your watched movies (<?= count($binge) ?>)</h3>
    <div class="rewatch-list">
        <?php foreach ($binge as $b): ?>
        <button type="button" class="rw-item" data-fill="<?= e($b['title']) ?>">
            <span><?= e($b['title']) ?> <span class="muted">(<?= (int)$b['release_year'] ?>)</span></span>
            <span class="muted small">watched &times;<?= (int)$b['watch_count'] ?><?= $b['user_rating'] !== null ? ' &middot; ' . number_format((float)$b['user_rating'], 1) : '' ?></span>
        </button>
        <?php endforeach; ?>
    </div>
</dialog>
<?php endif; ?>

<script>
(() => {
    const form = document.getElementById('bulk-form');
    const items = document.querySelectorAll('[data-bulk-item]');
    const count = document.querySelector('[data-bulk-count]');
    const cancel = document.querySelector('[data-bulk-cancel]');
    if (!form || !items.length) return;

    const update = () => {
        const n = document.querySelectorAll('[data-bulk-item]:checked').length;
        count.textContent = n;
        form.hidden = n === 0;
    };

    items.forEach(cb => cb.addEventListener('change', () => {
        cb.closest('.mcard-select').classList.toggle('is-selected', cb.checked);
        update();
    }));

    cancel?.addEventListener('click', () => {
        items.forEach(cb => { cb.checked = false; cb.closest('.mcard-select').classList.remove('is-selected'); });
        update();
    });

    form.addEventListener('submit', () => {
        form.querySelectorAll('input[name="movie_ids[]"]').forEach(el => el.remove());
        document.querySelectorAll('[data-bulk-item]:checked').forEach(cb => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'movie_ids[]'; inp.value = cb.value;
            form.appendChild(inp);
        });
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>