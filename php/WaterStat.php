<?php
/*CREATE TABLE `Water` (
`ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `coldwater` int(11) NOT NULL,
  `hotwater` int(11) NOT NULL,
  PRIMARY KEY (`ts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8*/

define('SET_WATER_VALUES',                          'INSERT INTO #table# (coldwater, hotwater) VALUES (#coldwater#, #hotwater#)');

define('GET_WATER_LAST_VALUES',                     'SELECT ts, coldwater, hotwater FROM #table# ORDER BY ts DESC LIMIT 1');

/* ----- Get rate ----- */
define('GET_WATER_CURRENT_DAY_RATE',                'SELECT MAX(coldwater) - MIN(coldwater) as coldwater, MAX(hotwater) - MIN(hotwater) as hotwater FROM (
                                                        (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) < CURDATE() ORDER BY ts DESC LIMIT 1)
                                                        UNION ALL
                                                        (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) = CURDATE() ORDER BY ts DESC LIMIT 1)
                                                    ) as smth;');

define('GET_WATER_CURRENT_MONTH_RATE',              'SELECT MAX(coldwater) - MIN(coldwater) as coldwater, MAX(hotwater) - MIN(hotwater) as hotwater FROM (
                                                        (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) < DATE_FORMAT(CURDATE(), \'%Y-%m-01\') ORDER BY ts DESC LIMIT 1)
                                                        UNION ALL
                                                        (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) <= CURDATE() ORDER BY ts DESC LIMIT 1)
                                                    ) as smth;');

define('GET_WATER_PREV_MONTH_RATE',                 'SELECT MAX(coldwater) - MIN(coldwater) as coldwater, MAX(hotwater) - MIN(hotwater) as hotwater FROM (
                                                        (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) < DATE_FORMAT(CURDATE(), \'%Y-%m-01\') - INTERVAL 1 MONTH ORDER BY ts DESC LIMIT 1)
                                                        UNION ALL
                                                        (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) <= DATE_FORMAT(CURDATE(), \'%Y-%m-01\') - INTERVAL 1 DAY ORDER BY ts DESC LIMIT 1)
                                                    ) as smth;');

/* ----- Get values ----- */
define('GET_WATER_CURRENT_DAY_VALUES',              'SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) = #date#
                                                    UNION ALL
                                                    (SELECT ts, coldwater, hotwater FROM #table# WHERE DATE(ts) < #date# ORDER BY ts DESC LIMIT 1) ORDER BY ts');

define('GET_WATER_CURRENT_MONTH_VALUES_BY_DAYS',    'SELECT DATE(ts) as ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater
                                                    FROM #table# WHERE
                                                        DATE(ts) > DATE_FORMAT(#date#, \'%Y-%m-01\') - INTERVAL 1 DAY
                                                        AND
                                                        DATE(ts) < (DATE_FORMAT(#date#, \'%Y-%m-01\') + INTERVAL 1 MONTH)
                                                    GROUP BY (1)
                                                    UNION ALL
                                                    (SELECT DATE(ts) as ts, coldwater, hotwater FROM #table#
                                                    WHERE DATE(ts) < DATE_FORMAT(#date#, \'%Y-%m-01\') ORDER BY #table#.ts DESC LIMIT 1) ORDER BY ts'
);
define('GET_WATER_LAST_12_MONTH_VALUES_BY_MONTHS', 'SELECT DATE_FORMAT(ts, \'%Y-%m\') as ts, MAX(coldwater) as coldwater, MAX(hotwater) as hotwater FROM #table#
                                                    WHERE DATE(ts) BETWEEN (DATE_FORMAT(CURDATE() - INTERVAL 12 MONTH, \'%Y-%m-01\')) AND CURDATE() GROUP BY (1)'
);

class WaterStat
{
    const COLDWATER = 'coldwater';
    const HOTWATER  = 'hotwater';
    const TIMESTAMP = 'ts';

    /** @var  DB */
    private $db;

    private $debug;

    public function __construct($debug)
    {
        $this->debug = $debug;

        $this->db = DB::getInstance();
        if (!$this->db->isDBReady()) {
            Utils::reportError(__CLASS__, DB::MYSQL_DB_IS_NOT_READY, $this->debug);
        }
    }

    public function actionSet()
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

        $result = $this->db->fetchSingleRow(GET_WATER_LAST_VALUES, ['table' => DB::MYSQL_TABLE_WATER]);

        if ($result === DB::MYSQL_EMPTY_SELECTION) {
            $data = array(
                self::COLDWATER => $tmp[self::COLDWATER],
                self::HOTWATER => $tmp[self::HOTWATER],
                'table' => DB::MYSQL_TABLE_WATER,
            );
        } elseif (is_array($result)) {
            $data = array(
                self::COLDWATER => $tmp[self::COLDWATER] + $result[self::COLDWATER],
                self::HOTWATER => $tmp[self::HOTWATER] + $result[self::HOTWATER],
                'table' => DB::MYSQL_TABLE_WATER,
            );
        } else {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Failed to get previous Values from DB');
        }

        $result = $this->db->executeQuery(SET_WATER_VALUES, $data, false);

        if ($result === true) {
            Utils::unifiedExitPoint(Utils::STATUS_SUCCESS);
        } elseif ($result === false) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL);
        } else {
            Utils::reportError(__CLASS__, 'Unknown error while adding Values to DB', $this->debug);
        }
    }

    public function actionGet()
    {
        if (!Vars::check('param')) {
            Utils::reportError(__CLASS__, 'Parameter should be passed', $this->debug);
        }

        $params = strtolower(Vars::get('param', null));

        switch ($params) {
            case 'main_stat':
                $current_values = $this->db->fetchSingleRow(GET_WATER_LAST_VALUES, ['table' => DB::MYSQL_TABLE_WATER]);
                $current_day_rate = $this->db->fetchSingleRow(GET_WATER_CURRENT_DAY_RATE, ['table' => DB::MYSQL_TABLE_WATER]);
                $current_month_rate = $this->db->fetchSingleRow(GET_WATER_CURRENT_MONTH_RATE, ['table' => DB::MYSQL_TABLE_WATER]);
                $prev_month_rate = $this->db->fetchSingleRow(GET_WATER_PREV_MONTH_RATE, ['table' => DB::MYSQL_TABLE_WATER]);

                $ret = [
                    self::TIMESTAMP => $current_values[self::TIMESTAMP],
                    self::COLDWATER => [
                        'current_value'   => sprintf('%.3f', $current_values[self::COLDWATER] / 1000),
                        'day_rate'        => sprintf('%.3f', $current_day_rate[self::COLDWATER] / 1000),
                        'month_rate'      => sprintf('%.3f', $current_month_rate[self::COLDWATER] / 1000),
                        'prev_month_rate' => sprintf('%.3f', $prev_month_rate[self::COLDWATER] / 1000),
                    ],
                    self::HOTWATER => [
                        'current_value'   => sprintf('%.3f', $current_values[self::HOTWATER] / 1000),
                        'day_rate'        => sprintf('%.3f', $current_day_rate[self::HOTWATER] / 1000),
                        'month_rate'      => sprintf('%.3f', $current_month_rate[self::HOTWATER] / 1000),
                        'prev_month_rate' => sprintf('%.3f', $prev_month_rate[self::HOTWATER] / 1000),
                    ],
                ];
                break;

            case 'current':
                $current_day_values = $this->db->executeQuery(GET_WATER_CURRENT_DAY_VALUES, ['date' => 'CURDATE()', 'table' => DB::MYSQL_TABLE_WATER]);
                $current_month_values = $this->db->executeQuery(GET_WATER_CURRENT_MONTH_VALUES_BY_DAYS, ['date' => 'CURDATE()', 'table' => DB::MYSQL_TABLE_WATER]);
                $last_12month_values = $this->db->executeQuery(GET_WATER_LAST_12_MONTH_VALUES_BY_MONTHS, ['table' => DB::MYSQL_TABLE_WATER]);

                $ret = [
                    'current_day' => Parser::parseCurrentDay($current_day_values),
                    'current_month' => Parser::parseMonth($current_month_values, true, false),
                    'last_12month' => Parser::parseMonth($last_12month_values, false, true),
                ];
                break;

            case 'day':
                $date = strtolower(Vars::get('date', null));
                if ($date == null) {
                    Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Date not passed');
                }
                $current_day = $this->db->executeQuery(GET_WATER_CURRENT_DAY_VALUES, ['date' => '\'' . $date . '\'', 'table' => DB::MYSQL_TABLE_WATER]);
                $ret = [
                    'current_day' => Parser::parseCurrentDay($current_day, $date == date('Y-m-d')),
                ];
                break;

            case 'month':
                $date = strtolower(Vars::get('date', null));
                if ($date == null) {
                    Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'Date not passed');
                }
                $current_month = $this->db->executeQuery(GET_WATER_CURRENT_MONTH_VALUES_BY_DAYS, ['date' => '\'' . $date . '-01' . '\'', 'table' => DB::MYSQL_TABLE_WATER]);
                $ret = [
                    'current_month' => Parser::parseMonth($current_month, $date == date('Y-m')),
                ];
                break;

            default:
                Utils::reportError(__CLASS__, Utils::UNKNOWN_PARAMETER, $this->debug);
                break;
        }
        Utils::unifiedExitPoint(Utils::STATUS_SUCCESS, $ret);
    }

    public function actionSendDataToPGU()
    {
        $WaterSettings = new WaterMetersSettings($this->debug);
        $paycode = $WaterSettings->cfg->get('paycode');
        $meters  = $WaterSettings->cfg->get('meters');
        $flat    = $WaterSettings->cfg->get('flat');

        if (empty($meters) || !$paycode || !$flat) {
            Utils::unifiedExitPoint(Utils::STATUS_FAIL, 'No meters data. Check Settings page');
        }

        $result = $this->db->fetchSingleRow(GET_WATER_LAST_VALUES, ['table' => DB::MYSQL_TABLE_WATER]);
        if (is_array($result)) {
            foreach ($meters as $meter) {
                $tmpMeters[] = [
                    'counterNum' => $meter['counterNum'],
                    'counterVal' => $meter['type'] == 1 ? sprintf('%.3f', $result[self::COLDWATER] / 1000) :
                        ($meter['type'] == 2 ? sprintf('%.3f', $result[self::HOTWATER] / 1000) :
                            null),
                    /*'counterVal' => $meter['type'] == 1 ? number_format($result[self::COLDWATER] / 1000, 3, ',', '') :
                        ($meter['type'] == 2 ? number_format($result[self::HOTWATER] / 1000, 3, ',', '') :
                            null),*/
                    'num'        => $meter['num'],
                    'period'     => date('Y-m-t'),
                ];
            }
            PguApi::sendWaterMetersData($paycode, $flat, $tmpMeters);
        } else {
            Utils::reportError(__CLASS__, 'Failed to send data to PGU. Can\'t get meters last values', $this->debug);
        }
    }
}
