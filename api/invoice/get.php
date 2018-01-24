<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/17/17
 * @package BlackMast Tasks
 */
require_once __DIR__ . '/../../core.php';

use core\api\Response;
use models\Invoice;

if (!isset($_REQUEST['slug']))
    Response::init('Please provide a slug', 400)->send();

if ($result = Invoice::findOne($_REQUEST))
    Response::init($result)->send();

Response::init("Invoice not found", 404);