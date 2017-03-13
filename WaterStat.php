<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

include_once "php/Utils.php";
define('GET_LAST_METERS_VALUES', 'SELECT #col1#, #col2# FROM WaterMeter ORDER BY Ts DESC LIMIT 1');
define('SET_METERS_VALUES',      'INSERT INTO WaterMeter (#col1#, #col2#) VALUES (#val1#, #val2#)');

class WaterStat
{
    const MYSQL_HOST        = 'localhost';
    const MYSQL_PORT        = 3306;
    const MYSQL_LOGIN       = 'water_meter';
    const MYSQL_PASS        = 'calcwater';
    const MYSQL_BASE        = 'HomeMetersStats';
    const MYSQL_BASE_LOCALE = 'utf8';

    const ACTION_SET      = 'set';
    const ACTION_GET =      'get';

    /** @var  DB */
    private $db;

    private $set;
    private $debug;
    private $action;

    public function init($debug = false)
    {
        $this->debug = $debug;
        $this->action = Vars::get('action', null);
        if (!$this->action) {
            die(Utils::reportError(__CLASS__, 'Action is not set', $this->debug));
        }

        $this->db = DB::getInstance();
        $this->db->init(self::MYSQL_HOST, self::MYSQL_PORT, self::MYSQL_LOGIN, self::MYSQL_PASS, $this->debug);
        $this->db->connect();
        $this->db->selectDB(self::MYSQL_BASE);
        $this->db->setLocale(self::MYSQL_BASE_LOCALE);
    }

    public function run()
    {
        switch ($this->action) {
            case self::ACTION_SET:
                $this->actionSet();
                break;

            case self::ACTION_GET:
                break;

            default:
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, Utils::UNKNOWN_ACTION);
        }
    }

    private function actionSet()
    {
        if (!Vars::check('values')) {
            Utils::reportError(__CLASS__, 'Meters Values should be passed', $this->debug);
        }

        $valuesToSet = Vars::get('values', null);
        if (!is_array($valuesToSet)) {
            Utils::reportError(__CLASS__, 'Values to set should be passed as array', $this->debug);
        }

        $i = 0;
        $data = array();
        foreach ($valuesToSet as $key => $value) {
            $i++;
            $data['col' . strval($i)] = strtolower($key);
            $data['val' . strval($i)] = $value;
        }

        $result = $this->db->executeQuery(GET_LAST_METERS_VALUES, $data, false);
        var_export($result);

        if (!is_array($result)) {
            Utils::unifiedExitPoint(Utils::STAUTS_FAIL, 'Failed to add Values to DB');
        }



        $result = $this->db->executeQuery(SET_METERS_VALUES, $data, false);

        if ($result === true) {
            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'Values added to DB successfully');
        } elseif ($result === false) {
            Utils::unifiedExitPoint(Utils::STAUTS_FAIL, 'Failed to add Values to DB');
        } else {
            Utils::reportError(__CLASS__, 'Unknown error while adding Values to DB', $this->debug);
        }
    }
}

$ws = new WaterStat();
$ws->init(true);
$ws->run();
