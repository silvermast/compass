<?php
/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/17/17
 * @package BlackMast Tasks
 */
require_once __DIR__ . '/../../core.php';

use core\api\Response;
use core\Format;
use models\Invoice;
use models\Task;

global $invoice;

if (!isset($_REQUEST['slug']))
    Response::init('Please provide a slug', 400)->send();

if (!$invoice = Invoice::findOne($_REQUEST))
    Response::init("Invoice not found", 404);

$tasks = Task::findMulti(['invoice_id' => $invoice->invoice_id]);

if (!$tasks || !count($tasks))
    Response::init("No tasks found for invoice '$invoice->title'", 404);

header('Pragma: public'); // required
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private', false);
header('Content-Transfer-Encoding: binary');
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . addslashes($invoice->title) . '.csv";');

$tmp_file = fopen('php://output', 'w+');

function formatRow($row) {
    $arr = get_object_vars($row);
    global $invoice;
    $arr['elapsed_hours'] = (strtotime($arr['end_time']) - strtotime($arr['start_time'])) / 3600;
    $arr['amount']        = $invoice->rate * $arr['elapsed_hours'];

    unset($arr['project'], $arr['invoice_title'], $arr['slug']);
    return $arr;
}

// output CSV
$column_labels = formatRow(reset($tasks));
$column_labels = array_map('core\\Format::snake_to_caps', array_keys($column_labels));
fputcsv($tmp_file, $column_labels);

$total_row                  = array_fill_keys($column_labels, '');
$total_row['Notes']         = 'TOTAL';
$total_row['Elapsed Hours'] = 0;
$total_row['Amount']        = 0;

foreach ($tasks as $task) {
    $row = formatRow($task);

    $total_row['Elapsed Hours'] += (double)$row['elapsed_hours'];
    $total_row['Amount']        += (double)$row['amount'];

    $row['amount'] = Format::money($row['amount']);
    fputcsv($tmp_file, (array)$row);
}

$total_row['Amount'] = Format::money($total_row['Amount']);
fputcsv($tmp_file, $total_row);

fclose($tmp_file);
die();

