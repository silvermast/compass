<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 1/17/18
 * @package compass.blackmast.org
 * @var \mysqli $db
 */

foreach (models\Invoice::findMulti([]) as $invoice)
    if (!$invoice->slug)
        $invoice->save();

foreach (models\Task::findMulti([]) as $task)
    if (!$task->slug)
        $task->save();