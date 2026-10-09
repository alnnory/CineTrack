<?php
// Binge Watch: ranks watched movies for a rewatch and picks one at random (weighted).
// Score favors a HIGH rating, a LOW watch count, and a LOW priority.
declare(strict_types=1);

function binge_candidates(mysqli $c, int $uid): array {
    $s = $c->prepare("SELECT * FROM movies WHERE user_id = ? AND watch_status = 'Watched'");
    $s->bind_param('i', $uid);
    $s->execute();
    $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);

    $prio = ['Low' => 1.0, 'Medium' => 0.5, 'High' => 0.0];
    foreach ($rows as &$r) {
        $rating = $r['user_rating'] !== null ? (float)$r['user_rating'] / 10 : 0.5;
        $count  = (int)$r['watch_count'];
        $r['weight'] = 1 + 3 * $rating + 2 / (1 + $count) + 1.5 * ($prio[$r['priority']] ?? 0.5);

        $why = [];
        if ($r['user_rating'] !== null && (float)$r['user_rating'] >= 8.5) {
            $why[] = 'High rating ' . number_format((float)$r['user_rating'], 1);
        }
        if ($count <= 1) {
            $why[] = 'Rarely rewatched';
        }
        if ($r['priority'] === 'Low') {
            $why[] = 'Low priority';
        }
        $r['why'] = $why;
    }
    unset($r);

    usort($rows, fn($a, $b) => $b['weight'] <=> $a['weight']);
    return $rows;
}

/** Weighted random pick. Weights are cubed so recommended movies come up clearly more often,
 *  while every watched movie still has a chance (it is a rewatch roulette, not a fixed answer). */
function binge_pick_random(array $rows): ?array {
    if (!$rows) return null;
    $w = fn(array $r): int => (int)round(($r['weight'] ** 3) * 10);
    $roll = random_int(1, array_sum(array_map($w, $rows)));
    foreach ($rows as $r) {
        $roll -= $w($r);
        if ($roll <= 0) return $r;
    }
    return $rows[0];
}
