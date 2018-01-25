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
    $v = core\Validator::init();
    $v->check_email($_POST['email'], 'email', 'Email', true);
    $v->check_text($_POST['pass'], 'pass', 'Password', 12, 255, true);
    $v->done();

    if (!$user = User::findOne(['email' => $_POST['email']]))
        throw new Exception("Email or Password is incorrect", 401);

    if (!password_verify($_POST['pass'], $user->passhash))
        throw new Exception("Email or Password is incorrect", 401);

    $user->authenticate();
    Response::init($user)->send();

} catch (Exception $e) {
    Response::init($e->getMessage(), 401)->send();
}

