<?php
// POST only: delete a folder or remove a movie from a folder.
// Movies themselves are never deleted here.

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/collections.php';

require_login();

$uid = current_user_id();

ensure_collections_schema($conn);

// Accept POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('watchlist.php');
}

// Validate the folder ID.
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$act = (string) ($_POST['action'] ?? '');

if ($id === false || $id === null || $id < 1) {
    flash('Invalid folder.', 'error');
    redirect('watchlist.php');
}

// Validate the CSRF token.
if (!csrf_valid()) {
    flash('Your session expired. Please try again.', 'error');
    redirect('folder.php?id=' . $id);
}

// Delete a custom folder.
if ($act === 'delete_folder') {

    $s = $conn->prepare("
        DELETE FROM collections
        WHERE collection_id = ?
          AND user_id = ?
    ");

    $s->bind_param('ii', $id, $uid);
    $s->execute();

    if ($s->affected_rows > 0) {
        flash('Folder deleted.');
    } else {
        flash('That folder was already removed or does not exist.', 'error');
    }

    redirect('watchlist.php');
}

// Remove a movie from a custom folder.
if ($act === 'remove') {

    // Validate the movie ID.
    $mid = filter_var(
        $_POST['movie_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($mid === false || $mid === null || $mid < 1) {
        flash('Invalid movie.', 'error');
        redirect('folder.php?id=' . $id);
    }

    // Confirm that the folder belongs to this user.
    $s = $conn->prepare("
        SELECT collection_id
        FROM collections
        WHERE collection_id = ?
          AND user_id = ?
    ");

    $s->bind_param('ii', $id, $uid);
    $s->execute();

    $folder = $s->get_result()->fetch_assoc();

    if (!$folder) {
        flash('That folder does not exist or you do not have permission to access it.', 'error');
        redirect('watchlist.php');
    }

    // Remove only the link between this folder and this user's movie.
    $s = $conn->prepare("
        DELETE cm
        FROM collection_movies cm
        INNER JOIN movies m
            ON m.movie_id = cm.movie_id
        INNER JOIN collections c
            ON c.collection_id = cm.collection_id
        WHERE cm.collection_id = ?
          AND cm.movie_id = ?
          AND c.user_id = ?
          AND m.user_id = ?
    ");

    $s->bind_param('iiii', $id, $mid, $uid, $uid);
    $s->execute();

    if ($s->affected_rows > 0) {
        flash('Removed from the folder.');
    } else {
        flash('The movie was not found in this folder.', 'error');
    }

    redirect('folder.php?id=' . $id);
}

// Handle unknown actions.
flash('Invalid action.', 'error');
redirect('watchlist.php');
