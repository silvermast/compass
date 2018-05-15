<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 5/14/18
 * @package compass.blackmast.org
 * @var \mysqli $db
 */

$db->query("CREATE TABLE IF NOT EXISTS `client` (
  `client_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->query("ALTER TABLE `invoice` ADD COLUMN `client_id` bigint(20) unsigned DEFAULT NULL AFTER `client`");
$db->query("ALTER TABLE `task` ADD COLUMN `client_id` bigint(20) unsigned DEFAULT NULL AFTER `client`");

$results = $db->query("SELECT DISTINCT `client` FROM `invoice` WHERE `client` IS NOT NULL");
while ($row = $results->fetch_object()) {
    $name = $db->escape_string($row->client);
    $slug = core\Model::generateId();
    $db->query("INSERT INTO `client` (`slug`, `name`) VALUES ('$slug', '$name')");
}

$db->query("UPDATE `task` LEFT JOIN `client` ON `task`.`client` = `client`.`name` SET `task`.`client_id` = `client`.`client_id`");
$db->query("UPDATE `invoice` LEFT JOIN `client` ON `invoice`.`client` = `client`.`name` SET `invoice`.`client_id` = `client`.`client_id`");