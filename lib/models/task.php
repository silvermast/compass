<?php

namespace models;

use core;
use \Exception;

/**
 * @author Jason Wright <jason@silvermast.io>
 * @since 1/17/2018
 */
class Task extends core\Model {

    const ID    = 'task_id';
    const TABLE = 'task';

    public $task_id;
    public $slug;
    public $title;
    public $project;
    public $client;
    public $invoice_id;
    public $invoice_title;
    public $start_time;
    public $end_time;
    public $notes;

    /**
     * @return $this
     * @throws Exception
     */
    public function validate() {

        $v = core\Validator::init();
        $v->check_number($this->task_id, 'task_id', 'Task ID', false);
        $v->check_text($this->title, 'title', 'title', 2, 255, false);
        $v->check_text($this->project, 'project', 'project', 2, 255, false);
        $v->check_text($this->client, 'client', 'client', 2, 255, false);
        $v->check_text($this->invoice_title, 'invoice_title', 'invoice_title', 2, 255, false);
        $v->check_date($this->start_time, 'start_time', 'start_time', false);
        $v->check_date($this->end_time, 'end_time', 'end_time', false);
        $v->check_text($this->notes, 'notes', 'notes', 2, pow(10, 7), false);
        $v->done();

        return $this;
    }

    /**
     * @return self
     */
    public function save() {
        if (!$this->slug) {
            do {
                $this->slug = self::generateId();

            } while (self::count(['slug' => $this->slug]) > 0);
        }

        if (!$this->invoice_id && $this->invoice_title)
            $this->invoice_id = Invoice::findOne(['title' => $this->invoice_title])->invoice_id;

        return parent::save();
    }

}