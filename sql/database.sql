CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    login VARCHAR(255) NOT NULL,
    password VARCHAR(64) NOT NULL,
    created DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX(email),
    INDEX(login)
);

CREATE TABLE IF NOT EXISTS users_other (
    id INT NOT NULL AUTO_INCREMENT,
    network VARCHAR(255) NOT NULL,
    network_id VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    login VARCHAR(255) DEFAULT NULL,
    created DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX(email),
    INDEX(login)
);