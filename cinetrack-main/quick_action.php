<?php
// Handles one-click actions: favorite, watched +1, and rating.
// POST only, CSRF-protected, and restricted to the movie owner.

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$uid = current_user_id();

// Allow only trusted pages as redirect destinations.
$back = (string)($_POST['back'] ?? '');

if (!preg_match(
    '/^(movies|index|watchlist|folder)\.php(\?[A-Za-z0-9_=&%.+\-]*)?$/',
    $back
)) {
    $back = 'movies.php';
}

// Read and validate the submitted movie ID and action.
$id = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT
);

$act = (string)($_POST['action'] ?? '');

// Only accept POST requests with a valid movie ID.
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !$id ||
    $id < 1
) {
    redirect('movies.php');
}

// Check the CSRF token.
if (!csrf_valid()) {
    flash('Your session expired. Please try again.', 'error');
    redirect($back);
}

// Make sure this movie belongs to the logged-in user.
$check = $conn->prepare(
    'SELECT movie_id
     FROM movies
     WHERE movie_id = ? AND user_id = ?'
);

$check->bind_param('ii', $id, $uid);
$check->execute();

$result = $check->get_result();

if (!$result || $result->num_rows === 0) {
    $check->close();

    flash('Movie not found in your list.', 'error');
    redirect($back);
}

$check->close();

try {
    if ($act === 'favorite') {
        // Toggle favorite on or off.
        $stmt = $conn->prepare(
            'UPDATE movies
             SET favorite = 1 - favorite
             WHERE movie_id = ? AND user_id = ?'
        );

        $stmt->bind_param('ii', $id, $uid);
        $stmt->execute();
        $stmt->close();

        flash('Favorite updated.');

    } elseif ($act === 'watched') {
        // Mark the movie as watched and add one to the watch count.
        // Prevent the count from exceeding 65535.
        $stmt = $conn->prepare(
            "UPDATE movies
             SET watch_status = 'Watched',
                 watch_count = LEAST(watch_count + 1, 65535)
             WHERE movie_id = ? AND user_id = ?"
        );

        $stmt->bind_param('ii', $id, $uid);
        $stmt->execute();
        $stmt->close();

        flash('Logged as watched.');

    } elseif ($act === 'rate') {
        // Save or clear the movie rating.
        $value = (string)($_POST['value'] ?? '');

        if ($value === 'none') {
            // Clear the rating.
            $stmt = $conn->prepare(
                'UPDATE movies
                 SET user_rating = NULL
                 WHERE movie_id = ? AND user_id = ?'
            );

            $stmt->bind_param('ii', $id, $uid);
            $stmt->execute();
            $stmt->close();

            flash('Rating cleared.');

        } elseif (
            ctype_digit($value) &&
            (int)$value >= 1 &&
            (int)$value <= 10
        ) {
            // Save the selected rating.
            $rating = (float)$value;

            $stmt = $conn->prepare(
                'UPDATE movies
                 SET user_rating = ?
                 WHERE movie_id = ? AND user_id = ?'
            );

            $stmt->bind_param(
                'dii',
                $rating,
                $id,
                $uid
            );

            $stmt->execute();
            $stmt->close();

            flash('Rating saved.');

        } else {
            flash(
                'Please select a valid rating from 1 to 10.',
                'error'
            );
        }

    } else {
        flash('Invalid action.', 'error');
    }

} catch (mysqli_sql_exception $e) {
    // Log technical details on the server.
    error_log(
        'CineTrack quick action failed: ' .
        $e->getMessage()
    );

    flash(
        'The action could not be completed. Please try again.',
        'error'
    );
}

redirect($back);
