<?php
require_once __DIR__ . '/../../core.php';

use core\api\Response;
use models\Task;
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
        $query['client'] = User::me()->client;
        break;

    default:
        Response::init('Invalid User', 401)->send();
}

$results = array_values(Task::findMulti($_REQUEST, ['sort' => ['start_time' => -1, 'task_id' => -1]]));

Response::init($results)->send();