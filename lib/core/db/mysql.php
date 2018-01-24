<?php
namespace core\db;

use \core;
use \Exception;
use \mysqli;

/**
 * Mysql class
 * @author Jason Wright <jason.dee.wright@gmail.com>
 * @since 2/9/17
 * @package BlackMast Tasks
 */
trait Mysql {

    /** @var \mysqli */
    protected static $_db;

    /**
     * Database factory.
     * @return mysqli
     * @throws Exception
     */
    protected static function _db() {
        if (self::$_db)
            return self::$_db;

        if (!$cfg = core\Config::init()->MySQL)
            throw new Exception("MySQL Connection information not found");

        $conn = new mysqli($cfg->host, $cfg->user, $cfg->pass, $cfg->db);
        if (!$conn || $conn->connect_errno)
            throw new Exception("MySQL Connection failed. $conn->connect_errno $conn->connect_error");

        return self::$_db = $conn;
    }

    /**
     * @param $str
     * @return string
     */
    public static function escape($str) {
        return $str === null ? 'NULL' : self::_db()->escape_string($str);
    }

    /**
     * @param $str
     * @return string
     */
    public static function escape_with_quotes($str) {
        return $str === null ? 'NULL' : '"' . self::_db()->escape_string($str) . '"';
    }

    /**
     * @param $sql
     * @param int $mode
     * @return bool|\mysqli_result
     */
    public static function query($sql, $mode = MYSQLI_STORE_RESULT) {
        core\Debug::info($sql);

        $query = self::_db()->query($sql, $mode);
        if (self::_db()->errno)
            throw new Exception(self::_db()->errno . ': ' . self::_db()->error);
        return $query;
    }

    /**
     * Builds WHERE statement
     * @param array $params
     * @return string $sql
     */
    public static function prepare_filter(array $params) {
        if (!is_array($params) || !count($params))
            return 1;

        $sql = [];
        foreach ($params as $key => $value) {
            if ($value === null) {
                $sql[] = "$key IS NULL";

            } elseif (is_array($value)) {

                if (isset($value['LIKE'])) {
                    $value['LIKE'] = self::escape($value['LIKE']);
                    $sql[]         = "$key LIKE '$value[LIKE]'";
                    unset($value['LIKE']);
                }
                if (isset($value['NOT'])) {
                    $value['NOT'] = self::escape($value['NOT']);
                    $sql[]        = "$key != '$value[NOT]'";
                    unset($value['NOT']);
                }
                if (isset($value['BETWEEN']) && is_array($value['BETWEEN'])) {
                    $values_escaped = array_map('self::escape', $value['BETWEEN']);
                    $sql[]          = "$key BETWEEN '$values_escaped[0]' AND '$values_escaped[1]'";
                    unset($value['BETWEEN']);
                }
                if (isset($value['$in'])) {
                    $values_escaped = array_map('self::escape', $value['$in']);
                    $sql[]          = "$key IN('" . implode("','", $values_escaped) . "')";
                    unset($value['$in']);
                }
                if (isset($value['$gt'])) {
                    $value_escaped = self::escape($value['$gt']);
                    $sql[]         = "$key > '$value_escaped'";
                    unset($value['$gt']);
                }
                if (isset($value['$gte'])) {
                    $value_escaped = self::escape($value['$gte']);
                    $sql[]         = "$key >= '$value_escaped'";
                    unset($value['$gte']);
                }
                if (isset($value['$lt'])) {
                    $value_escaped = self::escape($value['$lt']);
                    $sql[]         = "$key < '$value_escaped'";
                    unset($value['$lt']);
                }
                if (isset($value['$lte'])) {
                    $value_escaped = self::escape($value['$lte']);
                    $sql[]         = "$key <= '$value_escaped'";
                    unset($value['$lte']);
                }

                if (count($value)) {
                    $values_escaped = array_map('self::escape', $value);
                    $sql[]          = "$key IN('" . implode("','", $values_escaped) . "')";
                }

            } else {
                $value_escaped = self::escape($value);
                $sql[]         = "$key = '$value_escaped'";

            }
        }
        return implode(' AND ', $sql);
    }

}