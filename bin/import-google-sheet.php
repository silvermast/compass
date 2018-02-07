<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 2/6/18
 * @package compass.blackmast.org
 */
require_once __DIR__ . '/../core.php';
use models\Task;
use models\Invoice;


try {
    if (!is_readable($argv[1]))
        throw new Exception("Failed to read '$argv[1]'");

    if (!isset($argv[2]))
        throw new Exception('Missing Invoice Title parameter');

    $fh    = fopen($argv[1], 'r');
    $props = array_map('trim', fgetcsv($fh));

    // field possibilities
    // Date,Tickets,Clock In,Clock Out,Elapsed,Total
    // Date,Tickets,Clock In,Clock Out,Elapsed,Total
    // Date,Tickets,Clock In,Clock Out,Elapsed,Total
    // Date,Task,Clock In,Clock Out,Elapsed,Total
    // Date,Task,Clock In,Clock Out,Elapsed,Total
    // Date,Task,Clock In,Clock Out,Elapsed,Total
    // Date,Task,Clock In,Clock Out,Elapsed,Total

    $i_DATE    = 0;
    $i_TASK    = 1;
    $i_START   = 2;
    $i_FINISH  = 3;

    $invoices = [];

    while ($row = fgetcsv($fh)) {

        if (!isset($invoice))
            $invoice = Invoice::new([
                'title'      => $argv[2],
                'client'     => 'Ivio',
                'rate'       => 50,
                'status'     => 'paid',
                'date_added' => date('Y-m-d', strtotime($row[$i_DATE])),
            ])->save();

        $task_data = [
            'title'         => $row[$i_TASK],
            'project'       => preg_split('/[- ]/', $row[$i_TASK])[0],
            'client'        => 'Ivio',
            'invoice_id'    => $invoice->invoice_id,
            'invoice_title' => $invoice->title,
            'start_time'    => date(DATE_ATOM, strtotime("$row[$i_DATE] $row[$i_START]")),
            'end_time'      => date(DATE_ATOM, strtotime("$row[$i_DATE] $row[$i_FINISH]")),
            'notes'         => 'Imported from Google Doc',
        ];
        Task::new($task_data)->save();

        echo '.';
    }

    echo "\nDone!\n";
    die(0);

} catch (Exception $e) {
    echo "\nFailed: " . $e->getMessage() . "\n";
    die(1);
}