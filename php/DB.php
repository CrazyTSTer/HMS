<?php

include_once "Utils.php";

class DB
{
    private $host;
    private $port;
    private $login;
    private $password;
    private $mysql_descriptor;
    private $debug;

    private $is_connected = false;
    private $is_db_selected = false;
    private $is_locale_set = false;

    private static $instance;

    const MYSQL_EMPTY_SELECTION = 'Selection is empty';
    const MYSQL_INCORRECT_QUERY = 'Incorrect query. Check query syntax';
    const MYSQL_DATABASE_SELECTION_FAIL = 'Can\'t select database';
    const MYSQL_CONNECT_FAIL = 'Can\'t connect to mysql server';
    const MYSQL_DISCONNECT_FAIL = 'Can\'t disconnect from mysql server';
    const MYSQL_SET_LOCALE_FAIL = 'Can\'t set locale';
    const MYSQL_ARRAY_TYPE_ASSOC = 'assoc';
    const MYSQL_ARRAY_TYPE_ARRAY = 'array';
    const MYSQL_ROWS_COUNT = 'rows_count';

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct() {}

    public function init($host, $port, $login, $password, $debug = false)
    {
        $this->host = $host;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->debug = $debug;
    }

    public function connect()
    {
        $this->mysql_descriptor = @mysqli_connect($this->host, $this->login, $this->password, '', $this->port)
            or die(Utils::reportError(__CLASS__, self::MYSQL_CONNECT_FAIL, $this->debug));
        $this->is_connected = true;
    }

    public function disconnect()
    {
        if ($this->mysql_descriptor) {
            mysqli_close($this->mysql_descriptor)
                or die(Utils::reportError(__CLASS__, self::MYSQL_DISCONNECT_FAIL, $this->debug));
        }

        $this->is_connected = false;
        $this->is_db_selected = false;
        $this->is_locale_set = false;
    }

    public function selectDB($database)
    {
        mysqli_select_db($this->mysql_descriptor, $database)
            or die (Utils::reportError(__CLASS__, self::MYSQL_DATABASE_SELECTION_FAIL, $this->debug));
        $this->is_db_selected = true;
    }

    public function setLocale($locale)
    {
        mysqli_set_charset($this->mysql_descriptor, $locale)
            or die (Utils::reportError(__CLASS__, self::MYSQL_SET_LOCALE_FAIL, $this->debug));
        $this->is_locale_set = true;
    }

    public function escapeString($string)
    {
        return mysqli_real_escape_string($this->mysql_descriptor, $string);
    }

    private function executeQuery($query, $data, $array_type = self::MYSQL_ARRAY_TYPE_ASSOC)
    {
        $query = Utils::addDataToTemplate($query, $data);
        $result = mysqli_query($this->mysql_descriptor, $query)
            or die(Utils::reportError(__CLASS__, self::MYSQL_INCORRECT_QUERY, $this->debug));

        if (mysqli_num_rows($result) == 0) {
            $this->free($result);
            $ret = self::MYSQL_EMPTY_SELECTION;
        } else {
            $ret = $result;
        }

        return $ret;
    }

    public function fetchSingleRow($query, $data, $returnAsArray = true)
    {
        $result = $this->executeQuery($query, $data);

        if ($result == self::MYSQL_EMPTY_SELECTION) {
            return $result;
        }

        $ret = mysqli_fetch_assoc($result);

        if (!$returnAsArray) {
            $ret = implode('#', $ret);
        }

        $this->free($result);
        return $ret;
    }

    public function fetchMultipleRows($query, $data = null)
    {
        $result = $this->executeQuery($query, $data);
        if ($result == self::MYSQL_EMPTY_SELECTION) {
            return $result;
        }

        $ret[self::MYSQL_ROWS_COUNT] = mysqli_num_rows($result);

        while ($parsed_result = mysqli_fetch_assoc($result)) {
            $ret[] = $parsed_result;
        }
        $this->free($result);

        return $ret;
    }

    public function fetchSpecifiedRow($query, $data, $row = 0, $returnAsArray = true)
    {
        $ret = $this->fetchSingleRow($query . ' LIMIT ' . $row . ', 1', $data, $returnAsArray);
        return $ret;
    }

    public function fetchRandomRow($query, $data, $returnAsArray = true)
    {
        $ret = $this->fetchSingleRow($query . 'ORDER BY RAND() LIMIT 1', $data, $returnAsArray);
        return $ret;
    }

    public function fetchSpecifiedRowsLimit($query, $data, $row = 0, $length = 1, $returnAsArray = true)
    {
        $ret = $this->fetchMultipleRows($query . ' LIMIT ' . $row . ', ' . $length, $data, $returnAsArray);
        return $ret;
    }

    private function free($result)
    {
        mysqli_free_result($result);
    }

    public function getMYSQLErr()
    {
        return mysqli_error($this->mysql_descriptor) ? '. ' . mysqli_error($this->mysql_descriptor) : '';
    }

    public function getMYSQLErrNo()
    {
        return mysqli_errno($this->mysql_descriptor) ? ' Error: ' . mysqli_errno($this->mysql_descriptor) : '';
    }

    public function isDBReady()
    {
        return $this->is_connected && $this->is_db_selected && $this->is_locale_set;
    }

    public function isConnected()
    {
        return $this->is_connected;
    }
}
