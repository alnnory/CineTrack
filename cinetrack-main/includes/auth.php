<?php
// Authentication helpers.
// user      -> sees and edits only their own movies and folders
// developer -> same access as a user, plus the Developer panel and tools

declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function current_user_id(): int
{
    return (int)($_SESSION['user']['id'] ?? 0);
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']['id']);
}

function is_developer(): bool
{
    return ($_SESSION['user']['role'] ?? 'user') === 'developer';
}

/**
 * Refresh the logged-in user's account information from the database.
 * Suspended, deleted, or unavailable accounts should not keep access.
 */
function sync_session_user(mysqli $c): void
{
    if (!is_logged_in()) {
        return;
    }

    $id = current_user_id();

    try {
        $s = $c->prepare(
            'SELECT role, is_active, name, email
             FROM users
             WHERE user_id = ?
             LIMIT 1'
        );

        $s->bind_param('i', $id);
        $s->execute();

        $u = $s->get_result()->fetch_assoc();
        $s->close();
    } catch (mysqli_sql_exception $e) {
        error_log('Account verification failed: ' . $e->getMessage());

        http_response_code(503);
        exit('Unable to verify your account right now. Please try again later.');
    }

    if (!$u || (int)$u['is_active'] !== 1) {
        logout_user();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        flash(
            'Your account is not available. Please contact the developer.',
            'error'
        );

        redirect('login.php');
    }

    $_SESSION['user']['role'] = $u['role'];
    $_SESSION['user']['name'] = $u['name'];
    $_SESSION['user']['email'] = $u['email'];
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] =
            $_SERVER['REQUEST_URI'] ?? 'index.php';

        redirect('login.php');
    }

    global $conn;

    if (isset($conn) && $conn instanceof mysqli) {
        sync_session_user($conn);
    }
}

function require_developer(): void
{
    require_login();

    if (!is_developer()) {
        flash(
            'That page is for the developer account only.',
            'error'
        );

        redirect('index.php');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'    => (int)$user['user_id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'] ?? 'user',
    ];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }

    session_destroy();
}
