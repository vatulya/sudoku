CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    login VARCHAR(255) NOT NULL,
    password VARCHAR(64) NOT NULL,
    created DATETIME NOT NULL,
    PRIMARY KEY (id),
    INDEX(email),
    INDEX(login)
);