<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 1/17/18
 * @package compass.blackmast.org
 * @var \mysqli $db
 */

$db->query("ALTER TABLE `invoice` ADD COLUMN `slug` VARCHAR(255) DEFAULT NULL AFTER `invoice_id`");
$db->query("ALTER TABLE `task` ADD COLUMN `slug` VARCHAR(255) DEFAULT NULL AFTER `task_id`");