<?php

namespace App\Services;

use App\Exceptions\E;

/**
 * Facade for DatabaseHelper
 */
class Database
{
    public function __call($method, $args)
    {
        // Instantiate Helper
        $instance = new DatabaseHelper;
        // Call methods
        return call_user_func_array([$instance, $method], $args);
    }

    public static function __callStatic($method, $args)
    {
        $instance = new DatabaseHelper;
        return call_user_func_array([$instance, $method], $args);
    }
}

/**
 * Light-weight database helper
 *
 * @author  <h@prinzeugen.net>
 */
class DatabaseHelper
{
    /**
     * Instance of MySQLi
     *
     * @var null
     */
    private $connection = null;

    /**
     * Connection config
     *
     * @var array
     */
    private $config     = null;

    /**
     * Table name to do operations in
     *
     * @var string
     */
    private $table_name = "";

    /**
     * Construct with table name and another config optionally
     *
     * @param string $table_name
     * @param array $config
     */
    public function __construct($config = null)
    {
        $this->config = is_null($config) ? Config::getDbConfig() : $config;

        @$this->connection = new \mysqli(
            $this->config['host'],
            $this->config['username'],
            $this->config['password'],
            $this->config['database'],
            $this->config['port']
        );

        if ($this->connection->connect_error)
            throw new E("Could not connect to MySQL database. Check your configuration:".
                $this->connection->connect_error, $this->connection->connect_errno, true);

        $this->connection->query("SET names 'utf8'");
    }

    public function table($table_name, $no_prefix = false)
    {
        if (Utils::convertString($table_name) == $table_name) {
            $this->table_name = $no_prefix ? $table_name : $this->config['prefix'].$table_name;
            return $this;
        } else {
            throw new E('Table name contains invalid characters', 1);
        }
    }

    public function query($sql)
    {
        $result = $this->connection->query($sql);
        if ($this->connection->error)
            throw new E("Database query error: ".$this->connection->error.", Statement: ".$sql, -1);
        return $result;
    }

    public function fetchArray($sql)
    {
        return $this->query($sql)->fetch_array();
    }

    /**
     * Select records from table
     *
     * @param  string  $key
     * @param  string  $value
     * @param  array   $condition, see function `where`
     * @param  string  $table, which table to operate
     * @param  boolean $dont_fetch_array, return resources if true
     * @return array|resources
     */
    public function select($key, $value, $condition = null, $table = null, $dont_fetch_array = false)
    {
        $table = is_null($table) ? $this->table_name : $table;

        if (isset($condition['where'])) {
            $sql = "SELECT * FROM $table".$this->where($condition);
        } else {
            $sql = "SELECT * FROM $table WHERE $key='$value'";
        }

        if ($dont_fetch_array) {
            return $this->query($sql);
        } else {
            return $this->fetchArray($sql);
        }

    }

    public function insert($data, $table = null)
    {
        $keys   = "";
        $values = "";
        $table  = is_null($table) ? $this->table_name : $table;

        foreach($data as $key => $value) {
            if ($value == end($data)) {
                $keys .= '`'.$key.'`';
                $values .= '"'.$value.'"';
            } else {
                $keys .= '`'.$key.'`,';
                $values .= '"'.$value.'", ';
            }
        }

        $sql = "INSERT INTO $table ({$keys}) VALUES ($values)";
        return $this->query($sql);
    }

    public function has($key, $value, $table = null)
    {
        return ($this->getNumRows($key, $value, $table) != 0) ? true : false;
    }

    public function hasTable($table_name)
    {
        $sql = "SELECT table_name FROM `INFORMATION_SCHEMA`.`TABLES` WHERE (table_name = '$table_name') AND TABLE_SCHEMA='".Config::getDbConfig()['database']."'";
        return ($this->query($sql)->num_rows != 0) ? true : false;
    }

    public function update($key, $value, $condition = null, $table = null)
    {
        $table = is_null($table) ? $this->table_name : $table;
        return $this->query("UPDATE $table SET `$key`='$value'".$this->where($condition));
    }

    public function delete($condition = null, $table = null)
    {
        $table = is_null($table) ? $this->table_name : $table;
        return $this->query("DELETE FROM $table".$this->where($condition));
    }

    public function getNumRows($key, $value, $table = null)
    {
        $table = is_null($table) ? $this->table_name : $table;
        $sql = "SELECT * FROM $table WHERE $key='$value'";
        return $this->query($sql)->num_rows;
    }

    public function getRecordNum($table = null)
    {
        $table = is_null($table) ? $this->table_name : $table;
        $sql = "SELECT * FROM $table WHERE 1";
        return $this->query($sql)->num_rows;
    }

    /**
     * Generate where statement
     *
     * @param  array $condition, e.g. array('where'=>'username="shit"', 'limit'=>10, 'order'=>'uid')
     * @return string
     */
    private function where($condition)
    {
        $statement = "";
        if (isset($condition['where']) && $condition['where'] != "") {
            $statement .= ' WHERE '.$condition['where'];
        }
        if (isset($condition['order'])) {
            $statement .= ' ORDER BY `'.$condition['order'].'`';
        }
        if (isset($condition['limit'])) {
            $statement .= ' LIMIT '.$condition['limit'];
        }
        return $statement;
    }

    public function __destruct()
    {
        if (!is_null($this->connection))
            $this->connection->close();
    }

}
