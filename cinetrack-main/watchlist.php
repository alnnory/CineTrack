<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/collections.php';

require_login();

$uid = current_user_id();

ensure_collections_schema($conn);

// Get this user's movies marked To Watch.
$s = $conn->prepare("
    SELECT *
    FROM movies
    WHERE user_id = ?
      AND watch_status = 'To Watch'
    ORDER BY FIELD(priority, 'High', 'Medium', 'Low'), title
");

$s->bind_param('i', $uid);
$s->execute();

$toWatch = $s->get_result()->fetch_all(MYSQLI_ASSOC);

// Get this user's custom folders and count their own movies.
$s = $conn->prepare("
    SELECT
        c.*,
        COUNT(m.movie_id) AS movie_count
    FROM collections c
    LEFT JOIN collection_movies cm
        ON cm.collection_id = c.collection_id
    LEFT JOIN movies m
        ON m.movie_id = cm.movie_id
       AND m.user_id = ?
    WHERE c.user_id = ?
    GROUP BY c.collection_id
    ORDER BY c.name
");

$s->bind_param('ii', $uid, $uid);
$s->execute();

$folders = $s->get_result()->fetch_all(MYSQLI_ASSOC);

$back = 'watchlist.php';

// Page information.
$page_title = 'Watchlist';
$active = 'watchlist';
$extra_js = ['assets/js/collections.js'];

require __DIR__ . '/includes/header.php';
?>

<div class="page-head">

    <div>
        <span class="eyebrow">Watchlist</span>

        <h1>Your watchlist</h1>

        <p class="muted">
            <?= count($toWatch) ?>
            to watch &middot;
            <?= count($folders) ?>
            custom
            <?= count($folders) === 1 ? 'folder' : 'folders' ?>
        </p>
    </div>

    <div class="head-actions">

        <?php if ($toWatch): ?>
            <button
                class="btn"
                type="button"
                data-shuffle="watchlist"
            >
                <?= icon('shuffle', 16) ?>
                Shuffle My Watchlist
            </button>
        <?php endif; ?>

        <a class="btn btn-primary" href="watchlist_add.php">
            <?= icon('plus', 16) ?>
            Add to Watchlist
        </a>

    </div>

</div>

<section class="section">

    <div class="sec-title" style="--bar:var(--violet)">

        <div>
            <h2>Folders</h2>
            <p>Organize your watchlist your way</p>
        </div>

        <a class="btn btn-sm" href="folder_edit.php">
            <?= icon('plus', 15) ?>
            New folder
        </a>

    </div>

    <div class="folder-grid">

        <?= folder_card(builtin_folder(count($toWatch))) ?>

        <?php foreach ($folders as $f): ?>
            <?= folder_card($f) ?>
        <?php endforeach; ?>

        <a class="folder-new" href="folder_edit.php">
            <?= icon('plus', 22) ?>
            <span>Create a folder</span>
        </a>

    </div>

</section>

<section class="section">

    <div class="sec-title" style="--bar:var(--gold)">

        <div>
            <h2>All movies to watch</h2>
            <p>
                Everything with status To Watch, including movies inside your folders
            </p>
        </div>

    </div>

    <?php if ($toWatch): ?>

        <div class="grid grid-movies">

            <?php foreach ($toWatch as $m): ?>
                <?= movie_card($m, $back, true) ?>
            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="card empty">

            <h2>Your watchlist is empty</h2>

            <p>
                Add movies you want to watch and they will show up here.
            </p>

            <a class="btn btn-primary" href="watchlist_add.php">
                Add to Watchlist
            </a>

        </div>

    <?php endif; ?>

</section>

<dialog id="shuffle-dialog" class="dlg-wide">

    <div class="dlg-head">

        <h2>Shuffle my watchlist</h2>

        <button
            type="button"
            class="icon-btn"
            data-modal-close
            aria-label="Close"
        >
            <?= icon('x', 16) ?>
        </button>

    </div>

    <p class="muted">
        Can't decide? Here are 5 random picks.
        Shuffle again until one feels right.
    </p>

    <div
        id="shuffle-results"
        class="shuffle-grid"
        aria-live="polite"
    ></div>

    <div class="dialog-actions">

        <button
            type="button"
            class="btn"
            data-modal-close
        >
            Close
        </button>

        <button
            type="button"
            class="btn btn-primary"
            id="shuffle-again"
        >
            <?= icon('shuffle', 16) ?>
            Shuffle again
        </button>

    </div>

</dialog>

<?php require __DIR__ . '/includes/footer.php'; ?>
