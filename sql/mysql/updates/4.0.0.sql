CREATE TABLE IF NOT EXISTS `#__rsform_advancephp` (
  `form_id` int NOT NULL,
  `events_active` varchar(255) NOT NULL,
  `events_code` mediumtext NOT NULL,
  PRIMARY KEY (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__rsform_advancephpstore` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT 0,
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
