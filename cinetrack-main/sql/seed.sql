
-- CineTrack: Seed data (52 sample movies)
-- Run AFTER schema.sql and AFTER creating a developer account.
-- poster_url is NULL on purpose; the UI can display placeholder posters.
-- Sample ratings, reviews, and platforms can be edited freely.
--
-- Safety:
-- 1. Stops if no developer account exists.
-- 2. Skips sample movies that already exist for that developer.
-- 3. Does not delete or overwrite existing movies.

USE movie_watchlist;

-- Find the developer account that will own the sample movies.
SET @seed_uid = (
    SELECT user_id
    FROM users
    WHERE role = 'developer'
    ORDER BY user_id
    LIMIT 1
);

-- Stop the script if no developer account exists.
-- If this raises an error, create a developer account first using
-- tools/create_developer.php, then run this script again.
DELIMITER //

DROP PROCEDURE IF EXISTS check_seed_developer//

CREATE PROCEDURE check_seed_developer()
BEGIN
    IF @seed_uid IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'No developer account found. Create a developer account before running seed.sql.';
    END IF;
END//

DELIMITER ;

CALL check_seed_developer();

DROP PROCEDURE check_seed_developer;

-- Insert only sample movies that do not already exist for this developer.
-- The temporary table holds the original sample data.
CREATE TEMPORARY TABLE seed_movies (
    title              VARCHAR(150) NOT NULL,
    short_description  TEXT NULL,
    release_year       SMALLINT NOT NULL,
    genre              VARCHAR(50) NOT NULL,
    director           VARCHAR(100) NULL,
    `cast`             VARCHAR(255) NULL,
    duration_minutes   SMALLINT NULL,
    `language`         VARCHAR(40) NOT NULL DEFAULT 'English',
    country            VARCHAR(60) NULL,
    age_rating         VARCHAR(10) NULL,
    watch_status       ENUM('To Watch','Watching','Watched') NOT NULL DEFAULT 'To Watch',
    watch_count        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    date_added         DATE NOT NULL,
    poster_url         VARCHAR(500) NULL,
    user_rating        DECIMAL(3,1) NULL,
    review             TEXT NULL,
    streaming_platform VARCHAR(50) NULL,
    priority           ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
    favorite           TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (title, release_year)
) ENGINE=InnoDB;

INSERT INTO seed_movies
(title, short_description, release_year, genre, director, `cast`,
 duration_minutes, `language`, country, age_rating, watch_status,
 watch_count, date_added, poster_url, user_rating, review,
 streaming_platform, priority, favorite)
VALUES
-- ===== MARVEL CINEMATIC UNIVERSE =====
('Iron Man','Billionaire Tony Stark builds a powered suit to escape captivity and becomes a hero.',2008,'Action','Jon Favreau','Robert Downey Jr., Gwyneth Paltrow, Jeff Bridges',126,'English','USA','PG-13','Watched',3,'2026-01-05',NULL,8.5,'The one that started it all. RDJ was born for this role.','Disney+','Medium',1),
('Thor','The arrogant god Thor is banished to Earth and must prove himself worthy of his power.',2011,'Fantasy','Kenneth Branagh','Chris Hemsworth, Natalie Portman, Tom Hiddleston',115,'English','USA','PG-13','Watched',1,'2026-01-05',NULL,7.0,NULL,'Disney+','Low',0),
('Captain America: The First Avenger','A frail soldier becomes a super-soldier and takes on the Red Skull in World War II.',2011,'Action','Joe Johnston','Chris Evans, Hayley Atwell, Hugo Weaving',124,'English','USA','PG-13','Watched',2,'2026-01-06',NULL,7.5,NULL,'Disney+','Medium',0),
('The Avengers','Earth''s mightiest heroes unite to stop Loki and an alien invasion of New York.',2012,'Action','Joss Whedon','Robert Downey Jr., Chris Evans, Scarlett Johansson',143,'English','USA','PG-13','Watched',4,'2026-01-06',NULL,9.0,'Peak team-up energy. The long take in the battle is unforgettable.','Disney+','High',1),
('Iron Man 3','Tony Stark faces the Mandarin and his own anxiety after the Battle of New York.',2013,'Action','Shane Black','Robert Downey Jr., Guy Pearce, Ben Kingsley',130,'English','USA','PG-13','Watched',1,'2026-01-08',NULL,7.0,NULL,'Disney+','Low',0),
('Captain America: The Winter Soldier','Steve Rogers uncovers a conspiracy inside S.H.I.E.L.D. while hunted by a mysterious assassin.',2014,'Thriller','Anthony Russo, Joe Russo','Chris Evans, Scarlett Johansson, Sebastian Stan',136,'English','USA','PG-13','Watched',2,'2026-01-08',NULL,9.0,'A political thriller disguised as a superhero movie.','Disney+','High',1),
('Guardians of the Galaxy','A group of misfit outlaws joins forces to stop a fanatic from destroying the galaxy.',2014,'Sci-Fi','James Gunn','Chris Pratt, Zoe Saldana, Dave Bautista',121,'English','USA','PG-13','Watched',3,'2026-01-10',NULL,8.5,'Fun, heartfelt, and the soundtrack is perfect.','Disney+','Medium',1),
('Avengers: Age of Ultron','The Avengers face Ultron, an AI that decides humanity must be wiped out.',2015,'Action','Joss Whedon','Robert Downey Jr., Chris Hemsworth, Mark Ruffalo',141,'English','USA','PG-13','Watched',1,'2026-01-10',NULL,6.5,NULL,'Disney+','Low',0),
('Ant-Man','Thief Scott Lang inherits a suit that shrinks him and must pull off a world-saving heist.',2015,'Comedy','Peyton Reed','Paul Rudd, Michael Douglas, Evangeline Lilly',117,'English','USA','PG-13','Watched',1,'2026-01-12',NULL,7.0,NULL,'Disney+','Low',0),
('Captain America: Civil War','Heroes split into two camps over government oversight, leading to a fight between friends.',2016,'Action','Anthony Russo, Joe Russo','Chris Evans, Robert Downey Jr., Tom Holland',147,'English','USA','PG-13','Watched',3,'2026-01-12',NULL,9.0,'The airport scene alone is worth the watch.','Disney+','High',1),
('Doctor Strange','A brilliant surgeon discovers the mystic arts after a career-ending accident.',2016,'Fantasy','Scott Derrickson','Benedict Cumberbatch, Chiwetel Ejiofor, Tilda Swinton',115,'English','USA','PG-13','Watched',2,'2026-01-14',NULL,8.0,'Visually stunning, the mirror dimension is wild.','Disney+','Medium',0),
('Guardians of the Galaxy Vol. 2','The Guardians uncover the truth about Peter Quill''s father.',2017,'Sci-Fi','James Gunn','Chris Pratt, Zoe Saldana, Kurt Russell',136,'English','USA','PG-13','Watched',1,'2026-01-14',NULL,7.5,NULL,'Disney+','Medium',0),
('Spider-Man: Homecoming','Peter Parker balances high school life with being a hero under Tony Stark''s watch.',2017,'Action','Jon Watts','Tom Holland, Michael Keaton, Zendaya',133,'English','USA','PG-13','Watched',2,'2026-01-16',NULL,8.0,'Michael Keaton as the Vulture is a great villain.','Netflix','Medium',0),
('Thor: Ragnarok','Thor must escape a gladiator planet to stop Hela from destroying Asgard.',2017,'Comedy','Taika Waititi','Chris Hemsworth, Tom Hiddleston, Cate Blanchett',130,'English','USA','PG-13','Watched',3,'2026-01-16',NULL,9.0,'Hilarious and colorful. Waititi saved Thor.','Disney+','High',1),
('Black Panther','T''Challa returns to Wakanda to take the throne and faces a challenger from his past.',2018,'Action','Ryan Coogler','Chadwick Boseman, Michael B. Jordan, Lupita Nyong''o',134,'English','USA','PG-13','Watched',2,'2026-01-18',NULL,9.0,'Culturally huge and Killmonger is a top-tier villain.','Disney+','High',1),
('Avengers: Infinity War','The Avengers and Guardians battle Thanos as he hunts the Infinity Stones.',2018,'Action','Anthony Russo, Joe Russo','Robert Downey Jr., Josh Brolin, Chris Hemsworth',149,'English','USA','PG-13','Watched',3,'2026-01-18',NULL,9.5,'That ending still hurts.','Disney+','High',1),
('Captain Marvel','Carol Danvers becomes one of the universe''s most powerful heroes.',2019,'Sci-Fi','Anna Boden, Ryan Fleck','Brie Larson, Samuel L. Jackson, Jude Law',123,'English','USA','PG-13','Watched',1,'2026-01-20',NULL,6.5,NULL,'Disney+','Low',0),
('Avengers: Endgame','The surviving Avengers attempt to undo Thanos'' snap and restore the universe.',2019,'Action','Anthony Russo, Joe Russo','Robert Downey Jr., Chris Evans, Scarlett Johansson',181,'English','USA','PG-13','Watched',4,'2026-01-20',NULL,10.0,'Perfect payoff for 11 years of storytelling. Cried every time.','Disney+','High',1),
('Spider-Man: Far From Home','Peter Parker''s European vacation is interrupted by Nick Fury and Mysterio.',2019,'Action','Jon Watts','Tom Holland, Jake Gyllenhaal, Zendaya',129,'English','USA','PG-13','Watched',1,'2026-01-22',NULL,7.5,NULL,'Netflix','Medium',0),
('Spider-Man: No Way Home','Peter asks Doctor Strange to make the world forget his identity, opening the multiverse.',2021,'Action','Jon Watts','Tom Holland, Zendaya, Andrew Garfield',148,'English','USA','PG-13','Watched',3,'2026-01-22',NULL,9.5,'Pure fan service done right. The theater went crazy.','Netflix','High',1),
('Doctor Strange in the Multiverse of Madness','Doctor Strange teams up with America Chavez to battle threats across universes.',2022,'Fantasy','Sam Raimi','Benedict Cumberbatch, Elizabeth Olsen, Xochitl Gomez',126,'English','USA','PG-13','Watched',1,'2026-01-24',NULL,7.0,NULL,'Disney+','Medium',0),
('Black Panther: Wakanda Forever','Wakanda fights to protect itself after King T''Challa''s death.',2022,'Action','Ryan Coogler','Letitia Wright, Angela Bassett, Tenoch Huerta',161,'English','USA','PG-13','To Watch',0,'2026-02-01',NULL,NULL,NULL,'Disney+','Medium',0),
('Guardians of the Galaxy Vol. 3','The Guardians rally to protect Rocket and confront the High Evolutionary.',2023,'Sci-Fi','James Gunn','Chris Pratt, Bradley Cooper, Will Poulter',150,'English','USA','PG-13','Watched',1,'2026-02-03',NULL,9.0,'Emotional send-off for the team.','Disney+','High',1),
('Deadpool & Wolverine','Deadpool is recruited by the TVA and teams up with a reluctant Wolverine.',2024,'Comedy','Shawn Levy','Ryan Reynolds, Hugh Jackman, Emma Corrin',128,'English','USA','R','Watched',2,'2026-02-05',NULL,8.5,'Chaotic, funny, and full of cameos.','Disney+','High',1),

-- ===== POPULAR MOVIES =====
('The Shawshank Redemption','Two imprisoned men bond over years, finding solace and eventual redemption.',1994,'Drama','Frank Darabont','Tim Robbins, Morgan Freeman, Bob Gunton',142,'English','USA','R','Watched',2,'2026-02-08',NULL,10.0,'A perfect movie. Hope is a good thing.','Netflix','High',1),
('The Godfather','The aging patriarch of a crime dynasty transfers control to his reluctant son.',1972,'Crime','Francis Ford Coppola','Marlon Brando, Al Pacino, James Caan',175,'English','USA','R','Watched',1,'2026-02-08',NULL,9.5,NULL,'Paramount+','High',1),
('The Dark Knight','Batman faces the Joker, a criminal mastermind who pushes Gotham into chaos.',2008,'Action','Christopher Nolan','Christian Bale, Heath Ledger, Aaron Eckhart',152,'English','USA','PG-13','Watched',4,'2026-02-10',NULL,10.0,'Heath Ledger''s Joker is untouchable.','Max','High',1),
('Inception','A thief who steals secrets through dreams is given the task of planting an idea.',2010,'Sci-Fi','Christopher Nolan','Leonardo DiCaprio, Joseph Gordon-Levitt, Elliot Page',148,'English','USA','PG-13','Watched',3,'2026-02-10',NULL,9.0,'Mind-bending and rewatchable.','Netflix','High',1),
('Interstellar','Explorers travel through a wormhole to find a new home for humanity.',2014,'Sci-Fi','Christopher Nolan','Matthew McConaughey, Anne Hathaway, Jessica Chastain',169,'English','USA','PG-13','Watched',2,'2026-02-12',NULL,9.5,'The score and docking scene are incredible.','Paramount+','High',1),
('Pulp Fiction','Interlocking stories of hitmen, a boxer, and a gangster''s wife in Los Angeles.',1994,'Crime','Quentin Tarantino','John Travolta, Uma Thurman, Samuel L. Jackson',154,'English','USA','R','Watched',1,'2026-02-12',NULL,8.5,NULL,'Prime Video','Medium',0),
('Forrest Gump','A kind-hearted man with a low IQ witnesses and influences decades of American history.',1994,'Drama','Robert Zemeckis','Tom Hanks, Robin Wright, Gary Sinise',142,'English','USA','PG-13','Watched',2,'2026-02-14',NULL,8.5,NULL,'Paramount+','Medium',0),
('Fight Club','An insomniac office worker and a soap maker form an underground fight club.',1999,'Drama','David Fincher','Brad Pitt, Edward Norton, Helena Bonham Carter',139,'English','USA','R','Watched',1,'2026-02-14',NULL,8.5,NULL,'Hulu','Medium',0),
('The Matrix','A hacker learns reality is a simulation and joins a rebellion against its controllers.',1999,'Sci-Fi','Lana Wachowski, Lilly Wachowski','Keanu Reeves, Laurence Fishburne, Carrie-Anne Moss',136,'English','USA','R','Watched',3,'2026-02-16',NULL,9.0,'Changed action cinema forever.','Max','High',1),
('Gladiator','A betrayed Roman general seeks revenge as a gladiator against the corrupt emperor.',2000,'Action','Ridley Scott','Russell Crowe, Joaquin Phoenix, Connie Nielsen',155,'English','USA','R','Watched',1,'2026-02-16',NULL,8.5,NULL,'Paramount+','Medium',0),
('Titanic','A young couple from different classes fall in love aboard the doomed ship.',1997,'Romance','James Cameron','Leonardo DiCaprio, Kate Winslet, Billy Zane',194,'English','USA','PG-13','Watched',2,'2026-02-18',NULL,8.0,NULL,'Disney+','Low',0),
('Avatar','A paralyzed marine on Pandora is torn between following orders and protecting the Na''vi.',2009,'Sci-Fi','James Cameron','Sam Worthington, Zoe Saldana, Sigourney Weaver',162,'English','USA','PG-13','Watched',1,'2026-02-18',NULL,8.0,NULL,'Disney+','Low',0),
('Jurassic Park','Scientists and visitors are stalked by cloned dinosaurs at a theme park.',1993,'Adventure','Steven Spielberg','Sam Neill, Laura Dern, Jeff Goldblum',127,'English','USA','PG-13','Watched',2,'2026-02-20',NULL,9.0,'Still holds up. The T. rex scene is iconic.','Netflix','Medium',1),
('Parasite','A poor family schemes to infiltrate a wealthy household, with unexpected consequences.',2019,'Thriller','Bong Joon-ho','Song Kang-ho, Choi Woo-shik, Park So-dam',132,'Korean','South Korea','R','Watched',1,'2026-02-20',NULL,9.5,'Brilliant genre-bending script.','Hulu','High',1),
('Spirited Away','A girl wanders into a spirit world and must work in a bathhouse to free her parents.',2001,'Animation','Hayao Miyazaki','Rumi Hiiragi, Miyu Irino, Mari Natsuki',125,'Japanese','Japan','PG','Watched',2,'2026-02-22',NULL,9.5,'Pure imagination. Beautiful from start to finish.','Max','High',1),
('The Lion King','A young lion prince flees his kingdom after his father''s death, then returns to claim it.',1994,'Animation','Roger Allers, Rob Minkoff','Matthew Broderick, Jeremy Irons, James Earl Jones',88,'English','USA','G','Watched',3,'2026-02-22',NULL,9.0,NULL,'Disney+','Medium',0),
('Toy Story','A cowboy doll is threatened when a new spaceman toy becomes his owner''s favorite.',1995,'Animation','John Lasseter','Tom Hanks, Tim Allen, Don Rickles',81,'English','USA','G','Watched',2,'2026-02-24',NULL,8.5,NULL,'Disney+','Low',0),
('Star Wars: Episode IV - A New Hope','A farm boy joins a rebellion to rescue a princess and destroy the Death Star.',1977,'Sci-Fi','George Lucas','Mark Hamill, Harrison Ford, Carrie Fisher',121,'English','USA','PG','Watched',2,'2026-02-24',NULL,9.0,NULL,'Disney+','Medium',0),
('The Lord of the Rings: The Fellowship of the Ring','A hobbit and eight companions set out to destroy a powerful ring.',2001,'Fantasy','Peter Jackson','Elijah Wood, Ian McKellen, Viggo Mortensen',178,'English','New Zealand','PG-13','Watched',2,'2026-02-26',NULL,9.0,NULL,'Max','High',1),
('Harry Potter and the Sorcerer''s Stone','A boy discovers he is a wizard and begins his first year at Hogwarts.',2001,'Fantasy','Chris Columbus','Daniel Radcliffe, Emma Watson, Rupert Grint',152,'English','UK','PG','Watched',3,'2026-02-26',NULL,8.0,NULL,'Max','Low',0),
('Joker','A failed comedian descends into madness and becomes Gotham''s criminal icon.',2019,'Drama','Todd Phillips','Joaquin Phoenix, Robert De Niro, Zazie Beetz',122,'English','USA','R','Watched',1,'2026-03-01',NULL,8.0,NULL,'Max','Medium',0),
('Oppenheimer','The story of J. Robert Oppenheimer and the creation of the atomic bomb.',2023,'Drama','Christopher Nolan','Cillian Murphy, Emily Blunt, Robert Downey Jr.',180,'English','USA','R','Watched',1,'2026-03-01',NULL,9.0,'Cillian Murphy is phenomenal.','Prime Video','High',1),
('Dune','A noble family accepts stewardship of a desert planet whose spice is the most valuable substance.',2021,'Sci-Fi','Denis Villeneuve','Timothee Chalamet, Zendaya, Rebecca Ferguson',155,'English','USA','PG-13','Watched',1,'2026-03-03',NULL,8.5,NULL,'Max','Medium',0),
('Mad Max: Fury Road','In a desert wasteland, Furiosa and Max flee a tyrant in a high-speed chase.',2015,'Action','George Miller','Tom Hardy, Charlize Theron, Nicholas Hoult',120,'English','Australia','R','To Watch',0,'2026-03-05',NULL,NULL,NULL,'Max','High',0),
('Top Gun: Maverick','After thirty years, Maverick trains a new generation of elite pilots for a dangerous mission.',2022,'Action','Joseph Kosinski','Tom Cruise, Miles Teller, Jennifer Connelly',130,'English','USA','PG-13','To Watch',0,'2026-03-05',NULL,NULL,NULL,'Paramount+','Medium',0),
('Spider-Man: Into the Spider-Verse','Teen Miles Morales becomes Spider-Man and meets heroes from other dimensions.',2018,'Animation','Bob Persichetti, Peter Ramsey, Rodney Rothman','Shameik Moore, Jake Johnson, Hailee Steinfeld',117,'English','USA','PG','To Watch',0,'2026-03-07',NULL,NULL,NULL,'Netflix','High',0),
('Get Out','A young Black man visits his white girlfriend''s family estate and uncovers a disturbing secret.',2017,'Horror','Jordan Peele','Daniel Kaluuya, Allison Williams, Catherine Keener',104,'English','USA','R','To Watch',0,'2026-03-07',NULL,NULL,NULL,'Prime Video','Medium',0),
('Whiplash','A young drummer is pushed to the breaking point by a ruthless music instructor.',2014,'Drama','Damien Chazelle','Miles Teller, J.K. Simmons, Melissa Benoist',107,'English','USA','R','To Watch',0,'2026-03-09',NULL,NULL,NULL,'Netflix','Medium',0);

-- Insert only movies not already present for this developer.
INSERT INTO movies
(user_id, title, short_description, release_year, genre, director, `cast`,
 duration_minutes, `language`, country, age_rating, watch_status, watch_count,
 date_added, poster_url, user_rating, review, streaming_platform, priority, favorite)
SELECT
    @seed_uid, s.title, s.short_description, s.release_year, s.genre,
    s.director, s.`cast`, s.duration_minutes, s.`language`, s.country,
    s.age_rating, s.watch_status, s.watch_count, s.date_added, s.poster_url,
    s.user_rating, s.review, s.streaming_platform, s.priority, s.favorite
FROM seed_movies s
LEFT JOIN movies m
    ON m.user_id = @seed_uid
   AND m.title = s.title
   AND m.release_year = s.release_year
WHERE m.movie_id IS NULL;

DROP TEMPORARY TABLE seed_movies;

-- Show how many sample titles now exist for the developer account.
SELECT COUNT(*) AS seed_movies_found
FROM movies
WHERE user_id = @seed_uid;
