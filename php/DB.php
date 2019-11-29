<?php

//include_once "Utils.php";

class DB
{
    const MYSQL_HOST              = 'crazytster.ddns.net';
    const MYSQL_PORT              = 6033;
    const MYSQL_LOGIN             = 'hms';
    const MYSQL_PASS              = 'HMSStats1';
    const MYSQL_BASE              = 'HMS';
    const MYSQL_BASE_LOCALE       = 'utf8';
    const MYSQL_TABLE_WATER       = 'Water';
    const MYSQL_TABLE_ELECTRICITY = 'Electricity';

    const MYSQL_EMPTY_SELECTION         = 'Selection is empty';
    const MYSQL_INCORRECT_QUERY         = 'Incorrect query. Check query syntax.';
    const MYSQL_DATABASE_SELECTION_FAIL = 'Can\'t select database';
    const MYSQL_CONNECT_FAIL            = 'Can\'t connect to mysql server';
    const MYSQL_DISCONNECT_FAIL         = 'Can\'t disconnect from mysql server';
    const MYSQL_SET_LOCALE_FAIL         = 'Can\'t set locale';
    const MYSQL_DB_IS_NOT_READY         = 'DB is not ready';
    const MYSQL_ROWS_COUNT              = 'rows_count';

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
        $this->host     = $host;
        $this->port     = $port;
        $this->login    = $login;
        $this->password = $password;
        $this->debug    = $debug;
    }

    public function connect()
    {
        $this->mysql_descriptor = @mysqli_connect($this->host, $this->login, $this->password, '', $this->port)
            or die(Utils::reportError(__CLASS__, self::MYSQL_CONNECT_FAIL, $this->debug));
        $this->is_connected = true;
    }

    public function disconnect()
    {
        if (!empty($this->mysql_descriptor)) {
            mysqli_close($this->mysql_descriptor)
                or die(Utils::reportError(__CLASS__, self::MYSQL_DISCONNECT_FAIL, $this->debug));
            unset($this->mysql_descriptor);
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

    public function executeQuery($query, $data = [], $add_quotes = false, $array_type = MYSQLI_ASSOC)
    {
        $query = Utils::addDataToTemplate($query, $data, $add_quotes, $this->debug);
        $result = mysqli_query($this->mysql_descriptor, $query)
            or Utils::reportError(__CLASS__, self::MYSQL_INCORRECT_QUERY . ' Query: ' . $query, $this->debug);

        if ($result === true) {
            $ret = true;
        } elseif ($result === false) {
            $ret = false;
        } else {
            $num_rows = mysqli_num_rows($result);
            if ($num_rows == 0) {
                $ret = self::MYSQL_EMPTY_SELECTION;
            } else {
                $ret[self::MYSQL_ROWS_COUNT] = $num_rows;

                while ($parsed_result = mysqli_fetch_array($result, $array_type)) {
                    $ret[] = $parsed_result;
                }
            }
            $this->free($result);
        }

        return $ret;
    }

    public function fetchSingleRow($query, $data = [], $add_quotes = false, $array_type = MYSQLI_ASSOC)
    {
        $result = $this->executeQuery($query, $data, $add_quotes, $array_type);
        if (is_array($result) && isset($result[0])) {
            $result = $result[0];
        }
        return $result;
    }

    public function fetchSingleValue($query, $pos = 0, $data = [], $add_quotes = false, $array_type = MYSQLI_NUM)
    {
        $result = $this->fetchSingleRow($query, $data, $add_quotes, $array_type);
        if (is_array($result) && isset($result[$pos])) {
            $result = $result[$pos];
        }
        return $result;
    }
}
