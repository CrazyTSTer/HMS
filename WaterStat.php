<?php

ini_set('display_errors',"1");
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

include_once "php/Utils.php";
define('GET_LAST_VALUES',        'SELECT ts, coldwater, hotwater FROM WaterMeter ORDER BY ts DESC LIMIT 1');
define('SET_VALUES',             'INSERT INTO WaterMeter (coldwater, hotwater) VALUES (#coldwater#, #hotwater#)');
define('GET_CURRENT_DAY_VALUES',
    '(SELECT ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM WaterMeter 
    WHERE DATE(ts) < DATE(CURDATE()) GROUP BY (1) ORDER BY ts DESC LIMIT 1)
    UNION SELECT ts, coldwater, hotwater FROM WaterMeter WHERE DATE(ts) = CURDATE()'
);
define ('GET_CURRENT_MONTH_VALUES_BY_DAYS',
    '(SELECT DATE(ts) as ts, MAX(coldwater) AS coldwater, MAX(hotwater) as hotwater FROM WaterMeter 
    WHERE MONTH(ts)<MONTH(curdate()) GROUP BY (1) ORDER BY ts DESC LIMIT 1) 
    UNION SELECT DATE(ts) as ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM WaterMeter WHERE MONTH(ts) = MONTH(CURDATE()) GROUP BY (1)'
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

        $params =  strtolower(Vars::get('param', null));

        switch ($params) {
            case 'last':
                $result = $this->db->fetchSingleRow(GET_LAST_VALUES);
                if ($result == false) {
                    Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Can\'t get current values from DB');
                }
                Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $result);
                break;
            case 'current_day':
                $result = $this->db->executeQuery(GET_CURRENT_DAY_VALUES);

                if ($result == false) {
                    Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Can\'t get current day data from DB');
                }

                if ($result[DB::MYSQL_ROWS_COUNT] < 2 || $result == DB::MYSQL_EMPTY_SELECTION) {
                    Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'NoData');
                }

                $coldWaterFirstValue = $result[0][self::COLDWATER];
                $hotWaterFirstValue = $result[0][self::HOTWATER];
                $result[0][self::TIMESTAMP] = date('Y-m-d 00:00:00', strtotime($result[1][self::TIMESTAMP]));


                $ret['last_timestamp'] = $result[$result[DB::MYSQL_ROWS_COUNT] - 1][self::TIMESTAMP];
                $ret['current_date'] = date('Y-m-d', strtotime($result[$result[DB::MYSQL_ROWS_COUNT] - 1][self::TIMESTAMP]));

                for ($i = 0; $i < $result[DB::MYSQL_ROWS_COUNT]; $i++) {
                    $dt = strtotime($result[$i][self::TIMESTAMP]) * 1000;

                    $ret[self::COLDWATER][] = [
                        $dt,
                        $result[$i][self::COLDWATER] - $coldWaterFirstValue,
                    ];
                    $ret[self::HOTWATER][] = [
                        $dt,
                        $result[$i][self::HOTWATER] - $hotWaterFirstValue,
                    ];

                    if (!array_key_exists($i+1, $result)) continue;

                    //Get time interval between two points
                    $dt1 = strtotime($result[$i+1][self::TIMESTAMP]);
                    $dt2 = strtotime($result[$i][self::TIMESTAMP]);
                    $interval = round(abs($dt1 - $dt2) / 60);

                    if ($interval > 5) {
                        $dt = ($dt1 - 60) * 1000;//Сдвигаемся на минуту назад
                        if ($result[$i][self::COLDWATER] - $result[$i+1][self::COLDWATER] != 0) {
                            $ret[self::COLDWATER][] = [
                                $dt,
                                $result[$i][self::COLDWATER] - $coldWaterFirstValue,
                            ];
                        }
                        if ($result[$i][self::HOTWATER] - $result[$i+1][self::HOTWATER] != 0) {
                            $ret[self::HOTWATER][] = [
                                $dt,
                                $result[$i][self::HOTWATER] - $hotWaterFirstValue,
                            ];
                        }
                    }
                }

                Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
                break;
            case 'current_month':
                $result = $this->db->executeQuery(GET_CURRENT_MONTH_VALUES_BY_DAYS);

                if ($result == false) {
                    Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Can\'t get current month data from DB');
                }

                if ($result[DB::MYSQL_ROWS_COUNT] < 2 || $result == DB::MYSQL_EMPTY_SELECTION) {
                    Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, 'NoData');
                }

                for ($i = 1; $i < $result[DB::MYSQL_ROWS_COUNT]; $i++) {
                    $ret[self::TIMESTAMP][] = date('jS M', strtotime($result[$i][self::TIMESTAMP]));
                    $ret[self::COLDWATER][] = $result[$i][self::COLDWATER] - $result[$i-1][self::COLDWATER];
                    $ret[self::HOTWATER][] = $result[$i][self::HOTWATER] - $result[$i-1][self::HOTWATER];
                }

                if (date('Y-m-d',
                        strtotime($result[$result[DB::MYSQL_ROWS_COUNT] - 1][self::TIMESTAMP])
                    ) != date('Y-m-d')) {
                    $ret[self::TIMESTAMP][] = date('jS M');
                    $ret[self::COLDWATER][] = 0;
                    $ret[self::HOTWATER][] = 0;
                }

                Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
                break;
            default:
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, Utils::UNKNOWN_ACTION);
        }
    }
}

$ws = new WaterStat();
$ws->init(true);
$ws->run();
