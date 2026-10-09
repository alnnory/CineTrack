</main>

<?php if (!$auth_page && ($active ?? '') === 'home' && is_logged_in()):
$fg = $conn->prepare("SELECT genre FROM movies WHERE user_id = ? GROUP BY genre ORDER BY COUNT(*) DESC, genre LIMIT 5");
$fgid = current_user_id();
$fg->bind_param('i', $fgid); $fg->execute();
$footGenres = $fg->get_result()->fetch_all(MYSQLI_NUM);
?>
<footer class="site-footer">
    <div class="wrap">
        <div class="foot-grid">
            <div>
                <a class="brand" href="index.php"><span class="brand-mark"><?= icon('film', 22) ?></span>
                    <span class="brand-text"><span class="brand-name"><?= e($nameA) ?><b><?= e($nameB) ?></b></span></span></a>
                <p class="muted foot-blurb">Your personal movie tracker. Log what you watch, save what is next, and rate and review every movie in one place.</p>
            </div>
            <div>
                <h4>Quick navigation</h4>
                <a href="index.php">Dashboard</a><a href="movies.php">All movies</a>
                <a href="watchlist.php">Watchlist</a><a href="movies.php?status=Watched">Watched</a>
                <a href="movies.php?fav=1">Favorites</a><a href="add_movie.php">Add a movie</a>
            </div>
            <div>
                <h4>Top genres</h4>
                <?php foreach ($footGenres as [$g]): ?><a href="movies.php?genre=<?= urlencode($g) ?>"><?= e($g) ?></a><?php endforeach; ?>
            </div>
        </div>
        <div class="foot-bottom">
            <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Built with PHP &amp; MySQL.</span>
            <span><?= e(APP_TAGLINE) ?></span>
        </div>
    </div>
</footer>
<?php endif; ?>

<script src="assets/js/app.js"></script>
<script src="assets/js/bg3d.js"></script>
<script src="assets/js/animations.js"></script>
<script src="assets/js/command-palette.js"></script>
<?php foreach (($extra_js ?? []) as $js): ?><script src="<?= e($js) ?>"></script>
<?php endforeach; ?>

<dialog id="cmdk" class="cmdk" aria-label="Command palette">
    <div class="cmdk-inner">
        <div class="cmdk-search">
            <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="6"/><path d="M16 16l4 4"/></svg>
            <input type="text" id="cmdk-input" placeholder="Type a command or search movies..." autocomplete="off" spellcheck="false">
            <kbd class="cmdk-kbd">Esc</kbd>
        </div>
        <div class="cmdk-foot">
            <span><kbd>↑</kbd><kbd>↓</kbd> navigate</span>
            <span><kbd>⏎</kbd> select</span>
            <span><kbd>esc</kbd> close</span>
            <span style="margin-left:auto"><kbd>ctrl</kbd>+<kbd>alt</kbd>+<kbd>P</kbd></span>
        </div>
    </div>
</dialog>
</body>
</html>