<?php
namespace models;

use core;
use \Exception;

/**
 * @author Jason Wright <jason@silvermast.io>
 * @since 1/4/17
 * @package BlackMast Tasks
 */
class User extends core\Model {

    const ID    = 'user_id';
    const TABLE = 'user';

    const PERMLEVELS = [
        'Owner'    => 1,
        'Employee' => 10,
        'Patron'   => 100,
    ];

    public $user_id;
    public $client;
    public $name;
    public $email;
    public $perm_level = 10;
    public $passhash;

    /** @var User */
    private static $_me = null;

    /**
     * @return $this
     * @throws Exception
     */
    public function validate() {

        $v = core\Validator::init();
        $v->check_number($this->user_id, 'user_id', 'User ID', false);
        $v->check_text($this->client, 'client', 'Client', 2, 255, false);
        $v->check_text($this->name, 'name', 'Name', 2, 255, true);
        $v->check_email($this->email, 'email', 'Email', true);
        $v->done();

        return $this;
    }

    public function authenticate() {
        if (!session_id())
            session_start();

        $_SESSION['user_id'] = $this->user_id;
    }

    /**
     * Returns the authenticated user
     * @return User|null
     */
    public static function me() {
        return new self(); /* @todo implement login */

        if (!isset($_SESSION['user_id']))
            return null;

        if (!self::$_me instanceof self)
            self::$_me = self::findOne(['id' => $_SESSION['user_id']]);

        return self::$_me;
    }

    /**
     * @param $password
     * @return mixed
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Hashes the password and sets it into the object
     * @param string $password plaintext
     * @return self
     * @throws Exception
     */
    public function setPassword($password) {
        if (mb_strlen($password) < 12) throw new Exception('Passwords must be at least 12 characters. Type whatever you\'d like, though!');
        $this->passhash = self::hashPassword($password);
        return $this;
    }

}