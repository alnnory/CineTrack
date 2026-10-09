# CineTrack: setup (XAMPP)

**Fresh install**
1. phpMyAdmin: import `sql/schema.sql` (drops and recreates `movie_watchlist`).
2. Open `http://localhost/<folder>/tools/create_developer.php` and create the developer account (works once, on this PC only).
3. Optional demo data: import `sql/seed.sql`, then `sql/collections_sample.sql` (both are given to the developer account).

**Existing database:** run `sql/migrate.sql`, then step 2.

**Two kinds of data**
- *Users*: each account only sees its own movies and folders (every query is filtered by `user_id`).
- *Developer*: `admin.php` shows platform totals and every account (view data, suspend, promote, delete) and unlocks `tools/fetch_posters.php`.

Delete the old `connect.php`; it is not used.
