<?php
// Multi-select movie picker.
// Used by folder_add.php and watchlist_add.php.
// The page that includes this file handles the POST request.

declare(strict_types=1);

function render_movie_picker(
    array $movies,
    array $disabledIds,
    string $targetName
): void {
?>
<form method="post" class="picker" data-picker data-target="<?= e($targetName) ?>">
    <?= csrf_field() ?>

    <div class="card picker-bar">
        <div class="picker-search">
            <?= icon('search', 16) ?>
            <input
                type="search"
                data-q
                placeholder="Search by title, genre, director, or cast"
                aria-label="Search movies"
                autocomplete="off"
            >
        </div>

        <button type="button" class="btn btn-sm" data-select-shown>
            Select all shown
        </button>

        <button type="button" class="btn btn-sm" data-clear>
            Clear
        </button>

        <span class="muted small">
            <span data-shown><?= count($movies) ?> shown</span>
            &middot;
            <strong data-count>0 selected</strong>
        </span>

        <button class="btn btn-primary" type="submit" data-submit disabled>
            <?= icon('plus', 16) ?>
            <span data-submit-label>Select movies to add</span>
        </button>
    </div>

    <div class="grid grid-movies picker-grid">
        <?php foreach ($movies as $m): ?>
            <?php
            $id = (int)($m['movie_id'] ?? 0);

            $title = (string)($m['title'] ?? '');
            $genre = (string)($m['genre'] ?? '');
            $director = (string)($m['director'] ?? '');
            $cast = (string)($m['cast'] ?? '');
            $year = (int)($m['release_year'] ?? 0);
            $duration = (int)($m['duration_minutes'] ?? 0);

            $posterUrl = (string)($m['poster_url'] ?? '');
            $watchStatus = (string)($m['watch_status'] ?? 'Not added');
            $rating = $m['user_rating'] ?? null;

            $dis = in_array($id, $disabledIds, true);

            $hay = mb_strtolower(
                $title . ' ' .
                $genre . ' ' .
                $director . ' ' .
                $cast . ' ' .
                $year,
                'UTF-8'
            );
            ?>

            <label
                class="pick-card<?= $dis ? ' is-disabled' : '' ?>"
                data-search="<?= e($hay) ?>"
            >
                <input
                    type="checkbox"
                    name="movie_ids[]"
                    value="<?= $id ?>"
                    <?= $dis ? 'disabled' : '' ?>
                >

                <article class="mcard">
                    <div class="mcard-media">
                        <?= poster($posterUrl, $title) ?>

                        <span class="pill">
                            <?= e($genre !== '' ? $genre : 'Unknown genre') ?>
                        </span>

                        <span class="pick-check">
                            <?= icon('check', 16) ?>
                        </span>
                    </div>

                    <div class="mcard-body">
                        <div class="mcard-meta">
                            <span>
                                <?= $year > 0 ? $year : 'Year unknown' ?>
                                &bull;
                                <?= e(format_duration($duration)) ?>
                            </span>

                            <?php if ($rating !== null && $rating !== ''): ?>
                                <span class="rate">
                                    <?= icon('star', 13) ?>
                                    <?= number_format((float)$rating, 1) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3><?= e($title) ?></h3>

                        <div class="mcard-foot">
                            <span class="badge <?= e(status_class($watchStatus)) ?>">
                                <?= e($watchStatus) ?>
                            </span>

                            <?php if ($dis): ?>
                                <span class="muted small">Already added</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </label>
        <?php endforeach; ?>
    </div>

    <p class="card empty" data-empty hidden>
        No movies match your search.
    </p>
</form>
<?php
}
