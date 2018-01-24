<?php
/**
 * Updates the app
 */
require_once __DIR__ . '/../core.php';
use core\api\Response;

$root = ROOT;

Response::init([
    core\Shell::exec("git --exec-path='$root' fetch --quiet origin"),
    core\Shell::exec("git --exec-path='$root' reset --hard origin/master"),
], 200)->send();