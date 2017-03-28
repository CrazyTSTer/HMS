<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

include_once "php/Utils.php";
define('GET_LAST_METERS_VALUES', 'SELECT ts, coldwater, hotwater FROM WaterMeter ORDER BY ts DESC LIMIT 1');
define('SET_METERS_VALUES',      'INSERT INTO WaterMeter (coldwater, hotwater) VALUES (#coldwater#, #hotwater#)');
define('GET_METERS_VALUES_FROM', 'SELECT floor(UNIX_TIMESTAMP(ts) * 1000 ) as ts, coldwater, hotwater FROM WaterMeter WHERE date(ts) = curdate()');

class WaterStat
{
    const MYSQL_HOST        = 'localhost';
    const MYSQL_PORT        = 3306;
    const MYSQL_LOGIN       = 'water_meter';
    const MYSQL_PASS        = 'calcwater';
    const MYSQL_BASE        = 'HomeMetersStats';
    const MYSQL_BASE_LOCALE = 'utf8';

    const ACTION_SET      = 'set';
    const ACTION_GET      = 'get';

    const COLDWATER = 'coldwater';
    const HOTWATER  = 'hotwater';

    /** @var  DB */
    private $db;

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
                $this->actionGet();
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

        $tmp = array();
        foreach ($valuesToSet as $key => $value) {
            $tmp[strtolower($key)] = $value;
        }

        if (!array_key_exists(self::COLDWATER, $tmp) || !array_key_exists(self::HOTWATER, $tmp)) {
            Utils::reportError(__CLASS__, '*coldwater* or *hotwater* key is missing in Values array', $this->debug);
        }

        $result = $this->db->executeQuery(GET_LAST_METERS_VALUES);

        if ($result === DB::MYSQL_EMPTY_SELECTION) {
            $data = array(
                self::COLDWATER => $tmp[self::COLDWATER],
                self::HOTWATER => $tmp[self::HOTWATER],
            );
        } elseif (is_array($result)) {
            $data = array(
                self::COLDWATER => $tmp[self::COLDWATER] + $result[self::COLDWATER],
                self::HOTWATER => $tmp[self::HOTWATER] + $result[self::HOTWATER],
            );
        } else {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Failed to get previous Values from DB');
        }

        $result = $this->db->executeQuery(SET_METERS_VALUES, $data, false);

        if ($result === true) {
            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS);
        } elseif ($result === false) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL);
        } else {
            Utils::reportError(__CLASS__, 'Unknown error while adding Values to DB', $this->debug);
        }
    }

    private function actionGet()
    {
        if (!Vars::check('param')) {
            Utils::reportError(__CLASS__, 'Parameter should be passed', $this->debug);
        }

        $params =  strtolower(Vars::get('param', null));

        switch ($params) {
            case 'last':
                $result = $this->db->executeQuery(GET_LAST_METERS_VALUES);
                if ($result !== false) {
                    Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $result);
                } else {
                    Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Can\'t get current values from DB');
                }
                break;
            case 'current_day':
                $result = $this->db->executeQuery(GET_METERS_VALUES_FROM);
                $cold_tmp = $result[0]['coldwater'];
                $hot_tmp = $result[0]['hotwater'];
                for ($i=0; $i<$result['rows_count']; $i++) {
                    $result[$i]['coldwater'] -= $cold_tmp;
                    $result[$i]['hotwater'] -= $hot_tmp;
                }
                for ($i=0; $i<$result['rows_count']; $i++){
                    echo '[' . $result[$i]['ts'] . ', ' . $result[$i]['hotwater'] . '],' . '<br>';
		        }
                break;
            case 'range':
                break;
            default:
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, Utils::UNKNOWN_ACTION);
        }
        var_export($params);
    }
}

$ws = new WaterStat();
$ws->init(true);
$ws->run();
