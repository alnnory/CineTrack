<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$local = in_array(
    $_SERVER['REMOTE_ADDR'] ?? '',
    ['127.0.0.1', '::1'],
    true
);

$errors = [];
$m = ['name' => '', 'email' => ''];

try {
    $result = $conn->query(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = 'developer'"
    );

    $devCount = (int)$result->fetch_assoc()['total'];
    $result->free();
} catch (mysqli_sql_exception $e) {
    error_log('Developer setup count failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to check developer setup. Please try again later.');
}

$isDeveloper = is_logged_in() && is_developer();

/*
 * Initial setup is allowed only on localhost when no developer exists.
 * Once a developer exists, only a signed-in developer can use this page.
 */
$firstSetup = ($devCount === 0 && $local);

if (!$firstSetup && !$isDeveloper) {
    http_response_code(403);
    exit(
        $devCount > 0
            ? 'A developer account already exists. Sign in as a developer to continue.'
            : 'First developer setup is only available on the local machine.'
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $m['name'] = trim((string)($_POST['name'] ?? ''));
    $m['email'] = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');

    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please try again.';
    } else {
        if (
            $m['name'] === '' ||
            mb_strlen($m['name'], 'UTF-8') > 60
        ) {
            $errors['name'] = 'Enter a name (max 60 characters).';
        }

        if (
            $m['email'] === '' ||
            strlen($m['email']) > 254 ||
            !filter_var($m['email'], FILTER_VALIDATE_EMAIL)
        ) {
            $errors['email'] = 'Enter a valid email address.';
        }

        if (strlen($pass) < 8 || strlen($pass) > 4096) {
            $errors['password'] = 'Password must be 8 to 4096 bytes long.';
        }
    }

    if (!$errors) {
        try {
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            if ($hash === false) {
                throw new RuntimeException('Password hashing failed.');
            }

            $conn->begin_transaction();

            $s = $conn->prepare(
                'SELECT user_id FROM users WHERE email = ? LIMIT 1 FOR UPDATE'
            );
            $s->bind_param('s', $m['email']);
            $s->execute();
            $existing = $s->get_result()->fetch_assoc();
            $s->close();

            if ($existing) {
                /*
                 * Only a signed-in developer may promote an existing account.
                 * First-time setup must not take over an existing account.
                 */
                if (!$isDeveloper) {
                    $conn->rollback();
                    $errors['form'] =
                        'That email already belongs to an account. Sign in as a developer to promote an existing account.';
                } else {
                    $userId = (int)$existing['user_id'];

                    $s = $conn->prepare(
                        "UPDATE users
                         SET name = ?, role = 'developer',
                             is_active = 1, password_hash = ?
                         WHERE user_id = ?"
                    );
                    $s->bind_param('ssi', $m['name'], $hash, $userId);
                    $s->execute();
                    $s->close();

                    $conn->commit();

                    flash('Developer account updated: ' . $m['email']);
                    redirect('../admin.php');
                }
            } else {
                $s = $conn->prepare(
                    "INSERT INTO users (name, email, password_hash, role)
                     VALUES (?, ?, ?, 'developer')"
                );
                $s->bind_param('sss', $m['name'], $m['email'], $hash);
                $s->execute();
                $s->close();

                $conn->commit();

                flash('Developer account created: ' . $m['email']);
                redirect($isDeveloper ? '../admin.php' : '../login.php');
            }
        } catch (Throwable $e) {
            try {
                $conn->rollback();
            } catch (Throwable $rollbackError) {
                error_log('Developer setup rollback failed: ' . $rollbackError->getMessage());
            }

            error_log('Developer setup failed: ' . $e->getMessage());

            if ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1062) {
                $errors['email'] = 'That email address is already registered.';
            } else {
                $errors['form'] = 'Unable to save the developer account. Please try again.';
            }
        }
    }
}

$page_title = 'Developer account';
$active = 'admin';
$base_href = '../';

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <span class="eyebrow">Setup</span>
        <h1>Create a developer account</h1>
        <p class="muted">
            <?php if ($isDeveloper): ?>
                You can create a new developer account or promote an existing account.
            <?php else: ?>
                This page is only available for the initial local developer setup.
            <?php endif; ?>
        </p>
    </div>
</div>

<form method="post" style="max-width:560px">
    <?= csrf_field() ?>

    <?php if ($errors): ?>
        <div class="flash flash-error" role="alert">
            <span><?= e(reset($errors)) ?></span>
        </div>
    <?php endif; ?>

    <section class="card form-section">
        <div class="form-grid" style="grid-template-columns:1fr">
            <?php
            require_once __DIR__ . '/../includes/movie_form.php';

            f_input(
                'name',
                'Name',
                $m,
                $errors,
                'text',
                ['maxlength' => 60, 'autocomplete' => 'name'],
                true,
                true
            );

            f_input(
                'email',
                'Email',
                $m,
                $errors,
                'email',
                ['maxlength' => 254, 'autocomplete' => 'email'],
                true,
                true
            );

            f_input(
                'password',
                'Password (8–4096 bytes)',
                ['password' => ''],
                $errors,
                'password',
                ['autocomplete' => 'new-password', 'minlength' => 8, 'maxlength' => 4096],
                true,
                true
            );
            ?>
        </div>
    </section>

    <button class="btn btn-primary" type="submit" style="margin-top:1rem">
        <?= icon('shield', 16) ?> Save developer account
    </button>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
