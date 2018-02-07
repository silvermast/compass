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

    $fh    = fopen($argv[1], 'r');
    $props = array_map('trim', fgetcsv($fh, 0, ';'));

    $i_DATE    = array_search('Date', $props);
    $i_CLIENT  = array_search('Client', $props);
    $i_RATE    = array_search('Rate', $props);
    $i_TASK    = array_search('Task', $props);
    $i_PROJECT = array_search('Project', $props);
    $i_CLIENT  = array_search('Client', $props);
    $i_START   = array_search('Start', $props);
    $i_FINISH  = array_search('Finish', $props);
    $i_NOTE    = array_search('Note', $props);

    $invoices = [];

    while ($row = fgetcsv($fh, 0, ';')) {

        $invoice_month = date('F Y', strtotime($row[$i_DATE]));
        if (!isset($invoices["$row[$i_CLIENT] $invoice_month"])) {
            $invoices["$row[$i_CLIENT] $invoice_month"] = Invoice::new([
                'title'      => $invoice_month,
                'client'     => $row[$i_CLIENT],
                'rate'       => $row[$i_RATE],
                'status'     => 'paid',
                'date_added' => date('Y-m-1', strtotime($row[$i_DATE])),
            ])->save();
        }
        $invoice = $invoices["$row[$i_CLIENT] $invoice_month"];

        Task::new([
            'title'         => $row[$i_TASK],
            'project'       => $row[$i_PROJECT],
            'client'        => $row[$i_CLIENT],
            'invoice_id'    => $invoice->invoice_id,
            'invoice_title' => $invoice_month,
            'start_time'    => date(DATE_ATOM, strtotime("$row[$i_DATE] $row[$i_START]")),
            'end_time'      => date(DATE_ATOM, strtotime("$row[$i_DATE] $row[$i_FINISH]")),
            'notes'         => $row[$i_NOTE],
        ])->save();

        echo '.';
    }

    echo "\nDone!\n";
    die(0);

} catch (Exception $e) {
    echo "\nFailed: " . $e->getMessage() . "\n";
    die(1);
}