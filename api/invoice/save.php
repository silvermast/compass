<?php
require_once __DIR__ . '/../../core.php';

use core\api\Response;
use models\Invoice;
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

$invoice = Invoice::new($_REQUEST)->save();

Task::updateMulti(['invoice_id' => $invoice->invoice_id], [
    'client_id' => $invoice->client_id,
    'client'    => $invoice->client,
]);

Response::init($invoice)->send();