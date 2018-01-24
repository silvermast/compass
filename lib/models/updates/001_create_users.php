<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 1/17/18
 * @package compass.blackmast.org
 * @var \mysqli $db
 */

$db->query("CREATE TABLE IF NOT EXISTS `user` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `client` varchar(255) DEFAULT NULL,
  `perm_level` int unsigned NOT NULL, 
  `passhash` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");