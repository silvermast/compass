<?php
/**
 * Updates the app
 */
require_once __DIR__ . '/../core.php';
use core\api\Response;

$root = ROOT;

$response   = [];
$response[] = core\Shell::exec("git --exec-path='$root' fetch --quiet origin")
$response[] = core\Shell::exec("git --exec-path='$root' reset --hard origin/master")

ob_start();
core\Update::init()->run();
$response[] = ob_get_contents();
ob_end_clean();

Response::init($response, 200)->send();