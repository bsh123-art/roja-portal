CREATE TABLE IF NOT EXISTS `#__roja_comments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `article_id` INT UNSIGNED NOT NULL,
  `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `guest_name` VARCHAR(120) NOT NULL DEFAULT '',
  `guest_email` VARCHAR(190) NOT NULL DEFAULT '',
  `comment` TEXT NOT NULL,
  `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
  `recommend_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `reply_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `ip_hash` CHAR(64) NOT NULL DEFAULT '',
  `user_agent` VARCHAR(255) NOT NULL DEFAULT '',
  `created` DATETIME NOT NULL,
  `modified` DATETIME NOT NULL,
  `published` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_article` (`article_id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__roja_comment_votes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comment_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `session_hash` CHAR(64) NOT NULL DEFAULT '',
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_vote_identity` (`comment_id`, `user_id`, `session_hash`),
  KEY `idx_vote_comment` (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__roja_comment_reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comment_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `reason` VARCHAR(40) NOT NULL DEFAULT 'other',
  `description` VARCHAR(500) NOT NULL DEFAULT '',
  `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_report_comment` (`comment_id`),
  KEY `idx_report_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
