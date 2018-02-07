<?php
require_once __DIR__ . '/../../core.php';

use core\api\Response;
use models\Invoice;
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

$results = array_values(Invoice::findMulti($_REQUEST, ['sort' => ['status' => 1, 'date_added' => -1, 'invoice_id' => -1]]));

Response::init($results)->send();