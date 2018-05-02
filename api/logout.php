<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/17/17
 * @package BlackMast Tasks
 */
require_once __DIR__ . '/../core.php';

use core\Validator;
use core\api\Response;
use models\User;

try {
    if (!session_id())
        session_start();

    unset($_SESSION['user_id']);
    session_commit();
    Response::init('Successfully logged out')->send();

} catch (Exception $e) {
    Response::init($e->getMessage(), 401)->send();
}

