<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/17/17
 * @package BlackMast Tasks
 */
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

if (!isset($_REQUEST['slug']))
    Response::init('Please provide a slug', 400)->send();

if ($result = Client::findOne($_REQUEST))
    Response::init($result)->send();

Response::init("Client not found", 404);