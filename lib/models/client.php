<?php
namespace models;

use core;
use \Exception;

/**
 * @author Jason Wright <jason@silvermast.io>
 * @since 5/14/2018
 */
class Client extends core\Model {

    const ID    = 'client_id';
    const TABLE = 'client';

    public $client_id;
    public $slug;
    public $name;
    public $color;
    public $notes;
    public $date_added;

    /**
     * @return $this
     * @throws Exception
     */
    public function validate() {

        $v = core\Validator::init();
        $v->check_number($this->client_id, 'client_id', 'Client ID', false);
        $v->check_text($this->name, 'name', 'Name', 2, 255, true);
        $v->check_text($this->color, 'color', 'Color', 6, 6, false);
        $v->check_text($this->notes, 'notes', 'Notes', 0, pow(2, 32), false);
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