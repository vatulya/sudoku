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

CREATE TABLE IF NOT EXISTS user_sessions (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(255),
    ip VARCHAR(25) NOT NULL,
    created DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX (user_id),
    INDEX (session_id),
    INDEX (created)
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
    client_duration INT NOT NULL,
    parameters TEXT NOT NULL,
    hash VARCHAR(50) NOT NULL,
    updated TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    INDEX (user_id),
    INDEX (state),
    INDEX (created),
    INDEX (started),
    INDEX (duration),
    INDEX (hash)
);

CREATE TABLE IF NOT EXISTS sudoku_logs (
    id INT NOT NULL AUTO_INCREMENT,
    game_id INT NOT NULL,
    created TIMESTAMP NOT NULL,
    action_type VARCHAR(255) NOT NULL,
    new_parameters TEXT NOT NULL,
    old_parameters TEXT NOT NULL,
    PRIMARY KEY (id),
    INDEX (game_id),
    INDEX (created),
    INDEX (action_type)
);

CREATE TABLE IF NOT EXISTS sudoku_ratings (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    difficulty INT NOT NULL,
    position INT NOT NULL,
    rating INT NOT NULL,
    updated TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    INDEX (user_id),
    INDEX (difficulty),
    INDEX (rating)
);