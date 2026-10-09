<?php
// POST-only: deletes a movie from the logged-in user's personal collection.

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$uid = current_user_id();

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('movies.php');
}

// Validate the movie ID.
$id = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

if (!$id || $id < 1) {
    flash('Invalid movie ID.', 'error');
    redirect('movies.php');
}

// Verify the request's CSRF token.
if (!csrf_valid()) {
    flash('Your session expired. Please try again.', 'error');
    redirect('view_movie.php?id=' . $id);
}

try {
    $conn->begin_transaction();

    // Confirm the movie belongs to the logged-in user.
    $check = $conn->prepare(
        'SELECT movie_id
         FROM movies
         WHERE movie_id = ? AND user_id = ?
         FOR UPDATE'
    );

    $check->bind_param('ii', $id, $uid);
    $check->execute();

    $movie = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$movie) {
        $conn->rollback();

        flash('Movie not found or you do not have permission to delete it.', 'error');
        redirect('movies.php');
    }

    // Remove this movie from any custom folders owned by this user.
    $remove = $conn->prepare(
        'DELETE cm
         FROM collection_movies cm
         INNER JOIN collections c
             ON c.collection_id = cm.collection_id
         WHERE cm.movie_id = ?
           AND c.user_id = ?'
    );

    $remove->bind_param('ii', $id, $uid);
    $remove->execute();
    $remove->close();

    // Delete the movie itself, checking ownership again.
    $delete = $conn->prepare(
        'DELETE FROM movies
         WHERE movie_id = ? AND user_id = ?'
    );

    $delete->bind_param('ii', $id, $uid);
    $delete->execute();

    $deleted = $delete->affected_rows;
    $delete->close();

    $conn->commit();

    if ($deleted > 0) {
        flash('Movie deleted successfully.');
    } else {
        flash('That movie was already removed.', 'error');
    }

    redirect('movies.php');

} catch (mysqli_sql_exception $e) {
    $conn->rollback();

    error_log('Delete movie error: ' . $e->getMessage());

    flash('Unable to delete the movie right now. Please try again.', 'error');
    redirect('view_movie.php?id=' . $id);
}
