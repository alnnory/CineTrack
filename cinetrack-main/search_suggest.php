<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 2) { echo '[]'; exit; }

$uid = current_user_id();
$like = '%' . addcslashes($q, '%_\\') . '%';
$s = $conn->prepare("SELECT movie_id, title, release_year, genre FROM movies
    WHERE user_id = ? AND (title LIKE ? OR director LIKE ? OR `cast` LIKE ?)
    ORDER BY title LIMIT 8");
$s->bind_param('isss', $uid, $like, $like, $like);
$s->execute();
$rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(array_map(fn($r) => [
    'id'    => (int)$r['movie_id'],
    'title' => $r['title'],
    'year'  => (int)$r['release_year'],
    'genre' => $r['genre'],
], $rows));