-- Only needed if you imported schema.sql BEFORE this file existed.
-- phpMyAdmin → partfoliyo → Import → this file. (A fresh import of schema.sql already includes all of it.)
USE `partfoliyo`;

ALTER TABLE `admins`
  ADD COLUMN `totp_secret`   VARCHAR(64)  NULL AFTER `password_hash`,
  ADD COLUMN `totp_enabled`  TINYINT(1)   NOT NULL DEFAULT 0 AFTER `totp_secret`,
  ADD COLUMN `last_login_at` DATETIME     NULL AFTER `totp_enabled`;

ALTER TABLE `login_attempts`
  ADD COLUMN `username` VARCHAR(50) NOT NULL DEFAULT '' AFTER `ip`,
  ADD KEY `login_attempts_user_time_index` (`username`, `attempted_at`);

ALTER TABLE `api_tokens`
  ADD COLUMN `remember`   TINYINT(1)   NOT NULL DEFAULT 0 AFTER `last_used_at`,
  ADD COLUMN `ip`         VARCHAR(45)  NOT NULL DEFAULT '' AFTER `remember`,
  ADD COLUMN `user_agent` VARCHAR(255) NOT NULL DEFAULT '' AFTER `ip`;

ALTER TABLE `projects`
  ADD COLUMN `preview_mode` ENUM('image','live') NOT NULL DEFAULT 'image' AFTER `layout`;

CREATE TABLE IF NOT EXISTS `auth_challenges` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`   INT UNSIGNED NOT NULL,
  `token_hash` CHAR(64)     NOT NULL,
  `attempts`   INT UNSIGNED NOT NULL DEFAULT 0,
  `remember`   TINYINT(1)   NOT NULL DEFAULT 0,
  `expires_at` DATETIME     NOT NULL,
  `created_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `auth_challenges_hash_unique` (`token_hash`),
  CONSTRAINT `auth_challenges_admin_fk` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `auth_logs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`   INT UNSIGNED NULL,
  `username`   VARCHAR(50)  NOT NULL DEFAULT '',
  `event`      VARCHAR(30)  NOT NULL,
  `ip`         VARCHAR(45)  NOT NULL DEFAULT '',
  `user_agent` VARCHAR(255) NOT NULL DEFAULT '',
  `created_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `auth_logs_admin_time_index` (`admin_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE `projects` SET `preview_mode` = 'live' WHERE `live_url` IS NOT NULL AND `live_url` <> '';
