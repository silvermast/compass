<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 3/8/2018
 * @package BlackMast Tasks
 */
require_once __DIR__ . '/../../core.php';

use core\api\Response;
use models\User;

if (!$user = User::me())
    Response::init("Please log in", 401)->send();

unset($user->passhash);
Response::init($user, 404);