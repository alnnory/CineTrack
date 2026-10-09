<?php
// Add movies to your own watchlist.

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/picker.php';

require_login();

$uid = current_user_id();

// Add selected movies to the watchlist.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('Your session expired. Please try again.', 'error');
        redirect('watchlist_add.php');
    }

    $submittedIds = $_POST['movie_ids'] ?? [];
    $ids = [];

    if (is_array($submittedIds)) {
        foreach ($submittedIds as $value) {
            $id = filter_var($value, FILTER_VALIDATE_INT);

            if ($id !== false && $id > 0) {
                $ids[] = $id;
            }
        }
    }

    $ids = array_values(array_unique($ids));

    if (empty($ids)) {
        flash('Tick at least one movie to add.', 'error');
        redirect('watchlist_add.php');
    }

    $added = 0;

    foreach ($ids as $movieId) {
        // Only update a movie that belongs to the logged-in user.
        $check = $conn->prepare(
            "SELECT movie_id
             FROM movies
             WHERE movie_id = ? AND user_id = ?
             LIMIT 1"
        );

        $check->bind_param('ii', $movieId, $uid);
        $check->execute();

        $movie = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$movie) {
            continue;
        }

        // Set the movie to To Watch.
        $update = $conn->prepare(
            "UPDATE movies
             SET watch_status = 'To Watch'
             WHERE movie_id = ? AND user_id = ?"
        );

        $update->bind_param('ii', $movieId, $uid);
        $update->execute();

        if ($update->affected_rows > 0) {
            $added++;
        }

        $update->close();
    }

    if ($added > 0) {
        flash(
            "$added " . ($added === 1 ? 'movie was' : 'movies were')
            . ' added to your watchlist.'
        );
    } else {
        flash(
            'No movies were added. The selected movies may already be on your watchlist.',
            'error'
        );
    }

    redirect('watchlist.php');
}

// Display this user's movies that are not currently marked To Watch.
$ms = $conn->prepare(
    "SELECT *
     FROM movies
     WHERE user_id = ?
       AND (watch_status IS NULL OR watch_status <> 'To Watch')
     ORDER BY title"
);

$ms->bind_param('i', $uid);
$ms->execute();

$movies = $ms->get_result()->fetch_all(MYSQLI_ASSOC);
$ms->close();

$page_title = 'Add to Watchlist';
$active = 'watchlist';
$extra_js = ['assets/js/collections.js'];

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <span class="eyebrow">Watchlist</span>
        <h1>Add to Watchlist</h1>
        <p class="muted">
            Select movies from your collection to add them to your watchlist.
        </p>
    </div>

    <div class="head-actions">
        <a class="btn" href="watchlist.php">
            <?= icon('chevron-left', 16) ?> Back
        </a>
    </div>
</div>

<?php if ($movies): ?>

    <?php render_movie_picker($movies, [], 'your watchlist'); ?>

<?php else: ?>

    <div class="card empty">
        <h2>No movies available to add</h2>
        <p>
            All your movies are already marked as To Watch,
            or you have not added any movies yet.
        </p>
        <a class="btn btn-primary" href="movies.php">
            View My Movies
        </a>
    </div>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>