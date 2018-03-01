<?php
namespace core\api;
use models\User;

/**
 * Request class responsible for pulling POST data and sending a response
 * @author Jason Wright <jason@silvermast.io>
 * @since 3/7/15
 * @package BlackMast Tasks
 */

class Response {
    public $code;
    public $data;

    /**
     * @param $data
     * @param $code
     * @return Response
     */
    public static function init($data, $code = 200) {
        return new self($data, $code);
    }

    /**
     * Checks authentication
     */
    public static function checkAuth() {
        if (!$user = User::me())
            self::init("Please log in", 401)->send();
    }

    /**
     * Response constructor.
     * @param $data
     * @param int $code
     */
    public function __construct($data, $code = 200) {
        if ($data instanceof \Exception) {
            $this->data = $data->getMessage();
            $this->code = $data->getCode();

        } else {
            $this->data = $data;
            $this->code = $code;

        }
    }

    /**
     * Sends an http header, prints the content, and dies
     */
    public function send() {
        http_response_code($this->code);
        echo json_encode($this->data);
        die();
    }
}

