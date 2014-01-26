CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    login VARCHAR(255) NOT NULL,
    password VARCHAR(64) NOT NULL,
    created DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX (email),
    INDEX (login)
);

CREATE TABLE IF NOT EXISTS users_other (
    id INT NOT NULL AUTO_INCREMENT,
    network VARCHAR(255) NOT NULL,
    network_id VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    login VARCHAR(255) DEFAULT NULL,
    created DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX (email),
    INDEX (login)
);

CREATE TABLE IF NOT EXISTS games (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    game_code VARCHAR(255) NOT NULL,
    created DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX (user_id),
    INDEX (game_code),
    INDEX (created)
);

CREATE TABLE IF NOT EXISTS games_sudoku (
    id INT NOT NULL AUTO_INCREMENT,
    game_id INT NOT NULL,
    started DATETIME NOT NULL,
    ended DATETIME DEFAULT NULL,
    duration INT NOT NULL,
    parameters TEXT NOT NULL,
    PRIMARY KEY (id),
    INDEX (game_id),
    INDEX (started),
    INDEX (duration)
);
