<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/17/17
 * @package BlackMast Tasks
 */
require_once __DIR__ . '/../core.php';

use core\Validator;
use core\api\Response;

try {
    $v = core\Validator::init();
    $v->check_email($_REQUEST['email'], 'email', 'Email', true);
    $v->check_text($_REQUEST['pass'], 'pass', 'Password', 12, 255, true);
    $v->done();



} catch (Exception $e) {
    Response::init($e->getMessage(), $e->getCode())->send();
}