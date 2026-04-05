CREATE TABLE IF NOT EXISTS `notification_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `u_class` varchar(30) NOT NULL,
  `u_rollno` varchar(30) NOT NULL,
  `channel` varchar(20) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `status` varchar(30) NOT NULL,
  `response_message` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
