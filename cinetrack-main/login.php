<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');

    if (!csrf_valid()) {
        $errors['email'] = 'Your session expired. Please try again.';
    } elseif ($email === '' || $pass === '') {
        $errors['email'] = 'Enter your email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        try {
            $s = $conn->prepare(
                'SELECT user_id, name, email, password_hash, role, is_active
                 FROM users
                 WHERE email = ?
                 LIMIT 1'
            );

            $s->bind_param('s', $email);
            $s->execute();

            $u = $s->get_result()->fetch_assoc();
            $s->close();

            if (!$u || !password_verify($pass, $u['password_hash'])) {
                $errors['email'] = 'Wrong email or password.';
            } elseif ((int)$u['is_active'] !== 1) {
                $errors['email'] =
                    'This account is suspended. Please contact the developer.';
            } else {
                $lu = $conn->prepare(
                    'UPDATE users
                     SET last_login_at = NOW()
                     WHERE user_id = ?'
                );

                $luid = (int)$u['user_id'];

                $lu->bind_param('i', $luid);
                $lu->execute();
                $lu->close();

                login_user($u);

                $to = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);

                // Only allow redirects to local application paths.
                if (
                    !is_string($to)
                    || $to === ''
                    || preg_match('/^(?:https?:)?\\/\\//i', $to)
                    || str_contains($to, '\\')
                    || str_contains($to, "\r")
                    || str_contains($to, "\n")
                ) {
                    $to = 'index.php';
                }

                flash('Welcome back, ' . $u['name'] . '!');
                redirect($to);
            }
        } catch (mysqli_sql_exception $e) {
            error_log('CineTrack login error: ' . $e->getMessage());

            $errors['email'] =
                'Something went wrong while signing in. Please try again.';
        }
    }
}

$page_title = 'Sign in';
$active = '';
$auth_page = true;

require __DIR__ . '/includes/header.php';
?>

<div class="auth-split">
    <!-- LEFT: Branding -->
    <aside class="auth-aside">
        <a class="auth-brand" href="index.php">
            <span class="auth-brand-mark"><?= icon('film', 26) ?></span>
            <span class="auth-brand-text">
                <strong><?= e(APP_NAME) ?></strong>
                <small><?= e(APP_TAGLINE) ?></small>
            </span>
        </a>

        <div class="auth-aside-body">
            <span class="eyebrow">Welcome back</span>
            <h1>Pick up <span class="grad-text">where you left off</span>.</h1>

            <p class="muted">
                Your collection is waiting. Sign in to keep tracking, rating,
                and remembering the films that matter.
            </p>

            <ul class="auth-points">
                <li>
                    <span class="auth-point-icon"><?= icon('check', 14) ?></span>
                    Your data, only for your eyes
                </li>
                <li>
                    <span class="auth-point-icon"><?= icon('check', 14) ?></span>
                    Private, encrypted credentials
                </li>
                <li>
                    <span class="auth-point-icon"><?= icon('check', 14) ?></span>
                    No ads, no tracking
                </li>
            </ul>
        </div>

        <p class="auth-foot muted small">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Built with PHP &amp; MySQL.
        </p>
    </aside>

    <!-- RIGHT: Form -->
    <section class="auth-main">
        <div class="auth-card">
            <header class="auth-head">
                <h2>Sign in to <?= e(APP_NAME) ?></h2>
                <p class="muted">
                    New here? <a href="register.php">Create an account</a>
                </p>
            </header>

            <?php if (!empty($errors)): ?>
                <div class="flash flash-error" role="alert">
                    <span><?= e(reset($errors)) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <?= csrf_field() ?>

                <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="<?= e($email) ?>"
                        autocomplete="email"
                        autofocus
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>

                    <div class="pw-wrap">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="pw-toggle"
                            data-pw-toggle
                            aria-label="Show password"
                        >
                            <?= icon('eye', 18) ?>
                        </button>
                    </div>
                </div>

                <div class="auth-row">
                    <span class="muted small">
                        Forgot your password? Ask the developer to reset it.
                    </span>
                </div>

                <button class="btn btn-primary btn-lg auth-submit" type="submit">
                    <?= icon('play', 16) ?> Sign in
                </button>
            </form>

            <p class="auth-alt muted small">
                Or <a href="register.php">create a new account</a>.
            </p>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
