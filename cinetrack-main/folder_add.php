
<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/collections.php';
require_once __DIR__ . '/includes/picker.php';

require_login();

$uid = current_user_id();

ensure_collections_schema($conn);

// Validate the folder ID.
$fidValue = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $fidValue === false ||
    $fidValue === null ||
    $fidValue < 1
) {
    flash('Invalid folder.', 'error');
    redirect('watchlist.php');
}

$fid = (int) $fidValue;

// Confirm that the folder belongs to the logged-in user.
$s = $conn->prepare("
    SELECT *
    FROM collections
    WHERE collection_id = ?
      AND user_id = ?
");

$s->bind_param('ii', $fid, $uid);
$s->execute();

$f = $s->get_result()->fetch_assoc();

if (!$f) {
    flash('That folder no longer exists.', 'error');
    redirect('watchlist.php');
}

// Handle submitted movies.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check the CSRF token before making changes.
    if (!csrf_valid()) {
        flash('Your session expired. Please try again.', 'error');
        redirect('folder_add.php?id=' . $fid);
    }

    // Make sure movie_ids was submitted as an array.
    $rawIds = $_POST['movie_ids'] ?? [];

    if (!is_array($rawIds)) {
        flash('Invalid movie selection.', 'error');
        redirect('folder_add.php?id=' . $fid);
    }

    // Validate each movie ID and remove duplicates.
    $ids = [];

    foreach ($rawIds as $rawId) {
        $mid = filter_var($rawId, FILTER_VALIDATE_INT);

        if (
            $mid !== false &&
            $mid !== null &&
            $mid > 0
        ) {
            $ids[] = (int) $mid;
        }
    }

    $ids = array_values(array_unique($ids));

    if (empty($ids)) {
        flash('Tick at least one valid movie to add.', 'error');
        redirect('folder_add.php?id=' . $fid);
    }

    // Add only movies owned by this user.
    // Include user_id to satisfy the database constraints.
    // INSERT IGNORE prevents duplicate folder-movie links.
    $ins = $conn->prepare("
        INSERT IGNORE INTO collection_movies (
            collection_id,
            movie_id,
            user_id
        )
        SELECT ?, movie_id, user_id
        FROM movies
        WHERE movie_id = ?
          AND user_id = ?
    ");

    $added = 0;

    foreach ($ids as $mid) {
        $ins->bind_param('iii', $fid, $mid, $uid);
        $ins->execute();

        $added += max(0, $ins->affected_rows);
    }

    if ($added > 0) {
        flash(
            'Added ' . $added . ' ' .
            ($added === 1 ? 'movie' : 'movies') .
            ' to ' . $f['name'] . '.'
        );
    } else {
        flash(
            'No movies were added. They may already be in this folder, or they may not belong to your collection.',
            'error'
        );
    }

    redirect('folder.php?id=' . $fid);
}

// Get only this user's movies for the picker.
$ms = $conn->prepare("
    SELECT *
    FROM movies
    WHERE user_id = ?
    ORDER BY title
");

$ms->bind_param('i', $uid);
$ms->execute();

$movies = $ms->get_result()->fetch_all(MYSQLI_ASSOC);

// Get the movie IDs already in this folder.
// Check folder ownership and movie ownership as well.
$s = $conn->prepare("
    SELECT cm.movie_id
    FROM collection_movies cm
    INNER JOIN collections c
        ON c.collection_id = cm.collection_id
    INNER JOIN movies m
        ON m.movie_id = cm.movie_id
    WHERE cm.collection_id = ?
      AND c.user_id = ?
      AND m.user_id = ?
");

$s->bind_param('iii', $fid, $uid, $uid);
$s->execute();

$inFolder = array_map(
    'intval',
    array_column(
        $s->get_result()->fetch_all(MYSQLI_NUM),
        0
    )
);

// Page information.
$page_title = 'Add movies to ' . $f['name'];
$active = 'watchlist';
$extra_js = ['assets/js/collections.js'];

require __DIR__ . '/includes/header.php';
?>

<div
    class="page-head folder-head"
    style="--fc:<?= e(folder_color($f['color'] ?? 'violet')) ?>"
>
    <div class="folder-head-main">

        <span class="folder-tile big">
            <?= icon($f['icon'] ?? 'folder', 30) ?>
        </span>

        <div>
            <span class="eyebrow">Add movies</span>

            <h1>
                Add Movies to <?= e($f['name']) ?>
            </h1>

            <p class="muted">
                Search, tick the movies you want, then add them all at once.
            </p>
        </div>

    </div>

    <a
        class="btn"
        href="folder.php?id=<?= $fid ?>"
    >
        <?= icon('chevron-left', 16) ?>
        Back to folder
    </a>
</div>

<?php render_movie_picker($movies, $inFolder, $f['name']); ?>

<?php require __DIR__ . '/includes/footer.php'; ?>