<?php

ini_set('display_errors', "1");
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

include_once "php/Utils.php";
define('CURRENT_DATE',                        'SELECT NOW()');
define('SET_VALUES',                          'INSERT INTO WaterMeter (coldwater, hotwater) VALUES (#coldwater#, #hotwater#)');
define('GET_LAST_VALUES',                     'SELECT ts, coldwater, hotwater FROM WaterMeter ORDER BY ts DESC LIMIT 1');
define('GET_FIRST_VALUE_OF_CURRENT_DAY',      'SELECT MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM WaterMeter WHERE DATE(ts) = CURDATE() - INTERVAL 1 DAY');
define('GET_CURRENT_DAY_VALUES',              '(SELECT ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM WaterMeter 
                                              WHERE DATE(ts) < DATE(#date#) GROUP BY (1) ORDER BY ts DESC LIMIT 1)
                                              UNION SELECT ts, coldwater, hotwater FROM WaterMeter WHERE DATE(ts) = DATE(#date#)'
);
define('GET_CURRENT_MONTH_VALUES_BY_DAYS',    '(SELECT DATE(ts) as ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM WaterMeter
                                              WHERE DATE(ts) < DATE_FORMAT(#date#, \'%Y-%m-01\') GROUP BY (1) ORDER BY ts DESC LIMIT 1)
                                              UNION SELECT DATE(ts) as ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM WaterMeter 
                                              WHERE DATE(ts) BETWEEN DATE_FORMAT(#date#, \'%Y-%m-01\') AND (DATE_FORMAT(#date#, \'%Y-%m-01\') + INTERVAL 1 MONTH) - INTERVAL 1 DAY GROUP BY (1)'
);
define('GET_LAST_12_MONTH_VALUES_BY_MONTHS', 'SELECT DATE_FORMAT(ts, \'%Y-%m\') as ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM WaterMeter
                                              WHERE DATE(ts) BETWEEN (DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH, \'%Y-%m-01\')) AND CURDATE() GROUP BY (1)'
);

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

    /** @var  DB */
    private $db;

    private $debug;
    private $action;

    public function init($debug = false)
    {
        setlocale(LC_TIME, 'ru_RU.UTF-8');
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
        if (!Vars::check('param')) {
            Utils::reportError(__CLASS__, 'Parameter should be passed', $this->debug);
        }

        $params = strtolower(Vars::get('param', null));
        $date = strtolower(Vars::get('date', null));

        $current_ts = strtotime($this->db->fetchSingleValue(CURRENT_DATE));

        switch ($params) {
            case 'current_val':
                $current_values = $this->db->fetchSingleRow(GET_LAST_VALUES);
                $first_valuses_of_current_day = $this->db->fetchSingleRow(GET_FIRST_VALUE_OF_CURRENT_DAY);

                $cw_day_rate = $current_values[self::COLDWATER] - $first_valuses_of_current_day[self::COLDWATER];
                $hw_day_rate = $current_values[self::HOTWATER] - $first_valuses_of_current_day[self::HOTWATER];

                $ret[self::COLDWATER] = array(
                    'day_rate' => $cw_day_rate,
                    'cube'     => substr($current_values[self::COLDWATER], 0, -3),
                    'liter'    => substr($current_values[self::COLDWATER], -3)
                );
                $ret[self::HOTWATER] = array(
                    'day_rate' => $hw_day_rate,
                    'cube' => substr($current_values[self::HOTWATER], 0, -3),
                    'liter' =>substr($current_values[self::HOTWATER], -3)
                );

                Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $current_values);
                break;
            case 'current':
                $current_values = $this->db->fetchSingleRow(GET_LAST_VALUES);
                $current_day = $this->db->executeQuery(GET_CURRENT_DAY_VALUES, ['date' => 'CURDATE()']);
                $current_month = $this->db->executeQuery(GET_CURRENT_MONTH_VALUES_BY_DAYS, ['date' => 'CURDATE()']);
                $last_12month = $this->db->executeQuery(GET_LAST_12_MONTH_VALUES_BY_MONTHS);

                $ret['current_date'] = date('Y-m-d', $current_ts);
                $ret['current_values'] = Parser::parserCurrentValues($current_values);
                $ret['current_day'] = Parser::parseCurrentDay($current_day, $current_ts);
                $ret['current_month'] = Parser::parseMonth($current_month, $current_ts);
                $ret['last_12month'] = Parser::parseMonth($last_12month, $current_ts, true);

                Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
                break;

            case 'day':
                if ($date == null) {
                    Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Date not passed');
                }

                $current_day = $this->db->executeQuery(GET_CURRENT_DAY_VALUES, ['date' => $date], true);
                $ret['current_day'] = Parser::parseCurrentDay(
                    $current_day,
                    $date == date('Y-m-d', $current_ts) ? $current_ts : strtotime(date('Y-m-d 23:59:59', strtotime($date))));
                Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
                break;

            case 'month':
                if ($date == null) {
                    Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Date not passed');
                }
                $current_month = $this->db->executeQuery(GET_CURRENT_MONTH_VALUES_BY_DAYS, ['date' => $date], true);
                $ret['current_month'] = Parser::parseMonth($current_month);
                Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
                break;

            default:
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, Utils::UNKNOWN_PARAMETER);
        }
    }
}

$ws = new WaterStat();
$ws->init(true);
$ws->run();
