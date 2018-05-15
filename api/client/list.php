<?php
require_once __DIR__ . '/../../core.php';

use core\api\Response;
use models\Client;
use models\User;

/**
 * Requires login
 */
if (!$user = User::me())
    Response::init("Please log in", 401)->send();

$query = [];

switch ($user->perm_level) {
    case User::PERMLEVELS['Owner']:
    case User::PERMLEVELS['Employee']:
        break;

    case User::PERMLEVELS['Patron']:
    default:
        Response::init('Invalid User', 401)->send();
}

$results = array_values(Client::findMulti($_REQUEST, ['sort' => ['name' => 1]]));

Response::init($results)->send();