<?php

ini_set('display_errors',"1");
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

include_once "php/Utils.php";
define('CURRENT_DATE',                      'SELECT CURDATE()');
define('GET_LAST_VALUES',                   'SELECT ts, coldwater, hotwater FROM WaterMeter ORDER BY ts DESC LIMIT 1');
define('SET_VALUES',                        'INSERT INTO WaterMeter (coldwater, hotwater) VALUES (#coldwater#, #hotwater#)');
define('GET_CURRENT_DAY_VALUES',            '(SELECT ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM WaterMeter 
                                            WHERE DATE(ts) < DATE(CURDATE()) GROUP BY (1) ORDER BY ts DESC LIMIT 1)
                                            UNION SELECT ts, coldwater, hotwater FROM WaterMeter WHERE DATE(ts) = CURDATE()');

define ('GET_CURRENT_MONTH_VALUES_BY_DAYS', 'SELECT DATE(ts) as ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM WaterMeter 
                                            WHERE DATE(ts) BETWEEN DATE_FORMAT(CURDATE(), \'%Y-%m-01\') - INTERVAL 1 DAY AND CURDATE() GROUP BY (1)');

define('GET_VALUES_FOR_LAST_12_MONTH',      'SELECT DATE_FORMAT(ts, \'%Y-%m\') as ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM WaterMeter
                                            WHERE DATE(ts) BETWEEN (DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH, \'%Y-%m-01\')) AND CURDATE() GROUP BY (1)');
class WaterStat
{
    const MYSQL_HOST        = '192.168.1.2';
    const MYSQL_PORT        = 3306;
    const MYSQL_LOGIN       = 'water_meter';
    const MYSQL_PASS        = 'calcwater';
    const MYSQL_BASE        = 'HomeMetersStats';
    const MYSQL_BASE_LOCALE = 'utf8';

    const ACTION_SET      = 'set';
    const ACTION_GET      = 'get';

    const COLDWATER = 'coldwater';
    const HOTWATER  = 'hotwater';
    const TIMESTAMP = 'ts';

    const EMPTY_DATA = 'empty';

    /** @var  DB */
    private $db;

    private $debug;
    private $action;

    private $currentDate;

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

        $result = $this->db->fetchSingleRow(GET_LAST_VALUES);

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

        $result = $this->db->executeQuery(SET_VALUES, $data, false);

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
        /*if (!Vars::check('param')) {
            Utils::reportError(__CLASS__, 'Parameter should be passed', $this->debug);
        }

        $params =  strtolower(Vars::get('param', null));*/

        $current_date = date('Y-m-d', strtotime($this->db->fetchSingleValue(CURRENT_DATE)));
        $current_values = $this->db->fetchSingleRow(GET_LAST_VALUES);
        $current_day = $this->db->executeQuery(GET_CURRENT_DAY_VALUES);
        $current_month = $this->db->executeQuery(GET_CURRENT_MONTH_VALUES_BY_DAYS);

        $ret['current_date'] = $current_date;
        $ret['current_values'] = Parser::parserCurrentValues($current_values);
        $ret['current_day'] = Parser::parseCurrentDay($current_day);
        $ret['current_month'] = Parser::parseCurrentMonth($current_month, $current_date);

        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
    }
}

$ws = new WaterStat();
$ws->init(true);
$ws->run();
