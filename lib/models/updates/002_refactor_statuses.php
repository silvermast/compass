<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 1/22/18
 * @package compass.blackmast.org
 * @var \mysqli $db
 */
$db->query("UPDATE `invoice` SET status = LOWER(REPLACE(status, ' ', '_'))");