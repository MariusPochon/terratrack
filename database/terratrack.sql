-- ============================================================
-- TerraTrack – Database Schema
-- Project  : TPI CFC Informatique 2026
-- Author   : Marius Pochon
-- Version  : 1.1
-- Date     : 2026
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Drop tables (order matters for FK constraints)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `t_image`;
DROP TABLE IF EXISTS `t_coordinate`;
DROP TABLE IF EXISTS `t_observation`;
DROP TABLE IF EXISTS `t_category`;
DROP TABLE IF EXISTS `t_user`;

-- ============================================================
-- Table: t_user
-- Stores admin accounts (only 1 for TPI scope)
-- ============================================================
CREATE TABLE `t_user` (
    `pk_user`       INT          NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(50)  NOT NULL,
    `password`      VARCHAR(255) NOT NULL,  -- bcrypt hash
    PRIMARY KEY (`pk_user`),
    UNIQUE KEY `uq_user_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: t_category
-- Predefined categories for observations (no CRUD interface)
-- ============================================================
CREATE TABLE `t_category` (
    `pk_category`  INT          NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100) NOT NULL,
    `color`         VARCHAR(7)   NOT NULL,  -- hex color e.g. #FF5733
    `description`   TEXT         NULL,
    PRIMARY KEY (`pk_category`),
    UNIQUE KEY `uq_category_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: t_observation
-- Core table – stores all field records (points and zones)
-- ============================================================
CREATE TABLE `t_observation` (
    `pk_observation` INT                  NOT NULL AUTO_INCREMENT,
    `title`          VARCHAR(150)         NOT NULL,
    `description`    TEXT                 NULL,
    `type`           ENUM('point','zone') NOT NULL,
    `fk_category`   INT                  NOT NULL,
    `created_at`     DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`pk_observation`),
    CONSTRAINT `fk_observation_category`
        FOREIGN KEY (`fk_category`)
        REFERENCES `t_category` (`pk_category`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: t_coordinate
-- Stores lat/lng for both points (1 row) and zones (N rows)
-- order_index defines vertex order for polygon zones
-- ============================================================
CREATE TABLE `t_coordinate` (
    `pk_coordinate`  INT           NOT NULL AUTO_INCREMENT,
    `fk_observation` INT           NOT NULL,
    `latitude`       DECIMAL(10,7) NOT NULL,
    `longitude`      DECIMAL(10,7) NOT NULL,
    `order_index`    INT           NOT NULL DEFAULT 0,
    PRIMARY KEY (`pk_coordinate`),
    CONSTRAINT `fk_coordinate_observation`
        FOREIGN KEY (`fk_observation`)
        REFERENCES `t_observation` (`pk_observation`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: t_image
-- Stores file paths of photos linked to observations
-- ============================================================
CREATE TABLE `t_image` (
    `pk_image`       INT          NOT NULL AUTO_INCREMENT,
    `fk_observation` INT          NOT NULL,
    `file_path`      VARCHAR(500) NOT NULL,
    PRIMARY KEY (`pk_image`),
    CONSTRAINT `fk_image_observation`
        FOREIGN KEY (`fk_observation`)
        REFERENCES `t_observation` (`pk_observation`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Re-enable FK checks
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin user  (login: admin / password: admin123)
-- Hash generated with password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `t_user` (`username`, `password`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Predefined categories (labels in French, code in English)
INSERT INTO `t_category` (`name`, `color`, `description`) VALUES
('Faune sauvage',          '#E74C3C', 'Observation et habitat de la faune locale'),
('Flore protégée',         '#27AE60', 'Espèces végétales rares et forêts remarquables'),
('Patrimoine historique',  '#8E44AD', 'Châteaux, monuments et sites historiques'),
('Zones protégées',        '#2980B9', 'Réserves naturelles et espaces protégés'),
('Zones urbaines',         '#E67E22', 'Villes et agglomérations principales'),
('Patrimoine agricole',    '#F1C40F', 'Alpages traditionnels et fromageries');

