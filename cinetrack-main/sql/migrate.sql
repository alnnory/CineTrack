-- Upgrade an EXISTING movie_watchlist database to the multi-user + developer version (keeps your data).
-- Needs MariaDB 10.2+ (XAMPP). Run in phpMyAdmin with movie_watchlist selected.
-- After running: open tools/create_developer.php with the email you want as developer (promotes it),
-- then run the last UPDATE lines to give the old movies/folders to that account.
CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED NOT NULL AUTO_INCREMENT, name VARCHAR(60) NOT NULL, email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL, role ENUM('user','developer') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1, bio TEXT NULL, avatar_url VARCHAR(500) NULL, is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, last_login_at DATETIME NULL,
    PRIMARY KEY (user_id), UNIQUE KEY uq_user_email (email)) ENGINE=InnoDB;

ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user','developer') NOT NULL DEFAULT 'user';
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL;

ALTER TABLE movies ADD COLUMN IF NOT EXISTS user_id INT UNSIGNED NULL AFTER movie_id;
ALTER TABLE movies ADD COLUMN IF NOT EXISTS watch_url VARCHAR(500) NULL AFTER streaming_platform;
ALTER TABLE collections ADD COLUMN IF NOT EXISTS user_id INT UNSIGNED NULL AFTER collection_id;

-- Give existing rows to the first account (change the number if you want another owner), then lock the columns.
UPDATE movies      SET user_id = (SELECT MIN(user_id) FROM users) WHERE user_id IS NULL;
UPDATE collections SET user_id = (SELECT MIN(user_id) FROM users) WHERE user_id IS NULL;
ALTER TABLE movies      MODIFY user_id INT UNSIGNED NOT NULL, ADD INDEX idx_user (user_id), ADD CONSTRAINT fk_movie_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE;
ALTER TABLE collections MODIFY user_id INT UNSIGNED NOT NULL, DROP INDEX uq_collection_name, ADD UNIQUE KEY uq_collection_user_name (user_id, name), ADD CONSTRAINT fk_col_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE;
