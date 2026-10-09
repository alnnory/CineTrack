<?php
// Allows a logged-in user to edit their own movie.

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/movie_form.php';

require_login();

$uid = current_user_id();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    redirect('movies.php');
}

// Get the movie only if it belongs to the logged-in user.
$stmt = $conn->prepare(
    'SELECT *
     FROM movies
     WHERE movie_id = ? AND user_id = ?
     LIMIT 1'
);

$stmt->bind_param('ii', $id, $uid);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    flash('Movie not found or you do not have permission to edit it.', 'error');
    redirect('movies.php');
}

// Prepare the movie information for the form.
$m = array_intersect_key($row, array_flip(MOVIE_COLS));

foreach ($m as $key => $value) {
    if ($value === null) {
        $m[$key] = '';
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $m = movie_from_post();

    if (!csrf_valid()) {
        $errors = [
            'title' => 'Your session expired. Please submit again.'
        ];
    } else {
        $errors = movie_validate($m);
    }

    if (empty($errors)) {
        $sql = 'UPDATE movies SET ' .
            implode(
                ', ',
                array_map(
                    fn($column) => "`$column` = ?",
                    MOVIE_COLS
                )
            ) .
            ' WHERE movie_id = ? AND user_id = ?';

        try {
            $stmt = $conn->prepare($sql);

            $params = movie_params($m);
            $params[] = $id;
            $params[] = $uid;

            $types = MOVIE_TYPES . 'ii';

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();

            flash('Movie updated successfully.');
            redirect('view_movie.php?id=' . $id);

        } catch (mysqli_sql_exception $e) {
            error_log('Edit movie error: ' . $e->getMessage());

            $errors['title'] =
                'Unable to save your changes right now. Please try again.';
        }
    }
}

$page_title = 'Edit ' . $row['title'];
$active = 'movies';

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <span class="eyebrow">My movies</span>
        <h1>Edit movie</h1>
        <p class="muted"><?= e($row['title']) ?></p>
    </div>

    <a class="btn" href="view_movie.php?id=<?= (int)$id ?>">
        <?= icon('chevron-left', 16) ?> Back to details
    </a>
</div>

<?php
render_movie_form(
    $m,
    $errors,
    'Save changes',
    'view_movie.php?id=' . $id
);
?>

<?php require __DIR__ . '/includes/footer.php'; ?>
