<?php
/*CREATE TABLE `Water` (
`ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `coldwater` int(11) NOT NULL,
  `hotwater` int(11) NOT NULL,
  PRIMARY KEY (`ts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8*/

define('SET_VALUES',                          'INSERT INTO #table# (coldwater, hotwater) VALUES (#coldwater#, #hotwater#)');

define('GET_LAST_VALUES',                     'SELECT ts, coldwater, hotwater FROM #table# ORDER BY ts DESC LIMIT 1');

/* ----- Get rate ----- */
define('GET_CURRENT_DAY_RATE',                'SELECT MAX(coldwater) - MIN(coldwater) as coldwater, MAX(hotwater) - MIN(hotwater) as hotwater FROM (
                                                (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) < CURDATE() ORDER BY ts DESC LIMIT 1) 
                                                UNION ALL 
                                                (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) = CURDATE() ORDER BY ts DESC LIMIT 1)
                                               ) as smth;');

define('GET_CURRENT_MONTH_RATE',              'SELECT MAX(coldwater) - MIN(coldwater) as coldwater, MAX(hotwater) - MIN(hotwater) as hotwater FROM (
                                                (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) < DATE_FORMAT(CURDATE(), \'%Y-%m-01\') ORDER BY ts DESC LIMIT 1) 
                                                UNION ALL 
                                                (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) = CURDATE() ORDER BY ts DESC LIMIT 1)
                                              ) as smth;');

define('GET_PREV_MONTH_RATE',                 'SELECT MAX(coldwater) - MIN(coldwater) as coldwater, MAX(hotwater) - MIN(hotwater) as hotwater FROM (
                                                (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) < DATE_FORMAT(CURDATE(), \'%Y-%m-01\') - INTERVAL 1 MONTH ORDER BY ts DESC LIMIT 1) 
                                                UNION ALL 
                                                (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) = DATE_FORMAT(CURDATE(), \'%Y-%m-01\') - INTERVAL 1 DAY ORDER BY ts DESC LIMIT 1)
                                              ) as smth;');

/* ----- Get values ----- */
define('GET_CURRENT_DAY_VALUES',              'SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) = #date# 
                                                UNION ALL
                                                (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) < #date# ORDER BY ts DESC LIMIT 1) ORDER BY ts');

define('GET_CURRENT_MONTH_VALUES_BY_DAYS',    'SELECT DATE(ts) as ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater 
                                               FROM #table# WHERE 
                                                  DATE(ts) > DATE_FORMAT(#date#, \'%Y-%m-01\') - INTERVAL 1 DAY 
                                                  AND 
                                                  DATE(ts) < (DATE_FORMAT(#date#, \'%Y-%m-01\') + INTERVAL 1 MONTH) 
                                               GROUP BY (1) 
                                               UNION ALL 
                                               (SELECT DATE(ts) as ts, coldwater, hotwater FROM #table# 
                                                  WHERE DATE(ts) < DATE_FORMAT(#date#, \'%Y-%m-01\') ORDER BY #table#.ts DESC LIMIT 1) ORDER BY ts'
);
define('GET_LAST_12_MONTH_VALUES_BY_MONTHS', 'SELECT DATE_FORMAT(ts, \'%Y-%m\') as ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM #table#
                                              WHERE DATE(ts) BETWEEN (DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH, \'%Y-%m-01\')) AND CURDATE() GROUP BY (1)'
);

class WaterStat
{
    const MYSQL_HOST        = '192.168.1.2';
    const MYSQL_PORT        = 3306;
    const MYSQL_LOGIN       = 'hms';
    const MYSQL_PASS        = 'HMSStats1';
    const MYSQL_BASE        = 'HMS';
    const MYSQL_BASE_LOCALE = 'utf8';
    const MYSQL_TABLE_WATER = 'Water';

    const COLDWATER = 'coldwater';
    const HOTWATER  = 'hotwater';
    const TIMESTAMP = 'ts';

    /** @var  DB */
    private static $db;

    public static function initDB($debug)
    {
        self::$db = DB::getInstance();
        self::$db->init(self::MYSQL_HOST, self::MYSQL_PORT, self::MYSQL_LOGIN, self::MYSQL_PASS, $debug);
        self::$db->connect();
        self::$db->selectDB(self::MYSQL_BASE);
        self::$db->setLocale(self::MYSQL_BASE_LOCALE);
    }

    public static function actionSet($debug)
    {
        if (!Vars::check('values')) {
            Utils::reportError(__CLASS__, 'Meters Values should be passed', $debug);
        }

        $valuesToSet = Vars::get('values', null);
        if (!is_array($valuesToSet)) {
            Utils::reportError(__CLASS__, 'Values to set should be passed as array', $debug);
        }

        $tmp = array();
        foreach ($valuesToSet as $key => $value) {
            $tmp[strtolower($key)] = $value;
        }

        if (!array_key_exists(self::COLDWATER, $tmp) || !array_key_exists(self::HOTWATER, $tmp)) {
            Utils::reportError(__CLASS__, '*coldwater* or *hotwater* key is missing in Values array', $debug);
        }

        self::initDB($debug);
        if (!self::$db->isDBReady()) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, Utils::DB_IS_NOT_READY);
        }

        $result = self::$db->fetchSingleRow(GET_LAST_VALUES, ['table' => self::MYSQL_TABLE_WATER]);

        if ($result === DB::MYSQL_EMPTY_SELECTION) {
            $data = array(
                self::COLDWATER => $tmp[self::COLDWATER],
                self::HOTWATER => $tmp[self::HOTWATER],
                'table' => self::MYSQL_TABLE_WATER,
            );
        } elseif (is_array($result)) {
            $data = array(
                self::COLDWATER => $tmp[self::COLDWATER] + $result[self::COLDWATER],
                self::HOTWATER => $tmp[self::HOTWATER] + $result[self::HOTWATER],
                'table' => self::MYSQL_TABLE_WATER,
            );
        } else {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Failed to get previous Values from DB');
        }

        $result = self::$db->executeQuery(SET_VALUES, $data, false);

        if ($result === true) {
            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS);
        } elseif ($result === false) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL);
        } else {
            Utils::reportError(__CLASS__, 'Unknown error while adding Values to DB', $debug);
        }
    }

    public static function actionGet($debug)
    {
        if (!Vars::check('param')) {
            Utils::reportError(__CLASS__, 'Parameter should be passed', $debug);
        }

        $params = strtolower(Vars::get('param', null));

        self::initDB($debug);
        if (!self::$db->isDBReady()) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, Utils::DB_IS_NOT_READY);
        }

        switch ($params) {
            case 'main_stat':
                $current_values = self::$db->fetchSingleRow(GET_LAST_VALUES, ['table' => self::MYSQL_TABLE_WATER]);
                $current_day_rate = self::$db->fetchSingleRow(GET_CURRENT_DAY_RATE, ['table' => self::MYSQL_TABLE_WATER]);
                $current_month_rate = self::$db->fetchSingleRow(GET_CURRENT_MONTH_RATE, ['table' => self::MYSQL_TABLE_WATER]);
                $prev_month_rate = self::$db->fetchSingleRow(GET_PREV_MONTH_RATE, ['table' => self::MYSQL_TABLE_WATER]);

                $ret[self::TIMESTAMP] = $current_values[self::TIMESTAMP];

                $ret[self::COLDWATER] = array(
                    'current_value'   => number_format($current_values[self::COLDWATER] / 1000, 3, ',', ''),
                    'day_rate'        => number_format($current_day_rate[self::COLDWATER] / 1000, 3, ',', ''),
                    'month_rate'      => number_format($current_month_rate[self::COLDWATER] / 1000, 3, ',', ''),
                    'prev_month_rate' => number_format($prev_month_rate[self::COLDWATER] / 1000, 3, ',', ''),
                );

                $ret[self::HOTWATER] = array(
                    'current_value'   => number_format($current_values[self::HOTWATER] / 1000, 3, ',', ''),
                    'day_rate'        => number_format($current_day_rate[self::HOTWATER] / 1000, 3, ',', ''),
                    'month_rate'      => number_format($current_month_rate[self::HOTWATER] / 1000, 3, ',', ''),
                    'prev_month_rate' => number_format($prev_month_rate[self::HOTWATER] / 1000, 3, ',', ''),
                );

                Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
                break;
            case 'current':
                $current_day_values = self::$db->executeQuery(GET_CURRENT_DAY_VALUES, ['date' => 'CURDATE()', 'table' => self::MYSQL_TABLE_WATER]);
                $current_month_values = self::$db->executeQuery(GET_CURRENT_MONTH_VALUES_BY_DAYS, ['date' => 'CURDATE()', 'table' => self::MYSQL_TABLE_WATER]);
                $last_12month_values = self::$db->executeQuery(GET_LAST_12_MONTH_VALUES_BY_MONTHS, ['table' => self::MYSQL_TABLE_WATER]);

                $ret['current_day'] = Parser::parseCurrentDay($current_day_values);
                $ret['current_month'] = Parser::parseMonth($current_month_values, true, false);
                $ret['last_12month'] = Parser::parseMonth($last_12month_values, false, true);

                Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
                break;

            case 'day':
                $date = strtolower(Vars::get('date', null));
                if ($date == null) {
                    Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Date not passed');
                }
                $current_day = self::$db->executeQuery(GET_CURRENT_DAY_VALUES, ['date' => '\'' . $date  . '\'', 'table' => self::MYSQL_TABLE_WATER]);
                $ret['current_day'] = Parser::parseCurrentDay(
                    $current_day,
                    $date == date('Y-m-d'));
                Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
                break;

            case 'month':
                $date = strtolower(Vars::get('date', null));
                if ($date == null) {
                    Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Date not passed');
                }
                $current_month = self::$db->executeQuery(GET_CURRENT_MONTH_VALUES_BY_DAYS, ['date' => '\'' . $date . '-01' . '\'' , 'table' => self::MYSQL_TABLE_WATER]);
                $ret['current_month'] = Parser::parseMonth($current_month, $date == date('Y-m'));
                Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
                break;

            default:
                Utils::unifiedExitPoint(Utils::STATUS_FAIL, Utils::UNKNOWN_PARAMETER);
        }
    }
}