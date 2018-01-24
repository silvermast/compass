<?php
/**
 * Core Include
 * @author Jason Wright <jason@silvermast.io>
 * @since Feb 18, 2015
 * @copyright 2015 Jason Wright
 */
const ROOT = __DIR__;

ini_set('error_log', __DIR__ . '/log/error.log');
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_STRICT);

// check php version
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 70000) {
    echo 'BMTasks requires PHP Version 7.0 or above.';
    die();
}

/**
 * Auto-load classes with default php autoloader
 */
$paths = [
    get_include_path(),
    __DIR__ . '/lib',
];
set_include_path(implode(PATH_SEPARATOR, $paths));
spl_autoload_extensions('.php');
spl_autoload_register('spl_autoload');

$config = core\Config::init();

if (isset($config->ini))
    foreach ($config->ini as $key => $value)
        ini_set($key, $value);

if (isset($config->error_reporting))
    error_reporting($config->error_reporting);

if (isset($config->timezone))
    date_default_timezone_set($config->timezone);