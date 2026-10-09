<?php
declare(strict_types=1);

// Developer panel: platform-wide data and account management.
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_developer();

$me = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = filter_var(
        $_POST['user_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $act = (string)($_POST['action'] ?? '');
    $keep = filter_var(
        $_POST['keep'] ?? null,
        FILTER_VALIDATE_INT
    );

    $back = 'admin.php';

    if ($keep && $keep > 0) {
        $back .= '?user=' . $keep;
    }

    if (!csrf_valid()) {
        flash('Your session expired. Please try again.', 'error');
        redirect($back);
    }

    if (!$tid || $tid < 1) {
        flash('Invalid account selected.', 'error');
        redirect($back);
    }

    if ($tid === $me) {
        flash('You cannot change your own account here.', 'error');
        redirect($back);
    }

    $allowedActions = [
        'toggle_active',
        'make_developer',
        'make_user',
        'delete_user',
    ];

    if (!in_array($act, $allowedActions, true)) {
        flash('Invalid account action.', 'error');
        redirect($back);
    }

    try {
        // Verify that the target account exists.
        $s = $conn->prepare(
            'SELECT user_id, role, is_active
             FROM users
             WHERE user_id = ?
             LIMIT 1'
        );

        $s->bind_param('i', $tid);
        $s->execute();
        $target = $s->get_result()->fetch_assoc();
        $s->close();

        if (!$target) {
            flash('That account no longer exists.', 'error');
            redirect($back);
        }

        $targetIsDeveloper = $target['role'] === 'developer';
        $targetIsActive = (int)$target['is_active'] === 1;

        // Keep at least one active developer account available.
        if (
            $targetIsDeveloper
            && (
                $act === 'make_user'
                || $act === 'delete_user'
                || ($act === 'toggle_active' && $targetIsActive)
            )
        ) {
            $s = $conn->prepare(
                "SELECT COUNT(*) AS total
                 FROM users
                 WHERE role = 'developer'
                   AND is_active = 1
                   AND user_id <> ?"
            );

            $s->bind_param('i', $tid);
            $s->execute();

            $activeDevelopers = (int)$s->get_result()
                ->fetch_assoc()['total'];

            $s->close();

            if ($activeDevelopers < 1) {
                flash(
                    'You cannot perform this action because at least one other active developer must remain.',
                    'error'
                );
                redirect($back);
            }
        }

        if ($act === 'toggle_active') {
            $newStatus = $targetIsActive ? 0 : 1;

            $s = $conn->prepare(
                'UPDATE users
                 SET is_active = ?
                 WHERE user_id = ?'
            );

            $s->bind_param('ii', $newStatus, $tid);
            $s->execute();
            $s->close();

            flash(
                $newStatus === 1
                    ? 'Account reactivated.'
                    : 'Account suspended.'
            );

        } elseif ($act === 'make_developer' || $act === 'make_user') {
            $role = $act === 'make_developer'
                ? 'developer'
                : 'user';

            if ($target['role'] === $role) {
                flash('That account already has this role.', 'error');
                redirect($back);
            }

            $s = $conn->prepare(
                'UPDATE users SET role = ? WHERE user_id = ?'
            );

            $s->bind_param('si', $role, $tid);
            $s->execute();
            $s->close();

            flash('Role changed to ' . $role . '.');

        } elseif ($act === 'delete_user') {
            // Delete the account and its dependent data atomically.
            $conn->begin_transaction();

            try {
                $s = $conn->prepare(
                    'DELETE FROM users WHERE user_id = ?'
                );

                $s->bind_param('i', $tid);
                $s->execute();

                $deleted = $s->affected_rows;
                $s->close();

                if ($deleted !== 1) {
                    throw new RuntimeException(
                        'The account could not be deleted.'
                    );
                }

                $conn->commit();

                flash('Account and associated data deleted.');

            } catch (Throwable $e) {
                $conn->rollback();
                throw $e;
            }
        }

    } catch (Throwable $e) {
        error_log('CineTrack admin action error: ' . $e->getMessage());

        flash(
            'The account action could not be completed. Check the database configuration and try again.',
            'error'
        );
    }

    redirect($back);
}

// Helper for dashboard totals.
$one = static function (string $sql) use ($conn): int {
    return (int)$conn->query($sql)->fetch_row()[0];
};

try {
    $tot = [
        'users' => $one('SELECT COUNT(*) FROM users'),
        'devs' => $one(
            "SELECT COUNT(*) FROM users WHERE role = 'developer'"
        ),
        'new7' => $one(
            'SELECT COUNT(*) FROM users
             WHERE created_at >= NOW() - INTERVAL 7 DAY'
        ),
        'movies' => $one('SELECT COUNT(*) FROM movies'),
        'folders' => $one('SELECT COUNT(*) FROM collections'),
    ];

    $users = $conn->query(
        "SELECT
            u.user_id,
            u.name,
            u.email,
            u.role,
            u.is_active,
            u.created_at,
            u.last_login_at,
            (
                SELECT COUNT(*)
                FROM movies m
                WHERE m.user_id = u.user_id
            ) AS movies,
            (
                SELECT COUNT(*)
                FROM collections c
                WHERE c.user_id = u.user_id
            ) AS folders
         FROM users u
         ORDER BY
            u.role = 'developer' DESC,
            u.created_at DESC"
    )->fetch_all(MYSQLI_ASSOC);

} catch (mysqli_sql_exception $e) {
    error_log('CineTrack admin dashboard error: ' . $e->getMessage());

    http_response_code(500);
    exit('Unable to load the developer dashboard. Please check the database.');
}

$viewId = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT) ?: 0;
$viewUser = null;
$viewMovies = [];

if ($viewId > 0) {
    foreach ($users as $u) {
        if ((int)$u['user_id'] === $viewId) {
            $viewUser = $u;
            break;
        }
    }

    if ($viewUser) {
        $s = $conn->prepare(
            'SELECT
                title,
                release_year,
                genre,
                watch_status,
                user_rating,
                watch_count
             FROM movies
             WHERE user_id = ?
             ORDER BY date_added DESC, movie_id DESC
             LIMIT 200'
        );

        $s->bind_param('i', $viewId);
        $s->execute();
        $viewMovies = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        $s->close();
    }
}

// Generate account action forms with CSRF protection.
$act_btn = function (
    array $u,
    string $action,
    string $label,
    string $cls = '',
    string $confirm = ''
) use ($viewId): string {
    $confirmAttr = '';

    if ($confirm !== '') {
        $confirmAttr = ' onclick="return confirm('
            . htmlspecialchars(
                json_encode(
                    $confirm,
                    JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP
                ),
                ENT_QUOTES,
                'UTF-8'
            )
            . ')"';
    }

    $html = '<form method="post">'
        . csrf_field()
        . '<input type="hidden" name="user_id" value="'
        . (int)$u['user_id'] . '">'
        . '<input type="hidden" name="action" value="'
        . e($action) . '">';

    if ($viewId > 0) {
        $html .= '<input type="hidden" name="keep" value="'
            . (int)$viewId . '">';
    }

    $html .= '<button class="btn btn-sm ' . e($cls)
        . '" type="submit"' . $confirmAttr . '>'
        . e($label)
        . '</button></form> ';

    return $html;
};

$page_title = 'Developer panel';
$active = 'admin';

require __DIR__ . '/includes/header.php';
?>
