<?php
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
     WHERE movie_id = ? AND user_id = ?'
);

$stmt->bind_param('ii', $id, $uid);
$stmt->execute();

$m = $stmt->get_result()->fetch_assoc();

if (!$m) {
    flash('Movie not found in your list.', 'error');
    redirect('movies.php');
}

$mid = (int)$m['movie_id'];
$self = 'view_movie.php?id=' . $mid;

// Get the user's tracking information from the same movie record.
$t = [
    'watch_status' => (string)$m['watch_status'],
    'priority' => (string)$m['priority'],
    'watch_count' => (string)(int)$m['watch_count'],
    'user_rating' => $m['user_rating'] === null
        ? ''
        : (string)(float)$m['user_rating'],
    'review' => (string)($m['review'] ?? ''),
    'favorite' => (int)$m['favorite'],
];

$na = fn($v) =>
    ($v === null || $v === '') ? 'Not specified' : e((string)$v);

$page_title = $m['title'];
$active = 'movies';

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <?php if (!empty($m['poster_url'])): ?>
        <div
            class="hero-bg"
            style="background-image:url('<?= e($m['poster_url']) ?>')"
        ></div>
    <?php endif; ?>

    <div class="hero-body">
        <?= poster($m['poster_url'], $m['title'], 'hero-poster') ?>

        <div>
            <span class="badge"><?= $na($m['genre']) ?></span>

            <h1><?= e($m['title']) ?></h1>

            <p class="muted">
                <?= $na($m['release_year']) ?>
                &middot;
                <?= !empty($m['duration_minutes'])
                    ? e(format_duration((int)$m['duration_minutes']))
                    : 'Runtime not specified' ?>
                &middot;
                <?= $na($m['country']) ?>
                &middot;
                <?= $na($m['age_rating']) ?>
            </p>

            <p><?= $na($m['short_description']) ?></p>

            <div class="hero-actions">
                <?php if (!empty($m['watch_url'])): ?>
                    <a
                        class="btn btn-primary"
                        href="<?= e($m['watch_url']) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <?= icon('play', 16) ?> Watch now
                    </a>
                <?php endif; ?>

                <a class="btn" href="movies.php">
                    <?= icon('film', 16) ?> Back to my movies
                </a>
            </div>
        </div>
    </div>
</section>

<div class="detail-grid">
    <section class="card">
        <h2>Movie information</h2>

        <dl class="info">
            <div>
                <dt>Director</dt>
                <dd><?= $na($m['director']) ?></dd>
            </div>

            <div>
                <dt>Main cast</dt>
                <dd><?= $na($m['cast']) ?></dd>
            </div>

            <div>
                <dt>Release year</dt>
                <dd><?= $na($m['release_year']) ?></dd>
            </div>

            <div>
                <dt>Runtime</dt>
                <dd>
                    <?= !empty($m['duration_minutes'])
                        ? e(format_duration((int)$m['duration_minutes']))
                        : 'Not specified' ?>
                </dd>
            </div>

            <div>
                <dt>Language</dt>
                <dd><?= $na($m['language']) ?></dd>
            </div>

            <div>
                <dt>Country</dt>
                <dd><?= $na($m['country']) ?></dd>
            </div>

            <div>
                <dt>Age rating</dt>
                <dd><?= $na($m['age_rating']) ?></dd>
            </div>

            <div>
                <dt>Streaming on</dt>
                <dd><?= $na($m['streaming_platform']) ?></dd>
            </div>

            <div>
                <dt>Watch URL</dt>
                <dd>
                    <?php if (!empty($m['watch_url'])): ?>
                        <a
                            href="<?= e($m['watch_url']) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >Open link</a>
                    <?php else: ?>
                        Not specified
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </section>

    <section class="card">
        <h2>My list</h2>

        <p class="muted small">
            Added on <?= e(format_date($m['date_added'])) ?>.
            Only you can see and change your tracking details.
        </p>

        <?php render_track_form($t, $mid, $self); ?>

        <form
            method="post"
            action="list_action.php"
            style="margin-top:.8rem"
            onsubmit="return confirm('Remove this movie from your list? Your rating and review for it will be lost.')"
        >
            <?= csrf_field() ?>

            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="movie_id" value="<?= $mid ?>">
            <input type="hidden" name="back" value="movies.php">

            <button
                class="btn btn-sm btn-ghost-danger"
                type="submit"
            >
                <?= icon('x', 14) ?> Remove from my list
            </button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>