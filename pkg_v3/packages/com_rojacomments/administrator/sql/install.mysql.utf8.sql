CREATE TABLE IF NOT EXISTS `#__roja_comments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL DEFAULT 0,
  `parent_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `guest_name` varchar(255) NOT NULL DEFAULT '',
  `guest_email` varchar(255) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `recommend_count` int unsigned NOT NULL DEFAULT 0,
  `reply_count` int unsigned NOT NULL DEFAULT 0,
  `ip_hash` varchar(64) NOT NULL DEFAULT '',
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `published` datetime NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_roja_comments_article` (`article_id`),
  KEY `idx_roja_comments_parent` (`parent_id`),
  KEY `idx_roja_comments_user` (`user_id`),
  KEY `idx_roja_comments_status` (`status`),
  KEY `idx_roja_comments_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__roja_comment_votes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `session_hash` varchar(64) NOT NULL DEFAULT '',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_roja_votes_unique` (`comment_id`, `user_id`, `session_hash`),
  KEY `idx_roja_votes_comment` (`comment_id`),
  KEY `idx_roja_votes_session` (`session_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__roja_comment_reports` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` int unsigned NOT NULL DEFAULT 0,
  `user_id` int unsigned NOT NULL DEFAULT 0,
  `reason` varchar(64) NOT NULL DEFAULT 'other',
  `description` varchar(500) NOT NULL DEFAULT '',
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_roja_reports_comment` (`comment_id`),
  KEY `idx_roja_reports_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
