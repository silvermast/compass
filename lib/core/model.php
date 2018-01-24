<?php

namespace core;

use \Exception;
use \mysqli;

/**
 * @author Jason Wright <jason@silvermast.io>
 * @since 1/4/17
 * @package BlackMast Tasks
 */
abstract class Model {
    use Singleton;
    use db\Mysql;

    const ID    = 'id';
    const TABLE = 'default'; // override

    /** @var mixed */
    protected static $_indexes;

    /**
     * Base constructor.
     * @param array $vars
     */
    public function __construct($vars = []) {
        $this->setVars($vars);
    }

    /**
     * @param array $vars
     * @return $this
     */
    public function setVars($vars = []) {
        if (is_object($vars)) $vars = get_object_vars($vars);
        foreach ($vars as $key => $val)
            if (property_exists($this, $key)) $this->$key = $val;

        return $this;
    }

    /**
     * @throws Exception
     * @return self
     */
    public abstract function validate();

    /**
     * Saves the object
     * @throws Exception
     * @return static
     */
    public function save() {
        try {

            if (empty($this->{static::ID})) {
                $row   = (array)$this;
                $table = static::TABLE;

                $fields = '`' . implode('`,`', array_keys($row)) . '`';
                $values = implode(',', array_map('self::escape_with_quotes', array_values($row)));

                self::query("INSERT INTO `$table` ($fields) VALUES ($values)");
                $this->{static::ID} = self::_db()->insert_id;

            } else {
                // UPDATE
                $this->update($this);

            }

        } catch (Exception $e) {
            Debug::error($e->getMessage());
            throw new Exception('Unable to save the item. ' . json_encode($this), 500);
        }

        return $this;
    }

    /**
     * Updates only specified fields on an object
     * @param array|object $set
     * @return static
     * @throws Exception
     */
    public function update($set) {
        try {
            $table = static::TABLE;

            if (empty($this->{static::ID}))
                throw new Exception("ID is not set. " . json_encode($this));

            $set_string = [];
            foreach ($set as $field => $value)
                $set_string[] = "`$field`=" . self::escape_with_quotes($value);

            $set_string = implode(',', $set_string);
            $filter     = self::prepare_filter([static::ID => $this->{static::ID}]);

            self::query("UPDATE `$table` SET $set_string WHERE $filter");

        } catch (Exception $e) {
            Debug::error($e->getMessage());
            throw new Exception('Unable to update the item. ' . json_encode($this), 500);
        }

        return $this;
    }

    /**
     * Deletes this object from the database
     * @return self
     * @throws Exception
     */
    public function delete() {
        try {
            $table = static::TABLE;
            $filter = self::prepare_filter([static::ID => $this->{static::ID}]);
            self::query("DELETE FROM `$table` WHERE $filter");

        } catch (Exception $e) {
            Debug::error($e->getMessage());
            throw new Exception('Unable to delete the item. ' . json_encode($this), 500);
        }

        return $this;
    }

    /**
     * Simple web-safe random ID string generator
     * @return string
     */
    public static function generateId($n = 18) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len   = strlen($chars) - 1;
        $id    = '';
        for ($i = 0; $i < $n; $i++)
            $id .= $chars[mt_rand(0, $len)];

        return $id;
    }

    /**
     * Finds a single object
     * @param $query
     * @param array $opts
     * @return static|null
     */
    public static function findOne($query, $opts = []) {
        try {
            $results = static::findMulti($query, $opts + ['limit' => 1]);
            return count($results) ? reset($results) : null;

        } catch (Exception $e) {
            Debug::error($query);
        }

        return null;
    }

    /**
     * Returns an array of objects (in memory)
     * @param $query
     * @param array $opts
     * @return static[]
     */
    public static function findMulti($query, $opts = []) {
        $objects = [];
        try {
            if (isset($opts['offset'])) {
                $opts['skip'] = $opts['offset'];
                unset($opts['offset']);
            }

            $limit = [];
            if (isset($opts['skip']))
                $limit[] = (int)$opts['skip'];
            if (isset($opts['limit']) && $opts['limit'] > 0)
                $limit[] = (int)$opts['limit'];
            $limit = count($limit) ? 'LIMIT ' . implode(',', $limit) : '';

            $sort = [];
            if (isset($opts['sort']))
                foreach ($opts['sort'] as $field => $direction)
                    $sort[] = "`$field` " . ($direction > 0 ? 'ASC' : 'DESC');
            $sort = count($sort) ? 'ORDER BY ' . implode(', ', $sort) : '';


            $table   = static::TABLE;
            $filter  = self::prepare_filter($query);
            $cursor  = self::query(trim("SELECT * FROM `$table` WHERE $filter $sort $limit"), MYSQLI_USE_RESULT);
            $objects = [];

            while ($row = $cursor->fetch_object())
                $objects[$row->{static::ID}] = static::new($row);

        } catch (Exception $e) {
            Debug::error($query);
        }

        return $objects;
    }

    /**
     * Updates multiple items
     * @param $query
     * @param $set
     * @return int|false
     */
    public static function updateMulti($query, $set) {
        try {
            $table = static::TABLE;

            $set_string = [];
            foreach ($set as $field => $value)
                $set_string[] = "`$field`=" . self::escape_with_quotes($value);

            $set_string = implode(',', $set_string);
            $filter     = self::prepare_filter($query);

            self::query("UPDATE `$table` SET $set_string WHERE $filter");

        } catch (Exception $e) {
            Debug::error($e->getMessage() . ': ' . json_encode($query));
        }

        return false;
    }

    /**
     * Returns an array of objects (in memory)
     * @throws Exception
     */
    public static function group($query) {
        throw new Exception('Invalid method');
    }

    /**
     * Returns the number of objects matching the query
     * @param $query
     * @return int|false
     */
    public static function count($query = []) {
        try {
            $filter = self::prepare_filter($query);
            $table  = static::TABLE;
            $cursor = self::query("SELECT COUNT(*) AS `count` FROM `$table` WHERE $filter");

            $count = (int)$cursor->fetch_object()->count;
            $cursor->free_result();
            return $count;

        } catch (Exception $e) {
            Debug::error($e->getMessage());
        }

        return false;
    }

    /**
     * Clears all objects from the collection
     * @param $query
     * @return int|false
     * @throws Exception
     */
    public static function clear() {
        throw new Exception('Invalid Method');
    }

    /**
     * Deletes objects from the table
     * @param $query
     * @return int|false
     * @throws Exception
     */
    public static function deleteMulti($query) {
        $query = (array)$query;
        if (!count($query))
            throw new Exception('Must provide a valid delete query.');

        try {
            $table = static::TABLE;
            $filter = self::prepare_filter($query);
            self::query("DELETE FROM `$table` WHERE $filter");
            return true;

        } catch (Exception $e) {
            Debug::error($e->getMessage());
            throw new Exception("Failed to delete items.");
        }
    }

}