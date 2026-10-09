<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('movies.php');
}

if (!csrf_valid()) {
    flash('Your session expired. Please try again.', 'error');
    redirect('movies.php');
}

$uid = current_user_id();

$ids = array_values(array_unique(array_filter(
    array_map('intval', (array)($_POST['movie_ids'] ?? [])),
    fn($id) => $id > 0
)));

$act = (string)($_POST['action'] ?? '');
$back = (string)($_POST['back'] ?? 'movies.php');

// Only allow returning to movies.php.
if (!preg_match('/^movies\.php(?:\?[A-Za-z0-9_=&%.+\-]*)?$/', $back)) {
    $back = 'movies.php';
}

if (!$ids) {
    flash('Nothing selected.', 'error');
    redirect($back);
}

$in = implode(',', array_fill(0, count($ids), '?'));
$types = 'i' . str_repeat('i', count($ids));
$params = array_merge([$uid], $ids);

if ($act === 'watched') {

    $sql = "UPDATE movies
            SET watch_status = 'Watched',
                watch_count = watch_count + 1
            WHERE user_id = ? AND movie_id IN ($in)";

    $message = 'movie(s) marked as watched.';

} elseif ($act === 'to-watch') {

    $sql = "UPDATE movies
            SET watch_status = 'To Watch'
            WHERE user_id = ? AND movie_id IN ($in)";

    $message = 'movie(s) set to To Watch.';

} elseif ($act === 'favorite') {

    $sql = "UPDATE movies
            SET favorite = 1
            WHERE user_id = ? AND movie_id IN ($in)";

    $message = 'movie(s) added to favorites.';

} elseif ($act === 'delete') {

    // Remove selected movies from the user's collections first.
    $deleteCollections = $conn->prepare(
        "DELETE cm
         FROM collection_movies cm
         JOIN collections c
             ON c.collection_id = cm.collection_id
         WHERE c.user_id = ?
           AND cm.movie_id IN ($in)"
    );

    $deleteCollections->bind_param($types, ...$params);
    $deleteCollections->execute();

    $sql = "DELETE FROM movies
            WHERE user_id = ? AND movie_id IN ($in)";

    $message = 'movie(s) deleted.';

} else {
    flash('Invalid action.', 'error');
    redirect($back);
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

flash($stmt->affected_rows . ' ' . $message);

redirect($back);