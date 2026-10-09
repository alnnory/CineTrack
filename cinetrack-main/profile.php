<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/movie_form.php';

require_login();
$uid = current_user_id();

$errors = [];
$s = $conn->prepare('SELECT name, email, bio, avatar_url, is_public FROM users WHERE user_id = ?');
$s->bind_param('i', $uid); $s->execute();
$m = $s->get_result()->fetch_assoc() ?: [];
$m['bio'] = $m['bio'] ?? '';
$m['avatar_url'] = $m['avatar_url'] ?? '';
$m['is_public'] = (int)($m['is_public'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['name', 'email', 'bio', 'avatar_url'] as $k) $m[$k] = trim((string)($_POST[$k] ?? ''));
    $m['is_public'] = isset($_POST['is_public']) ? 1 : 0;

    if (!csrf_valid()) {
        $errors['name'] = 'Your session expired. Please try again.';
    } else {
        if ($m['name'] === '' || mb_strlen($m['name']) > 60)  $errors['name']  = 'Enter your name (max 60 chars).';
        if (!filter_var($m['email'], FILTER_VALIDATE_EMAIL))  $errors['email'] = 'Enter a valid email.';
        if (mb_strlen($m['bio']) > 1000)                      $errors['bio']   = 'Keep your bio under 1000 characters.';
        if ($m['avatar_url'] !== '' && !filter_var($m['avatar_url'], FILTER_VALIDATE_URL))
                                                              $errors['avatar_url'] = 'Enter a full URL, or leave blank.';
        if (!$errors) {
            $s = $conn->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ?');
            $s->bind_param('si', $m['email'], $uid); $s->execute();
            if ($s->get_result()->num_rows) $errors['email'] = 'That email is already in use.';
        }
    }
    if (!$errors) {
        $bio = $m['bio'] === '' ? null : $m['bio'];
        $av  = $m['avatar_url'] === '' ? null : $m['avatar_url'];
        $s = $conn->prepare('UPDATE users SET name = ?, email = ?, bio = ?, avatar_url = ?, is_public = ? WHERE user_id = ?');
        $s->bind_param('ssssii', $m['name'], $m['email'], $bio, $av, $m['is_public'], $uid);
        $s->execute();
        $_SESSION['user']['name']  = $m['name'];
        $_SESSION['user']['email'] = $m['email'];
        flash('Profile updated.');
        redirect('profile.php');
    }
}

$st_stmt = $conn->prepare("SELECT COUNT(*) total,
        COALESCE(SUM(watch_status = 'Watched'), 0) watched,
        COALESCE(SUM(favorite), 0) favs,
        COALESCE(SUM(duration_minutes * watch_count), 0) mins
    FROM movies WHERE user_id = ?");
$st_stmt->bind_param('i', $uid); $st_stmt->execute();
$st = $st_stmt->get_result()->fetch_assoc();
$hours = (int)round($st['mins'] / 60);

$page_title = 'My profile';
$active = '';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div>
        <span class="eyebrow">Account</span>
        <h1>My profile</h1>
        <p class="muted">Manage your account and public profile.</p>
    </div>
    <a class="btn" href="logout.php"><?= icon('x', 16) ?> Sign out</a>
</div>

<div class="grid grid-stats section" style="margin-top:0;margin-bottom:1.4rem">
    <div class="card stat"><span class="stat-icon"><?= icon('film', 22) ?></span><div><div class="stat-value"><?= (int)$st['total'] ?></div><div class="stat-label">Total movies</div></div></div>
    <div class="card stat"><span class="stat-icon t-green"><?= icon('check', 22) ?></span><div><div class="stat-value"><?= (int)$st['watched'] ?></div><div class="stat-label">Watched</div></div></div>
    <div class="card stat"><span class="stat-icon t-gold"><?= icon('heart', 22) ?></span><div><div class="stat-value"><?= (int)$st['favs'] ?></div><div class="stat-label">Favorites</div></div></div>
    <div class="card stat"><span class="stat-icon t-blue"><?= icon('clock', 22) ?></span><div><div class="stat-value"><?= number_format($hours) ?></div><div class="stat-label">Hours watched</div></div></div>
</div>

<form method="post" novalidate style="max-width: 640px;">
    <?= csrf_field() ?>
    <?php if ($errors): ?><div class="flash flash-error" role="alert"><span>Please fix the highlighted fields.</span></div><?php endif; ?>
    <section class="card form-section">
        <h2 class="form-h" style="--bar:var(--magenta)">Account details</h2>
        <div class="form-grid" style="grid-template-columns:1fr;">
            <?php
            f_input('name', 'Name', $m, $errors, 'text', ['maxlength' => 60], true, true);
            f_input('email', 'Email', $m, $errors, 'email', [], true, true);
            f_input('avatar_url', 'Avatar URL', $m, $errors, 'url', ['placeholder' => 'https://...'], false, true);
            f_input('bio', 'Bio', $m, $errors, 'textarea', ['rows' => 4, 'maxlength' => 1000], false, true);
            ?>
            <div class="field check full">
                <input id="is_public" type="checkbox" name="is_public" value="1" <?= $m['is_public'] ? 'checked' : '' ?>>
                <label for="is_public">Make my profile public</label>
            </div>
        </div>
    </section>
    <button class="btn btn-primary" type="submit" style="margin-top:1rem;"><?= icon('check', 16) ?> Save changes</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>