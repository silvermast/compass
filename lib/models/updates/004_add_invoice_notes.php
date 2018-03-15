<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 3/15/2018
 * @package compass.blackmast.org
 * @var \mysqli $db
 */

$db->query("ALTER TABLE `invoice` ADD COLUMN `notes` mediumtext DEFAULT NULL AFTER title");