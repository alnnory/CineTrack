<?php
// Adds a movie to the logged-in user's personal list.

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/movie_form.php';

require_login();

$uid = current_user_id();
$m = movie_defaults();
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
        $columns = array_map(
            fn($column) => "`$column`",
            MOVIE_COLS
        );

        $sql = 'INSERT INTO movies (user_id, ' .
            implode(', ', $columns) .
            ') VALUES (' .
            implode(
                ', ',
                array_fill(0, count(MOVIE_COLS) + 1, '?')
            ) .
            ')';

        try {
            $stmt = $conn->prepare($sql);

            $params = movie_params($m);
            array_unshift($params, $uid);

            $types = 'i' . MOVIE_TYPES;

            $stmt->bind_param($types, ...$params);
            $stmt->execute();

            $movieId = $conn->insert_id;

            $stmt->close();

            flash('Movie added successfully.');
            redirect('view_movie.php?id=' . $movieId);

        } catch (mysqli_sql_exception $e) {
            error_log('Add movie error: ' . $e->getMessage());

            $errors['title'] =
                'Unable to add this movie right now. Please check your information and try again.';
        }
    }
}

$page_title = 'Add movie';
$active = 'catalog';

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <span class="eyebrow">My movies</span>
        <h1>Add a movie</h1>

        <p class="muted">
            Add a movie to your personal collection.
            Fields marked * are required.
        </p>
    </div>

    <a class="btn" href="index.php">
        <?= icon('film', 16) ?> Back
    </a>
</div>

<?php render_movie_form($m, $errors, 'Add movie', 'index.php'); ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

