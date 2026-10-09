<?php
// Watchlist folders: constants, table setup, and folder card component.

declare(strict_types=1);

const FOLDER_COLORS = [
    'violet' => '#8b5cf6',
    'blue'   => '#3b82f6',
    'green'  => '#22c55e',
    'amber'  => '#f59e0b',
    'rose'   => '#f43f5e',
    'cyan'   => '#06b6d4',
    'slate'  => '#64748b',
];

const FOLDER_ICONS = [
    'folder',
    'heart',
    'star',
    'film',
    'clock',
    'bookmark',
    'dice',
    'clapper',
];

/**
 * Ensure that both collection tables exist.
 *
 * This function creates missing tables using sql/collections.sql.
 * It does not modify tables that already exist.
 */
function ensure_collections_schema(mysqli $c): void
{
    static $done = false;

    if ($done) {
        return;
    }

    // Check whether both tables already exist.
    $collectionsResult = $c->query(
        "SHOW TABLES LIKE 'collections'"
    );

    $collectionsExists = $collectionsResult->num_rows > 0;
    $collectionsResult->free();

    $moviesResult = $c->query(
        "SHOW TABLES LIKE 'collection_movies'"
    );

    $collectionMoviesExists = $moviesResult->num_rows > 0;
    $moviesResult->free();

    if ($collectionsExists && $collectionMoviesExists) {
        $done = true;
        return;
    }

    // Locate the SQL file.
    $sqlFile = __DIR__ . '/../sql/collections.sql';

    if (!is_file($sqlFile) || !is_readable($sqlFile)) {
        throw new RuntimeException(
            'The collections.sql file is missing or unreadable.'
        );
    }

    $sql = file_get_contents($sqlFile);

    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException(
            'The collections.sql file is empty or could not be read.'
        );
    }

    // Execute the SQL statements.
    if (!$c->multi_query($sql)) {
        throw new RuntimeException(
            'Could not create the collection tables: ' . $c->error
        );
    }

    // Process every result so the connection is ready for later queries.
    do {
        $result = $c->store_result();

        if ($result instanceof mysqli_result) {
            $result->free();
        }

        if ($c->errno !== 0) {
            throw new RuntimeException(
                'Error setting up collection tables: ' . $c->error
            );
        }

        if (!$c->more_results()) {
            break;
        }

        if (!$c->next_result()) {
            throw new RuntimeException(
                'Error processing collections.sql: ' . $c->error
            );
        }
    } while (true);

    // Confirm that both tables now exist.
    $collectionsResult = $c->query(
        "SHOW TABLES LIKE 'collections'"
    );

    $collectionsExists = $collectionsResult->num_rows > 0;
    $collectionsResult->free();

    $moviesResult = $c->query(
        "SHOW TABLES LIKE 'collection_movies'"
    );

    $collectionMoviesExists = $moviesResult->num_rows > 0;
    $moviesResult->free();

    if (!$collectionsExists || !$collectionMoviesExists) {
        throw new RuntimeException(
            'The collection tables could not be created successfully.'
        );
    }

    $done = true;
}

/**
 * Get a folder color.
 */
function folder_color(?string $key): string
{
    if ($key === null || !isset(FOLDER_COLORS[$key])) {
        return FOLDER_COLORS['violet'];
    }

    return FOLDER_COLORS[$key];
}

/**
 * Get the built-in watchlist folder information.
 */
function builtin_folder(int $count): array
{
    return [
        'collection_id' => 0,
        'name'          => 'My Watchlist',
        'description'   => 'All movies in your personal watchlist.',
        'color'         => 'violet',
        'icon'          => 'bookmark',
        'movie_count'   => max(0, $count),
        'is_builtin'    => true,
    ];
}

/**
 * Render a collection folder card.
 */
function folder_card(array $f): string
{
    $id = (int) ($f['collection_id'] ?? 0);
    $name = (string) ($f['name'] ?? 'Untitled Folder');
    $description = (string) ($f['description'] ?? '');
    $colorKey = (string) ($f['color'] ?? 'violet');
    $iconName = (string) ($f['icon'] ?? 'folder');
    $count = max(0, (int) ($f['movie_count'] ?? 0));

    $color = folder_color($colorKey);

    // Allow only icons defined in FOLDER_ICONS.
    if (!in_array($iconName, FOLDER_ICONS, true)) {
        $iconName = 'folder';
    }

    $safeName = e($name);
    $safeDescription = e($description);
    $safeColor = e($color);
    $safeIcon = e($iconName);

    $url = $id === 0
        ? 'watchlist.php'
        : 'folder.php?id=' . $id;

    $safeUrl = e($url);

    ob_start();
    ?>

    <a
        class="folder-card"
        href="<?= $safeUrl ?>"
        style="--folder-color: <?= $safeColor ?>;"
    >
        <div class="folder-card-icon">
            <span class="folder-icon" aria-hidden="true">
                <?= icon($safeIcon) ?>
            </span>
        </div>

        <div class="folder-card-content">
            <h3><?= $safeName ?></h3>

            <?php if ($description !== ''): ?>
                <p><?= $safeDescription ?></p>
            <?php endif; ?>

            <span class="folder-card-count">
                <?= $count ?>
                <?= $count === 1 ? 'movie' : 'movies' ?>
            </span>
        </div>
    </a>

    <?php
    return (string) ob_get_clean();
}
