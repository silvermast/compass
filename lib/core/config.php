<?php

namespace core;

/**
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/11/17
 * @package BlackMast Tasks
 *
 * @property mixed *
 */
class Config {
    use Singleton;

    /**
     * Config constructor.
     */
    public function __construct() {
        if (!$json = json_decode(file_get_contents(ROOT . '/config.json')))
            Debug::error("Failed to parse config.json: " . json_last_error_msg());

        foreach ($json as $key => $value)
            $this->$key = $value;
    }

    public function save() {
        @file_put_contents(ROOT . '/config.json', json_encode($this, JSON_PRETTY_PRINT));
    }

}