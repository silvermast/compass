<?php
/**
 * Updates the app
 */
require_once __DIR__ . '/../core.php';
use core\api\Response;

$root = ROOT;

ob_start();
core\Update::init()->run();
$update = ob_get_contents();
ob_end_clean();

Response::init([
    core\Shell::exec("git --exec-path='$root' fetch --quiet origin"),
    core\Shell::exec("git --exec-path='$root' reset --hard origin/master"),
    $update,
], 200)->send();