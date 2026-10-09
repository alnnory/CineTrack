-- Watchlist folders (per user). Safe to run on a database that already has the users and movies tables.
CREATE TABLE IF NOT EXISTS collections (
    collection_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    name          VARCHAR(60)  NOT NULL,
    description   VARCHAR(255) NULL,
    color         VARCHAR(10)  NOT NULL DEFAULT 'violet',
    icon          VARCHAR(20)  NOT NULL DEFAULT 'folder',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (collection_id),
    UNIQUE KEY uq_collection_user_name (user_id, name),
    CONSTRAINT fk_col_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS collection_movies (
    collection_id INT UNSIGNED NOT NULL,
    movie_id      INT UNSIGNED NOT NULL,
    added_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (collection_id, movie_id),
    CONSTRAINT fk_cm_collection FOREIGN KEY (collection_id) REFERENCES collections (collection_id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_movie      FOREIGN KEY (movie_id)      REFERENCES movies (movie_id)           ON DELETE CASCADE
) ENGINE=InnoDB;
