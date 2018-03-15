<?php
namespace models;

use core;
use \Exception;

/**
 * @author Jason Wright <jason@silvermast.io>
 * @since 1/17/2018
 */
class Invoice extends core\Model {

    const ID    = 'invoice_id';
    const TABLE = 'invoice';

    const STATUSES = [
        'sent'        => 'Sent',
        'paid'        => 'Paid',
        'in_progress' => 'In Progress',
    ];

    public $invoice_id;
    public $slug;
    public $title;
    public $notes;
    public $client;
    public $rate;
    public $status;
    public $date_added;

    /**
     * @return $this
     * @throws Exception
     */
    public function validate() {

        $v = core\Validator::init();
        $v->check_number($this->invoice_id, 'invoice_id', 'Invoice ID', false);
        $v->check_text($this->title, 'title', 'Title', 2, 255, true);
        $v->check_text($this->notes, 'notes', 'Notes', 0, pow(2, 32), true);
        $v->check_text($this->client, 'client', 'Client', 2, 255, true);
        $v->check_number($this->rate, 'rate', 'Rate', true);
        $v->check_list($this->status, 'status', self::STATUSES, true);
        $v->done();

        return $this;
    }

    /**
     * @return static
     */
    public function save() {
        if (empty($this->date_added))
            $this->date_added = date(DATE_ATOM);

        if (!$this->slug) {
            do {
                $this->slug = self::generateId();

            } while (self::count(['slug' => $this->slug]) > 0);
        }

        return parent::save();
    }

}