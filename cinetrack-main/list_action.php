<?php
// POST only: remove a movie or save its tracking details.

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/movie_form.php';

require_login();

$uid = current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('movies.php');
}

$id = filter_var($_POST['movie_id'] ?? null, FILTER_VALIDATE_INT);
$act = (string)($_POST['action'] ?? '');
$back = (string)($_POST['back'] ?? 'movies.php');

// Allow only known local pages and optional query strings.
if (!preg_match(
    '/^(catalog|movies|index|watchlist|folder|view_movie)\.php(\?[A-Za-z0-9_=&%.+\-]*)?$/',
    $back
)) {
    $back = 'movies.php';
}

if (!$id || $id < 1) {
    flash('Invalid movie ID.', 'error');
    redirect('movies.php');
}

if (!csrf_valid()) {
    flash('Your session expired. Please try again.', 'error');
    redirect($back);
}

// Confirm that the movie belongs to the logged-in user.
$c = $conn->prepare(
    'SELECT movie_id
     FROM movies
     WHERE movie_id = ? AND user_id = ?'
);
$c->bind_param('ii', $id, $uid);
$c->execute();

if (!$c->get_result()->num_rows) {
    flash('Movie not found in your list.', 'error');
    redirect('movies.php');
}

if ($act === 'remove') {
    try {
        $conn->begin_transaction();

        // Remove links to this movie from the user's collections.
        $s = $conn->prepare(
            'DELETE cm
             FROM collection_movies cm
             JOIN collections c
               ON c.collection_id = cm.collection_id
             WHERE c.user_id = ? AND cm.movie_id = ?'
        );
        $s->bind_param('ii', $uid, $id);
        $s->execute();

        // Delete only the logged-in user's movie.
        $s = $conn->prepare(
            'DELETE FROM movies
             WHERE movie_id = ? AND user_id = ?'
        );
        $s->bind_param('ii', $id, $uid);
        $s->execute();

        $conn->commit();

        flash('Movie removed from your list.');

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();

        flash('Unable to remove the movie. Please try again.', 'error');
    }

} elseif ($act === 'track') {
    // Read and validate the submitted tracking details.
    $t = track_from_post();
    $errs = track_validate($t);

    if ($errs) {
        flash(reset($errs), 'error');
        redirect($back);
    }

    $rating = $t['user_rating'] === ''
        ? null
        : (float)$t['user_rating'];

    $review = $t['review'] === ''
        ? null
        : $t['review'];

    $cnt = (int)$t['watch_count'];
    $fav = (int)$t['favorite'];

    $s = $conn->prepare(
        'UPDATE movies
         SET watch_status = ?,
             priority = ?,
             watch_count = ?,
             user_rating = ?,
             review = ?,
             favorite = ?
         WHERE movie_id = ? AND user_id = ?'
    );

    $s->bind_param(
        'ssidsiii',
        $t['watch_status'],
        $t['priority'],
        $cnt,
        $rating,
        $review,
        $fav,
        $id,
        $uid
    );

    $s->execute();

    flash('Your details were saved.');

} elseif ($act === 'add') {
    // The current database stores personal movie records directly.
    flash('Please use the Add Movie page to add a movie.', 'error');
    redirect('add_movie.php');

} else {
    flash('Invalid action.', 'error');
}

redirect($back);
