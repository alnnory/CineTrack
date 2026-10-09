<?php
// Returns up to 5 random movies as an HTML fragment.
// scope=watchlist or scope=folder:ID

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/collections.php';

if (!is_logged_in()) {
    http_response_code(401);
    exit('Please sign in.');
}

$uid = current_user_id();

ensure_collections_schema($conn);

const SHUFFLE_COUNT = 5;

$scope = (string)($_GET['scope'] ?? 'watchlist');

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

// Choose the correct query based on the requested scope.
if (preg_match('/^folder:([1-9][0-9]*)$/', $scope, $mm)) {
    $fid = (int)$mm[1];

    // Only shuffle movies from a folder owned by this user.
    $s = $conn->prepare(
        "SELECT m.*
         FROM movies m
         INNER JOIN collection_movies cm
             ON cm.movie_id = m.movie_id
         INNER JOIN collections c
             ON c.collection_id = cm.collection_id
         WHERE c.collection_id = ?
           AND c.user_id = ?
           AND m.user_id = ?
         ORDER BY RAND()
         LIMIT " . SHUFFLE_COUNT
    );

    $s->bind_param('iii', $fid, $uid, $uid);

    // Count only movies owned by this user in this folder.
    $c = $conn->prepare(
        "SELECT COUNT(*)
         FROM collection_movies cm
         INNER JOIN collections c
             ON c.collection_id = cm.collection_id
         INNER JOIN movies m
             ON m.movie_id = cm.movie_id
         WHERE c.collection_id = ?
           AND c.user_id = ?
           AND m.user_id = ?"
    );

    $c->bind_param('iii', $fid, $uid, $uid);

    $label = 'this folder';

} else {
    // Default scope: the user's To Watch list.
    $s = $conn->prepare(
        "SELECT *
         FROM movies
         WHERE user_id = ?
           AND watch_status = 'To Watch'
         ORDER BY RAND()
         LIMIT " . SHUFFLE_COUNT
    );

    $s->bind_param('i', $uid);

    $c = $conn->prepare(
        "SELECT COUNT(*)
         FROM movies
         WHERE user_id = ?
           AND watch_status = 'To Watch'"
    );

    $c->bind_param('i', $uid);

    $label = 'your watchlist';
}

// Get the random movies.
$s->execute();
$picks = $s->get_result()->fetch_all(MYSQLI_ASSOC);
$s->close();

// Count all eligible movies for this scope.
$c->execute();
$total = (int)$c->get_result()->fetch_row()[0];
$c->close();

// Display a message if there are no movies.
if (empty($picks)) {
    echo '<p class="muted shuffle-note">'
       . 'There is nothing to shuffle yet. Add some movies first.'
       . '</p>';
    exit;
}

// Display the selected movies.
foreach ($picks as $m):
?>
    <a class="shuffle-card"
       href="view_movie.php?id=<?= (int)$m['movie_id'] ?>">

        <?= poster(
            (string)($m['poster_url'] ?? ''),
            (string)($m['title'] ?? '')
        ) ?>

        <strong><?= e($m['title'] ?? '') ?></strong>

        <span class="muted small">
            <?= (int)($m['release_year'] ?? 0) ?>
            &middot;
            <?= e($m['genre'] ?? '') ?>
            &middot;
            <?= e(format_duration((int)($m['duration_minutes'] ?? 0))) ?>
        </span>
    </a>
<?php
endforeach;

// Explain when fewer than five movies are available.
if ($total < SHUFFLE_COUNT):
?>
    <p class="muted shuffle-note">
        Only <?= $total ?>
        <?= $total === 1 ? 'movie is' : 'movies are' ?>
        in <?= e($label) ?>, so that is everything you have.
    </p>
<?php endif; ?>