CREATE TABLE IF NOT EXISTS `#__rsform_advancephp` (
  `form_id` int(11) NOT NULL,
  `events_active` varchar(255) NOT NULL,
  `events_code` mediumtext NOT NULL,
  PRIMARY KEY (`form_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__rsform_advancephpstore` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;