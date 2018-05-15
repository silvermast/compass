<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 5/14/2018
 * @package compass.blackmast.org
 * @var \mysqli $db
 */

$db->query("ALTER TABLE `client` ADD COLUMN `notes` mediumtext DEFAULT NULL AFTER `color`");