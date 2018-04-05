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

switch ($user->perm_level) {
    case User::PERMLEVELS['Owner']:
    case User::PERMLEVELS['Employee']:
        break;

    case User::PERMLEVELS['Patron']:
    default:
        Response::init('Invalid User', 401)->send();
}

if (!isset($_REQUEST['slug']))
    Response::init('Please provide a slug', 400)->send();

if (!$task = Task::findOne(['slug' => $_REQUEST['slug']]))
    Response::init("Task not found", 404)->send();

$task->delete();

Response::init("Successfully deleted the task.")->send();