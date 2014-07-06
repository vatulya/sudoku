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
) DEFAULT CHARSET=utf8;

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
    multiplayer_id INT NOT NULL DEFAULT 0,
    difficulty_id INT NOT NULL,
    state INT NOT NULL DEFAULT 0,
    created DATETIME NOT NULL,
    started DATETIME NOT NULL,
    ended DATETIME DEFAULT NULL,
    duration INT NOT NULL,
    client_duration INT NOT NULL,
    parameters TEXT NOT NULL,
    rating INT DEFAULT NULL,
    hash VARCHAR(50) NOT NULL,
    updated TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    INDEX (user_id),
    INDEX (difficulty_id),
    INDEX (state),
    INDEX (created),
    INDEX (started),
    INDEX (duration),
    INDEX (rating),
    INDEX (hash)
);

CREATE TABLE IF NOT EXISTS sudoku_difficulties (
    id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    title VARCHAR(100) NOT NULL,
    hidden INT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX (code),
    INDEX (hidden)
) DEFAULT CHARSET=utf8;

INSERT IGNORE INTO sudoku_difficulties (id, code, title, hidden) VALUES
(-1, 'test', 'Тестовая', 1),
 (0, 'random', 'Случайная', 1),
 (1, 'practice', 'Практика', 0),
 (2, 'easy', 'Легкая', 0),
 (4, 'normal', 'Средняя', 0),
 (6, 'expert', 'Сложная', 0),
(10, 'nightmare', 'Эксперт', 0)
;

CREATE TABLE IF NOT EXISTS sudoku_difficulty_parameters (
    difficulty_id INT NOT NULL,
    open_cells VARCHAR(255) NOT NULL,
    start_rating INT NOT NULL,
    minimal_rating INT NOT NULL,
    penalty_per_second INT NOT NULL,
    PRIMARY KEY (difficulty_id)
);

INSERT IGNORE INTO sudoku_difficulty_parameters (difficulty_id, open_cells, start_rating, minimal_rating, penalty_per_second) VALUES
(-1, 78, 0, 0, 0),
 (0, '{"min": 35, "max": 45}', 0, 0, 0),
 (1, 50, 1000, 1000, 0),
 (2, 45, 2000, 1100, 3),
 (4, 40, 4000, 1900, 5),
 (6, 35, 8000, 2200, 12),
(10, 30, 16000, 4000, 17)
;

CREATE TABLE IF NOT EXISTS sudoku_logs (
    id INT NOT NULL AUTO_INCREMENT,
    game_id INT NOT NULL,
    created_microtime DECIMAL(26,6) NOT NULL,
    action_type VARCHAR(255) NOT NULL,
    new_parameters TEXT NOT NULL,
    old_parameters TEXT NOT NULL,
    PRIMARY KEY (id),
    INDEX (game_id),
    INDEX (created_microtime),
    INDEX (action_type)
);

CREATE TABLE IF NOT EXISTS sudoku_ratings (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    difficulty_id INT NOT NULL,
    position INT NOT NULL,
    rating INT NOT NULL,
    faster_game_hash VARCHAR(50) NOT NULL,
    faster_game_duration INT NOT NULL
    updated TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (user_id, difficulty),
    INDEX (user_id),
    INDEX (difficulty_id),
    INDEX (position),
    INDEX (rating),
    INDEX (faster_game_duration)
);

CREATE TABLE IF NOT EXISTS sudoku_multiplayer (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    difficulty_id INT NOT NULL,
    game_type ENUM('versus_bot', 'versus_player') NOT NULL,
    state INT NOT NULL DEFAULT 0,
    created DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX (user_id),
    INDEX (difficulty_id),
    INDEX (game_type),
    INDEX (state)
);