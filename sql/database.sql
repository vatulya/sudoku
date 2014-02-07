CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT,
    role_id INT NOT NULL DEFAULT 0,
    email VARCHAR(255) NOT NULL,
    login VARCHAR(255) NOT NULL,
    network VARCHAR(255) NOT NULL,
    network_id VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL DEFAULT 'mr. Anonymous',
    password VARCHAR(64) NOT NULL,
    created DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX (email),
    INDEX (login)
);

CREATE TABLE IF NOT EXISTS sudoku_games (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    state INT NOT NULL DEFAULT 0,
    difficulty INT NOT NULL,
    created DATETIME NOT NULL,
    started DATETIME NOT NULL,
    ended DATETIME DEFAULT NULL,
    duration INT NOT NULL,
    parameters TEXT NOT NULL,
    PRIMARY KEY (id),
    INDEX (user_id),
    INDEX (state),
    INDEX (created),
    INDEX (started),
    INDEX (duration)
);
