-- Portfolio admin database.
-- phpMyAdmin: "Import" bo'limida shu faylni tanlang (baza avtomatik yaratiladi).
-- MySQL 5.7+ / 8.x, MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS `partfoliyo`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `partfoliyo`;

-- ─── Admins ───
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  -- Two-factor authentication (TOTP, e.g. Google Authenticator)
  `totp_secret`   VARCHAR(64)  NULL,
  `totp_enabled`  TINYINT(1)   NOT NULL DEFAULT 0,
  `last_login_at` DATETIME     NULL,
  `created_at`    DATETIME     NOT NULL,
  `updated_at`    DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Only a SHA-256 hash of each token is stored, so a leaked database can't be used to log in
CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`     INT UNSIGNED NOT NULL,
  `token_hash`   CHAR(64)     NOT NULL,
  `expires_at`   DATETIME     NOT NULL,
  `last_used_at` DATETIME     NULL,
  -- 'Remember me' sessions last longer and aren't ended by a short idle period
  `remember`     TINYINT(1)   NOT NULL DEFAULT 0,
  `ip`           VARCHAR(45)  NOT NULL DEFAULT '',
  `user_agent`   VARCHAR(255) NOT NULL DEFAULT '',
  `created_at`   DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_tokens_hash_unique` (`token_hash`),
  KEY `api_tokens_expires_index` (`expires_at`),
  CONSTRAINT `api_tokens_admin_fk` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed logins (wrong password or 2FA code), counted per IP and per username to lock out guessing.
-- Unknown usernames are counted the same way, so lockouts don't reveal which accounts exist.
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`           VARCHAR(45)  NOT NULL,
  `username`     VARCHAR(50)  NOT NULL DEFAULT '',
  `attempted_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login_attempts_ip_time_index` (`ip`, `attempted_at`),
  KEY `login_attempts_user_time_index` (`username`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password was right but a 2FA code is still needed: this short-lived ticket links the two steps
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

-- Security log: logins, failures, lockouts, password and 2FA changes (shown in the admin profile)
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

-- ─── Projects ───
CREATE TABLE IF NOT EXISTS `projects` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title_uz`     VARCHAR(150) NOT NULL,
  `title_en`     VARCHAR(150) NOT NULL DEFAULT '',
  `title_ru`     VARCHAR(150) NOT NULL DEFAULT '',
  `desc_uz`      TEXT         NULL,
  `desc_en`      TEXT         NULL,
  `desc_ru`      TEXT         NULL,
  `image`        VARCHAR(255) NULL COMMENT 'Path relative to backend/public',
  `image_fit`    ENUM('cover','contain') NOT NULL DEFAULT 'cover',
  `github_url`   VARCHAR(255) NULL,
  `live_url`     VARCHAR(255) NULL,
  `layout`       ENUM('featured','regular','wide') NOT NULL DEFAULT 'regular',
  -- 'live' shows the running site (live_url) in the card instead of the image
  `preview_mode` ENUM('image','live') NOT NULL DEFAULT 'image',
  `sort_order`   INT          NOT NULL DEFAULT 0,
  `is_published` TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`   DATETIME     NOT NULL,
  `updated_at`   DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_published_order_index` (`is_published`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Contact form messages ───
CREATE TABLE IF NOT EXISTS `messages` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `subject`       VARCHAR(150) NOT NULL DEFAULT '',
  `message`       TEXT         NOT NULL,
  `lang`          CHAR(2)      NOT NULL DEFAULT 'uz',
  `ip`            VARCHAR(45)  NOT NULL DEFAULT '',
  `user_agent`    VARCHAR(255) NOT NULL DEFAULT '',
  `is_read`       TINYINT(1)   NOT NULL DEFAULT 0,
  `email_sent`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`    DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_read_index` (`is_read`),
  KEY `messages_ip_time_index` (`ip`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Starting content: the projects that were hard-coded on the site ───
INSERT INTO `projects`
  (`title_uz`, `title_en`, `title_ru`, `desc_uz`, `desc_en`, `desc_ru`, `image`, `image_fit`, `github_url`, `live_url`, `layout`, `preview_mode`, `sort_order`, `is_published`, `created_at`, `updated_at`)
SELECT t_uz, t_en, t_ru, d_uz, d_en, d_ru, img, fit, gh, live, lay, IF(live IS NULL, 'image', 'live'), ord, 1, NOW(), NOW() FROM (
  SELECT
    'Ta''lim platformasi' AS t_uz, 'Education Platform' AS t_en, 'Образовательная платформа' AS t_ru,
    'Kurslar va testlar bilan onlayn ta''lim platformasi.' AS d_uz,
    'Online learning platform with courses and quizzes.' AS d_en,
    'Онлайн-платформа для обучения с курсами и тестами.' AS d_ru,
    'uploads/seed/education.webp' AS img, 'cover' AS fit,
    'https://github.com/RizaSoft-Group/riza-edu' AS gh, NULL AS live, 'featured' AS lay, 1 AS ord
  UNION ALL SELECT
    'Yangiliklar veb-sayti', 'News Website', 'Новостной сайт',
    'So''nggi maqolalar va toifalar bilan moslashuvchan yangiliklar veb-sayti.',
    'Responsive news website with latest articles and categories.',
    'Адаптивный новостной сайт с последними статьями и категориями.',
    'uploads/seed/news.avif', 'cover', 'https://github.com/Shaxzod-hp/Shaxzod', NULL, 'regular', 2
  UNION ALL SELECT
    'Ta''lim CRM tizimi', 'Education CRM System', 'CRM система для образования',
    'Ta''lim muassasalari uchun talabalar va kurslarni boshqarish uchun CRM tizimi.',
    'CRM system for educational institutions to manage students and courses.',
    'CRM система для учебных заведений для управления студентами и курсами.',
    'uploads/seed/crm.webp', 'contain', 'https://github.com/Shaxzod-hp/Iso-Uz', 'https://shaxzod-hp.github.io/Iso-Uz/#/access', 'regular', 3
  UNION ALL SELECT
    'Onlayn bozor', 'Online Market', 'Онлайн рынок',
    'Onlayn mahsulot sotib olish va sotish uchun elektron tijorat platformasi.',
    'E-commerce platform for buying and selling products online.',
    'Платформа электронной коммерции для покупки и продажи товаров онлайн.',
    'uploads/seed/market.webp', 'cover', 'https://github.com/Shaxzod-hp/Shaxzod', NULL, 'wide', 4
  UNION ALL SELECT
    'Admin paneli', 'Admin Dashboard', 'Панель администратора',
    'Grafiklar va tahlillar bilan zamonaviy admin paneli UI.',
    'Modern admin dashboard UI with charts and analytics.',
    'Современный интерфейс панели администратора с графиками и аналитикой.',
    'uploads/seed/admin.webp', 'contain', NULL, NULL, 'regular', 5
) AS seed
-- Re-importing the file must not duplicate the starter projects
WHERE NOT EXISTS (SELECT 1 FROM `projects`);
