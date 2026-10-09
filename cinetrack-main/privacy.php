<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
$page_title = 'Privacy & Terms';
$active = '';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div><span class="eyebrow">Legal</span><h1>Privacy Policy &amp; Terms</h1></div></div>
<section class="card form-section" style="max-width:760px">
    <h2 class="form-h" style="--bar:var(--magenta)">Privacy</h2>
    <p>CineTrack stores your name, email, a hashed password, and the movies and folders you create. Your passwords are never stored in plain text.</p>
    <p>Your movies and folders are private to your account. The developer account can view account data for support and maintenance only. There are no ads and no third-party tracking.</p>
    <p>You can delete your movies at any time, and you can ask the developer to delete your whole account.</p>
    <h2 class="form-h" id="terms" style="--bar:var(--gold);margin-top:1.6rem">Terms</h2>
    <p>Use CineTrack for your own personal movie tracking. Do not try to access other people's data. Accounts that abuse the service may be suspended.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
