<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/17/17
 * @package BlackMast Tasks
 */
require_once __DIR__ . '/../../core.php';

use core\api\Response;
use models\Task;

if (!isset($_REQUEST['slug']))
    Response::init('Please provide a slug', 400)->send();

if ($result = Task::findOne(['slug' => $_REQUEST['slug']]))
    Response::init($result)->send();

Response::init("Task not found", 404);