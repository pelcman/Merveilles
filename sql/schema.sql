-- Merveilles Database Schema (MySQL 5.7+ / MariaDB 10.2+)
-- Modernized: proper types, primary key, password hash support

CREATE DATABASE IF NOT EXISTS `merveilles`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `merveilles`;

-- Players
DROP TABLE IF EXISTS `players`;
CREATE TABLE `players` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `mv_name`           VARCHAR(3)   NOT NULL UNIQUE,
    `mv_password`       VARCHAR(255) NOT NULL,           -- bcrypt hash
    `x`                 INT          NOT NULL DEFAULT 27,
    `y`                 INT          NOT NULL DEFAULT 41,
    `floor`             INT          NOT NULL DEFAULT 1,
    `max_floor`         INT          NOT NULL DEFAULT 10,
    `xp`                INT          NOT NULL DEFAULT 0,
    `hp`                INT          NOT NULL DEFAULT 30,
    `mp`                INT          NOT NULL DEFAULT 30,
    `kill`              INT          NOT NULL DEFAULT 0,
    `save`              INT          NOT NULL DEFAULT 0,
    `message`           VARCHAR(255) DEFAULT NULL,
    `message_timestamp` INT          NOT NULL DEFAULT 0,
    `avatar_head`       TINYINT      NOT NULL DEFAULT 2,
    `avatar_body`       TINYINT      NOT NULL DEFAULT 2,
    `warp1`             TINYINT      NOT NULL DEFAULT 0,
    `warp2`             TINYINT      NOT NULL DEFAULT 0,
    `warp3`             TINYINT      NOT NULL DEFAULT 0,
    `warp4`             TINYINT      NOT NULL DEFAULT 0,
    `mv_time`           INT          NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX `idx_floor` (`floor`),
    INDEX `idx_online` (`mv_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Monsters (active state per floor)
DROP TABLE IF EXISTS `monsters`;
CREATE TABLE `monsters` (
    `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `x`      INT          NOT NULL,
    `y`      INT          NOT NULL,
    `health` INT          NOT NULL DEFAULT 100,
    `time`   INT          NOT NULL DEFAULT 0,
    `floor`  INT          NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_floor` (`floor`),
    INDEX `idx_position` (`floor`, `x`, `y`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Special tiles (portals, events)
DROP TABLE IF EXISTS `specials`;
CREATE TABLE `specials` (
    `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `x`        INT          NOT NULL,
    `y`        INT          NOT NULL,
    `message`  VARCHAR(255) DEFAULT NULL,
    `to_floor` INT          NOT NULL DEFAULT 0,
    `to_x`     INT          NOT NULL DEFAULT 0,
    `to_y`     INT          NOT NULL DEFAULT 0,
    `image`    VARCHAR(64)  DEFAULT NULL,
    `floor`    INT          NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_floor` (`floor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
