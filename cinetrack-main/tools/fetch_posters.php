
<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_developer();

/*
 * This tool can update movies belonging to multiple users and export SQL.
 * Keep it restricted to the local machine.
 */
$local = in_array(
    $_SERVER['REMOTE_ADDR'] ?? '',
    ['127.0.0.1', '::1'],
    true
);

if (!$local) {
    http_response_code(403);
    exit('This tool can only be used on the local machine.');
}

const WIKI_API = 'https://en.wikipedia.org/w/api.php';

$results = [];
$ran = false;
$exportMsg = '';
$errorMsg = '';
$sslUsed = false;

/**
 * Request JSON from the fixed Wikipedia API over HTTPS.
 * Certificate verification remains enabled.
 */
function http_json(string $url): ?array
{
    $apiHost = parse_url($url, PHP_URL_HOST);

    if (
        parse_url($url, PHP_URL_SCHEME) !== 'https' ||
        $apiHost !== 'en.wikipedia.org'
    ) {
        return null;
    }

    $ua = 'CineTrack/1.0 (local personal project)';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);

        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => $ua,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // curl_close() is deprecated in PHP 8.5.
        // PHP releases the cURL handle automatically when unused.

        if ($body === false || $status < 200 || $status >= 300) {
            return null;
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: {$ua}\r\n",
                'timeout' => 20,
                'follow_location' => 0,
                'ignore_errors' => false,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            return null;
        }
    }

    $data = json_decode((string) $body, true);

    return is_array($data) ? $data : null;
}

/**
 * @return array{0:?string,1:string,2:string}
 * [poster URL, status, matched Wikipedia title]
 */
function find_poster(string $title, int $year): array
{
    $search = trim($title . ' ' . $year . ' film');

    $url = WIKI_API . '?' . http_build_query([
        'action' => 'query',
        'format' => 'json',
        'generator' => 'search',
        'gsrsearch' => $search,
        'gsrlimit' => 5,
        'prop' => 'pageimages',
        'piprop' => 'thumbnail',
        'pithumbsize' => 500,
        'redirects' => 1,
    ]);

    $data = http_json($url);
    $pages = $data['query']['pages'] ?? [];

    if (!is_array($pages) || !$pages) {
        return [null, 'not found', ''];
    }

    uasort(
        $pages,
        fn($a, $b) => ($a['index'] ?? 99) <=> ($b['index'] ?? 99)
    );

    $normalize = static function (string $value): string {
        $value = preg_replace('/\s*\(.*?\)\s*$/', '', $value) ?? $value;

        return preg_replace('/[^a-z0-9]+/', '', strtolower($value)) ?? '';
    };

    $wanted = $normalize($title);
    $candidates = [];

    foreach ($pages as $page) {
        $source = $page['thumbnail']['source'] ?? '';
        $host = is_string($source)
            ? parse_url($source, PHP_URL_HOST)
            : null;

        if (
            is_string($source) &&
            $source !== '' &&
            parse_url($source, PHP_URL_SCHEME) === 'https' &&
            $host === 'upload.wikimedia.org'
        ) {
            $candidates[] = [
                $source,
                (string) ($page['title'] ?? ''),
                $normalize((string) ($page['title'] ?? '')),
            ];
        }
    }

    foreach ($candidates as [$source, $matchedTitle, $normalizedTitle]) {
        if ($normalizedTitle !== '' && $normalizedTitle === $wanted) {
            return [$source, 'matched', $matchedTitle];
        }
    }

    foreach ($candidates as [$source, $matchedTitle, $normalizedTitle]) {
        if (
            $normalizedTitle !== '' &&
            $wanted !== '' &&
            str_starts_with($wanted, $normalizedTitle)
        ) {
            return [$source, 'matched', $matchedTitle];
        }
    }

    if ($candidates) {
        return [
            $candidates[0][0],
            'check',
            $candidates[0][1],
        ];
    }

    return [null, 'no image', ''];
}

/**
 * Export poster updates using escaped SQL values.
 */
function export_posters(mysqli $conn): array
{
    $lines = [
        '-- Poster links exported by CineTrack.',
        'USE movie_watchlist;',
        '',
    ];

    $result = $conn->query(
        "SELECT title, release_year, poster_url
         FROM movies
         WHERE poster_url IS NOT NULL
           AND poster_url <> ''
         ORDER BY title, release_year"
    );

    while ($row = $result->fetch_assoc()) {
        $title = $conn->real_escape_string((string) $row['title']);
        $posterUrl = $conn->real_escape_string((string) $row['poster_url']);
        $year = (int) $row['release_year'];

        $lines[] = sprintf(
            "UPDATE movies SET poster_url = '%s' WHERE title = '%s' AND release_year %s;",
            $posterUrl,
            $title,
            $row['release_year'] === null ? 'IS NULL' : '= ' . $year
        );
    }

    $result->free();

    $path = __DIR__ . '/../sql/posters.sql';

    if (@file_put_contents($path, implode("\n", $lines) . "\n") === false) {
        return [
            false,
            'Could not write sql/posters.sql. Check folder permissions.',
        ];
    }

    return [
        true,
        'Saved poster updates to sql/posters.sql.',
    ];
}

/*
 * Handle the form submission.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errorMsg = 'Your session expired. Refresh the page and try again.';
    } else {
        try {
            set_time_limit(300);

            $overwrite = isset($_POST['overwrite'])
                && $_POST['overwrite'] === '1';

            $sql = 'SELECT movie_id, title, release_year FROM movies';

            if (!$overwrite) {
                $sql .= " WHERE poster_url IS NULL OR poster_url = ''";
            }

            $sql .= ' ORDER BY title';

            $query = $conn->query($sql);
            $rows = $query->fetch_all(MYSQLI_ASSOC);
            $query->free();

            $update = $conn->prepare(
                'UPDATE movies SET poster_url = ? WHERE movie_id = ?'
            );

            foreach ($rows as $row) {
                $title = (string) $row['title'];
                $year = (int) ($row['release_year'] ?? 0);

                [$posterUrl, $status, $matched] = find_poster($title, $year);

                if ($posterUrl !== null) {
                    $movieId = (int) $row['movie_id'];

                    $update->bind_param('si', $posterUrl, $movieId);
                    $update->execute();
                }

                $results[] = [
                    'movie_id' => (int) $row['movie_id'],
                    'title' => $title,
                    'release_year' => $row['release_year'],
                    'url' => $posterUrl,
                    'status' => $status,
                    'matched' => $matched,
                ];

                // Pause briefly between requests to Wikipedia.
                usleep(150000);
            }

            $update->close();

            [$exported, $exportMsg] = export_posters($conn);

            $ran = true;
        } catch (Throwable $e) {
            error_log('CineTrack poster fetch failed: ' . $e->getMessage());
            $errorMsg = 'The poster-fetch operation failed. Check your PHP error log and try again.';
        }
    }
}

/*
 * Count missing posters across the database.
 * This tool is intended to prepare shared poster links for the project.
 */
$missing = (int) $conn->query(
    "SELECT COUNT(*)
     FROM movies
     WHERE poster_url IS NULL OR poster_url = ''"
)->fetch_row()[0];

$page_title = 'Fetch posters';
$active = 'movies';
$base_href = '../';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <span class="eyebrow">One-time tool</span>
        <h1>Fetch movie posters</h1>
        <p class="muted">
            <?= $missing ?> of your movies have no poster yet.
            Posters are searched through Wikipedia.
        </p>
    </div>

    <a class="btn" href="../movies.php">
        <?= icon('film', 16) ?> Back to movies
    </a>
</div>

<?php if ($errorMsg !== ''): ?>
    <div class="flash flash-error" role="alert">
        <?= e($errorMsg) ?>
    </div>
<?php endif; ?>

<section class="card form-section">
    <form method="post" class="tool-form">
        <?= csrf_field() ?>

        <button class="btn btn-primary" type="submit">
            <?= icon('search', 16) ?> Fetch posters now
        </button>

        <label class="check-inline">
            <input type="checkbox" name="overwrite" value="1">
            Replace posters that are already set
        </label>

        <span class="muted small">
            Requires an internet connection. Processing time depends on the number of movies.
        </span>
    </form>
</section>

<?php if ($ran): ?>
    <?php
    $got = count(array_filter($results, fn($r) => $r['url'] !== null));
    $check = count(array_filter($results, fn($r) => $r['status'] === 'check'));
    ?>

    <section class="card form-section" style="margin-top:1.2rem">
        <h2 class="form-h" style="--bar:var(--gold)">
            Results: <?= $got ?> of <?= count($results) ?> found
        </h2>

        <p class="muted">
            <?= e($exportMsg) ?>

            <?php if ($check > 0): ?>
                <?= $check ?> result(s) are best guesses. Check the matching article
                and correct any wrong poster using Edit Movie.
            <?php endif; ?>
        </p>

        <div class="table-wrap">
            <table class="result-table">
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Movie</th>
                        <th>Result</th>
                        <th>Wikipedia article</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td>
                                <?php if ($r['url'] !== null): ?>
                                    <img
                                        src="<?= e($r['url']) ?>"
                                        alt=""
                                        width="36"
                                        loading="lazy"
                                    >
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= e($r['title']) ?>
                                <span class="muted">
                                    (<?= e((string) ($r['release_year'] ?? 'N/A')) ?>)
                                </span>
                            </td>

                            <td>
                                <span class="badge <?= $r['status'] === 'matched'
                                    ? 'badge-watched'
                                    : ($r['status'] === 'check'
                                        ? 'badge-watching'
                                        : 'badge-high') ?>">
                                    <?= e($r['status']) ?>
                                </span>
                            </td>

                            <td class="muted">
                                <?= e($r['matched']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>