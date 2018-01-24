<?php

namespace core;

/**
 *
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 7/11/17
 * @package BlackMast Tasks
 */
class Format {

    /**
     * @param $path
     * @return mixed
     */
    public static function domain($path) {
        $path = trim($path);
        if (!preg_match('#^(\w+:)?//#', $path))
            return explode('/', $path, 2)[0];
        else
            return parse_url($path, PHP_URL_HOST);
    }

    /**
     * Adds or subtracts www from a root level domain
     * @param $domain
     * @return bool|string
     */
    public static function getW3Variant($domain) {
        if (substr($domain, 0, 4) === 'www.')
            return substr($domain, 4); // add non-www version
        elseif (substr_count($domain, '.') === 1)
            return "www.$domain"; // add www version

        return false;
    }

}